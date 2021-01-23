<?php


namespace model;


use que\database\interfaces\Builder;
use que\database\model\Model;

class LoanApplication extends Model
{
    protected string $modelKey = 'loanApplicationModel';
    protected array $copy = ['created_at' => 'date', 'date' => 'date_short', 'due_date' => 'due_date_short'];
    protected array $casts = ['is_granted' => 'bool', 'is_repaid' => 'bool', 'is_active' => 'bool',
        'due_date_short' => "date:jS M 'y", 'date' => 'date:d/m/y', 'date_short' => 'date:jS M'];
    protected array $appends = ['applicant', 'has_granted'];

    public function getApplicant() {
        return $this->belongTo('users', 'user_id', 'id', 'userModel');
    }

    public function getLoan($modelKey = 'loanModel') {
        return $this->belongTo('loans', 'loan_id', 'uuid', $modelKey ?: 'que');
    }

    public function getHasGranted() {
        return $this->getBool('is_granted');
    }
}
