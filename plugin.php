<?php
class PluginBrokenLinksChecker extends Plugin
{
    protected $links = [];

    public function init()
    {
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
        <div id="resultsPlaceholder"></div>
        ';

        $html .= '
        <table class="table table-bordered table-sm table-hover" id="allLinksTable">
            <thead>
                <th>Status</th>
                <th>Edit</th>
                <th>Link</th>
            </thead>

            <tbody>';
        foreach ($this->links as $link) {
            $html .='
                    <tr>
                        <td><span class="status"><span class="oi oi-arrow-circle-right text-info"></span></span></td>
                        <td><a class="btn btn-outline-secondary btn-sm mb-1" href="'.HTML_PATH_ADMIN_ROOT.'edit-content/'.$link['src'].'" target="_blank"><span class="oi oi-pencil"></span></a></td>
                        <td>'.$link['href'].'</td>
                    </tr>';
        }
        $html .= '
            </tbody>
        </table>';

        $html .= '
        <script>
        // DOM Ready
        $(function(){
            $("#checkLinksButton").click(function(){
                var failedCount = 0;
                $("#allLinksTable tbody tr").each(function(){
                    $row = $(this);
                    var link = $row.find("td:eq(2)").text();

                    $("#checkLinksButton").prop("disabled", true);
                    $("#checkLinksButton").html("Loading...");

                    var params = "q=" + encodeURI(link);

                    // In Progress
                    $row.find("td:eq(0)").html("<span class=\"oi oi-loop-circular text-info\"></span>");

                    // Disable async to prevent excess load | Deprecated: Remove later.
                    $.ajax({
                        type: "get",
                        url: "'.DOMAIN_BASE.'broken-link-checker'.'",
                        data: params,
                        async: false,
                        success: function(data) {
                            if (data.isUp) {
                                // Success
                                $row.find("td:eq(0)").html("<span class=\"oi oi-circle-check text-success\"></span>");
                            } else {
                                // Fail
                                $row.find("td:eq(0)").html("<span class=\"oi oi-circle-x text-danger\"></span>");
                                failedCount++;
                                $("#resultsPlaceholder").html("<div class=\"alert alert-info mt-3\">Failed: "+failedCount+"</div>");
                            }
                        },
                        error: function(data) {
                            // Fail
                            $row.find("td:eq(0)").html("<span class=\"oi oi-circle-x text-danger\"></span>");
                            failedCount++;
                            $("#resultsPlaceholder").html("<div class=\"alert alert-info mt-3\">Failed: "+failedCount+"</div>");
                        }
                    });

                    // Rollback to default layout
                    $("#checkLinksButton").removeAttr("disabled");
                    $("#checkLinksButton").html("Check All Links");

                });
            });
        });
        </script>
        ';

        return $html;
    }

    protected function getLinks(string $html, string $pageKey) : array
    {
        /**
         * Get Ignored Domains
         */
        $ignoredDomains = explode("\n", $this->getValue('ignoredDomains'));

        $links = [];

        $DOM = new DOMDocument();
        // Load HTML into the DOMDocument
        $DOM->loadHTML($html);
        // Fetch all anchor tags
        $anchorTags = $DOM->getElementsByTagName('a');
        // Loop through anchor tags
        foreach ($anchorTags as $link) {
            $href = $link->getAttribute('href');
            // Check and skip if it is under ignoredDomains
            if ($this->isValidUrl($href) && !$this->checkIfIgnored($ignoredDomains, $href)) {
                $links[] = [
                    'src' => $pageKey,
                    'href' => $href
                ];
            } else {
                /**
                 * Invalid Link $link->getAttribute('href');
                 * Attempt to fix to absolute if local and not beginning with #
                 */
                if (isset($href[0]) && ($href[0] !== '#')) {
                    if ($href[0] === '.') {
                        $fixedHref = DOMAIN.substr($href, 1);
                    } else {
                        $fixedHref = DOMAIN_BASE.$href;
                    }

                    if ($this->isValidUrl($fixedHref) && !$this->checkIfIgnored($ignoredDomains, $fixedHref)) {
                        $links[] = [
                            'src' => $pageKey,
                            'href' => $fixedHref
                        ];
                    }
                }
            }
        }
        return $links;
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

    protected function isValidUrl(string $url) : bool
    {
        return (filter_var($url, FILTER_VALIDATE_URL) !== false);
    }

    protected function isWorkingLink(string $url) : bool
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, true);    // we want headers
        curl_setopt($ch, CURLOPT_NOBODY, true);    // we don't need body
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $successCodes = [200,301,302];

        return (in_array($httpcode, $successCodes)) ? true : false;
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

    // Generate the cache file
    // Call it when you create, edit or remove content
    private function createCache()
    {
        /**
         * Get All Pages
         */
        global $pages;
        $list = $pages->getList($pageNumber = 1, $numberOfItems = -1, $onlyPublished = false);

        /**
         * Iterate through each page and get links
         */
        foreach ($list as $pageKey) {
            $page = buildPage($pageKey);
            $content = $page->content();

            // Get links
            $pageLinks = $this->getLinks($content, $pageKey);

            foreach ($pageLinks as $link) {
                $this->links[] = $link;
            }
        }

        // Generate JSON file with the cache
        $json = json_encode($this->links);
        return file_put_contents($this->cacheFile(), $json, LOCK_EX);
    }

    public function beforeAll()
    {
        $webhook = 'broken-link-checker';
        if ($this->webhook($webhook)) {
            /**
             * Check if authenticated
             */
            $login = new Login();
            if (! $login->isLogged()) {
                $this->sendResponse(['error' => 'Not authenticated']);
            }

            $query = isset($_GET['q']) ? $_GET['q'] : '';
            if ($query) {
                $response = [
                    'query' => $query,
                    'isUp' => $this->isWorkingLink($query)
                ];
                $this->sendResponse($response);
            } else {
                $this->sendResponse(['error' => 'Invalid query']);
            }
        }
    }

    protected function sendResponse(array $array)
    {
        header('Content-type: application/json');
        echo json_encode($array);
        exit(0);
    }
}
