<?php


namespace custom\model;


use que\database\interfaces\Builder;
use que\database\model\Model;

class LoanApplicationModel extends Model
{
    protected string $modelKey = 'loanApplicationModel';
    protected array $copy = ['created_at' => 'date_short'];
    protected array $casts = ['is_granted' => 'bool', 'is_active' => 'bool', 'created_at' => 'date:d/m/y', 'date_short' => 'date:jS M'];
    protected array $renames = ['created_at' => 'date'];
    protected array $appends = ['applicant', 'has_granted'];

    public function getApplicant() {
        return $this->belongTo('users', 'user_id', 'id', 'userModel');
    }

    public function getLoan() {
        return $this->belongTo('loans', 'loan_id', 'uuid', 'loanModel');
    }

    public function getHasGranted() {
        return $this->getBool('is_granted');
    }
}
