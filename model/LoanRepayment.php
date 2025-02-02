<?php


namespace model;


use que\database\model\Model;
use que\utility\money\Item;

class LoanRepayment extends Model
{
    protected array $viewable = ['uuid', 'application_id', 'amount', 'user_id'];
    protected array $copy = ['created_at' => 'date'];
    protected array $casts = ['amount' => 'double', 'date' => "jS M 'y"];

    const PAYMENT_CHANNEL_WALLET = 1;
    const PAYMENT_CHANNEL_BANK = 2;

    public function getArray(bool $onlyFillable = false): array
    {
        $data = parent::getArray($onlyFillable);
        $data['amount'] = Item::cents($data['amount'])->getFactor();
        return $data; // TODO: Change the autogenerated stub
    }

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