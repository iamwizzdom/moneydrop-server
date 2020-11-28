<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 11/26/2020
 * Time: 10:07 AM
 */

namespace profile;


use que\common\exception\BaseException;
use que\common\manager\Manager;
use que\common\structure\Api;
use que\database\interfaces\Builder;
use que\http\HTTP;
use que\http\input\Input;
use que\http\output\response\Html;
use que\http\output\response\Json;
use que\http\output\response\Jsonp;
use que\http\output\response\Plain;
use que\http\request\Request;
use utility\paystack\exception\PaystackException;
use utility\paystack\Paystack;

class Wallet extends Manager implements Api
{
    use \utility\Wallet, Paystack;

    const TOP_UP_AMOUNT = 1000;

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.
        $validator = $this->validator($input);

        try {

            switch (Request::getUriParam('type')) {
                case "top-up":

                    $validator->validate('amount')->isNumber('Please enter a valid amount')
                        ->isNumberGreaterThanOrEqual(self::TOP_UP_AMOUNT,
                            "Sorry, your top-up amount must be at least %s");

                    $validator->validate('card')->isUUID("That card is invalid")
                        ->isFoundInDB('cards', 'uuid', "That card doesn't exist",
                            function (Builder $builder) {
                                $builder->where('user_id', $this->user('id'));
                                $builder->where('is_active', true);
                            });

                    if ($validator->hasError()) throw $this->baseException(
                        "The inputted data is invalid", "Wallet Failed", HTTP::UNPROCESSABLE_ENTITY);

                    try {
                        if ($this->isFrozenWallet()) throw $this->baseException(
                            "Sorry, your wallet is frozen.", "Wallet Failed", HTTP::EXPECTATION_FAILED);
                    } catch (\Exception $e) {
                        throw $this->baseException($e->getMessage(), "Wallet Failed", HTTP::EXPECTATION_FAILED);
                    }

                    try {
                        if (!$this->isActiveWallet()) throw $this->baseException(
                            "Sorry, your wallet is deactivated.", "Wallet Failed", HTTP::EXPECTATION_FAILED);
                    } catch (\Exception $e) {
                        throw $this->baseException($e->getMessage(), "Wallet Failed", HTTP::EXPECTATION_FAILED);
                    }

                    try {
                        $charge = $this->charge_card($input['card'], $this->user('email'), $input['amount']);
                    } catch (PaystackException $e) {
                        throw $this->baseException($e->getMessage(), "Wallet Failed", HTTP::EXPECTATION_FAILED);
                    }

                    if (!$charge->isSuccessful()) throw $this->baseException(
                        "Sorry, an unexpected error occurred connecting to payment gateway",
                            "Wallet Failed", HTTP::BAD_GATEWAY);

                    $data = $charge->getResponseArray()['data'] ?? [];

                    if (($data['status'] ?? 'failed') != 'success') throw $this->baseException(
                        $data['message'] ?? "Failed to charge the selected card, please try another card.",
                        "Wallet Failed", HTTP::EXPECTATION_FAILED);

                    try {
                        $verify = $this->verify_transaction($data['reference']);
                    } catch (PaystackException $e) {
                        throw $this->baseException($e->getMessage(), "Wallet Failed", HTTP::EXPECTATION_FAILED);
                    }

                    $data = $verify->getResponseArray()['data'] ?? [];

                    if (($data['status'] ?? 'failed') != 'success') throw $this->baseException(
                        $data['message'] ?? "The charge on that card could not be verified at this time. This is usually rectified within 24 hours.",
                        "Wallet Failed", HTTP::EXPECTATION_FAILED);

                    try {
                        $topUp = $this->creditWallet($input['amount']);
                    } catch (\Exception $e) {
                        throw $this->baseException($e->getMessage(), "Wallet Failed", HTTP::EXPECTATION_FAILED);
                    }

                    if (!$topUp) $this->baseException(
                        "Failed to top up wallet at this time. This is usually resolved within 24 hours",
                        "Wallet Failed", HTTP::EXPECTATION_FAILED);

                    return $this->http()->output()->json([
                        'status' => true,
                        'code' => HTTP::OK,
                        'title' => 'Wallet Successful',
                        'message' => "Your wallet has been credited successfully",
                        'response' => [
                            'balance' => $topUp
                        ]
                    ], HTTP::OK);

                default:
                    throw $this->baseException(
                        "Sorry, we're not sure what you're trying to do there.", "Wallet Failed", HTTP::BAD_REQUEST);
            }

        } catch (BaseException $e) {

            return $this->http()->output()->json([
                'status' => $e->getStatus(),
                'code' => $e->getCode(),
                'title' => $e->getTitle(),
                'message' => $e->getMessage(),
                'error' => (object)$validator->getErrors()
            ], $e->getCode());
        }
    }
}