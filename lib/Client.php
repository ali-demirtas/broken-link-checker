<?php

namespace BrokenLinkChecker;

class Client
{

    const USER_AGENT = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    public static function getLinkStatus(string $url): array
    {
        // Attempt a HEAD request
        $httpCode = self::getHttpCode($url, 'HEAD');
        /**
         * When HTTP 405 Method Not Allowed (HEAD), Fallback to a GET request.
         * Example: https://www.amazon.in
         */
        if ($httpCode === 405) {
            $httpCode = self::getHttpCode($url, 'GET');
        }
        return [
            'url' => $url,
            'code' => $httpCode,
            'linkStatus' => self::convertHttpCodeToStatus($httpCode)
        ];
    }

    /**
     * [getHttpCode Make HTTP Request]
     * @param  string $url    [url]
     * @param  string $method [HTTP Method (HEAD/GET)]
     * @return [int]         [httpCode]
     */
    protected static function getHttpCode(string $url, $method = 'HEAD'): int
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
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return (int)$httpCode;
    }

    protected static function convertHttpCodeToStatus(int $httpCode): string
    {
        if (($httpCode >= 200) && ($httpCode <= 299)) {
            return 'Success';
        } elseif (($httpCode >= 300) && ($httpCode <= 310)) {
            return 'Redirect';
        } else {
            return 'Error';
        }
    }
}
