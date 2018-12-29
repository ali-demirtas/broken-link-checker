<?php

namespace BrokenLinkChecker;

class Parser
{

    public static function getLinks(string $html) : array
    {

        $links = [];

        $DOM = new \DOMDocument();
        // Load HTML into the DOMDocument
        $DOM->loadHTML($html);
        // Fetch all anchor tags and iterate
        $anchorTags = $DOM->getElementsByTagName('a');
        foreach ($anchorTags as $link) {
            $href = $link->getAttribute('href');
            if (Valid::url($href)) {
                $links[] = $href;
            } else {
                // Invalid Link $href
            }
        }
        // Fetch all image tags and iterate
        $imageTags = $DOM->getElementsByTagName('img');
        foreach ($imageTags as $link) {
            $href = $link->getAttribute('src');
            if (Valid::url($href)) {
                $links[] = $href;
            } else {
                // Invalid Link $href
            }
        }
        return $links;
    }
}
