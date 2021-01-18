<?php


namespace observers;


use custom\model\LoanModel;
use loan\Loan;
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
                'type' => TRANSACTION_CHARGE,
                'direction' => "w2s",
                'gateway_reference' => $model->getValue('uuid'),
                'amount' => $model->getFloat('amount'),
                'status' => APPROVAL_PROCESSING,
                'narration' => "Loan offer charge transaction"
            ]);

            if (!$trans->isSuccessful()) $this->getSignal()->undoOperation("Unable to transact at this time");
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

            if (!$newModel instanceof LoanModel) $newModel = LoanModel::cast($newModel);


            log_err(['loan' => $newModel]);

            if ($newModel->getInt('loan_type') == Loan::LOAN_TYPE_OFFER) {

                if ($newModel->getInt('status') == STATE_SUCCESSFUL) {

                    $trans = db()->find('transactions', $newModel->getValue('uuid'), 'gateway_reference');

                    $application = db()->find('loan_applications', $newModel->getValue('uuid'), 'loan_id', function (Builder $builder) {
                        $builder->where('is_granted', true);
                        $builder->where('is_active', true);
                    });
                    $application->setModelKey('loanApplicationModel');
                    $application = $application->getFirstWithModel();
                    $application->load('loan');
                    $application->applicant->load('wallet');
                    $application->loan->user->load('wallet');

                    $trans = $trans->getFirstWithModel();

                    $status = $trans->update(['type' => TRANSACTION_TRANSFER,
                        'from_wallet_id' => $application->loan->user->wallet->id,
                        'to_wallet_id' => $application->applicant->wallet->id,
                        'status' => APPROVAL_SUCCESSFUL]);

                    if (!$status) $this->getSignal()->undoOperation("Unable to transact at this time");
                }
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
