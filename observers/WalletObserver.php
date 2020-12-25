<?php


namespace observers;


use que\common\exception\QueException;
use que\database\interfaces\model\Model;
use que\database\model\ModelCollection;
use que\database\observer\ObserverSignal;
use que\mail\Mail;
use que\mail\Mailer;
use que\user\XUser;

class WalletObserver implements \que\database\interfaces\observer\Observer
{
    private ObserverSignal $signal;

    /**
     * @inheritDoc
     */
    public function __construct(ObserverSignal $signal)
    {
        $this->signal = $signal;
    }

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

            $mailer = Mailer::getInstance();

            $mail = new Mail('wallet-created');
            $user = XUser::getUser($model->getInt('user_id'), 'model');
            $mail->addRecipient($user->getValue('email'),
                $name = "{$user->getValue('firstname')} {$user->getValue('lastname')}");
            $mail->setSubject("Wallet Created");
            $mail->setData([
                'title' => 'Wallet Created',
                'name' => $name,
                'app_name' => config('template.app.header.name')
            ]);
            $mail->setHtmlPath('email/html/wallet-created.tpl');
            $mail->setBodyPath('email/text/wallet-created.txt');
            $mail->setFrom('noreply@moneydrop.com', 'MoneyDrop');

            $mailer->addMail($mail);
            $mailer->prepare('wallet-created');
            $mailer->dispatch('wallet-created');

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
        $newModels->map(function (Model $model) use ($oldModels) {

            $oldModel = $oldModels->find(function (Model $m) use ($model) {
                return $model->validate('id')->isEqual($m->getValue('id'));
            });

            try {

                $isCredit = $model->validate('available_balance')
                    ->isFloatingNumberGreaterThan($oldModel->getFloat('available_balance'));

                if ($isCredit) {
                    $amount = $model->getFloat('available_balance') - $oldModel->getFloat('available_balance');
                } else {
                    $amount = $oldModel->getFloat('available_balance') - $model->getFloat('available_balance');
                }

                $mailer = Mailer::getInstance();

                $mail = new Mail('wallet-updated');
                $user = XUser::getUser($model->getInt('user_id'), 'model');
                $mail->addRecipient($user->getValue('email'),
                    $name = "{$user->getValue('firstname')} {$user->getValue('lastname')}");
                $mail->setSubject("Wallet " . ($isCredit ? "Credited" : "Debited"));
                $mail->setData([
                    'title' => "Wallet " . ($isCredit ? "Credited" : "Debited"),
                    'name' => $name,
                    'action' => ($isCredit ? "credited" : "debited"),
                    'amount' => number_format($amount, 2),
                    'balance' => number_format($model->getFloat('available_balance'), 2),
                    'currency' => 'NGN',
                    'app_name' => config('template.app.header.name')
                ]);
                $mail->setHtmlPath('email/html/wallet-updated.tpl');
                $mail->setBodyPath('email/text/wallet-updated.txt');
                $mail->setFrom('noreply@moneydrop.com', 'MoneyDrop');

                $mailer->addMail($mail);
                $mailer->prepare('wallet-updated');
                $mailer->dispatch('wallet-updated');

            } catch (QueException $e) {
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

    /**
     * @inheritDoc
     */
    public function getSignal(): ObserverSignal
    {
        // TODO: Implement getSignal() method.
        return $this->signal;
    }
}