<?php


namespace utility\paystack;


use que\http\curl\CurlRequest;
use que\http\curl\CurlResponse;
use que\support\Str;
use que\utility\random\UUID;
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
                    'type' => TRANSACTION_TOP_UP,
                    'direction' => 'b2w',
                    'gateway_reference' => $data['reference'],
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => APPROVAL_PENDING,
                    'narration' => 'Add card charge/top-up transaction'
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
                    'type' => TRANSACTION_TOP_UP,
                    'direction' => 'b2w',
                    'gateway_reference' => $data['reference'],
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => APPROVAL_PENDING,
                    'narration' => "wallet top-up transaction"
                ];

                if ($extraCharge) $trans['fees'] = $extraCharge;

                $trans = db()->insert('transactions', $trans);

                $verify = $this->verify_transaction($data['reference']);

                if ($trans->isSuccessful() && $verify->isSuccessful()) {

                    $data = $verify->getResponseArray()['data'] ?? [];

                    $trans->getFirstWithModel()?->update([
                        'status' => ($data['status'] ?? 'failed') == 'success' ? APPROVAL_SUCCESSFUL : APPROVAL_FAILED
                    ]);
                }
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

        if (!empty($firstName)) $post['first_name'] = $firstName;

        if (!empty($middleName)) $post['middle_name'] = $middleName;

        if (!empty($lastName)) $post['last_name'] = $lastName;

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

        if (empty($bankCode)) throw new PaystackException("Please set a valid bank code.");

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

    /**
     * @param string $name
     * @param string $accountNumber
     * @param string $bankCode
     * @param string $currency
     * @return CurlResponse
     * @throws PaystackException
     */
    public function create_transfer_recipient(string $name, string $accountNumber,
                                              string $bankCode, string $currency = 'NGN'): CurlResponse
    {

        if (empty($name)) throw new PaystackException("Please set a valid account name.");
        if (empty($accountNumber)) throw new PaystackException("Please set a valid account number.");
        if (empty($bankCode)) throw new PaystackException("Please set a valid bank code.");

        $curl = CurlRequest::getInstance();

        $curl->setUrl(PAYSTACK_TRANSFER_RECIPIENT_URL);
        $curl->setPosts([
            'type' => 'nuban',
            'name' => $name,
            'account_number' => $accountNumber,
            'bank_code' => $bankCode,
            'currency' => $currency
        ]);
        $curl->setHeaders([
            'Authorization: Bearer ' . PAYSTACK_KEY,
            'Content-Type: application/json',
            'Cache-Control: no-cache'
        ]);
        return $curl->_exec();
    }

    /**
     * @param float $amount
     * @param string $recipient
     * @param string $reference
     * @param string|null $reason
     * @param string $currency
     * @return CurlResponse
     * @throws PaystackException
     */
    public function init_transfer(float $amount, string $recipient, string $reference,
                                  string $reason = null, string $currency = "NGN"): CurlResponse
    {


        if ($amount < WALLET_TRANSFER_MIN) throw new PaystackException(sprintf(
            "Sorry you can't an amount less than %s {$currency}", WALLET_TRANSFER_MIN));
        if (empty($recipient)) throw new PaystackException("Please set a valid recipient.");
        if (empty($reference) || !UUID::is_valid($reference)) throw new PaystackException("Please set a valid reference.");

        $curl = CurlRequest::getInstance();

        $curl->setUrl(PAYSTACK_TRANSFER_URL);
        $post = [
            'source' => 'balance',
            'amount' => $amount,
            'recipient' => $recipient,
            'reference' => $reference,
            'currency' => $currency
        ];
        if ($reason) $post['reason'] = $reason;
        $curl->setPosts($post);
        $curl->setHeaders([
            'Authorization: Bearer ' . PAYSTACK_KEY,
            'Content-Type: application/json',
            'Cache-Control: no-cache'
        ]);

        $response = $curl->_exec();

        if ($response->isSuccessful()) {

            $data = $response->getResponseArray()['data'] ?? [];

            if (!empty($data)) {

                $trans = [
                    'uuid' => Str::uuidv4(),
                    'user_id' => user('id'),
                    'type' => TRANSACTION_WITHDRAWAL,
                    'direction' => "w2b",
                    'recipient_code' => $recipient,
                    'gateway_reference' => $data['reference'],
                    'transfer_code' => $data['transfer_code'],
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => APPROVAL_PENDING,
                    'narration' => 'Wallet cash-out transaction'
                ];

                if ($reason) $trans['narration'] = $reason;

                $trans = db()->insert('transactions', $trans);

                if ($trans->isSuccessful()) {

                    $trans->getFirstWithModel()?->update([
                        'status' => ($data['status'] ?? 'failed') == 'success' ? APPROVAL_PROCESSING : APPROVAL_FAILED
                    ]);
                }
            }
        }

        return $response;
    }

}
