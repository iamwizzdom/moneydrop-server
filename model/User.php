<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 5/9/2020
 * Time: 7:20 PM
 */

namespace model;

use JetBrains\PhpStorm\ArrayShape;
use que\database\interfaces\Builder;
use que\database\model\Model;
use que\support\Config;
use que\user\XUser;
use que\utility\money\Item;

class User extends Model
{
    protected string $modelKey = 'userModel';
    protected array $hidden = ['password', 'pn_token', 'max_loan_amount', 'google_id'];
    protected array $viewable = ['uuid', 'firstname', 'middlename', 'lastname', 'phone', 'email', 'password', 'bvn',
        'picture', 'dob', 'gender', 'address', 'country_id', 'state_id', 'status', 'is_active'];
    protected array $appends = ['country', 'state', 'rating', 'max_loan_amount'];
    protected array $casts = ['id,gender,country_id,state_id,status' => 'int',
        'address,bvn,pn_token,picture' => 'string', 'is_active' => 'bool'];

    public function addHidden(): ?array
    {
        Config::set('auth.providers.user', 'que');
        if (is_logged_in() && $this->getValue('id') != user('id')) {
            return [
                'bvn',
                'phone',
                'email',
                'address'
            ];
        }
        Config::set('auth.providers.user', 'userModel');
        return null;
    }

    public function getCountry(): ?\que\database\interfaces\model\Model
    {
        return $this->belongTo('countries', 'country_id');
    }

    public function getState(): ?\que\database\interfaces\model\Model
    {
        return $this->belongTo('states', 'state_id');
    }

    public function getBankStatement(): \que\database\model\base\BaseModel|BankStatement|null
    {
        $statement = $this->hasOne('bank_statements', 'user_id');
        if (!$statement) $statement = db()->insert('bank_statements', ['user_id' => $this->getInt('id')])->getFirstWithModel();
        return $statement ? BankStatement::cast($statement) : null;
    }

    #[ArrayShape(['email' => "bool", 'phone' => "bool"])] public function getVerified(): array
    {

        $emailVerification = db()->find('verifications', $this->getValue('email'),
            'data', function (Builder $builder) {
                $builder->where('type', 'email');
                $builder->where('is_verified', true);
                $builder->where('is_active', true);
            });

        $phoneVerification = db()->find('verifications', $this->getValue('phone'),
            'data', function (Builder $builder) {
                $builder->where('type', 'phone');
                $builder->where('is_verified', true);
                $builder->where('is_active', true);
            });

        return [
            'email' => $emailVerification->isSuccessful(),
            'phone' => $phoneVerification->isSuccessful()
        ];
    }

    public function getWallet(): ?\que\database\interfaces\model\Model
    {
        return $this->hasOne('wallets', 'user_id');
    }

    public function getRating(): float|int
    {

        $sum = db()->sum('ratings', 'rating')
            ->where('user_id', $this->getInt('id'))
            ->where('is_active', true)->exec();

        $count = db()->count('ratings', 'id')
            ->where('user_id', $this->getInt('id'))
            ->where('is_active', true)->exec();

//        $systemRating = db()->find('system_ratings', $this->getInt('id'), 'user_id');

//        if ($systemRating->isSuccessful()) $systemRating = $systemRating->getFirstWithModel()?->getFloat('rating') ?: 0;
//        else {
//            $systemRating = db()->insert('system_ratings', [
//                'rating' => 0,
//                'user_id' => $this->getInt('id'),
//                'is_active' => true
//            ])->getFirstWithModel()?->getFloat('rating') ?: 0;
//        }

        if (!$sum->isSuccessful() || !$count->isSuccessful()) return 0;

        if (!($sum = $sum->getQueryResponse()) || !($count = $count->getQueryResponse())) return 0;

//        if (!($sum = $sum->getQueryResponse()) || !($count = $count->getQueryResponse())) return ($systemRating + 0) / 2;

        return round(($sum / $count));
    }

    public function getMaxLoanAmount(): float|int
    {

        $maxAmount = Loan::MIN_AMOUNT;

//        if ($rating <= 1) $maxAmount = $rating < 1 ? 500000 : 1000000;
//        elseif ($rating <= 2) $maxAmount = $rating < 2 ? 1500000 : 3000000;
//        elseif ($rating <= 3) $maxAmount = $rating < 3 ? 4000000 : 6000000;
//        elseif ($rating <= 4) $maxAmount = $rating < 4 ? 7000000 : 8000000;
//        elseif ($rating <= 5) $maxAmount = $rating < 5 ? 9000000 : 10000000;

        $maxIncome = db()->select('max(ba.income) as max_income')
            ->table('bank_accounts as ba')
            ->where('user_id', $this->getInt('id'))
            ->where('income_type', BankAccount::INCOME_TYPE_REGULAR)
            ->where('is_active', true)
            ->limit(1)
            ->exec();

        $maxIncome = $maxIncome->getFirstWithModel()?->getFloat('max_income');

        if ($maxIncome && $maxIncome > Loan::MIN_AMOUNT) {
            $percentage = (Loan::PERCENTAGE_INCOME +  $this->getFloat('rating'));
            $availableIncome = (float) Item::cents($maxIncome)->percentage($percentage)->getCents();
            if ($availableIncome > Loan::MIN_AMOUNT) $maxAmount = $availableIncome;
        }

        if ($maxAmount < Loan::MAX_AMOUNT) {

            $max = db()->select('max(l.amount) as max_amount')->table('loan_applications as la')
                ->join('loans as l', 'la.loan_id', 'l.uuid')
                ->where('l.loan_type', Loan::LOAN_TYPE_OFFER)
                ->where('la.user_id', $this->getInt('id'))
                ->where('la.status', LoanApplication::STATUS_REPAID)
                ->where('la.is_active', true)
                ->where('l.is_active', true)
                ->limit(1)
                ->exec();

            $maxLoanAmount = $max->getFirstWithModel()?->getFloat('max_amount');
            $maxAmount = ($maxLoanAmount && $maxLoanAmount < Loan::MAX_AMOUNT) ? ($maxLoanAmount * 2) : $maxAmount;
        }

        return $maxAmount > Loan::MAX_AMOUNT ? Loan::MAX_AMOUNT : $maxAmount;
    }
}
