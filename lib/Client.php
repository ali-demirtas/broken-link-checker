<?php

namespace BrokenLinkChecker;

class Client
{

    const USER_AGENT = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:67.0) Gecko/20100101 Firefox/67.0';

    public static function getHeaders(string $url) : array
    {
        // Attempt a HEAD request
        $response = self::head($url);
        /**
         * When HTTP 405 Method Not Allowed (HEAD), Fallback to a GET request.
         * Example: https://www.amazon.in
         */
        if ($response['code'] === 405) {
            return self::get($url);
        }
        return $response;
    }

    /**
     * [HTTP HEAD Request]
     */
    protected static function head(string $url) : array
    {
        $ch = curl_init($url);
        // Spoof User Agent
        curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
        // We need headers
        curl_setopt($ch, CURLOPT_HEADER, true);
        // We dont need body of response
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [
            'code' => $httpcode,
            'isUp' => self::isUp($httpcode)
        ];
    }

    /**
     * [HTTP GET Request]
     */
    protected static function get(string $url) : array
    {
        $headers = get_headers($url);
        $httpcode = substr($headers[0], 9, 3);
        return [
            'code' => $httpcode,
            'isUp' => self::isUp($httpcode)
        ];
    }

    protected static function isUp($httpcode) : bool
    {
        $httpcode = (int)$httpcode;
        return (($httpcode >= 200) && ($httpcode <= 299)) ? true : false;
    }
}
