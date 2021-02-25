<?php


namespace observers;


use access\Verification;
use que\common\exception\QueException;
use que\common\exception\RouteException;
use que\database\interfaces\model\Model;
use que\database\model\ModelCollection;
use que\database\observer\Observer;
use que\http\HTTP;
use que\mail\Mail;
use que\mail\Mailer;
use que\utility\hash\Hash;
use utility\sms\SmartSms;

class VerificationObserver extends Observer
{
    use SmartSms;

    public function onCreating(Model $model)
    {
        // TODO: Implement onCreating() method.
    }

    public function onCreated(Model $model)
    {
        // TODO: Implement onCreated() method.
        try {

            if ($model->validate('type')->isEqual(Verification::VERIFICATION_TYPE_EMAIL)) {

                $mailer = Mailer::getInstance();

                $appName = config('template.app.header.name');
                $mail = new Mail('verify');
                $mail->addRecipient($model->getValue('data'));
                $mail->setSubject("{$appName} Email Verification");
                $mail->setData([
                    'title' => 'Email Verification',
                    'otp' => $model->getValue('code'),
                    'expire' => get_date('h:i a M jS, Y', $model->getValue('expiration'))
                ]);
                $mail->setHtmlPath('email/html/verification-otp.tpl');
                $mail->setBodyPath('email/text/verification-otp.txt');

                $mailer->addMail($mail);
                $mailer->prepare('verify');
                if (!$mailer->dispatch('verify')) throw new QueException($mailer->getError('verify'));

            } elseif ($model->validate('type')->isEqual(Verification::VERIFICATION_TYPE_PHONE)) {

                $composer = composer(false);
                $composer->setTmpFileName('sms/verification-otp.txt');
                $composer->dataExtra([
                    'title' => 'Phone Verification',
                    'otp' => $model->getValue('code'),
                    'expire' => get_date('h:i a M jS, Y', $model->getValue('expiration'))
                ]);

                $sms = $this->send($composer->prepare()->renderWithSmarty(true), $model->getValue('data'));
                $sms = $sms->getResponseArray();

//                if (($sms['code'] ?? '01') != '1000') throw new QueException(($sms['comment'] ?? null) ?: "Sorry we couldn't send you an SMS at this time.");

            } else throw new QueException("Invalid verification type");

            $model->update(['code' => Hash::sha($model->getValue('code'))]);

        } catch (QueException $e) {
            $this->getSignal()->undoOperation($e->getMessage());
        }
    }

    public function onCreateFailed(Model $model, array $errors, $errorCode)
    {
        // TODO: Implement onCreateFailed() method.
    }

    public function onCreateRetryStarted(Model $model)
    {
        // TODO: Implement onCreateRetryStarted() method.
    }

    public function onCreateRetryComplete(Model $model, bool $status, int $attempts)
    {
        // TODO: Implement onCreateRetryComplete() method.
    }

    public function onUpdating(ModelCollection $newModels, ModelCollection $oldModels)
    {
        // TODO: Implement onUpdating() method.
    }

    public function onUpdated(ModelCollection $newModels, ModelCollection $oldModels)
    {
        // TODO: Implement onUpdated() method.
        $newModels->map(function (Model $newModel) use ($oldModels) {

            $oldModel = $oldModels->find(function (Model $m) use ($newModel) {
                return $newModel->validate('id')->isEqual($m->getValue('id'));
            });

            if ($oldModel->validate('is_verified')->isEqual(false) &&
                $newModel->validate('is_verified')->isEqual(true)) {

                try {

                    if ($newModel->validate('type')->isEqual(Verification::VERIFICATION_TYPE_EMAIL)) {

                        $mailer = Mailer::getInstance();

                        $appName = config('template.app.header.name');
                        $mail = new Mail('verified');
                        $mail->addRecipient($newModel->getValue('data'));
                        $mail->setSubject("{$appName} Email Verified");
                        $mail->setData([
                            'title' => 'Email Verified',
                            'type' => $newModel->getValue('type'),
                            'data' => $newModel->getValue('data')
                        ]);
                        $mail->setHtmlPath('email/html/verified.tpl');
                        $mail->setBodyPath('email/text/verified.txt');

                        $mailer->addMail($mail);
                        $mailer->prepare('verified');
                        $mailer->dispatch('verified');

                    } elseif ($newModel->validate('type')->isEqual(Verification::VERIFICATION_TYPE_PHONE)) {

                        $composer = composer(false);
                        $composer->setTmpFileName('sms/verified.txt');
                        $composer->dataExtra([
                            'title' => 'Phone Verified',
                            'type' => $newModel->getValue('type'),
                            'data' => $newModel->getValue('data')
                        ]);

                        $this->send($composer->prepare()->renderWithSmarty(true), $newModel->getValue('data'));

                    } else throw new QueException("Invalid verification type");

                } catch (QueException $e) {
                    $this->getSignal()->undoOperation($e->getMessage());
                }

            }

        });
    }

    public function onUpdateFailed(ModelCollection $models, array $errors, $errorCode)
    {
        // TODO: Implement onUpdateFailed() method.
    }

    public function onUpdateRetryStarted(ModelCollection $models)
    {
        // TODO: Implement onUpdateRetryStarted() method.
    }

    public function onUpdateRetryComplete(ModelCollection $models, bool $status, int $attempts)
    {
        // TODO: Implement onUpdateRetryComplete() method.
    }

    public function onDeleting(ModelCollection $models)
    {
        // TODO: Implement onDeleting() method.
    }

    public function onDeleted(ModelCollection $models)
    {
        // TODO: Implement onDeleted() method.
    }

    public function onDeleteFailed(ModelCollection $models, array $errors, $errorCode)
    {
        // TODO: Implement onDeleteFailed() method.
    }

    public function onDeleteRetryStarted(ModelCollection $models)
    {
        // TODO: Implement onDeleteRetryStarted() method.
    }

    public function onDeleteRetryComplete(ModelCollection $models, bool $status, int $attempts)
    {
        // TODO: Implement onDeleteRetryComplete() method.
    }
}