<?php

namespace BrokenLinkChecker;

class Client
{

    const USER_AGENT = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    public static function getHeaders(string $url): array
    {
        // Attempt a HEAD request
        $response = self::request($url, 'HEAD');
        /**
         * When HTTP 405 Method Not Allowed (HEAD), Fallback to a GET request.
         * Example: https://www.amazon.in
         */
        if ($response['code'] === 405) {
            return self::request($url, 'GET');
        }
        return $response;
    }

    /**
     * [request Make HTTP Request]
     * @param  string $url    [url]
     * @param  string $method [HTTP Method (HEAD/GET)]
     * @return [array]         [result]
     */
    protected static function request(string $url, $method = 'HEAD'): array
    {
        $ch = curl_init($url);
        // Spoof User Agent
        curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
        // We need headers
        curl_setopt($ch, CURLOPT_HEADER, true);
        // We dont need body of response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        if ($method === 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [
            'code' => $httpcode,
            'isUp' => self::isUp($httpcode)
        ];
    }

    protected static function isUp($httpcode): bool
    {
        $httpcode = (int)$httpcode;
        return (($httpcode >= 200) && ($httpcode <= 299)) ? true : false;
    }
}
