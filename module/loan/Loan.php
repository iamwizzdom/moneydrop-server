<?php


namespace loan;


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
use utility\Wallet;

class Loan extends \que\common\manager\Manager implements \que\common\structure\Api
{
    use Wallet;

    const MIN_LOAN_OFFER = 5000;
    const MIN_LOAN_REQUEST = 5000;

    const LOAN_TYPE_OFFER = -7;
    const LOAN_TYPE_REQUEST = -8;

    const INTEREST_TYPE_STATIC = 1;
    const INTEREST_TYPE_NON_STATIC = 2;

    /**
     * Loan tenor constants
     */
    const LOAN_TENOR_ONE_WEEK = -1;
    const LOAN_TENOR_TWO_WEEKS = -2;
    const LOAN_TENOR_THREE_WEEKS = -3;
    const LOAN_TENOR_ONE_MONTH = 1;
    const LOAN_TENOR_TWO_MONTHS = 2;
    const LOAN_TENOR_THREE_MONTHS = 3;
    const LOAN_TENOR_FOUR_MONTHS = 4;
    const LOAN_TENOR_FIVE_MONTHS = 5;
    const LOAN_TENOR_SIX_MONTHS = 6;
    const LOAN_TENOR_SEVEN_MONTHS = 7;
    const LOAN_TENOR_EIGHT_MONTHS = 8;
    const LOAN_TENOR_NINE_MONTHS = 9;
    const LOAN_TENOR_TEN_MONTHS = 10;
    const LOAN_TENOR_ELEVEN_MONTHS = 11;
    const LOAN_TENOR_ONE_YEAR = 12;
    const LOAN_TENOR_ONE_YEAR_AND_SIX_MONTHS = 18;
    const LOAN_TENOR_TWO_YEARS = 24;
    const LOAN_TENOR_TWO_YEARS_AND_SIX_MONTHS = 30;
    const LOAN_TENOR_THREE_YEARS = 36;
    const LOAN_TENOR_THREE_YEARS_AND_SIX_MONTHS = 42;
    const LOAN_TENOR_FOUR_YEARS = 48;
    const LOAN_TENOR_FOUR_YEARS_AND_SIX_MONTHS = 54;
    const LOAN_TENOR_FIVE_YEARS = 60;
    const LOAN_TENOR_FIVE_YEARS_AND_SIX_MONTHS = 66;
    const LOAN_TENOR_SIX_YEARS = 72;
    const LOAN_TENOR_SIX_YEARS_AND_SIX_MONTHS = 78;
    const LOAN_TENOR_SEVEN_YEARS = 84;
    const LOAN_TENOR_SEVEN_YEARS_AND_SIX_MONTHS = 90;
    const LOAN_TENOR_EIGHT_YEARS = 96;
    const LOAN_TENOR_EIGHT_YEARS_AND_SIX_MONTHS = 102;
    const LOAN_TENOR_NINE_YEARS = 108;
    const LOAN_TENOR_NINE_YEARS_AND_SIX_MONTHS = 114;
    const LOAN_TENOR_TEN_YEARS = 120;

    /**
     * Loan purpose
     */
    const LOAN_PURPOSE_HOUSEHOLD_PURCHASE = 1;
    const LOAN_PURPOSE_PAY_RENT = 2;
    const LOAN_PURPOSE_GADGET_PURCHASE = 3;
    const LOAN_PURPOSE_CAR_PURCHASE = 4;
    const LOAN_PURPOSE_HOUSE_PURCHASE = 5;
    const LOAN_PURPOSE_PAY_SCHOOL_FEES = 6;
    const LOAN_PURPOSE_START_BUSINESS = 7;
    const LOAN_PURPOSE_HEALTHCARE = 8;
    const LOAN_PURPOSE_TRAVEL = 9;
    const LOAN_PURPOSE_OTHERS = -1;

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

                    $validator->validate('amount')->continueValidationIf(function (Condition $condition) use ($type) {
                        return $type == 'request' || $this->getAvailableBalance() > (float) $condition->getValue();
                    }, "Sorry, you don't have up to {$input['amount']} NGN in your wallet")->isNumeric('Please enter a valid amount')
                        ->isNumberGreaterThanOrEqual($type == "offer" ? self::MIN_LOAN_OFFER : self::MIN_LOAN_REQUEST,
                            "Sorry, you must {$type} at least %s NGN");

                    $validator->validate('tenor')->isNumeric("Please a loan tenor")
                        ->isEqualToAny(get_class_consts($this, 'LOAN_TENOR_'), 'Please select a valid tenor');

