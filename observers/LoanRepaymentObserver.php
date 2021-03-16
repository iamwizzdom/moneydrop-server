<?php


namespace observers;


use model\Loan;
use model\LoanApplication;
use model\LoanRepayment;
use model\Notification;
use model\Transaction;
use que\database\interfaces\model\Model;
use que\database\model\ModelCollection;
use que\database\observer\Observer;
use que\support\Str;
use que\utility\money\Item;

class LoanRepaymentObserver extends Observer
{

    /**
     * @inheritDoc
     */
    public function onCreating(Model $model)
    {
        // TODO: Implement onCreating() method.
    }

    /**
     * @inheritDoc
     */
    public function onCreated(Model $model)
    {
        // TODO: Implement onCreated() method.
        if (!$model instanceof LoanRepayment) $model = LoanRepayment::cast($model);
        if (!$model->has('application')) $model->load('application');
        if (!$model->has('payer')) $model->load('payer');

        $model->application->load('loan');
        $model->payer->load('wallet');

        $interest = $model->application->loan->interest;

        if ($model->application->loan->getInt('interest_type') == Loan::INTEREST_TYPE_NON_STATIC &&
            $model->application->validate('due_at')->isDateLessThan(new \DateTime('now'))) {
            $interest += ($interest / 2);
        }

        $profit = (float) Item::cents($model->application->loan->amount)->percentage($interest)->getCents();

        $profit = ($profit * ((1 / 4) * (4 * $model->application->loan->absolute_tenure)));

        $percentage = (($model->getFloat('amount') / $model->application->amount_payable) * $profit);
        $percentage = (float) Item::cents($percentage)->percentage(($interest * ((1 / 4) * (4 * $model->application->loan->absolute_tenure))))->getCents();

        if ($model->application->loan->loan_type == Loan::LOAN_TYPE_OFFER) {

            $model->application->loan->user->load('wallet');

            $trans = db()->insert('transactions', [
                'uuid' => Str::uuidv4(),
                'user_id' => $model->user_id,
                'type' => Transaction::TRANS_TYPE_TRANSFER,
                'to_wallet_id' => $model->application->loan->user->wallet->id,
                'from_wallet_id' => $model->payer->wallet->id,
                'gateway_reference' => $model->uuid,
                'direction' => 'w2w',
                'amount' => $model->getFloat('amount'),
                'creditor_fee' => $percentage,
                'status' => Transaction::TRANS_STATUS_SUCCESSFUL,
                'narration' => "Loan repayment transfer to {$model->application->loan->user->firstname} {$model->application->loan->user->lastname}"
            ]);

            if (!$trans->isSuccessful()) $this->getSignal()->undoOperation($trans->getQueryError() ?: "Sorry we couldn't transact repayment at this time. Let's try it again later.");
            else {

                if ($model->application->is_repaid) {

                    $model->application->loan->update(['status' => Loan::STATUS_COMPLETED]);
                    $model->application->update(['status' => LoanApplication::STATUS_REPAID]);

                    $amount = Item::cents($model->amount)->getFactor(true);
                    Notification::create("Loan Repayment",
                        "{$model->payer->firstname} has made a repayment of {$amount} NGN on your loan, which has now completed their repayment.",
                        "loanRepaymentTransaction", $model->application->loan->user->id, $model->application, $model->payer->picture);

                } else {
                    $amount = Item::cents($model->amount)->getFactor(true);
                    Notification::create("Loan Repayment",
                        "{$model->payer->firstname} has made a repayment of {$amount} NGN on your loan",
                        "loanRepaymentTransaction", $model->application->loan->user->id, $model->application, $model->payer->picture);
                }
            }

        } elseif ($model->application->loan->loan_type == Loan::LOAN_TYPE_REQUEST) {

            $model->application->load('applicant')->applicant->load('wallet');

            $trans = db()->insert('transactions', [
                'uuid' => Str::uuidv4(),
                'user_id' => $model->user_id,
                'type' => Transaction::TRANS_TYPE_TRANSFER,
                'to_wallet_id' => $model->application->applicant->wallet->id,
                'from_wallet_id' => $model->payer->wallet->id,
                'gateway_reference' => $model->uuid,
                'direction' => 'w2w',
                'amount' => $model->getFloat('amount'),
                'creditor_fee' => $percentage,
                'status' => Transaction::TRANS_STATUS_SUCCESSFUL,
                'narration' => "Loan repayment transfer to {$model->application->applicant->firstname} {$model->application->applicant->lastname}"
            ]);

            if (!$trans->isSuccessful()) $this->getSignal()->undoOperation($trans->getQueryError() ?: "Sorry we couldn't transact repayment at this time. Let's try it again later.");
            else {

                if ($model->application->is_repaid) {

                    $model->application->loan->update(['status' => Loan::STATUS_COMPLETED]);
                    $model->application->update(['status' => LoanApplication::STATUS_REPAID]);

                    $amount = Item::cents($model->amount)->getFactor(true);
                    Notification::create("Loan Repayment",
                        "{$model->payer->firstname} has made a repayment of {$amount} NGN on your loan, which has now completed their repayment.",
                        "loanRepaymentTransaction", $model->application->applicant->id, $model->application, $model->payer->picture);

                } else {

                    $amount = Item::cents($model->amount)->getFactor(true);
                    Notification::create("Loan Repayment",
                        "{$model->payer->firstname} has made a repayment of {$amount} NGN on your loan",
                        "loanRepaymentTransaction", $model->application->applicant->id, $model->application, $model->payer->picture);
                }
            }

        }
    }

