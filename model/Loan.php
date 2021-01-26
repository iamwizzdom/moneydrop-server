<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 5/9/2020
 * Time: 7:20 PM
 */

namespace model;

use JetBrains\PhpStorm\Pure;
use que\database\interfaces\Builder;
use que\database\model\Model;

class Loan extends Model
{
    protected string $modelKey = 'loanModel';
    protected array $fillable = ['uuid', 'amount', 'tenure', 'interest', 'purpose', 'interest_type', 'note', 'loan_type', 'is_fund_raiser', 'user_id', 'status', 'is_active'];
    protected array $appends = ['absolute_tenure', 'status_readable', 'type', 'loan_type_readable', 'interest_type_readable', 'tenure_readable', 'purpose_readable', 'transaction', 'user', 'is_mine', 'has_applied'];
    protected array $casts = [
        'note' => 'string', 'amount' => 'double', 'loan_type' => 'int',
        'interest' => 'double', 'is_fund_raiser' => 'bool', 'loan_type_readable' => 'func::strtolower',
        'purpose_readable' => 'func::strtolower|str_replace,_, ,:subject|ucfirst',
        'tenure_readable' => 'func::strtolower|str_replace,_, ,:subject|ucfirst',
        'interest_type_readable' => 'func::strtolower|str_replace,_, ,:subject|ucfirst',
        'is_active' => 'bool', 'created_at' => 'date:d/m/y'
    ];
    protected array $hidden = ['updated_at'];
    protected array $renames = ['created_at' => 'date'];
    public static array $applied = [];

    /**
     * Minimum loan amount
     */
    const MIN_LOAN_AMOUNT = 5000;

    /**
     * Loan types
     */
    const LOAN_TYPE_OFFER = -7;
    const LOAN_TYPE_REQUEST = -8;

    const INTEREST_TYPE_STATIC = 1;
    const INTEREST_TYPE_NON_STATIC = 2;

    /**
     * Loan tenure constants
     */
    const LOAN_TENURE_ONE_WEEK = -1;
    const LOAN_TENURE_TWO_WEEKS = -2;
    const LOAN_TENURE_THREE_WEEKS = -3;
    const LOAN_TENURE_ONE_MONTH = 1;
    const LOAN_TENURE_TWO_MONTHS = 2;
    const LOAN_TENURE_THREE_MONTHS = 3;
    const LOAN_TENURE_FOUR_MONTHS = 4;
    const LOAN_TENURE_FIVE_MONTHS = 5;
    const LOAN_TENURE_SIX_MONTHS = 6;
    const LOAN_TENURE_SEVEN_MONTHS = 7;
    const LOAN_TENURE_EIGHT_MONTHS = 8;
    const LOAN_TENURE_NINE_MONTHS = 9;
    const LOAN_TENURE_TEN_MONTHS = 10;
    const LOAN_TENURE_ELEVEN_MONTHS = 11;
    const LOAN_TENURE_ONE_YEAR = 12;
    const LOAN_TENURE_ONE_YEAR_AND_SIX_MONTHS = 18;
    const LOAN_TENURE_TWO_YEARS = 24;
    const LOAN_TENURE_TWO_YEARS_AND_SIX_MONTHS = 30;
    const LOAN_TENURE_THREE_YEARS = 36;
    const LOAN_TENURE_THREE_YEARS_AND_SIX_MONTHS = 42;
    const LOAN_TENURE_FOUR_YEARS = 48;
    const LOAN_TENURE_FOUR_YEARS_AND_SIX_MONTHS = 54;
    const LOAN_TENURE_FIVE_YEARS = 60;
    const LOAN_TENURE_FIVE_YEARS_AND_SIX_MONTHS = 66;
    const LOAN_TENURE_SIX_YEARS = 72;
    const LOAN_TENURE_SIX_YEARS_AND_SIX_MONTHS = 78;
    const LOAN_TENURE_SEVEN_YEARS = 84;
    const LOAN_TENURE_SEVEN_YEARS_AND_SIX_MONTHS = 90;
    const LOAN_TENURE_EIGHT_YEARS = 96;
    const LOAN_TENURE_EIGHT_YEARS_AND_SIX_MONTHS = 102;
    const LOAN_TENURE_NINE_YEARS = 108;
    const LOAN_TENURE_NINE_YEARS_AND_SIX_MONTHS = 114;
    const LOAN_TENURE_TEN_YEARS = 120;

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

    public function getUser(): ?\que\database\interfaces\model\Model
    {
        return $this->belongTo('users', 'user_id', 'id', 'userModel');
    }

    public function getStatusReadable() {
        return converter()->convertEnvConst($this->getValue('status'), "STATE_");
    }

    public function isMine() {
        return user()->getInt('id') == $this->getInt('user_id');
    }

    public function getInterestTypeReadable() {
        return converter()->convertClassConst($this->getInt('interest_type'), $this, "INTEREST_TYPE_");
    }

    public function getTenureReadable() {
        return converter()->convertClassConst($this->getInt('tenure'), $this, "LOAN_TENURE_");
    }

    public function getPurposeReadable() {
        return converter()->convertClassConst($this->getInt('purpose'), $this, "LOAN_PURPOSE_");
    }

    public function getLoanTypeReadable() {
        return converter()->convertClassConst($this->getInt('loan_type'), $this, "LOAN_TYPE_");
    }

    public function getTransaction() {
        return $this->hasOne("transactions", 'gateway_reference', 'uuid', 'transactionModel');
    }

    public function getHasApplied() {
        $loan_id = $this->getValue('uuid');
        if (!isset(Loan::$applied[$loan_id])) {
            $application = db()->find('loan_applications', $loan_id, 'loan_id', function (Builder $builder) {
                $builder->where('user_id', user('id'));
                $builder->where('is_active', true);
            });
            Loan::$applied[$loan_id] = $application->isSuccessful();
        }
        return Loan::$applied[$loan_id];
    }

