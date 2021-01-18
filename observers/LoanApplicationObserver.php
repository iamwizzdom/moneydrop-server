<?php


namespace observers;


use custom\model\LoanApplicationModel;
use loan\Loan;
use que\common\exception\QueException;
use que\database\interfaces\model\Model;
use que\database\model\ModelCollection;
use que\database\observer\Observer;
use que\mail\Mail;
use que\mail\Mailer;
use que\support\Str;

class LoanApplicationObserver extends Observer
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
        try {

            if (!$model instanceof LoanApplicationModel) $model = LoanApplicationModel::cast($model);

            if (!$model->has('loan')) $model->load('loan');

            if ($model->loan->getInt('loan_type') == Loan::LOAN_TYPE_REQUEST) {

                $trans = db()->insert('transactions', [
                    'uuid' => Str::uuidv4(),
                    'user_id' => $model->getInt('user_id'),
                    'type' => TRANSACTION_CHARGE,
                    'direction' => "w2s",
                    'gateway_reference' => $model->getValue('uuid'),
                    'amount' => $model->getFloat('amount'),
                    'status' => APPROVAL_PROCESSING,
                    'narration' => "Loan request application charge transaction"
                ]);

                if (!$trans->isSuccessful()) {
                    $this->getSignal()->undoOperation("Unable to transact at this time");
                    return;
                }
            }

            $mailer = Mailer::getInstance();

            $mail = new Mail('loan-application');
            $mail->addRecipient($model->loan->user->email,
                $name = "{$model->loan->user->firstname} {$model->loan->user->lastname}");
            $mail->setSubject("Loan Application");
            $mail->setData([
                'title' => 'Loan Application',
                'name' => $name,
                'type' => $model->loan->type,
                'amount' => "NGN {$model->loan->amount}",
                'applicant' => "{$model->applicant->firstname} {$model->applicant->lastname}",
                'app_name' => config('template.app.header.name')
            ]);
            $mail->setHtmlPath('email/html/loan-application.tpl');
            $mail->setBodyPath('email/text/loan-application.txt');
            $mail->setFrom('noreply@moneydrop.com', 'MoneyDrop');

            $mailer->addMail($mail);
            $mailer->prepare('loan-application');
            $mailer->dispatch('loan-application');

        } catch (QueException $e) {
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

            if (!$newModel instanceof LoanApplicationModel) $newModel = LoanApplicationModel::cast($newModel);

            if (!$newModel->has('loan')) $newModel->load('loan');

            if ($newModel->loan->getInt('loan_type') == Loan::LOAN_TYPE_REQUEST) {

                $trans = db()->find('transactions', $newModel->getValue('uuid'), 'gateway_reference');
                $trans = $trans->getFirstWithModel();

                if (!$newModel->getInt('is_active')) {
                    $status = $trans->update(['status' => APPROVAL_REVERSED]);
                } elseif ($newModel->getBool('is_granted')) {
                    $status = $trans->update(['status' => APPROVAL_SUCCESSFUL]);
                }

                if (!$status) $this->getSignal()->undoOperation("Unable to transact at this time");

            } elseif ($newModel->loan->getInt('loan_type') == Loan::LOAN_TYPE_OFFER) {
                if ($newModel->getBool('is_granted')) {
                    $newModel->loan->update(['status' => STATE_SUCCESSFUL]);
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