    /**
     * @inheritDoc
     */
    public function onCreateFailed(Model $model, array $errors, $errorCode)
    {
        // TODO: Implement onCreateFailed() method.
    }

    /**
     * @inheritDoc
     */
    public function onCreateRetryStarted(Model $model)
    {
        // TODO: Implement onCreateRetryStarted() method.
    }

    /**
     * @inheritDoc
     */
    public function onCreateRetryComplete(Model $model, bool $status, int $attempts)
    {
        // TODO: Implement onCreateRetryComplete() method.
    }

    /**
     * @inheritDoc
     */
    public function onUpdating(ModelCollection $newModels, ModelCollection $oldModels)
    {
        // TODO: Implement onUpdating() method.
    }

    /**
     * @inheritDoc
     */
    public function onUpdated(ModelCollection $newModels, ModelCollection $oldModels)
    {
        // TODO: Implement onUpdated() method.
    }

    /**
     * @inheritDoc
     */
    public function onUpdateFailed(ModelCollection $models, array $errors, $errorCode)
    {
        // TODO: Implement onUpdateFailed() method.
    }

    /**
     * @inheritDoc
     */
    public function onUpdateRetryStarted(ModelCollection $models)
    {
        // TODO: Implement onUpdateRetryStarted() method.
    }

    /**
     * @inheritDoc
     */
    public function onUpdateRetryComplete(ModelCollection $models, bool $status, int $attempts)
    {
        // TODO: Implement onUpdateRetryComplete() method.
    }

    /**
     * @inheritDoc
     */
    public function onDeleting(ModelCollection $models)
    {
        // TODO: Implement onDeleting() method.
    }

    /**
     * @inheritDoc
     */
    public function onDeleted(ModelCollection $models)
    {
        // TODO: Implement onDeleted() method.
    }

    /**
     * @inheritDoc
     */
    public function onDeleteFailed(ModelCollection $models, array $errors, $errorCode)
    {
        // TODO: Implement onDeleteFailed() method.
    }

    /**
     * @inheritDoc
     */
    public function onDeleteRetryStarted(ModelCollection $models)
    {
        // TODO: Implement onDeleteRetryStarted() method.
    }

    /**
     * @inheritDoc
     */
    public function onDeleteRetryComplete(ModelCollection $models, bool $status, int $attempts)
    {
        // TODO: Implement onDeleteRetryComplete() method.
    }
}