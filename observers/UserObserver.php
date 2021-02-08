<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 5/10/2020
 * Time: 11:09 PM
 */

namespace observers;


use que\common\exception\QueException;
use que\database\interfaces\model\Model;
use que\database\model\ModelCollection;
use que\database\observer\Observer;
use que\http\HTTP;
use que\mail\Mail;
use que\mail\Mailer;

class UserObserver extends Observer
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

            $mailer = Mailer::getInstance();

            $mail = new Mail('register');
            $mail->addRecipient($model->getValue('email'),
                $name = "{$model->getValue('firstname')} {$model->getValue('lastname')}");
            $mail->setSubject("Successful Registration");
            $mail->setData([
                'title' => 'Successful Registration',
                'name' => $name,
                'app_name' => config('template.app.header.name'),
                'year' => APP_YEAR,
                'logo' => base_url(config('template.app.header.logo.small.origin')),
            ]);
            $mail->setHtmlPath('email/html/successful-registration-notice.tpl');
            $mail->setBodyPath('email/text/successful-registration-notice.txt');
            $mail->setFrom('account@moneydrop.com', 'MoneyDrop');

            $mailer->addMail($mail);
            $mailer->prepare('register');
            $mailer->dispatch('register');

        } catch (QueException $e) {
        }
    }

    /**
     * @inheritDoc
     */
    public function onCreateFailed(Model $model, array $errors, $errorCode)
    {
        // TODO: Implement onCreateFailed() method.
        $this->getSignal()->retryOperation(2);
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

            if ($model->validate('password')->isNotEqual($oldModel->getValue('password'))) {

                try {

                    $mailer = Mailer::getInstance();

                    $mail = new Mail('reset');
                    $mail->addRecipient($model->getValue('email'),
                        $name = "{$model->getValue('firstname')} {$model->getValue('lastname')}");
                    $mail->setSubject("Password Reset");
                    $mail->setData([
                        'title' => 'Password Reset',
                        'name' => $name,
                        'app_name' => config('template.app.header.name'),
                        'year' => APP_YEAR,
                        'logo' => base_url(config('template.app.header.logo.small.origin')),
                    ]);
                    $mail->setHtmlPath('email/html/reset-password.tpl');
                    $mail->setBodyPath('email/text/reset-password.txt');

                    $mailer->addMail($mail);
                    $mailer->prepare('reset');
                    $mailer->dispatch('reset');

                } catch (QueException $e) {
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
        $this->getSignal()->retryOperation(1);
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
        $this->getSignal()->retryOperation(3);
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
