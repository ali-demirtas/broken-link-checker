<?php

namespace BrokenLinkChecker;

class Valid
{
    public function url(string $url) : bool
    {
        return (filter_var($url, FILTER_VALIDATE_URL) !== false);
    }
}
