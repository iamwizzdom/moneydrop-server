<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 4/12/2021
 * Time: 11:37 PM
 */

namespace utility\mono;


use que\http\curl\CurlRequest;
use que\http\curl\CurlResponse;
use utility\mono\exception\MonoException;

trait Mono
{
    /**
     * @param string $code
     * @return CurlResponse
     * @throws MonoException
     */
    public function account_auth(string $code): CurlResponse
    {
        if (empty($code)) throw new MonoException("Please set a valid linking code.");

        $curl = CurlRequest::getInstance();

        $curl->setUrl(MONO_ACCOUNT_AUTH_URL);
        $curl->setHeaders([
            'mono-sec-key: ' . env('MONO_API_KEY'),
            'Content-Type: application/json',
        ]);
        $curl->setPost("code", $code);

        return $curl->send();
    }

    /**
     * @param string $accountID
     * @return CurlResponse
     * @throws MonoException
     */
    public function account_details(string $accountID): CurlResponse
    {

        if (empty($accountID)) throw new MonoException("Please provide a valid account id.");

        $curl = CurlRequest::getInstance();

        $curl->setUrl(MONO_ACCOUNT_DETAILS_URL . "/{$accountID}");
        $curl->setHeader('mono-sec-key', env('MONO_API_KEY'));
        $curl->setMethod("GET");

        return $curl->send();
    }

    /**
     * @param string $accountID
     * @return CurlResponse
     * @throws MonoException
     */
    public function account_income(string $accountID): CurlResponse
    {

        if (empty($accountID)) throw new MonoException("Please provide a valid account id.");

        $curl = CurlRequest::getInstance();

        $curl->setUrl("https://api.withmono.com/accounts/{$accountID}/income");
        $curl->setHeader('mono-sec-key', env('MONO_API_KEY'));
        $curl->setHeader('Content-Type', 'application/json');
        $curl->setMethod("GET");

        return $curl->send();
    }

}