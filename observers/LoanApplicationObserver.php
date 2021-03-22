<?php


namespace observers;


use model\LoanApplication;
use model\Loan;
use model\Notification;
use model\Transaction;
use que\common\exception\QueException;
use que\database\interfaces\model\Model;
use que\database\model\ModelCollection;
use que\database\observer\Observer;
use que\mail\Mail;
use que\mail\Mailer;
use que\support\Str;
use que\utility\money\Item;

class LoanApplicationObserver extends Observer
{

    /**
     * @inheritDoc
     */
    public function onCreating(Model $model)
    {
        // TODO: Implement onCreating() method.
        if (!$model instanceof LoanApplication) $model = LoanApplication::cast($model);

        if (!$model->has('loan')) $model->load('loan');

        if ($model->getModel('loan')->getFloat('amount') > user('max_loan_amount')) {
            $this->getSignal()->discontinueOperation("Sorry, you're not qualified to apply for that loan.");
        }
    }

    /**
     * @inheritDoc
     */
    public function onCreated(Model $model)
    {
        // TODO: Implement onCreated() method.
        try {

            if (!$model instanceof LoanApplication) $model = LoanApplication::cast($model);

            if (!$model->has('loan')) $model->load('loan');

            if ($model->loan->getInt('loan_type') == Loan::LOAN_TYPE_REQUEST) {

                $trans = db()->insert('transactions', [
                    'uuid' => Str::uuidv4(),
                    'user_id' => $model->getInt('user_id'),
                    'type' => Transaction::TRANS_TYPE_CHARGE,
                    'direction' => "w2s",
                    'gateway_reference' => $model->getValue('uuid'),
                    'amount' => $model->getFloat('amount'),
                    'status' => Transaction::TRANS_STATUS_PROCESSING,
                    'narration' => "Loan request application charge transaction"
                ]);

                if (!$trans->isSuccessful()) {
                    $this->getSignal()->undoOperation($trans->getQueryError() ?: "Unable to transact at this time");
                    return;
                }
            }

            $amount = Item::cents($model->loan->amount)->getFactor(true);

            Notification::create("Loan Application",
                "One {$model->applicant->firstname} {$model->applicant->lastname} has applied for you loan {$model->loan->type} of {$amount} NGN",
                "loanApplicant", $model->loan->user->id, $model->loan, $model->applicant->picture);

            $mailer = Mailer::getInstance();

            $mail = new Mail('loan-application');
            $mail->addRecipient($model->loan->user->email,
                $name = "{$model->loan->user->firstname} {$model->loan->user->lastname}");
            $mail->setSubject("Loan Application");
            $mail->setData([
                'title' => 'Loan Application',
                'name' => $name,
                'type' => $model->loan->type,
                'amount' => $amount,
                'applicant' => "{$model->applicant->firstname} {$model->applicant->lastname}",
                'app_name' => config('template.app.header.name'),
                'year' => APP_YEAR,
                'logo' => base_url(config('template.app.header.logo.small.origin')),
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

            if (!$newModel instanceof LoanApplication) $newModel = LoanApplication::cast($newModel);

            if (!$newModel->has('loan')) $newModel->load('loan');

            if ($newModel->loan->getInt('loan_type') == Loan::LOAN_TYPE_REQUEST) {

                if ($newModel->getInt('status') == LoanApplication::STATUS_GRANTED) {
                    $update = $newModel->loan->update(['status' => Loan::STATUS_GRANTED]);
                    if (!$update?->isSuccessful()) $this->getSignal()->undoOperation($update?->getQueryError() ?: "Unable to grant loan at this time");
                    else {
                        $newModel->loan->load('user');
                        Notification::create("Granted Loan",
                            "Your application for {$newModel->loan->user->firstname}'s loan request has been granted",
                            "loanApplicationDetails", $newModel->applicant->id, $newModel);
                    }
                } elseif ($newModel->getBool('is_active') == false || $newModel->getInt('status') == LoanApplication::STATUS_REJECTED) {

                    $trans = db()->find('transactions', $newModel->getValue('uuid'), 'gateway_reference');
                    $trans->getFirstWithModel()?->update(['status' => Transaction::TRANS_STATUS_REVERSED]);

                    $newModel->loan->load('user');
                    Notification::create("Rejected Loan Application",
                        "Your application for {$newModel->loan->user->firstname}'s loan request has been rejected",
                        "loanApplicationDetails", $newModel->applicant->id, $newModel);
                }

            } elseif ($newModel->loan->getInt('loan_type') == Loan::LOAN_TYPE_OFFER) {
                if ($newModel->getInt('status') == LoanApplication::STATUS_GRANTED) {
                    $update = $newModel->loan->update(['status' => Loan::STATUS_GRANTED]);
                    if (!$update?->isSuccessful()) $this->getSignal()->undoOperation($update?->getQueryError() ?: "Unable to grant loan at this time");
                    else {

                        $newModel->loan->load('user');
                        Notification::create("Granted Loan",
                            "Your application for {$newModel->loan->user->firstname}'s loan offer has been granted",
                            "loanApplicationDetails", $newModel->applicant->id, $newModel);
                    }
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