<?php


namespace model;


use que\database\interfaces\Builder;
use que\database\model\Model;

class LoanApplication extends Model
{
    const AWAITING = 0;
    const GRANTED = 1;
    const REJECTED = 2;

    protected string $modelKey = 'loanApplicationModel';
    protected array $fillable = ['uuid', 'loan_id', 'user_id', 'amount', 'note', 'due_date', 'status', 'is_active'];
    protected array $copy = ['created_at' => 'date', 'date' => 'date_short', 'due_date' => 'due_date_short'];
    protected array $casts = ['amount_payable' => 'double', 'repaid_amount' => 'double', 'status' => 'int', 'is_repaid' => 'bool', 'is_active' => 'bool',
        'due_date_short' => "date:jS M 'y", 'date' => 'date:d/m/y', 'date_short' => 'date:jS M'];
    protected array $appends = ['repaid_amount', 'amount_payable', 'status_readable', 'applicant', 'has_granted', 'is_repaid', 'date_granted'];

    public function getApplicant() {
        return $this->belongTo('users', 'user_id', 'id', 'userModel');
    }

    public function getLoan($modelKey = 'loanModel') {
        return $this->belongTo('loans', 'loan_id', 'uuid', $modelKey ?: 'que');
    }

    public function getHasGranted() {
        return $this->getInt('status') == self::GRANTED;
    }

    public function getAmountPayable() {
        $loan = $this->getLoan('que');
        if ($loan !== null) {

            $percentage = ((($amount = $loan->getFloat('amount')) / 100) * $loan->getFloat('interest'));

            if ($loan->getInt('interest_type') == Loan::INTEREST_TYPE_STATIC) {
                return $amount + $percentage;
            } else {
                $tenure = $loan->getInt('tenure');
                if ($tenure < Loan::LOAN_TENURE_ONE_MONTH) {
                    if ($tenure == Loan::LOAN_TENURE_ONE_WEEK) {
                        $tenure = (1 / 4);
                    } elseif ($tenure == Loan::LOAN_TENURE_TWO_WEEKS) {
                        $tenure = ((1 / 4) * 2);
                    } elseif ($tenure == Loan::LOAN_TENURE_THREE_WEEKS) {
                        $tenure = ((1 / 4) * 3);
                    }
                }
                return $amount + ($percentage * $tenure);
            }
        }
        return 0;
    }

    public function isRepaid() {
        return $this->getFloat('repaid_amount') >= $this->getFloat('amount_payable');
    }

    public function getRepaidAmount() {
        $repayments = db()->findAll('loan_repayments', $this->getValue('uuid'), 'application_id');
        $repayments = $repayments->getAllWithModel();
        return $repayments?->sum(function (\que\database\interfaces\model\Model $model) {
            return $model->getFloat('amount');
        }) ?: 0;
    }

    public function getDateGranted() {
        return $this->getHasGranted() ? get_date("jS M 'y", $this->getValue('updated_at')) : "Unavailable";
    }

    public function getStatusReadable() {
        return converter()->convertClassConst($this->getInt('status'), $this);
    }
}
