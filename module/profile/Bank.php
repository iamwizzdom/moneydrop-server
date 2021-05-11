<?php


namespace profile;


use model\BankAccount;
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
use que\support\Arr;
use que\support\Str;
use que\utility\money\Item;
use utility\enum\BanksEnum;
use utility\mono\exception\MonoException;
use utility\mono\Mono;
use utility\paystack\exception\PaystackException;
use utility\paystack\Paystack;

class Bank extends Manager implements Api
{
    const MAX_BANK_ACCOUNT = 5;

    use Paystack, Mono;

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.
        $validator = $this->validator($input);

        try {

//            if (empty($this->user('bvn'))) throw $this->baseException(
//                "Sorry, you must add your BVN to perform any bank operation.", "Bank Failed", HTTP::EXPECTATION_FAILED);

            $type = Request::getUriParam('type');

            switch ($type) {
                case "add-account":

                    $bank_accounts = $this->db()->count('bank_accounts', 'id')
                        ->where('user_id', $this->user('id'))
                        ->where('is_active', true)->exec();

                    if ($bank_accounts->getQueryResponse() >= self::MAX_BANK_ACCOUNT) {
                        throw $this->baseException("Sorry, you can't have more than " . self::MAX_BANK_ACCOUNT . " bank accounts.",
                            "Bank Failed", HTTP::EXPECTATION_FAILED);
                    }

                    if ($input->validate('bank_code')->isEmpty()) throw $this->baseException(
                        "Please enter a valid bank code", "Bank Failed", HTTP::UNPROCESSABLE_ENTITY);

                    try {
                        $accountAuth = $this->account_auth($input['bank_code']);
                    } catch (MonoException $e) {
                        throw $this->baseException($e->getMessage(), "Bank Failed", HTTP::EXPECTATION_FAILED);
                    }

                    if (!$accountAuth->isSuccessful() || !($accountID = $accountAuth->getResponseArray()['id'] ?? null)) {
                        throw $this->baseException(
                            "Sorry, we couldn't generate an account ID at this time, let's try that again later.",
                            "Bank Failed", HTTP::EXPECTATION_FAILED
                        );
                    }

                    $add = $this->db()->insert('bank_accounts', [
                        'uuid' => Str::uuidv4(),
                        'account_id' => $accountID,
                        'user_id' => $this->user('id')
                    ]);

                    if (!$add->isSuccessful()) throw $this->baseException(
                        "Sorry we couldn't add that account ID at this time, let's that again later.",
                        "Bank Failed", HTTP::EXPECTATION_FAILED
                    );

                    $add->setModelKey('bankAccountModel');

                    $account = $add->getFirstWithModel();

                    $charge = null;
                    try {
                        $charge = \utility\Wallet::charge(\model\Transaction::ACCOUNT_DETAIL_RETRIEVAL_FEE,
                            1000, $account->uuid, "Bank account detail retrieval charge");
                        if ($charge->isSuccessful()) $monoAccount = $this->account_details($accountID);
                        else throw new MonoException($charge->getQueryError());
                    } catch (MonoException $e) {
                        $account->update(['is_active' => false]);
                        if ($charge && $charge->isSuccessful()) \utility\Wallet::reverseTransaction($charge->getFirstWithModel());
                        throw $this->baseException($e->getMessage(), "Bank Failed", HTTP::EXPECTATION_FAILED);
                    }

                    if (!$monoAccount->isSuccessful()) {
                        $account->update(['is_active' => false]);
                        \utility\Wallet::reverseTransaction($charge->getFirstWithModel());
                        throw $this->baseException(
                            "Sorry we couldn't retrieve your bank account details at this time, let's try that again later.",
                            "Bank Failed", HTTP::EXPECTATION_FAILED
                        );
                    }

                    $response = $monoAccount->getResponseArray();

                    if (($response['meta']['data_status'] ?? null) != "AVAILABLE" || !($accountDetails = ($response['account'] ?? null))) {
                        $account->update(['is_active' => false]);
                        throw $this->baseException(
                            "Sorry, your bank account details are not available at this time, let's try that again later.",
                            "Bank Failed", HTTP::EXPECTATION_FAILED
                        );
                    }

                    $exists = $this->db()->exists('bank_accounts', function ($query) use ($accountDetails) {
                        $query->where('account_number', $accountDetails['accountNumber']);
                        $query->where('user_id', $this->user('id'));
                        $query->where('is_active', true);
                    });

                    if ($exists->isSuccessful()) {
                        $account->update(['is_active' => false]);
                        throw $this->baseException(
                            "Sorry, you already added that bank account.",
                            "Bank Failed", HTTP::EXPECTATION_FAILED
                        );
                    }

                    $update = $account->update([
                        'account_name' => $accountDetails['name'],
                        'account_number' => $accountDetails['accountNumber'],
                        'bank_code' => $accountDetails['institution']['bankCode'],
                        'bank_name' => $accountDetails['institution']['name'],
                        'currency' => $accountDetails['currency']
                    ]);

                    if (!$update?->isSuccessful()) {
                        $account->update(['is_active' => false]);
                        throw $this->baseException(
                            "Sorry we couldn't add that account number at this time, let's that again later.",
                            "Bank Failed", HTTP::EXPECTATION_FAILED
                        );
                    }

                    $charge = null;
                    try {
                        $charge = \utility\Wallet::charge(\model\Transaction::ACCOUNT_INCOME_RETRIEVAL_FEE,
                            2000, $account->uuid, "Bank account income retrieval charge");
                        if ($charge->isSuccessful()) $monoIncome = $this->account_income($accountID);
                        else throw new MonoException($charge->getQueryError());
                    } catch (MonoException $e) {
                        $account->update(['is_active' => false]);
                        if ($charge && $charge->isSuccessful()) \utility\Wallet::reverseTransaction($charge->getFirstWithModel());
                        throw $this->baseException($e->getMessage(), "Bank Failed", HTTP::EXPECTATION_FAILED);
                    }

                    if (!$monoIncome->isSuccessful()) {
                        $account->update(['is_active' => false]);
                        \utility\Wallet::reverseTransaction($charge->getFirstWithModel());
                        throw $this->baseException(
                            "Sorry we couldn't retrieve your bank account income at this time, let's try that again later.",
                            "Bank Failed", HTTP::EXPECTATION_FAILED
                        );
                    }

                    $response = $monoIncome->getResponseArray();

                    if (!isset($response['amount'])) {
                        $account->update(['is_active' => false]);
                        throw $this->baseException(
                            sprintf("Sorry we couldn't retrieve your bank account income at this time, %s",
                            isset($response['message']) ? ("because, " . strtolower($response['message'])) : "let's try that again later."),
                            "Bank Failed", HTTP::EXPECTATION_FAILED
                        );
                    }

                    $update = $account->update([
                        'income' => (float) Item::cents($response['amount'])->percentage(($response['confidence'] * 100))->getCents(),
                        'income_type' => $response['type'] == 'INCOME' ? BankAccount::INCOME_TYPE_REGULAR : BankAccount::INCOME_TYPE_IRREGULAR
                    ]);

                    if (!$update?->isSuccessful()) {
                        $account->update(['is_active' => false]);
                        throw $this->baseException(
                            "Sorry we couldn't add that account number at this time, let's that again later.",
                            "Bank Failed", HTTP::EXPECTATION_FAILED
                        );
                    }

                    return $this->http()->output()->json([
                        'status' => true,
                        'code' => HTTP::OK,
                        'title' => 'Bank Successful',
                        'message' => "Successfully added account number",
                        'response' => [
                            'account' => $account
                        ]
                    ]);

//
//                    $validator->validate('account_number')->isNumeric("Account number should be numeric")
//                        ->hasMinLength(6, "Account number should be at least %s digits")
//                        ->hasMaxLength(17, "Account number should not be more than %s digits")
//                        ->isNotFoundInDB('bank_accounts', 'account_number', 'You already added that account number',
//                            function (Builder $builder) {
//                                $builder->where('user_id', $this->user('id'));
//                                $builder->where('is_active', true);
//                            });
//
//                    $validator->validate('bank_code')->isNotEmpty("Please enter a valid bank code")
//                        ->isEqualToAny(BanksEnum::getBankCodes(), "Invalid bank code");
//
//                    if ($validator->hasError()) throw $this->baseException(
//                        "The inputted data is invalid", "Bank Failed", HTTP::UNPROCESSABLE_ENTITY);
//
//                    try {
//                        $resolve = $this->resolve_account($input['account_number'], $input['bank_code']);
//                    } catch (PaystackException $e) {
//                        throw $this->baseException($e->getMessage(), "Bank Failed", HTTP::EXPECTATION_FAILED);
//                    }
//
//                    if (!$resolve->isSuccessful()) throw $this->baseException(
//                        "Sorry we couldn't resolve that account number at this time, let's try that again later.",
//                        "Bank Failed", HTTP::EXPECTATION_FAILED
//                    );
//
//                    $response = $resolve->getResponseArray();
//
//                    if (!($response['status'] ?? false)) {
//                        throw $this->baseException(
//                            $response['message'] ?? "Sorry we couldn't resolve that account number, seems it's invalid.",
//                            "Bank Failed", HTTP::EXPECTATION_FAILED
//                        );
//                    }
//
//                    try {
//                        $charge = \utility\Wallet::charge(\model\Transaction::BVN_MATCH_FEE, 500, "Match BVN charge");
//                        if ($charge->isSuccessful()) $match = $this->match_bvn($this->user('bvn'), $input['account_number'], $input['bank_code']);
//                        else throw new PaystackException($charge->getQueryError());
//                    } catch (PaystackException $e) {
//                        throw $this->baseException($e->getMessage(), "Bank Failed", HTTP::EXPECTATION_FAILED);
//                    }
//
//                    if (!$match->isSuccessful()) throw $this->baseException(
//                        "Sorry we couldn't verify that account number with your BVN at this time, let's try that again later.",
//                        "Bank Failed", HTTP::EXPECTATION_FAILED
//                    );
//
//                    $response = $match->getResponseArray();
//
//                    if (!($response['status'] ?? false)) {
//                        throw $this->baseException(
//                            $response['message'] ?? "Sorry that account number seems not to be associated with your BVN.",
//                            "Bank Failed", HTTP::EXPECTATION_FAILED
//                        );
//                    }
//
//                    $data = $response['data'] ?? [];
//
//                    if (($data['is_blacklisted'] ?? false)) {
//                        throw $this->baseException(
//                            "Sorry that account number is blacklisted",
//                            "Bank Failed", HTTP::EXPECTATION_FAILED
//                        );
//                    }
//
//                    if (LIVE && !($data['account_number'] ?? false)) {
//                        throw $this->baseException(
//                            "Sorry that account number is not linked with your BVN",
//                            "Bank Failed", HTTP::EXPECTATION_FAILED
//                        );
//                    }
//
//                    try {
//                        $recipient = $this->create_transfer_recipient(
//                            "{$this->user('firstname')} {$this->user('lastname')}",
//                            $input['account_number'], $input['bank_code']
//                        );
//                    } catch (PaystackException $e) {
//                        throw $this->baseException($e->getMessage(), "Bank Failed", HTTP::EXPECTATION_FAILED);
//                    }
//
//                    if (!$recipient->isSuccessful()) throw $this->baseException(
//                        "Sorry we couldn't add that account number at this time, let's that again later.",
//                        "Bank Failed", HTTP::EXPECTATION_FAILED
//                    );
//
//                    $response = $recipient->getResponseArray();
//
//                    if (!($response['status'] ?? false)) {
//                        throw $this->baseException(
//                            $response['message'] ?? "Sorry we couldn't add that account number at this time, let's that again later.",
//                            "Bank Failed", HTTP::EXPECTATION_FAILED
//                        );
//                    }
//
//                    $data = $response['data'] ?? [];
//
//                    $add = $this->db()->insert('bank_accounts', [
//                        'uuid' => Str::uuidv4(),
//                        'account_name' => $data['name'] ?? "{$this->user('firstname')} {$this->user('lastname')}",
//                        'account_number' => $input['account_number'],
//                        'bank_code' => $input['bank_code'],
//                        'bank_name' => $data['details']['bank_name'] ?? null,
//                        'currency' => $data['currency'] ?? null,
//                        'recipient_code' => $data['recipient_code'] ?? null,
//                        'user_id' => $this->user('id')
//                    ]);
//
//                    if (!$add->isSuccessful()) throw $this->baseException(
//                        "Sorry we couldn't add that account number at this time, let's that again later.",
//                        "Bank Failed", HTTP::EXPECTATION_FAILED
//                    );
//
//                    $account = $add->getFirstArray();
//
//                    $account = Arr::extract_by_keys($account, ['uuid', 'account_name', 'account_number', 'bank_name', 'recipient_code']);
//
//                    $account['account_number'] = hide_number($account['account_number'], 0, strlen($account['account_number']) - 4);
//
//                    return $this->http()->output()->json([
//                        'status' => true,
//                        'code' => HTTP::OK,
//                        'title' => 'Bank Successful',
//                        'message' => "Successfully added account number",
//                        'response' => [
//                            'account' => $account
//                        ]
//                    ]);
                case 'retrieve':

                    $id = Request::getUriParam('id');

                    if ($id == 'all') {

                        $account = $this->db()->findAll('bank_accounts', $this->user('id'), 'user_id',
                            function (Builder $builder) {
                                $builder->where('is_active', true);
                                $builder->orderBy('desc', 'id');
                            });

                        $account->setModelKey('bankAccountModel');

                        return $this->http()->output()->json([
                            'status' => true,
                            'code' => HTTP::OK,
                            'title' => 'Bank Successful',
                            'message' => $account->getResponseSize() > 0 ? "Bank accounts retrieved successfully." : "No Bank account found.",
                            'response' => [
                                'accounts' => $account->getAllWithModel() ?: [],
                                'banks' => BanksEnum::getBanks()
                            ]
                        ]);
                    }

                    $monoAccount = $this->db()->find('bank_accounts', $this->user('id'), 'user_id',
                        function (Builder $builder) use ($id) {
                            $builder->where('uuid', $id);
                            $builder->where('is_active', true);
                        });

                    if (!$monoAccount->isSuccessful()) return $this->http()->output()->json([
                        'status' => true,
                        'code' => HTTP::NOT_FOUND,
                        'title' => 'Bank Account Not Found',
                        'message' => "That account either does not exist or has been deactivated.",
                        'response' => []
                    ], HTTP::NOT_FOUND);

                    $monoAccount->setModelKey('bankAccountModel');

                    return $this->http()->output()->json([
                        'status' => true,
                        'code' => HTTP::OK,
                        'title' => 'Bank Successful',
                        'message' => "Bank account retrieved successfully.",
                        'response' => $monoAccount->getFirstArray()
                    ]);
                case 'remove':

                    $id = Request::getUriParam('id');

                    $check = $this->db()->exists('loans', function (Builder $builder) {
                        $builder->where('user_id', $this->user('id'));
                        $builder->where('loan_type', \model\Loan::LOAN_TYPE_REQUEST);
                        $builder->where('status', \model\Loan::STATUS_COMPLETED, '!=');
                        $builder->where('status', \model\Loan::STATUS_REVOKED, '!=');
                        $builder->where('is_active', true);
                    });

                    if ($check->isSuccessful()) throw $this->baseException(
                        "You cannot remove a bank account when you have an uncompleted loan request.", "Remove Failed", HTTP::FORBIDDEN);

                    $check = $this->db()->select('*')->table('loan_applications as la')
                        ->join('loans as l', 'la.loan_id', 'l.uuid')
                        ->where('la.user_id', $this->user('id'))
                        ->where('la.status', \model\LoanApplication::STATUS_REPAID, '!=')
                        ->where('la.status', \model\LoanApplication::STATUS_REJECTED, '!=')
                        ->where('l.loan_type', \model\Loan::LOAN_TYPE_OFFER)
                        ->where('la.is_active', true)
                        ->where('l.is_active', true)
                        ->limit(1)
                        ->exec();

                    if ($check->isSuccessful()) throw $this->baseException(
                        "You cannot remove a bank account when you have an un-repaid loan offer.", "Remove Failed", HTTP::FORBIDDEN);

                    $bankAccount = $this->db()->find('bank_accounts', $id, 'uuid', function (Builder $builder) {
                        $builder->where('user_id', $this->user('id'));
                    });

                    $remove = false;

                    if ($bankAccount->isSuccessful()) {
                        $remove = !!$bankAccount->getFirstWithModel()?->update(['is_active' => false])?->isSuccessful();
                    }

                    return $this->http()->output()->json([
                        'status' => $remove,
                        'code' => HTTP::OK,
                        'title' => $remove ? 'Remove Successful' : 'Remove Failed',
                        'message' => $remove ? "Bank account removed successfully." : "Bank account removal failed",
                        'response' => []
                    ], HTTP::OK);

                default:
                    throw $this->baseException(
                        "Sorry, we're not sure what you're trying to do there.", "Bank Failed", HTTP::BAD_REQUEST);
            }

        } catch (BaseException $e) {

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
