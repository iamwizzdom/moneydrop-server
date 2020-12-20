<?php


namespace utility\paystack;


use que\http\curl\CurlRequest;
use que\http\curl\CurlResponse;
use que\support\Str;
use utility\Card;
use utility\paystack\exception\PaystackException;

trait Paystack
{
    use Card;

    /**
     * @param float $amount
     * @param float|null $extraCharge
     * @param string $currency
     * @param string|null $callbackUrl
     * @return CurlResponse
     * @throws PaystackException
     */
    public function init_transaction(float $amount, ?float $extraCharge = null,
                                     string $currency = 'NGN', string $callbackUrl = null): CurlResponse
    {

        if ($amount <= 0) throw new PaystackException("Please set a valid amount to pay.");

        $curl = CurlRequest::getInstance();

        $curl->setUrl(PAYSTACK_INIT_TRANS_URL);
        $curl->setHeaders([
            'Authorization: Bearer ' . PAYSTACK_KEY,
            'Content-Type: application/json',
            'Cache-Control: no-cache'
        ]);

        $post = [
            'email' => user('email'),
            'amount' => ($amount * 100),
            'currency' => $currency
        ];

        if ($extraCharge) $post['transaction_charge'] = $extraCharge;

        if ($callbackUrl) $post['callback_url'] = $callbackUrl;

        $curl->setPosts($post);

        $response = $curl->_exec();

        if ($response->isSuccessful()) {

            $data = $response->getResponseArray()['data'] ?? [];

            if (!empty($data)) {

                $trans = [
                    'uuid' => Str::uuidv4(),
                    'user_id' => user('id'),
                    'transaction_state' => TRANSACTION_TOPUP,
                    'transaction_type' => TRANSACTION_CREDIT,
                    'gateway_reference' => $data['reference'],
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => APPROVAL_PENDING
                ];

                if ($extraCharge) $trans['fees'] = $extraCharge;

                db()->insert('transactions', $trans);
            }
        }

        return $response;
    }

    /**
     * @param string $reference
     * @return CurlResponse
     * @throws PaystackException
     */
    public function verify_transaction(string $reference): CurlResponse
    {
        if (empty($reference)) throw new PaystackException("Please set a valid transaction reference.");

        $curl = CurlRequest::getInstance();

        $curl->setUrl(PAYSTACK_VERIFY_INIT_TRANS_URL . "/{$reference}");
        $curl->setHeaders([
            'Authorization: Bearer ' . PAYSTACK_KEY,
            'Content-Type: application/json',
            'Cache-Control: no-cache'
        ]);
        $curl->setMethod("GET");

        return $curl->_exec();
    }

    /**
     * @param string $cardUUID
     * @param float $amount
     * @param float|null $extraCharge
     * @param string $currency
     * @return CurlResponse
     * @throws PaystackException
     */
    public function charge_card(string $cardUUID, float $amount,
                                ?float $extraCharge = null, string $currency = 'NGN'): CurlResponse
    {

        if ($amount <= 0) throw new PaystackException("Please set a valid amount to pay.");

        $curl = CurlRequest::getInstance();

        $curl->setUrl(PAYSTACK_CHARGE_CARD_URL);
        $curl->setHeaders([
            'Authorization: Bearer ' . PAYSTACK_KEY,
            'Content-Type: application/json',
            'Cache-Control: no-cache'
        ]);

        $authCode = $this->getCardAuthCode($cardUUID);

        if (!$authCode) throw new PaystackException("Card authorization not found.");

        $post = [
            'email' => user('email'),
            'amount' => ($amount * 100),
            'currency' => $currency,
            'authorization_code' => $authCode
        ];

        if ($extraCharge) $post['transaction_charge'] = $extraCharge;

        $curl->setPosts($post);

        $response = $curl->_exec();

        if ($response->isSuccessful()) {

            $data = $response->getResponseArray()['data'] ?? [];

            if (!empty($data)) {

                $trans = [
                    'uuid' => Str::uuidv4(),
                    'user_id' => user('id'),
                    'card_id' => $cardUUID,
                    'transaction_state' => TRANSACTION_TOPUP,
                    'transaction_type' => TRANSACTION_CREDIT,
                    'gateway_reference' => $data['reference'],
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => ($data['status'] ?? '') == 'success' ? APPROVAL_SUCCESSFUL : APPROVAL_FAILED
                ];

                if ($extraCharge) $trans['fees'] = $extraCharge;

                db()->insert('transactions', $trans);
            }
        }

        return $response;
    }

    /**
     * @param string $bvn
     * @return CurlResponse
     * @throws PaystackException
     */
    public function resolve_bvn(string $bvn): CurlResponse
    {
        if (empty($bvn)) throw new PaystackException("Please set a valid BVN.");

        $curl = CurlRequest::getInstance();

        $curl->setUrl(PAYSTACK_RESOLVE_BVN_URL . "/{$bvn}");
        $curl->setHeaders([
            'Authorization: Bearer ' . PAYSTACK_KEY,
            'Content-Type: application/json',
            'Cache-Control: no-cache'
        ]);
        return $curl->_exec();
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
                              $firstName = null, $middleName = null, $lastName = null): CurlResponse
    {
        if (empty($bvn)) throw new PaystackException("Please set a valid BVN.");

        if (empty($accountNumber)) throw new PaystackException("Please set a valid account number.");

        if (empty($bankCode)) throw new PaystackException("Please set a valid account code.");

        $curl = CurlRequest::getInstance();

        $post = [
            'bvn' => $bvn,
            'account_number' => $accountNumber,
            'bank_code' => $bankCode
        ];

        if (!is_null($firstName)) $post['first_name'] = $firstName;

        if (!is_null($middleName)) $post['middle_name'] = $middleName;

        if (!is_null($lastName)) $post['last_name'] = $lastName;

        $curl->setUrl(PAYSTACK_MATCH_BVN_URL);
        $curl->setPosts($post);
        $curl->setHeaders([
            'Authorization: Bearer ' . PAYSTACK_KEY,
            'Content-Type: application/json',
            'Cache-Control: no-cache'
        ]);
        return $curl->_exec();
    }

    /**
     * @param string $accountNumber
     * @param string $bankCode
     * @return CurlResponse
     * @throws PaystackException
     */
    public function resolve_account(string $accountNumber, string $bankCode): CurlResponse
    {
        if (empty($accountNumber)) throw new PaystackException("Please set a valid account number.");

        if (empty($bankCode)) throw new PaystackException("Please set a valid account code.");

        $curl = CurlRequest::getInstance();

        $query = http_build_query([
            'bank_code' => $bankCode,
            'account_number' => $accountNumber,
        ]);

        $curl->setUrl(PAYSTACK_RESOLVE_ACCOUNT_URL . "?{$query}");

        $curl->setHeaders([
            'Authorization: Bearer ' . PAYSTACK_KEY,
            'Content-Type: application/json',
            'Cache-Control: no-cache'
        ]);

        return $curl->_exec();
    }

    /**
     * @param string $cardBin
     * @return CurlResponse
     * @throws PaystackException
     */
    public function resolve_card(string $cardBin): CurlResponse
    {

        if (empty($cardBin)) throw new PaystackException("Please set a valid card bin.");

        $curl = CurlRequest::getInstance();

        $curl->setUrl(PAYSTACK_RESOLVE_CARD_URL . "/{$cardBin}");
        $curl->setHeaders([
            'Authorization: Bearer ' . PAYSTACK_KEY,
            'Content-Type: application/json',
            'Cache-Control: no-cache'
        ]);
        return $curl->_exec();
    }

}