<?php


namespace observers;


use model\Loan;
use model\LoanApplication;
use model\Notification;
use model\Transaction;
use que\database\interfaces\Builder;
use que\database\interfaces\model\Model;
use que\database\model\ModelCollection;
use que\database\observer\Observer;
use que\support\Str;

class LoanObserver extends Observer
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
        if ($model->getInt('loan_type') == Loan::LOAN_TYPE_OFFER) {

            $trans = db()->insert('transactions', [
                'uuid' => Str::uuidv4(),
                'user_id' => $model->getInt('user_id'),
                'type' => Transaction::TRANS_TYPE_CHARGE,
                'direction' => "w2s",
                'gateway_reference' => $model->getValue('uuid'),
                'amount' => $model->getFloat('amount'),
                'status' => Transaction::TRANS_STATUS_PROCESSING,
                'narration' => "Loan offer charge transaction"
            ]);

            if (!$trans->isSuccessful()) $this->getSignal()->undoOperation($trans->getQueryError() ?: "Unable to transact at this time");
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
        $newModels->map(function (Model $newModel) use ($oldModels) {

            if (!$newModel instanceof Loan) $newModel = Loan::cast($newModel);

            $oldModel = $oldModels->find(function (Model $m) use ($newModel) {
                return $newModel->validate('id')->isEqual($m->getValue('id'));
            });

            if ($oldModel->getInt('status') == Loan::STATUS_GRANTED) {

                if ((($newModel->getInt('status') != Loan::STATUS_GRANTED &&
                        $newModel->getInt('status') != Loan::STATUS_COMPLETED) || !$newModel->getBool('is_active'))) {

                    $this->getSignal()->undoOperation("You can't invalidate an already successful/granted loan.");
                    return;
                }

            }

            if ($newModel->getInt('status') == Loan::STATUS_GRANTED) {

                $application = db()->find('loan_applications', $newModel->getValue('uuid'), 'loan_id', function (Builder $builder) {
                    $builder->where('status', LoanApplication::STATUS_GRANTED);
                    $builder->where('is_active', true);
                });
                $application->setModelKey('loanApplicationModel');
                $application = $application->getFirstWithModel();
                $application->load('loan');
                $application->applicant->load('wallet');
                $application->loan->user->load('wallet');

                if ($newModel->getInt('loan_type') == Loan::LOAN_TYPE_OFFER) {

                    $trans = db()->find('transactions', $newModel->getValue('uuid'), 'gateway_reference');

                    if ($trans->isSuccessful()) {

                        $transModel = $trans->getFirstWithModel();

                        if ($transModel->getFloat('amount') == $newModel->amount) {

                            $update = $transModel->update(['type' => Transaction::TRANS_TYPE_TRANSFER,
                                'from_wallet_id' => $application->loan->user->wallet->id,
                                'to_wallet_id' => $application->applicant->wallet->id,
                                'status' => Transaction::TRANS_STATUS_SUCCESSFUL]);

                            if (!$update?->isSuccessful()) $this->getSignal()->undoOperation($update?->getQueryError() ?: "Unable to transact at this time");

                        } else {
                            $this->getSignal()->undoOperation("The amount found for the transaction on this loan does not match the loan amount.");
                        }

                    } else {
                        $this->getSignal()->undoOperation("No transaction was found for this loan.");
                    }


                } elseif ($newModel->getInt('loan_type') == Loan::LOAN_TYPE_REQUEST) {

                    $trans = db()->find('transactions', $application->getValue('uuid'), 'gateway_reference');

                    if ($trans->isSuccessful()) {

                        $transModel = $trans->getFirstWithModel();

                        if ($transModel->getFloat('amount') == $newModel->amount) {

                            $update = $transModel->update(['type' => Transaction::TRANS_TYPE_TRANSFER,
                                'from_wallet_id' => $application->applicant->wallet->id,
                                'to_wallet_id' => $application->loan->user->wallet->id,
                                'status' => Transaction::TRANS_STATUS_SUCCESSFUL]);

                            if (!$update?->isSuccessful()) $this->getSignal()->undoOperation($update?->getQueryError() ?: "Unable to transact at this time");

                        } else {
                            $this->getSignal()->undoOperation("The amount found for the transaction on this loan application does not match the loan amount.");
                        }

                    } else {
                        $this->getSignal()->undoOperation("No transaction was found for this loan application.");
                    }

                }
            } elseif ($newModel->getInt('status') == Loan::STATUS_REVOKED) {

                if ($newModel->getInt('loan_type') == Loan::LOAN_TYPE_OFFER) {

                    $trans = db()->find('transactions', $newModel->getValue('uuid'), 'gateway_reference');

                    if ($trans->isSuccessful()) {

                        $transModel = $trans->getFirstWithModel();

                        $update = $transModel->update(['status' => Transaction::TRANS_STATUS_REVERSED]);

                        if (!$update?->isSuccessful()) $this->getSignal()->undoOperation($update?->getQueryError() ?: "Unable to transact at this time");

                    } else {
                        $this->getSignal()->undoOperation("No transaction was found for this loan.");
                    }


                }

                $application = db()->findAll('loan_applications', $newModel->getValue('uuid'), 'loan_id',
                    function (Builder $builder) {
                        $builder->where('status', LoanApplication::STATUS_AWAITING);
                        $builder->where('is_active', true);
                    });

                if ($application->isSuccessful()) {

                    $applications = $application->getAllWithModel();

                    $update = $applications->update(['status' => LoanApplication::STATUS_REJECTED]);

                    if (!$update) $this->getSignal()->undoOperation("Unable to transact at this time");

                }

            } elseif ($newModel->getInt('status') == Loan::STATUS_AWAITING) {
                Notification::create("Loan Approved",
                    "Your loan {$newModel->loan_type_readable} has been approved",
                    "loanDetails", $newModel->user_id, $newModel);
            } elseif ($newModel->getInt('status') == Loan::STATUS_REJECTED) {
                Notification::create("Loan Rejected",
                    "Your loan {$newModel->loan_type_readable} has been rejected",
                    "loanDetails", $newModel->user_id, $newModel);
            }
        });

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
