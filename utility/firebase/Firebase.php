<?php


namespace utility\firebase;


use model\Push;
use que\http\curl\CurlResponse;

trait Firebase
{
    // sending push message to single user by firebase reg id
    public function send($to, Push $push): CurlResponse
    {
        return $this->sendPushNotification([
            'to' => $to,
            'data' => $push->getData()
        ]);
    }

    // Sending message to a topic by topic name
    public function sendToTopic($to, Push $push): CurlResponse
    {
        return $this->sendPushNotification([
            'to' => '/topics/' . $to,
            'data' => $push->getData()
        ]);
    }

    // sending push message to multiple users by firebase registration ids
    public function sendMultiple($registration_ids, Push $push): CurlResponse
    {
        return $this->sendPushNotification([
            'to' => $registration_ids,
            'data' => $push->getData()
        ]);
    }

    // function makes curl request to firebase servers
    private function sendPushNotification($fields): CurlResponse
    {
        $curl = http()->curl_request();
        $curl->setUrl('https://fcm.googleapis.com/fcm/send');
        $curl->setHeaders([
            'Authorization: key=' . env('FIREBASE_API_KEY'),
            'Content-Type: application/json'
        ]);
        $curl->setPosts($fields);
        return $curl->send();
    }
}