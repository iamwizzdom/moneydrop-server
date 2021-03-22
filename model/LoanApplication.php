<?php


namespace model;


use DateTime;
use que\database\interfaces\Builder;
use que\database\model\Model;
use que\utility\money\Item;

class LoanApplication extends Model
{
    const STATUS_AWAITING = 0;
    const STATUS_GRANTED = 1;
    const STATUS_REJECTED = 2;
    const STATUS_REPAID = 3;

    public static array $granted = [];

    protected string $modelKey = 'loanApplicationModel';
    protected array $fillable = ['uuid', 'loan_id', 'user_id', 'amount', 'note', 'due_at', 'status', 'is_active'];
    protected array $copy = ['created_at' => 'date', 'date' => 'date_short', 'due_at' => 'due_date_short', 'granted_at' => 'granted_date_short'];
    protected array $casts = ['amount_payable,repaid_amount' => 'double', 'status' => 'int', 'is_active,is_repaid' => 'bool',
        'due_date_short' => "date:jS M 'y", 'date' => 'date:d/m/y', 'date_short' => 'date:jS M', 'granted_date_short' => "date:jS M 'y"];
    protected array $appends = ['repaid_amount', 'amount_payable', 'unpaid_amount', 'status_readable', 'applicant', 'has_granted', 'is_repaid', 'is_reviewed'];

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
                $builder->where('status', [self::STATUS_GRANTED, self::STATUS_REPAID]);
                $builder->where('is_active', true);
            });
            self::$granted[$loan_id] = $application->isSuccessful();
        }
        return self::$granted[$loan_id];
    }

    public function getRepaidAmount() {
        $repayments = db()->findAll('loan_repayments', $this->getValue('uuid'), 'application_id');
        $repayments = $repayments->getAllWithModel();
        return $repayments?->sumColumn('amount') ?: 0;
    }

    public function getUnpaidAmount() {
        $payable = $this->getFloat('amount_payable');
        return $payable ? $payable - $this->getFloat('repaid_amount') : $payable;
    }

    public function getAmountPayable() {
        $loan = $this->getLoan('que');
        if ($loan !== null && $this->getInt('status') == self::STATUS_GRANTED) {

            $amount = $loan->getFloat('amount');
            $interest = $loan->getFloat('interest');
            $tenure = $loan->getInt('tenure');

            $isDue = $this->validate('due_at')->isDateLessThan($now = new DateTime('now'));

            if ($loan->getInt('interest_type') == Loan::INTEREST_TYPE_NON_STATIC && $isDue) {
                $interest += ($interest / 2);
            }

            $percentage = (float) Item::cents($amount)->percentage($interest)->getCents();

            $date = new DateTime($this->getValue('granted_at') ?: $this->getValue('updated_at'));

            if ($tenure < Loan::LOAN_TENURE_ONE_MONTH) {

                $weeks = ($date->diff($now)->days / 7) ?: 1;

                if ($loan->getInt('interest_type') == Loan::INTEREST_TYPE_STATIC) {
                    if ($tenure == Loan::LOAN_TENURE_ONE_WEEK) $tenure = (1 / 4);
                    elseif ($tenure == Loan::LOAN_TENURE_TWO_WEEKS) $tenure = ((1 / 4) * 2);
                    elseif ($tenure == Loan::LOAN_TENURE_THREE_WEEKS) $tenure = ((1 / 4) * 3);
                    $amount += ($percentage * ($isDue ? ceil($weeks) : $tenure));
                } else {
                    $amount += ($percentage * ceil($weeks));
                }

            } else {

                $months = ((12 * $date->diff($now)->y) + $date->diff($now)->m) ?: 1;

                if ($loan->getInt('interest_type') == Loan::INTEREST_TYPE_STATIC) {
                    $amount += ($percentage * ($isDue ? ceil($months) : $tenure));
                } else {
                    $amount += ($percentage * ceil($months));
                }

            }

            return $amount;
        }
        return 0;
    }

    public function isRepaid() {
        return $this->getInt('status') == self::STATUS_REPAID || ($this->getFloat('amount_payable') && $this->getFloat('repaid_amount') >= $this->getFloat('amount_payable'));
    }

    public function isReviewed() {
        return $this->validate('uuid')->isFoundInDB('reviews', 'application_id', function (Builder $builder) {
            $builder->where('is_active', true);
        });
    }

    public function getStatusReadable() {
        return converter()->convertClassConst($this->getInt('status'), $this, 'STATUS_');
    }
}
