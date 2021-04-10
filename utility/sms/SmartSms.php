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
        $token = '2nZc8deS6gM1uuXT5XDQNFx1wQIIdwAtDPx5vRDeid2pnJH3Ee2MTEIurcnOqyAYWzqo6NN7aO2DbsGsZNROL7JjY8OEi6NpM2Ah';

        $curl = http()->curl_request();
        $curl->setUrl('https://smartsmssolutions.com/api/json.php?');
        $curl->setPosts([
            'sender' => "MoneyDrop",
            'to' => $to,
            'message' => $message,
            'type' => '0',
            'routing' => 3,
            'token' => $token
        ]);

        return $curl->send();
    }
}