                    $validator->validate('interest')->isNumeric('Please enter a valid interest rate');

                    $validator->validate('interest_type')->isNumeric('Please select an interest type')
                        ->isEqualToAny([self::INTEREST_TYPE_STATIC, self::INTEREST_TYPE_NON_STATIC], "Please select a valid interest type");

                    $validator->validate('purpose', true)->isNumeric('Please select a loan purpose')
                        ->isEqualToAny(get_class_consts($this, 'LOAN_PURPOSE_'), "Please select a valid loan purpose");

                    $validator->validate('note', true)->isNotEmpty("Your note shouldn't be empty")
                        ->hasMinWord(10, "Please write a meaningful note of at least %s words");

//                    $validator->validate('loan_type')->isNumeric('Loan type must be numeric')
//                        ->isEqualToAny([self::LOAN_TYPE_OFFER, self::LOAN_TYPE_REQUEST], "Please select a valid loan type");

                    $validator->validate('is_fund_raiser', true)->isBool(
                        "Please enter a valid value to tell us if this is a fund raiser loan or not.");

                    if ($validator->hasError()) throw $this->baseException(
                        "The inputted data is invalid", "Loan Failed", HTTP::UNPROCESSABLE_ENTITY);

                    $check = $this->db()->check('loans', function (Builder $builder) use ($type) {
                        $builder->where('amount', \input('amount'));
                        $builder->where('tenor', \input('tenor'));
                        $builder->where('interest', \input('interest'));
                        $builder->where('interest_type', \input('interest_type'));
                        $builder->where('loan_type', $type == "offer" ? self::LOAN_TYPE_OFFER : self::LOAN_TYPE_REQUEST);
                        $builder->where('status', STATE_PENDING);
                        $builder->where('is_active', true);
                    });

                    if ($check->isSuccessful()) throw $this->baseException(
                        "You already {$type}ed that exact loan and it's still pending.", "Loan Failed", HTTP::CONFLICT);

                    $loan = $this->db()->insert('loans', array_merge([
                        'uuid' => Str::uuidv4(),
                        'user_id' => user('id'),
                        'status' => STATE_PENDING,
                        'loan_type' => $type == "offer" ? self::LOAN_TYPE_OFFER : self::LOAN_TYPE_REQUEST
                    ], $validator->getValidated()));

                    if (!$loan->isSuccessful()) throw $this->baseException(
                        "Failed to {$type} loan at this time, please try again later.",
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
                            'balance' => $this->getAvailableBalance()
                        ]
                    ], HTTP::CREATED);
                case "offers":
                case "requests":

                    $loans = $this->db()->select("*")->table('loans')
                        ->where('is_active', true)
                        ->where('loan_type', $type == "offers" ? self::LOAN_TYPE_OFFER : self::LOAN_TYPE_REQUEST)
                        ->orderBy('desc', 'id')->paginate(30);

                    $loans->setModelKey("loanModel");

                    $pagination = Pagination::getInstance();

                    return [
                        'pagination' => [
                            'page' => $pagination->getPaginator("default")->getPage(),
                            'totalRecords' => $pagination->getTotalRecords("default"),
                            'totalPages' => $pagination->getTotalPages("default"),
                            'nextPage' => $pagination->getNextPage("default", true),
                            'previousPage' => $pagination->getPreviousPage("default", true)
                        ],
                        'loans' => $loans->getAllWithModel() ?: []
                    ];
                case "constants":

                    $tenor = array_flip(get_class_consts($this, 'LOAN_TENOR_'));
                    $purpose = array_flip(get_class_consts($this, 'LOAN_PURPOSE_'));
                    $interest_type = array_flip(get_class_consts($this, 'INTEREST_TYPE_'));

                    Arr::callback($tenor, function ($tenor) {
                        return ucfirst(strtolower(str_replace("_", " ", str_start_from($tenor, 'LOAN_TENOR_'))));
                    });

                    Arr::callback($purpose, function ($tenor) {
                        return ucfirst(strtolower(str_replace("_", " ", str_start_from($tenor, 'LOAN_PURPOSE_'))));
                    });

                    Arr::callback($interest_type, function ($tenor) {
                        return ucfirst(strtolower(str_replace("_", " ", str_start_from($tenor, 'INTEREST_TYPE_')))) . " interest";
                    });

                    return [
                        'tenor' => $tenor,
                        'purpose' => $purpose,
                        'interest_type' => $interest_type
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
                'error' => (object)$validator->getErrors()
            ], $e->getCode());
        }
    }
}
