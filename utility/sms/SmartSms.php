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
        $sender = config('template.app.header.name');
        $data = [
            "to" => $to,
            "from" => $sender,
            "sms" => $message,
            "type" => "plain",
            "channel" => "sms",
            "api_key" => "TLD9FjI2ZvMtOtXQWtTZ5ezY9JQG1tT7FFCFSOoP1IPWsVQ01imZLuo6r6XU1e"
        ];
        $curl = http()->curl_request();
        $curl->setUrl('https://termii.com/api/sms/send');
        $curl->setPosts($data);

        $curl->send();
        $curl->setUrl('https://termii.com/api/sms/send');
        $data['channel'] = 'whatsapp';
        $curl->setPosts($data);

        return $curl->send();
    }
}
