<?php


namespace loan;


use model\BankAccount;
use que\common\exception\BaseException;
use que\common\validator\interfaces\Condition;
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
use que\template\Pagination;
use que\utility\money\Item;
use utility\Wallet;

class Loan extends \que\common\manager\Manager implements \que\common\structure\Api
{
    use Wallet;

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.
        $validator = $this->validator($input);

        try {

            switch ($type = Request::getUriParam('type')) {
                case "request":
                case "offer":

                    $validator->validate('amount')->isNumeric('Please enter a valid amount')
                        ->isNumberGreaterThanOrEqual((\model\Loan::MIN_AMOUNT / 100), "Sorry, you must {$type} at least %s NGN")
                        ->if(function (Condition $condition) use ($type) {
                            return $type == 'request' || ($this->getAvailableBalance() >= (float)$condition->getValue());
                        }, "Sorry, you don't have up to {$input['amount']} NGN in your wallet");

                    $validator->validate('tenure')->isNumeric("Please a loan tenure")
                        ->isEqualToAny(get_class_consts(\model\Loan::class, 'TENURE_'), 'Please select a valid tenure');

                    $validator->validate('interest')->isNumeric('Please enter a valid interest rate');

                    $validator->validate('interest_type')->isNumeric('Please select an interest type')
                        ->isEqualToAny([\model\Loan::INTEREST_TYPE_STATIC, \model\Loan::INTEREST_TYPE_NON_STATIC], "Please select a valid interest type");

                    $validator->validate('purpose', true)->isNumeric('Please select a loan purpose')
                        ->isEqualToAny(get_class_consts(\model\Loan::class, 'PURPOSE_'), "Please select a valid loan purpose");

                    $validator->validate('note', true)->isNotEmpty("Your note shouldn't be empty")
                        ->hasMinWord(10, "Please write a meaningful note of at least %s words");

//                    $validator->validate('loan_type')->isNumeric('Loan type must be numeric')
//                        ->isEqualToAny([\model\Loan::LOAN_TYPE_OFFER, \model\Loan::LOAN_TYPE_REQUEST], "Please select a valid loan type");

//                    $validator->validate('is_fund_raiser', true)->isBool(
//                        "Please enter a valid value to tell us if this is a fund raiser loan or not.");

                    if ($validator->hasError()) throw $this->baseException(
                        "The inputted data is invalid", "Loan Failed", HTTP::UNPROCESSABLE_ENTITY);

                    $amount = Item::factor(\input('amount'))->getCents();

                    if ($type == 'request') {

                        $check = $this->db()->exists('bank_accounts', function (Builder $builder) {
                            $builder->where('user_id', user('id'));
                            $builder->where('income_type', BankAccount::INCOME_TYPE_REGULAR);
                            $builder->where('is_active', true);
                        });

                        if (!$check->isSuccessful()) throw $this->baseException(
                            "Sorry, you can't request for a loan when you have not added a regular income bank account.",
                            "Loan Failed", HTTP::EXPECTATION_FAILED);

                        if ($amount > $this->user('max_loan_amount')) {
                            $eligible = Item::cents($this->user('max_loan_amount'))->getFactor(true, 0);
                            throw $this->baseException(
                            "Sorry, you are currently only eligible to request a loan of {$eligible} NGN.",
                            "Loan Failed", HTTP::EXPECTATION_FAILED);
                        }

                    }

                    $check = $this->db()->exists('loans', function (Builder $builder) use ($type, $amount) {
                        $builder->where('amount', $amount);
                        $builder->where('tenure', \input('tenure'));
                        $builder->where('interest', \input('interest'));
                        $builder->where('interest_type', \input('interest_type'));
                        $builder->where('loan_type', $type == "offer" ? \model\Loan::LOAN_TYPE_OFFER : \model\Loan::LOAN_TYPE_REQUEST);
                        $builder->where('status', \model\Loan::STATUS_PENDING);
                        $builder->where('is_active', true);
                    });

                    if ($check->isSuccessful()) throw $this->baseException(
                        "You already {$type}ed that exact loan and it's still pending.", "Loan Failed", HTTP::CONFLICT);

                    $check = $this->db()->exists('loans', function (Builder $builder) use ($type, $amount) {
                        $builder->where('user_id', $this->user('id'));
                        $builder->where('loan_type', \model\Loan::LOAN_TYPE_REQUEST);
                        $builder->where('status', \model\Loan::STATUS_COMPLETED, '!=');
                        $builder->where('status', \model\Loan::STATUS_REVOKED, '!=');
                        $builder->where('is_active', true);
                    });

                    if ($check->isSuccessful()) throw $this->baseException(
                        "You cannot {$type} a loan when you have an uncompleted loan request.", "Loan Failed", HTTP::FORBIDDEN);

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
                        "You cannot {$type} a loan when you have an un-repaid loan offer.", "Loan Failed", HTTP::FORBIDDEN);

                    $data = $validator->getValidated();
                    $data['amount'] = $amount;

                    $loan = $this->db()->insert('loans', array_merge([
                        'uuid' => Str::uuidv4(),
                        'user_id' => user('id'),
                        'status' => \model\Loan::STATUS_PENDING,
                        'loan_type' => $type == "offer" ? \model\Loan::LOAN_TYPE_OFFER : \model\Loan::LOAN_TYPE_REQUEST
                    ], $data));

                    if (!$loan->isSuccessful()) throw $this->baseException(
                        $loan->getQueryError() ?: "Failed to {$type} loan at this time, please try again later.",
                        "Loan Failed", HTTP::EXPECTATION_FAILED);

                    $loan->setModelKey("loanModel");
                    $this->refreshWallet();

                    return $this->http()->output()->json([
                        'status' => true,
                        'code' => HTTP::CREATED,
                        'title' => 'Loan Successful',
                        'message' => "Loan {$type}ed successfully.",
                        'response' => [
                            'loan' => $loan->getFirstWithModel(),
                            'balance' => $this->getBalance(),
                            'available_balance' => $this->getAvailableBalance()
                        ]
                    ], HTTP::CREATED);
                case "offers":
                case "requests":

                    $loans = $this->db()->select("*")->table('loans')
                        ->where('amount', $this->user('max_loan_amount'), '<=')
                        ->where('status', \model\Loan::STATUS_AWAITING)
                        ->where('is_active', true)
                        ->where('loan_type', $type == "offers" ? \model\Loan::LOAN_TYPE_OFFER : \model\Loan::LOAN_TYPE_REQUEST)
                        ->orderBy('desc', 'id')->paginate(headers('X-PerPage', PAGINATION_PER_PAGE));

                    $loans->setModelKey("loanModel");

                    $pagination = Pagination::getInstance();

                    return [
                        'pagination' => [
                            'page' => $pagination->getPaginator("default")->getCurrentPage(),
                            'totalRecords' => $pagination->getTotalRecords("default"),
                            'totalPages' => $pagination->getTotalPages("default"),
                            'perPage' => $pagination->getPerPage('default'),
                            'nextPage' => $pagination->getNextPage("default", true),
                            'previousPage' => $pagination->getPreviousPage("default", true)
                        ],
                        'loans' => $loans->getAllWithModel() ?: []
                    ];

                case "constants":

                    $tenures = array_flip(get_class_consts(\model\Loan::class, 'TENURE_'));
                    $purposes = array_flip(get_class_consts(\model\Loan::class, 'PURPOSE_'));
                    $interest_types = array_flip(get_class_consts(\model\Loan::class, 'INTEREST_TYPE_'));

                    Arr::callback($tenures, function ($tenure) {
                        return ucfirst(strtolower(str_replace("_", " ", str_start_from($tenure, 'TENURE_'))));
                    });

                    Arr::callback($purposes, function ($purpose) {
                        return ucfirst(strtolower(str_replace("_", " ", str_start_from($purpose, 'PURPOSE_'))));
                    });

                    Arr::callback($interest_types, function ($interest_type) {
                        return ucfirst(strtolower(str_replace("_", " ", str_start_from($interest_type, 'INTEREST_TYPE_')))) . " interest";
                    });

                    return [
                        'tenure' => $tenures,
                        'purpose' => $purposes,
                        'interest_type' => $interest_types
                    ];
                default:
                    throw $this->baseException(
                        "Sorry, we're not sure what you're trying to do there.", "Loan Failed", HTTP::BAD_REQUEST);
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

    public function revoke(Input $input)
    {

        try {

            $input['loan_id'] = Request::getUriParam('id');

            $validator = $input->validate('loan_id');

            if (!$validator->isUUID()) throw $this->baseException(
                "Please enter a valid loan ID", "Revoke Failed", HTTP::EXPECTATION_FAILED);

            if (!$validator->isFoundInDB('loans', 'uuid')) throw $this->baseException(
                "Sorry, that loan ID does not exist", "Revoke Failed", HTTP::EXPECTATION_FAILED);

            $loan = $this->db()->find('loans', $input['loan_id'], 'uuid');
            $loan->setModelKey('loanModel');
            $loan = $loan->getFirstWithModel();

            if ($loan?->getInt('user_id') != $this->user('id'))
                throw $this->baseException("Sorry, you can't revoke a loan that's not yours.", "Revoke Failed", HTTP::UNAUTHORIZED);

            if (!$loan?->getBool('is_active'))
                throw $this->baseException("Sorry, you can't revoke an inactive loan.", "Revoke Failed", HTTP::FORBIDDEN);

            if ($loan?->getInt('status') == \model\Loan::STATUS_REVOKED)
                throw $this->baseException("This loan has already been revoked.", "Revoke Failed", HTTP::CONFLICT);

            if ($loan?->getInt('status') != \model\Loan::STATUS_PENDING && $loan?->getInt('status') != \model\Loan::STATUS_AWAITING)
                throw $this->baseException("Sorry, you can only revoke a pending or awaiting loan.", "Revoke Failed", HTTP::NOT_ACCEPTABLE);

            $revoke = $loan?->update(['status' => \model\Loan::STATUS_REVOKED]);

            if (!$revoke?->isSuccessful()) throw $this->baseException(
                $revoke->getQueryError() ?: "Failed to revoke this loan at this time. Let's that again later", "Revoke Failed", HTTP::EXPECTATION_FAILED);

            $this->refreshWallet();

            return $this->http()->output()->json([
                'status' => true,
                'code' => HTTP::OK,
                'title' => 'Revoke Successful',
                'message' => "Loan revoked successfully.",
                'response' => [
                    'loan' => $loan,
                    'balance' => $this->getBalance(),
                    'available_balance' => $this->getAvailableBalance()
                ]
            ]);

        } catch (BaseException $e) {

            return $this->http()->output()->json([
                'status' => $e->getStatus(),
                'code' => $e->getCode(),
                'title' => $e->getTitle(),
                'message' => $e->getMessage(),
                'errors' => (object)[]
            ], $e->getCode());
        }
    }
}
