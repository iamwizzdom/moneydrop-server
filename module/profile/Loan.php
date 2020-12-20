<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 11/20/2020
 * Time: 3:59 PM
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
use que\support\Str;

class Loan extends Manager implements Api
{
    const MIN_LOAN_OFFER = 5000;

    const LOAN_TYPE_OFFER = -7;
    const LOAN_TYPE_REQUEST = -8;

    const STATIC_INTEREST = -1;
    const NON_STATIC_INTEREST = 2;

    /**
     * Loan tenor constants
     */
    const LOAN_TENOR_ONE_WEEK = -1;
    const LOAN_TENOR_TWO_WEEK = -2;
    const LOAN_TENOR_THREE_WEEK = -3;
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
    const LOAN_TENOR_TWO_YEAR_AND_SIX_MONTHS = 30;
    const LOAN_TENOR_THREE_YEARS = 36;
    const LOAN_TENOR_THREE_YEAR_AND_SIX_MONTHS = 42;
    const LOAN_TENOR_FOUR_YEARS = 48;
    const LOAN_TENOR_FOUR_YEAR_AND_SIX_MONTHS = 54;
    const LOAN_TENOR_FIVE_YEARS = 60;
    const LOAN_TENOR_FIVE_YEAR_AND_SIX_MONTHS = 66;
    const LOAN_TENOR_SIX_YEARS = 72;
    const LOAN_TENOR_SIX_YEAR_AND_SIX_MONTHS = 78;
    const LOAN_TENOR_SEVEN_YEARS = 84;
    const LOAN_TENOR_SEVEN_YEAR_AND_SIX_MONTHS = 90;
    const LOAN_TENOR_EIGHT_YEARS = 96;
    const LOAN_TENOR_EIGHT_YEAR_AND_SIX_MONTHS = 102;
    const LOAN_TENOR_NINE_YEARS = 108;
    const LOAN_TENOR_NINE_YEAR_AND_SIX_MONTHS = 114;
    const LOAN_TENOR_TEN_YEARS = 120;

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.
        $validator = $this->validator($input);

        try {

            switch ($type = Request::getUriParam('type')) {
                case "offers":
                case "requests":

                    $date = date('m/y');
                    $converter = converter();

                    $list = [];
                    for ($i = 0; $i < 30; $i++) {
                        $list[] = [
                            'id' => $i,
                            'type' => "Loan {$type}",
                            'amount' => 65000,
                            'status' => $converter->convertEnvConst(STATE_SUCCESSFUL, "STATE_"),
                            'date' => $date
                        ];
                    }

                    return [
                        'page' => get('page') + 1,
                        "loan_{$type}" => $list
                    ];

                case "request":
                case "offer":

                    $validator->validate('amount')->isNumber('Please enter a valid amount')
                        ->isNumberGreaterThanOrEqual(self::MIN_LOAN_OFFER, "Sorry, you must offer at least %s");

                    $validator->validate('tenor')->isEqualToAny(get_class_consts($this), 'Please enter a valid tenor');

                    $validator->validate('interest')->isNumeric('Please enter a valid interest rate');

                    $validator->validate('interest_type')->isNumeric('Interest type must be numeric')
                        ->isEqualToAny([self::STATIC_INTEREST, self::NON_STATIC_INTEREST], "Please select a valid interest type");

                    $validator->validate('note', true)->isNotEmpty("Your note shouldn't be empty")
                        ->hasMinWord(20, "Please write a meaningful note of at least %s words");

                    $validator->validate('loan_type')->isNumeric('Loan type must be numeric')
                        ->isEqualToAny([self::LOAN_TYPE_OFFER, self::LOAN_TYPE_REQUEST], "Please select a valid loan type");

                    $validator->validate('is_fund_raiser', true)->isBool();

                    if ($validator->hasError()) throw $this->baseException(
                        "The inputted data is invalid", "Loan Failed", HTTP::UNPROCESSABLE_ENTITY);

                    $check = $this->db()->check('loans', function (Builder $builder) {
                        $builder->where('amount', \input('amount'));
                        $builder->where('tenor', \input('tenor'));
                        $builder->where('interest', \input('interest'));
                        $builder->where('interest_type', \input('interest_type'));
                        $builder->where('loan_type', \input('loan_type'));
                        $builder->where('is_active', true);
                    });

                    $loanType = $this->converter()->convertClassConst($input['loan_type'], $this, "LOAN_TYPE_");

                    if ($check->isSuccessful()) throw $this->baseException(
                        "You already {$loanType}ed that exact loan", "Loan Failed", HTTP::CONFLICT);

                    $loan = $this->db()->insert('loans', array_merge([
                        'uuid' => Str::uuidv4(),
                        'user_id' => user('id')
                    ], $validator->getValidated()));

                    if (!$loan->isSuccessful()) throw $this->baseException(
                        "Failed to {$loanType} loan at this time, please try again later.",
                        "Loan Failed", HTTP::EXPECTATION_FAILED);

                    return $this->http()->output()->json([
                        'status' => true,
                        'code' => HTTP::CREATED,
                        'title' => 'Loan Successful',
                        'message' => "Loan {$loanType}ed successfully.",
                        'response' => [
                            'loan' => $loan->getFirstArray()
                        ]
                    ], HTTP::CREATED);

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