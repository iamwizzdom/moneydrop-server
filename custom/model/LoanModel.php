<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 5/9/2020
 * Time: 7:20 PM
 */

namespace custom\model;

use loan\Loan;
use que\database\model\Model;

class LoanModel extends Model
{
    protected string $modelKey = 'loanModel';
    protected array $appends = ['user', 'status', 'loan_type', 'transaction'];
    protected array $casts = ['amount' => 'double', 'is_active' => 'bool', 'created_at' => 'date:d/m/y'];
    protected array $hidden = ['updated_at'];
    protected array $renames = ['created_at' => 'date', 'loan_type' => 'type'];

    public function getUser(): ?\que\database\interfaces\model\Model
    {
        return $this->belongTo('users', 'user_id');
    }

    public function getStatus() {
        return converter()->convertEnvConst($this->getValue('status'), "STATE_");
    }

    public function getLoanType() {
        return "Loan " . converter()->convertClassConst($this->getValue('loan_type'), Loan::class, "LOAN_TYPE_");
    }

    public function getTransaction() {
        return $this->hasOne("transactions", 'gateway_reference', 'uuid', 'transactionModel');
    }

}
