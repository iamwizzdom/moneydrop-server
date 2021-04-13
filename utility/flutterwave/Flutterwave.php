<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 4/13/2021
 * Time: 5:19 PM
 */

namespace utility\flutterwave;


use que\http\curl\CurlRequest;
use que\http\curl\CurlResponse;
use utility\Card;
use utility\flutterwave\exception\FlutterwaveException;

trait Flutterwave
{
    use Card;

    /**
     * @param string $reference
     * @return CurlResponse
     * @throws FlutterwaveException
     */
    public function verify_transaction(string $reference): CurlResponse
    {
        if (empty($reference)) throw new FlutterwaveException("Please set a valid transaction reference.");

        $curl = CurlRequest::getInstance();

        $curl->setUrl(FLUTTERWAVE_TRANS_VERIFY_URL);
        $curl->setHeader('Content-Type', 'application/json');
        $curl->setPosts(['txref' => $reference, 'SECKEY' => FLUTTERWAVE_SECRET_KEY]);

        return $curl->send();
    }
}