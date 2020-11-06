<?php


namespace utility\paystack;


use que\http\curl\CurlRequest;
use que\http\curl\CurlResponse;
use utility\paystack\exception\PaystackException;

class Paystack
{
    /**
     * @var Paystack
     */
    private static $instance;

    protected function __construct()
    {
    }

    private function __wakeup()
    {
        // TODO: Implement __wakeup() method.
    }

    private function __clone()
    {
        // TODO: Implement __clone() method.
    }

    /**
     * @return Paystack
     */
    public static function getInstance() {
        if (!isset(self::$instance))
            self::$instance = new self;
        return self::$instance;
    }

    /**
     * @param int $amount
     * @return CurlResponse
     * @throws PaystackException
     */
    public function init_payment(int $amount) {
        if ($amount <= 0)
            throw new PaystackException("Please set a valid amount to pay.");

        $curl = CurlRequest::getInstance();

        $curl->setUrl(PAYSTACK_TRANS_URL);
        $curl->setHeader([
            'Authorization: Bearer ' . PAYSTACK_TEST_PRIVATE_KEY,
            'Content-Type: application/json',
            'Cache-Control: no-cache'
        ]);
        $curl->setPost(json_encode([
            'email' => PAYSTACK_EMAIL,
            'amount' => $amount
        ], true));
        return $curl->connect();
    }

    /**
     * @param string $bvn
     * @return CurlResponse
     * @throws PaystackException
     */
    public function resolve_bvn(string $bvn) {
        if (empty($bvn))
            throw new PaystackException("Please set a valid BVN.");

        $curl = CurlRequest::getInstance();

        $curl->setUrl(PAYSTACK_RESOLVE_BVN_URL . "/{$bvn}");
        $curl->setHeader([
            'Authorization: Bearer ' . PAYSTACK_TEST_PRIVATE_KEY,
            'Content-Type: application/json',
            'Cache-Control: no-cache'
        ]);
        return $curl->connect();
    }

    /**
     * @param string $bvn
     * @param string $accountNumber
     * @param string $bankCode
     * @param null $firstName
     * @param null $middleName
     * @param null $lastName
     * @return CurlResponse
     * @throws PaystackException
     */
    public function match_bvn(string $bvn, string $accountNumber, string $bankCode,
                              $firstName = null, $middleName = null, $lastName = null) {
        if (empty($bvn))
            throw new PaystackException("Please set a valid BVN.");

        if (empty($accountNumber))
            throw new PaystackException("Please set a valid account number.");

        if (empty($bankCode))
            throw new PaystackException("Please set a valid account code.");

        $curl = CurlRequest::getInstance();

        $post = [
            'bvn' => $bvn,
            'account_number' => $accountNumber,
            'bank_code' => $bankCode
        ];

        if (!is_null($firstName))
            $post['first_name'] = $firstName;

        if (!is_null($middleName))
            $post['middle_name'] = $middleName;

        if (!is_null($lastName))
            $post['last_name'] = $lastName;

        $curl->setUrl(PAYSTACK_MATCH_BVN_URL);
        $curl->setPost(json_encode($post));
        $curl->setHeader([
            'Authorization: Bearer ' . PAYSTACK_TEST_PRIVATE_KEY,
            'Content-Type: application/json',
            'Cache-Control: no-cache'
        ]);
        return $curl->connect();
    }

    /**
     * @param string $accountNumber
     * @param string $bankCode
     * @return CurlResponse
     * @throws PaystackException
     */
    public function resolve_account(string $accountNumber, string $bankCode) {
        if (empty($accountNumber))
            throw new PaystackException("Please set a valid account number.");

        if (empty($bankCode))
            throw new PaystackException("Please set a valid account code.");

        $curl = CurlRequest::getInstance();

        $curl->setUrl(PAYSTACK_RESOLVE_ACCOUNT_URL . "?account_number={$accountNumber}&bank_code={$bankCode}");
        $curl->setHeader([
            'Authorization: Bearer ' . PAYSTACK_TEST_PRIVATE_KEY,
            'Content-Type: application/json',
            'Cache-Control: no-cache'
        ]);
        return $curl->connect();
    }

    /**
     * @param string $cardBin
     * @return CurlResponse
     * @throws PaystackException
     */
    public function resolve_card(string $cardBin) {

        if (empty($cardBin))
            throw new PaystackException("Please set a valid card bin.");

        $curl = CurlRequest::getInstance();

        $curl->setUrl(PAYSTACK_RESOLVE_CARD_URL . "/{$cardBin}");
        $curl->setHeader([
            'Authorization: Bearer ' . PAYSTACK_TEST_PRIVATE_KEY,
            'Content-Type: application/json',
            'Cache-Control: no-cache'
        ]);
        return $curl->connect();
    }

}