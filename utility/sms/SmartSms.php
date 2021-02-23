<?php

namespace utility\sms;


use que\http\curl\CurlResponse;

trait SmartSms
{
    /**
     * @param string $message
     * @param string $to
     * @return CurlResponse
     */
    public function send(string $message, string $to): CurlResponse
    {
        $token = 'JzTVSHcCxmmKxwuJN7MF3iO6E6x7q50nVjdNIq1I4eIzMoXmmgQG3wB5fzbzOoi2xp44peY2bOzxffDAAlbBiWKCrrAVLQtWDLfR';

        $curl = http()->curl_request();
        $curl->setUrl('https://smartsmssolutions.com/api/json.php?');
        $curl->setPosts([
            'sender' => config('template.app.header.name'),
            'to' => $to,
            'message' => $message,
            'type' => '0',
            'routing' => 3,
            'token' => $token
        ]);

        return $curl->_exec();
    }
}
