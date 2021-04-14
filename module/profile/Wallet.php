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
use que\database\interfaces\model\Model;
use que\http\HTTP;
use que\http\input\Input;
use que\http\output\response\Html;
use que\http\output\response\Json;
use que\http\output\response\Jsonp;
use que\http\output\response\Plain;
use que\http\request\Request;
use que\support\Str;
use que\utility\money\Item;
use utility\flutterwave\Flutterwave;
use utility\flutterwave\exception\FlutterwaveException;

class Wallet extends Manager implements Api
{
    use \utility\Wallet, Flutterwave;

    const MIN_TOP_UP_AMOUNT = 1000;
    const MIN_CASH_OUT_AMOUNT = 1000;

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.
        $validator = $this->validator($input);

        $transaction = null;

        try {

            switch (Request::getUriParam('type')) {
                case "top-up":

                    $input['card'] = Request::getUriParam('id');

                    $validator->validate('amount')->isFloatingNumber('Please enter a valid amount')
                        ->isFloatingNumberGreaterThanOrEqual(self::MIN_TOP_UP_AMOUNT,
                            "Sorry, your top-up amount must be at least %s");

                    $validator->validate('card')->isUUID("That card is invalid")
                        ->isFoundInDB('cards', 'uuid', "That card doesn't exist",
                            function (Builder $builder) {
                                $builder->where('user_id', $this->user('id'));
                                $builder->where('is_active', true);
                            });

                    if ($validator->hasError()) throw $this->baseException(
                        "The inputted data is invalid", "Top-up Failed", HTTP::UNPROCESSABLE_ENTITY);

                    try {
                        if ($this->isFrozenWallet()) throw $this->baseException(
                            "Sorry, your wallet is frozen.", "Top-up Failed", HTTP::EXPECTATION_FAILED);
                    } catch (\Exception $e) {
                        throw $this->baseException($e->getMessage(), "Top-up Failed", HTTP::EXPECTATION_FAILED);
                    }

                    try {
                        if (!$this->isActiveWallet()) throw $this->baseException(
                            "Sorry, your wallet is deactivated.", "Top-up Failed", HTTP::EXPECTATION_FAILED);
                    } catch (\Exception $e) {
                        throw $this->baseException($e->getMessage(), "Top-up Failed", HTTP::EXPECTATION_FAILED);
                    }

                    try {
                        $charge = $this->charge_card($input['card'], $input['amount']);
                    } catch (FlutterwaveException $e) {
                        throw $this->baseException($e->getMessage(), "Top-up Failed", HTTP::EXPECTATION_FAILED);
                    }

                    if (!$charge->isSuccessful()) throw $this->baseException(
                        "Sorry, an unexpected error occurred connecting to payment gateway",
                            "Top-up Failed", HTTP::BAD_GATEWAY);

                    $response = $charge->getResponseArray();

                    $data = $response['data'] ?? [];

                    if (!in_array(($data['status'] ?? 'failed'), ['success', 'successful'])) throw $this->baseException(
                        $data['message'] ?? "Failed to charge the selected card, please try another card.",
                        "Top-up Failed", HTTP::EXPECTATION_FAILED);

                    $trans = $this->db()->find('transactions', $data[GATEWAY == PAYSTACK ? 'reference' : 'txRef'], 'gateway_reference');

                    $trans->setModelKey("transactionModel");

                    if ($trans->isSuccessful()) $transaction = $trans->getFirstWithModel();

                    $this->refreshWallet();

                    return $this->http()->output()->json([
                        'status' => true,
                        'code' => HTTP::OK,
                        'title' => 'Top-up Successful',
                        'message' => "Your wallet has been credited successfully",
                        'response' => [
                            'balance' => $this->getBalance(),
                            'available_balance' => $this->getAvailableBalance(),
                            'transaction' => $transaction ?: []
                        ]
                    ]);

                case 'cash-out':

                    $input['recipient'] = Request::getUriParam('id');

                    $validator->validate('amount')->isFloatingNumber('Please enter a valid amount')
                        ->isFloatingNumberGreaterThanOrEqual(self::MIN_CASH_OUT_AMOUNT,
                            "Sorry, your cash-out amount must be at least %s");

                    $validator->validate('recipient')->isNotEmpty('Please enter a valid bank account')
                        ->isFoundInDB('bank_accounts', 'uuid',
                            'That bank account either does not exist or has been deactivated', function (Builder $builder) {
                                $builder->where('user_id', $this->user('id'));
                                $builder->where('is_active', true);
                            });

                    if ($validator->hasError()) throw $this->baseException(
                        "The inputted data is invalid", "Cash-out Failed", HTTP::UNPROCESSABLE_ENTITY);

                    try {
                        if ($this->isFrozenWallet()) throw $this->baseException(
                            "Sorry, your wallet is frozen.", "Cash-out Failed", HTTP::EXPECTATION_FAILED);
                    } catch (\Exception $e) {
                        throw $this->baseException($e->getMessage(), "Cash-out Failed", HTTP::EXPECTATION_FAILED);
                    }

                    try {
                        if (!$this->isActiveWallet()) throw $this->baseException(
                            "Sorry, your wallet is deactivated.", "Cash-out Failed", HTTP::EXPECTATION_FAILED);
                    } catch (\Exception $e) {
                        throw $this->baseException($e->getMessage(), "Cash-out Failed", HTTP::EXPECTATION_FAILED);
                    }

                    $reference = null;

                    $trans = $this->db()->find('transactions', $this->user('id'), 'user_id',
                        function (Builder $builder) use ($input) {
                            $builder->where('amount', Item::factor($input['amount'])->getCents());
                            $builder->where('recipient_code', $input['recipient']);
                            $builder->where('type', \model\Transaction::TRANS_TYPE_WITHDRAWAL);
                            $builder->where('status', \model\Transaction::TRANS_STATUS_PROCESSING);
                            $builder->orderBy('desc', 'id');
                        });

                    $trans->setModelKey("transactionModel");

                    if ($trans->isSuccessful()) {
                        $transaction = $trans->getFirstWithModel();
                        $reference = $transaction->getValue('gateway_reference');
                    }

                    $account = $this->db()->find('bank_accounts', $input['recipient'], 'uuid',
                        function (Builder $builder) {
                            $builder->where('user_id', $this->user('id'));
                            $builder->where('is_active', true);
                        });

                    $account = $account->getFirstWithModel();

                    $bankName = $account->getValue('bank_name');
                    $bankCode = $account->getValue('bank_code');
                    $accountNumber = $account->getValue('account_number');

                    try {
                        $transfer = $this->init_transfer($input['amount'], $input['recipient'], $bankCode, $accountNumber, $reference ?: Str::uuidv4());
                    } catch (FlutterwaveException $e) {
                        throw $this->baseException($e->getMessage(), "Cash-out Failed", HTTP::EXPECTATION_FAILED);
                    }

                    if (!$transfer->isSuccessful()) throw $this->baseException(
                        "Sorry, an unexpected error occurred connecting to payment gateway",
                        "Cash-out Failed", HTTP::BAD_GATEWAY);

                    $response = $transfer->getResponseArray();

//                    if (!($response['status'] ?? false)) {
//                        throw $this->baseException(
//                            $response['message'] ?? "Sorry we couldn't complete that transfer at this time, let's try it again later.",
//                            "Cash-out Failed", HTTP::EXPECTATION_FAILED
//                        );
//                    }

                    $data = $response['data'] ?? [];

                    if (!in_array(($data['status'] ?? 'failed'), ['SUCCESS', 'SUCCESSFUL', 'NEW'])) throw $this->baseException(
                        $response['message'] ?? "Sorry we couldn't complete that transfer at this time, let's try it again later.",
                        "Cash-out Failed", HTTP::EXPECTATION_FAILED);

                    if (!$transaction) {
                        $trans = $this->db()->find('transactions', $data['reference'], 'gateway_reference');
                        $trans->setModelKey("transactionModel");
                        if ($trans->isSuccessful()) $transaction = $trans->getFirstWithModel();
                    }

                    $this->refreshWallet();

                    $input['amount'] = Item::cents($transaction->amount)->getFactor(true);

                    return $this->http()->output()->json([
                        'status' => true,
                        'code' => HTTP::OK,
                        'title' => 'Cash-out Successful',
                        'message' => "The sum of {$input['amount']} NGN has been transferred to your {$bankName} account {$accountNumber}. " .
                            "Please note that in some cases deposit to your account might take up to 24 hours.",
                        'response' => [
                            'balance' => $this->getBalance(),
                            'available_balance' => $this->getAvailableBalance(),
                            'transaction' => $transaction ?: []
                        ]
                    ]);
                default:
                    throw $this->baseException(
                        "Sorry, we're not sure what you're trying to do there.", "Wallet Failed", HTTP::BAD_REQUEST);
            }

        } catch (BaseException $e) {

            $transaction?->update(['narration' => $e->getMessage()]);

            return $this->http()->output()->json([
                'status' => $e->getStatus(),
                'code' => $e->getCode(),
                'title' => $e->getTitle(),
                'message' => $e->getMessage(),
                'errors' => (object)$validator->getErrors()
            ], $e->getCode());
        }
    }
}
