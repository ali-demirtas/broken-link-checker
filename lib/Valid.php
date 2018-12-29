<?php

namespace BrokenLinkChecker;

class Valid
{
    public static function url(string $url) : bool
    {
        return (filter_var($url, FILTER_VALIDATE_URL) !== false);
    }
}