    public function getApprovedApplicant() {
        $applications = $this->hasMany('loan_applications', 'loan_id', 'uuid', 'loanApplicationModel');
        return $applications->find(function (\que\database\interfaces\model\Model $model) {
            return $model->getBool('is_active') && $model->getInt('status') == LoanApplication::GRANTED;
        });
    }

    public function getAbsoluteTenure() {
        $tenure = $this->getInt('tenure');
        if ($tenure < Loan::LOAN_TENURE_ONE_MONTH) {
            if ($tenure == Loan::LOAN_TENURE_ONE_WEEK) {
                $tenure = (1 / 4);
            } elseif ($tenure == Loan::LOAN_TENURE_TWO_WEEKS) {
                $tenure = ((1 / 4) * 2);
            } elseif ($tenure == Loan::LOAN_TENURE_THREE_WEEKS) {
                $tenure = ((1 / 4) * 3);
            }
        }
        return $tenure;
    }

    /**
     * @param int $tenure
     * @return string
     */
    #[Pure] public static function getLoanDueDate(int $tenure): string
    {
        switch ($tenure) {
            case self::LOAN_TENURE_ONE_WEEK:
                $date = strtotime('+1 week');
                break;
            case self::LOAN_TENURE_TWO_WEEKS:
                $date = strtotime('+2 weeks');
                break;
            case self::LOAN_TENURE_THREE_WEEKS:
                $date = strtotime('+3 weeks');
                break;
            case self::LOAN_TENURE_ONE_MONTH:
                $date = strtotime('+1 month');
                break;
            case self::LOAN_TENURE_TWO_MONTHS:
                $date = strtotime('+2 months');
                break;
            case self::LOAN_TENURE_THREE_MONTHS:
                $date = strtotime('+3 months');
                break;
            case self::LOAN_TENURE_FOUR_MONTHS:
                $date = strtotime('+4 months');
                break;
            case self::LOAN_TENURE_FIVE_MONTHS:
                $date = strtotime('+5 months');
                break;
            case self::LOAN_TENURE_SIX_MONTHS:
                $date = strtotime('+6 months');
                break;
            case self::LOAN_TENURE_SEVEN_MONTHS:
                $date = strtotime('+7 months');
                break;
            case self::LOAN_TENURE_EIGHT_MONTHS:
                $date = strtotime('+8 months');
                break;
            case self::LOAN_TENURE_NINE_MONTHS:
                $date = strtotime('+9 months');
                break;
            case self::LOAN_TENURE_TEN_MONTHS:
                $date = strtotime('+10 months');
                break;
            case self::LOAN_TENURE_ELEVEN_MONTHS:
                $date = strtotime('+11 months');
                break;
            case self::LOAN_TENURE_ONE_YEAR:
                $date = strtotime('+1 year');
                break;
            case self::LOAN_TENURE_ONE_YEAR_AND_SIX_MONTHS:
                $date = strtotime('+1 year');
                $date = strtotime('+6 months', $date);
                break;
            case self::LOAN_TENURE_TWO_YEARS:
                $date = strtotime('+2 years');
                break;
            case self::LOAN_TENURE_TWO_YEARS_AND_SIX_MONTHS:
                $date = strtotime('+2 years');
                $date = strtotime('+6 months', $date);
                break;
            case self::LOAN_TENURE_THREE_YEARS:
                $date = strtotime('+3 years');
                break;
            case self::LOAN_TENURE_THREE_YEARS_AND_SIX_MONTHS:
                $date = strtotime('+3 years');
                $date = strtotime('+6 months', $date);
                break;
            case self::LOAN_TENURE_FOUR_YEARS:
                $date = strtotime('+4 years');
                break;
            case self::LOAN_TENURE_FOUR_YEARS_AND_SIX_MONTHS:
                $date = strtotime('+4 years');
                $date = strtotime('+6 months', $date);
                break;
            case self::LOAN_TENURE_FIVE_YEARS:
                $date = strtotime('+5 years');
                break;
            case self::LOAN_TENURE_FIVE_YEARS_AND_SIX_MONTHS:
                $date = strtotime('+5 years');
                $date = strtotime('+6 months', $date);
                break;
            case self::LOAN_TENURE_SIX_YEARS:
                $date = strtotime('+6 years');
                break;
            case self::LOAN_TENURE_SIX_YEARS_AND_SIX_MONTHS:
                $date = strtotime('+6 years');
                $date = strtotime('+6 months', $date);
                break;
            case self::LOAN_TENURE_SEVEN_YEARS:
                $date = strtotime('+7 years');
                break;
            case self::LOAN_TENURE_SEVEN_YEARS_AND_SIX_MONTHS:
                $date = strtotime('+7 years');
                $date = strtotime('+6 months', $date);
                break;
            case self::LOAN_TENURE_EIGHT_YEARS:
                $date = strtotime('+8 years');
                break;
            case self::LOAN_TENURE_EIGHT_YEARS_AND_SIX_MONTHS:
                $date = strtotime('+8 years');
                $date = strtotime('+6 months', $date);
                break;
            case self::LOAN_TENURE_NINE_YEARS:
                $date = strtotime('+9 years');
                break;
            case self::LOAN_TENURE_NINE_YEARS_AND_SIX_MONTHS:
                $date = strtotime('+9 years');
                $date = strtotime('+6 months', $date);
                break;
            case self::LOAN_TENURE_TEN_YEARS:
                $date = strtotime('+10 years');
                break;
            default:
                $date = strtotime('+1 day');
                break;
        }
        return date(DATE_FORMAT_MYSQL, $date);
    }

}
