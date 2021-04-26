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
        $sender = config('template.app.header.name');
        $curl = http()->curl_request();
        $curl->setUrl('https://smartsmssolutions.com/api/json.php?');
        $curl->setPosts([
            'sender' => $sender,
            'to' => $to,
            'message' => $message,
            'type' => '0',
            'routing' => 3,
            'token' => $token
        ]);

        $cl = curl_init();

        $post_data = json_encode([
            "to" => $to,
            "from" => $sender,
            "sms" => $message,
            "type" => "plain",
            "channel" => "whatsapp",
            "api_key" => "TLD9FjI2ZvMtOtXQWtTZ5ezY9JQG1tT7FFCFSOoP1IPWsVQ01imZLuo6r6XU1e"
        ]);

        curl_setopt_array($cl, [
            CURLOPT_URL => "https://termii.com/api/sms/send",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        ]);

        curl_exec($cl);

        curl_close($cl);

        return $curl->send();
    }
}
