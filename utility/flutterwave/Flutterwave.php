<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 4/13/2021
 * Time: 5:19 PM
 */

namespace utility\flutterwave;


use model\Transaction;
use que\http\curl\CurlRequest;
use que\http\curl\CurlResponse;
use que\support\Str;
use que\utility\money\Item;
use que\utility\random\UUID;
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
        $curl->setPosts(['txref' => $reference, 'SECKEY' => env('FLUTTERWAVE_SECRET_KEY')]);

        return $curl->send();
    }

    /**
     * @param string $cardUUID
     * @param float $amount
     * @param float|null $extraCharge
     * @param string $currency
     * @return CurlResponse
     * @throws FlutterwaveException
     */
    public function charge_card(string $cardUUID, float $amount,
                                ?float $extraCharge = null, string $currency = 'NGN'): CurlResponse
    {

        if ($amount <= 0) throw new FlutterwaveException("Please set a valid amount to pay.");

//        $amount = Item::factor($amount)->getCents();

        $curl = CurlRequest::getInstance();

        $curl->setUrl(FLUTTERWAVE_CHARGE_CARD_URL);
        $curl->setHeader('Content-Type', 'application/json');

        $card = $this->getMyCard($cardUUID);

        if (!$card) throw new FlutterwaveException("Card not found.");

        $reference = Str::uuidv4();

        $card->load('user');

        $curl->setPosts([
            'SECKEY' => env('FLUTTERWAVE_SECRET_KEY'),
            'amount' => $amount,
            'currency' => $currency,
            'token' => $card->auth,
            'firstname' => $card->user->firstname,
            'lastname' => $card->user->lastname,
            'email' => $card->user->email,
            'txRef' => $reference,
            'narration' => "Moneydrop wallet top-up"
        ]);

        $response = $curl->send();

        if ($response->isSuccessful()) {

            $res = $response->getResponseArray();

            $data = $res['data'] ?? [];

            if (!empty($data) && in_array(($data['status'] ?? 'failed'), ['success', 'successful'])) {

                $trans = [
                    'uuid' => Str::uuidv4(),
                    'user_id' => user('id'),
                    'card_id' => $cardUUID,
                    'type' => Transaction::TRANS_TYPE_TOP_UP,
                    'direction' => 'b2w',
                    'gateway_reference' => $data['txRef'],
                    'amount' => Item::factor($amount)->getCents(),
                    'currency' => $currency,
                    'status' => Transaction::TRANS_STATUS_PENDING,
                    'narration' => "wallet top-up transaction"
                ];

                if ($extraCharge) $trans['fee'] = $extraCharge;

                $trans = db()->insert('transactions', $trans);

                $verify = $this->verify_transaction($data['txRef']);

                if ($trans->isSuccessful() && $verify->isSuccessful()) {

                    $veri = $verify->getResponseArray();
                    $data = $veri['data'] ?? [];
                    $success = (in_array(($data['status'] ?? 'failed'), ['success', 'successful']) && $data['chargecode'] === '00');
                    $fields = ['status' => $success ? Transaction::TRANS_STATUS_SUCCESSFUL : Transaction::TRANS_STATUS_FAILED];
                    if (!$success) $fields['narration'] = $veri['message'];
                    $trans->getFirstWithModel()?->update($fields);
                    if (!$success) throw new FlutterwaveException($veri['message']);
                }
            }
        }

        return $response;
    }

    /**
     * @param float $amount
     * @param string $recipient
     * @param string $bankCode
     * @param string $accountNumber
     * @param string $reference
     * @param string|null $reason
     * @param string $currency
     * @return CurlResponse
     * @throws FlutterwaveException
     */
    public function init_transfer(float $amount, string $recipient, string $bankCode, string $accountNumber, string $reference,
                                  string $reason = null, string $currency = "NGN"): CurlResponse
    {

        if ($amount < WALLET_TRANSFER_MIN) throw new FlutterwaveException(sprintf(
            "Sorry you can't an amount less than %s {$currency}", WALLET_TRANSFER_MIN));
        if (empty($recipient) || !UUID::is_valid($recipient)) throw new FlutterwaveException("Please set a valid recipient.");
        if (empty($reference) || !UUID::is_valid($reference)) throw new FlutterwaveException("Please set a valid reference.");

        $curl = CurlRequest::getInstance();

        $curl->setUrl(FLUTTERWAVE_TRANSFER_URL);
        $curl->setHeader('Content-Type', 'application/json');
        $curl->setPosts([
            'seckey' => env('FLUTTERWAVE_SECRET_KEY'),
            'narration' => $reason ?: 'Moneydrop wallet cash-out',
            'amount' => $amount,
            'account_bank' => $bankCode,
            'account_number' => $accountNumber,
            'reference' => $reference,
            'currency' => $currency
        ]);

        $amount = Item::factor($amount)->getCents();

        $response = $curl->send();

        if ($response->isSuccessful()) {

            $res = $response->getResponseArray();
            $data = $res['data'] ?? [];

            if (!empty($data) && in_array(($data['status'] ?? 'failed'), ['success', 'successful'])) {

                $fee = Transaction::TRANSFER_5K_FEE;
                if ($amount > 500000 && $amount < 5000000) $fee = Transaction::TRANSFER_50K_FEE;
                elseif ($amount >= 5000000) $fee = Transaction::TRANSFER_51K_FEE;

                $trans = [
                    'uuid' => Str::uuidv4(),
                    'user_id' => user('id'),
                    'type' => Transaction::TRANS_TYPE_WITHDRAWAL,
                    'direction' => "w2b",
                    'recipient_code' => $recipient,
                    'gateway_reference' => $data['reference'],
                    'transfer_code' => $data['id'],
                    'amount' => $amount,
                    'fee' => $fee,
                    'currency' => $currency,
                    'status' => Transaction::TRANS_STATUS_PENDING,
                    'narration' => 'wallet cash-out transaction'
                ];

                if ($reason) $trans['narration'] = $reason;

                $trans = db()->insert('transactions', $trans);

                if ($trans->isSuccessful()) {

                    $success = in_array(($data['status'] ?? 'failed'), ['SUCCESS', 'SUCCESSFUL', 'NEW']);
                    $fields = ['status' => $success ? Transaction::TRANS_STATUS_SUCCESSFUL : Transaction::TRANS_STATUS_FAILED];
                    if (!$success) $fields['narration'] = $data['complete_message'];
                    $trans->getFirstWithModel()?->update($fields);
                    if (!$success) throw new FlutterwaveException($data['complete_message']);
                }
            }
        }

        return $response;
    }
}