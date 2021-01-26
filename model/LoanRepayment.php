<?php


namespace model;


use que\database\model\Model;

class LoanRepayment extends Model
{
    protected array $fillable = ['uuid', 'application_id', 'amount', 'user_id'];
    protected array $copy = ['created_at' => 'date'];
    protected array $casts = ['amount' => 'double', 'date' => "jS M 'y"];

    public function getApplication() {
        return $this->belongTo('loan_applications', 'application_id', 'uuid', 'loanApplicationModel');
    }

    public function getPayer() {
        return $this->belongTo('users', 'user_id', 'id', 'userModel');
    }

    public function getTransaction() {
        return $this->belongTo('transactions', 'uuid', 'gateway_reference', 'transactionModel');
    }
}