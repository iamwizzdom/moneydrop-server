<?php


namespace model;


use que\database\interfaces\Builder;
use que\database\model\Model;
use que\support\Num;
use que\utility\money\Item;

class LoanApplication extends Model
{
    const AWAITING = 0;
    const GRANTED = 1;
    const REJECTED = 2;

    public static array $granted = [];

    protected string $modelKey = 'loanApplicationModel';
    protected array $fillable = ['uuid', 'loan_id', 'user_id', 'amount', 'note', 'due_date', 'status', 'is_active'];
    protected array $copy = ['created_at' => 'date', 'date' => 'date_short', 'due_date' => 'due_date_short'];
    protected array $casts = ['amount_payable,repaid_amount' => 'double', 'status' => 'int', 'is_active,is_repaid' => 'bool',
        'due_date_short' => "date:jS M 'y", 'date' => 'date:d/m/y', 'date_short' => 'date:jS M'];
    protected array $appends = ['repaid_amount', 'amount_payable', 'unpaid_amount', 'status_readable', 'applicant', 'has_granted', 'is_repaid', 'is_reviewed', 'date_granted'];

    public function getArray(bool $onlyFillable = false): array
    {
        $data = parent::getArray($onlyFillable);
        $data['amount'] = Item::cents($data['amount'])->getFactor();
        $data['unpaid_amount'] = Item::cents($data['unpaid_amount'])->getFactor();
        $data['repaid_amount'] = Item::cents($data['repaid_amount'])->getFactor();
        $data['amount_payable'] = Item::cents($data['amount_payable'])->getFactor();
        return $data; // TODO: Change the autogenerated stub
    }

    public function getApplicant() {
        return $this->belongTo('users', 'user_id', 'id', 'userModel');
    }

    public function getLoan($modelKey = 'loanModel') {
        return $this->belongTo('loans', 'loan_id', 'uuid', $modelKey ?: 'que');
    }

    public function getHasGranted() {
        $loan_id = $this->getValue('loan_id');
        if (!isset(self::$granted[$loan_id])) {
            $application = db()->find('loan_applications', $loan_id, 'loan_id', function (Builder $builder) {
                $builder->where('status', self::GRANTED);
                $builder->where('is_active', true);
            });
            self::$granted[$loan_id] = $application->isSuccessful();
        }
        return self::$granted[$loan_id];
    }

    public function getUnpaidAmount() {
        return $this->getFloat('amount_payable') - $this->getFloat('repaid_amount');
    }

    public function getAmountPayable() {
        $loan = $this->getLoan('que');
        if ($loan !== null) {

            $amount = (float) $loan->getFloat('amount');

            $percentage = (float) Item::cents($amount)->percentage($loan->getFloat('interest'))->getCents();

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

    public function isReviewed() {
        return $this->validate('uuid')->isFoundInDB('reviews', 'application_id', function (Builder $builder) {
            $builder->where('is_active', true);
        });
    }

    public function getRepaidAmount() {
        $repayments = db()->findAll('loan_repayments', $this->getValue('uuid'), 'application_id');
        $repayments = $repayments->getAllWithModel();
        return $repayments?->sumColumn('amount') ?: 0;
    }

    public function getDateGranted() {
        return $this->getHasGranted() ? get_date("jS M 'y", $this->getValue('updated_at')) : "Unavailable";
    }

    public function getStatusReadable() {
        return converter()->convertClassConst($this->getInt('status'), $this);
    }
}
