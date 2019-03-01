<?php

use BrokenLinkChecker\Valid;
use BrokenLinkChecker\Client;
use BrokenLinkChecker\Parser;

class PluginBrokenLinksChecker extends Plugin
{
    protected $links = [];

    public function init()
    {
        require_once __DIR__ . '/init.php';

        $this->dbFields = [
            'ignoredDomains'=>''
        ];
    }

    public function adminSidebar()
    {
        return '<a class="nav-link" href="'.HTML_PATH_ADMIN_ROOT.'configure-plugin/'.$this->className().'">Broken Links</a>';
    }

    public function form()
    {
        global $L;

        $html = '';

        $html  = '<div class="alert alert-primary" role="alert">';
        $html .= $this->description();
        $html .= '</div>';

        /**
         * Ignored Domains
         */
        $html .= '<div class="mb-3">';
        $html .= '<label>'.$L->get('ignored-domains').'</label>';
        $html .= '<textarea name="ignoredDomains" id="jstext">'.$this->getValue('ignoredDomains').'</textarea>';
        $html .= '</div>';

        // Read the cache file
        $json = file_get_contents($this->cacheFile());
        $this->links = json_decode($json, true);

        $html .= '<button type="button" class="btn btn-outline-dark mb-2" id="checkLinksButton">Check All Links</button>
        <div id="resultsPlaceholder"></div>';

        $html .= '
        <table class="table table-bordered table-sm table-hover" id="allLinksTable">
            <thead>
                <th>Status</th>
                <th>Action</th>
                <th>Code</th>
                <th>Link</th>
            </thead>

            <tbody>';
        foreach ($this->links as $key => $link) {
            $html .='
                    <tr id="link-'.$key.'">
                        <td><span class="status"><span class="oi oi-arrow-circle-right text-info"></span></span></td>
                        <td style="white-space: nowrap;">
                        <a class="btn btn-outline-secondary btn-sm mb-1" href="'.HTML_PATH_ADMIN_ROOT.'edit-content/'.$link['src'].'" target="_blank"><span class="oi oi-pencil"></span></a>
                        <a class="btn btn-outline-secondary btn-sm mb-1" rel="noreferrer" href="'.$link['href'].'" target="_blank"><span class="oi oi-eye"></span></a>
                        </td>
                        <td></td>
                        <td>'.$link['href'].'</td>
                    </tr>';
        }
        $html .= '
            </tbody>
        </table>';

        $html .= $this->includeJS('sortTable.js');
        // Define Javascript variable DOMAIN_BASE if it does not exist.
        $html .= '<script>
            if (typeof DOMAIN_BASE === "undefined") {
              var DOMAIN_BASE = "'.DOMAIN_BASE.'";
            }
            </script>';
        $html .= $this->includeJS('plugin.js');

        return $html;
    }

    public function install($position = 0)
    {
        parent::install($position);
        return $this->createCache();
    }

    // Method called when the user click on button save in the settings of the plugin
    public function post()
    {
        parent::post();
        $this->createCache();
    }

    public function afterPageCreate()
    {
        $this->createCache();
    }

    public function afterPageModify()
    {
        $this->createCache();
    }

    public function afterPageDelete()
    {
        $this->createCache();
    }

    // Returns the absolute path of the cache file
    private function cacheFile()
    {
        return $this->workspace().'cache.json';
    }

    /**
     * Generate cache
     * Call it when you create, edit or remove content
     */
    private function createCache()
    {
        /**
         * Get All Pages
         */
        global $pages;
        $list = $pages->getDB();

        /**
         * Get Ignored Domains
         */
        $ignoredDomains = explode("\n", $this->getValue('ignoredDomains'));

        /**
         * Iterate through each page and get links (Not under ignoredDomains)
         */
        foreach ($list as $pageKey) {
            $page = buildPage($pageKey);
            $content = $page->content();

            // Get links
            $pageLinks = Parser::getLinks($content);

            foreach ($pageLinks as $link) {
                if (!$this->checkIfIgnored($ignoredDomains, $link)) {
                    $this->links[] = [
                        'src' => $pageKey,
                        'href' => $link
                    ];
                }
            }
        }

        // Generate JSON file with the cache
        $json = json_encode($this->links);
        return file_put_contents($this->cacheFile(), $json, LOCK_EX);
    }

    /**
     * Endpoint: broken-link-checker?q={url}
     * Requires authentication
     */
    public function beforeAll()
    {
        $webhook = 'broken-link-checker';
        if ($this->webhook($webhook)) {
            $login = new Login();
            if (! $login->isLogged()) {
                $this->sendResponse(['error' => 'Unauthorized'], 401);
            }

            $query = isset($_GET['q']) ? $_GET['q'] : '';
            $trId = isset($_GET['trId']) && is_string($_GET['trId']) ? $_GET['trId'] : '';
            if ($query && (Valid::url($query))) {
                $response = [
                    'query' => $query,
                    'trId' => $trId,
                    'result' => Client::getHeaders($query)
                ];
                $this->sendResponse($response);
            } else {
                $this->sendResponse([
                    'query' => $query,
                    'trId' => $trId,
                    'error' => 'Invalid query'
                ], 400);
            }
        }
    }

    protected function sendResponse(array $array, int $httpcode = 200)
    {
        header('Content-type: application/json');
        http_response_code($httpcode);
        echo json_encode($array);
        exit(0);
    }

    protected function checkIfIgnored(array $ignoredDomains, string $url) : bool
    {
        foreach ($ignoredDomains as $domain) {
            $domain = trim($domain);
            // Skip blank domains (Invalid User Input)
            if (empty($domain)) {
                continue;
            }
            if (Text::stringContains($url, $domain, $caseSensitive = false)) {
                return true;
            }
        }
        return false;
    }
}
