<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 5/9/2020
 * Time: 7:20 PM
 */

namespace custom\model;

use loan\Loan;
use que\database\interfaces\Builder;
use que\database\model\Model;

class LoanModel extends Model
{
    protected string $modelKey = 'loanModel';
    protected array $appends = ['status_readable', 'type', 'interest_type_readable', 'tenure_readable', 'purpose_readable', 'transaction', 'user', 'is_mine', 'has_applied'];
    protected array $casts = [
        'note' => 'string', 'amount' => 'double',
        'interest' => 'double', 'is_fund_raiser' => 'bool', 'loan_type' => 'func::strtolower',
        'purpose' => 'func::strtolower|str_replace,_, ,:subject|ucfirst',
        'tenure' => 'func::strtolower|str_replace,_, ,:subject|ucfirst',
        'interest_type' => 'func::strtolower|str_replace,_, ,:subject|ucfirst',
        'is_active' => 'bool', 'created_at' => 'date:d/m/y'
    ];
    protected array $hidden = ['updated_at'];
    protected array $renames = ['created_at' => 'date'];
    public static array $applied = [];

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
        return converter()->convertClassConst($this->getInt('interest_type'), Loan::class, "INTEREST_TYPE_");
    }

    public function getTenureReadable() {
        return converter()->convertClassConst($this->getInt('tenure'), Loan::class, "LOAN_TENURE_");
    }

    public function getPurposeReadable() {
        return converter()->convertClassConst($this->getInt('purpose'), Loan::class, "LOAN_PURPOSE_");
    }

    public function getType() {
        return converter()->convertClassConst($this->getInt('loan_type'), Loan::class, "LOAN_TYPE_");
    }

    public function getTransaction() {
        return $this->hasOne("transactions", 'gateway_reference', 'uuid', 'transactionModel');
    }

    public function getHasApplied() {
        $loan_id = $this->getValue('uuid');
        if (!isset(LoanModel::$applied[$loan_id])) {
            $application = db()->find('loan_applications', $loan_id, 'loan_id', function (Builder $builder) {
                $builder->where('user_id', user('id'));
                $builder->where('is_active', true);
            });
            LoanModel::$applied[$loan_id] = $application->isSuccessful();
        }
        return LoanModel::$applied[$loan_id];
    }

}
