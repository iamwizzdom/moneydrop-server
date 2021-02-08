<?php


namespace access;


use DateTime;
use que\common\exception\BaseException;
use que\common\exception\QueException;
use que\common\manager\Manager;
use que\common\structure\Api;
use que\database\interfaces\Builder;
use que\http\HTTP;
use que\http\input\Input;
use que\utility\hash\Hash;

class Verification extends Manager implements Api
{
    const VERIFICATION_ACTION_REQUEST = 'request';
    const VERIFICATION_ACTION_VERIFY = 'verify';
    const VERIFICATION_TYPE_EMAIL = 'email';
    const VERIFICATION_TYPE_PHONE = 'phone';

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        $validator = $this->validator($input);
        try {

            switch ($input['route.params.type']) {
                case self::VERIFICATION_TYPE_EMAIL:

                    switch ($input['route.params.action']) {
                        case self::VERIFICATION_ACTION_REQUEST:

                            $condition = $validator->validate('email')->isEmail("Please enter a valid email address")->toLower();

                            if ($validator->hasError()) throw $this->baseException(
                                "The inputted data is invalid", "Verification failed", HTTP::UNPROCESSABLE_ENTITY);

                            $condition->isNotFoundInDB('verifications', 'data',
                                'That email has already been verified.', function (Builder $builder) {
                                    $builder->where('type', self::VERIFICATION_TYPE_EMAIL);
                                    $builder->where('is_verified', true);
                                    $builder->where('is_active', true);
                                });

                            if ($validator->hasError()) throw $this->baseException(
                                current($validator->getError('email')), "Verified", HTTP::CONFLICT);

                            $insert = $this->db()->select('*')->table('verifications')
                                ->where('data', $validator->getValue('email'))
                                ->where('type', self::VERIFICATION_TYPE_EMAIL)
                                ->where('is_active', true)->exec();

                            if ($insert->isSuccessful()) {

                                if (($model = $insert->getFirstWithModel())->validate('expiration')->isDateLessThan(
                                    DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', APP_TIME)),
                                    'Y-m-d H:i:s')) $model->update(['is_active' => false]); else goto RESPOND;

                            }

                            $otp = mt_rand(1111, 9999);

                            $this->db()->transStart();

                            $insert = $this->db()->insert('verifications', [
                                'data' => $validator->getValue('email'),
                                'code' => Hash::sha($otp),
                                'type' => self::VERIFICATION_TYPE_EMAIL,
                                'expiration' => date('Y-m-d H:i:s', APP_TIME + TIMEOUT_TEN_MIN),
                                'is_verified' => false,
                                'is_active' => true
                            ]);

                            if (!$insert->isSuccessful()) throw $this->baseException(
                                "Sorry, we couldn't verify your email at this time, please try again later.",
                                "Verification failed", HTTP::EXPECTATION_FAILED);

                            try {

                                $mailer = $this->mailer();

                                $mail = $this->mail('verify');
                                $mail->addRecipient($validator->getValue('email'));
                                $mail->setSubject(config('template.app.header.name') . " Email Verification");
                                $mail->setData([
                                    'title' => 'Email Confirmation',
                                    'otp' => $otp,
                                    'year' => APP_YEAR,
                                    'expire' => get_date('h:i a M jS, Y', $insert->getFirstWithModel()->getValue('expiration'))
                                ]);
                                $mail->setHtmlPath('email/html/otp.tpl');
                                $mail->setBodyPath('email/text/otp.txt');

                                $mailer->addMail($mail);
                                $mailer->prepare('verify');
                                if (!$mailer->dispatch('verify'))
                                    throw new QueException($mailer->getError('verify'));

                            } catch (QueException $e) {
                                $this->db()->transRollBack();
                                throw $this->baseException($e->getMessage(),
                                    "Verification failed", HTTP::EXPECTATION_FAILED, false);
                            }

                            $this->db()->transComplete();

                            RESPOND:

                            $model = $insert->getFirstWithModel();

                            return $this->http()->output()->json([
                                'status' => true,
                                'code' => HTTP::OK,
                                'title' => 'Verification OTP Sent',
                                'message' => "Verify your email with the OTP we sent to {$validator->getValue('email')}",
                                'expire' => strtotime($model->getValue('expiration')) - APP_TIME,
                                'email' => $model->getValue('data')
                            ]);

                            break;
                        case self::VERIFICATION_ACTION_VERIFY:

                            $validator->validate('email')->isEmail("Please enter a valid email address")->toLower()
                                ->isFoundInDB('verifications', 'data', "That email has not requested for verification yet.");

                            $condition = $validator->validate('code')->isNumber('Please enter a valid verification code')->hash()
                                ->isFoundInDB('verifications', 'code', "That verification code does not exist");

                            if ($validator->hasError()) throw $this->baseException(
                                "The inputted data is invalid", "Verification failed", HTTP::UNPROCESSABLE_ENTITY);

                            $condition->isNotFoundInDB('verifications', 'data', 'That email has already been verified.',
                                function (Builder $builder) {
                                    $builder->where('type', self::VERIFICATION_TYPE_EMAIL);
                                    $builder->where('is_verified', true);
                                });

                            if ($validator->hasError()) throw $this->baseException(
                                "The inputted data already exist", "Verified", HTTP::CONFLICT);

                            $verify = $this->db()->select()->table('verifications')
                                ->where('data', $validator->getValue('email'))
                                ->where('code', $validator->getValue('code'))
                                ->where('type', self::VERIFICATION_TYPE_EMAIL)
                                ->where('is_active', true)
                                ->exec();

                            if (!$verify->isSuccessful()) throw $this->baseException(
                                "No active verification request was found for that email with the specified code.", "Verification failed", HTTP::NOT_FOUND);

                            $verify = $verify->getFirstWithModel();

                            if ($verify->validate('expiration')->isDateLessThan(
                                DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', APP_TIME)))) {

                                $verify->update(['is_active' => false]);

                                throw $this->baseException(
                                    "That verification code has expired.", "Verification failed", HTTP::EXPECTATION_FAILED);
                            }

                            $this->db()->transStart();

                            $verified = $verify->update(['is_verified' => true]);

                            if (!$verified?->isSuccessful()) throw $this->baseException(
                                "Sorry, we could not verify that email at this time, please try again later.",
                                "Verification failed", HTTP::EXPECTATION_FAILED);

                            try {

                                $mailer = $this->mailer();

                                $mail = $this->mail('verified');
                                $mail->addRecipient($validator->getValue('email'));
                                $mail->setSubject(($app_name = config('template.app.header.name')) . " Email Verified");
                                $mail->setData([
                                    'title' => 'Email Verified',
                                    'type' => $verify->getValue('type'),
                                    'data' => $verify->getValue('data'),
                                    'app_name' => $app_name,
                                    'year' => APP_YEAR,
                                    'logo' => base_url(config('template.app.header.logo.small.origin')),
                                ]);
                                $mail->setHtmlPath('email/html/verified.tpl');
                                $mail->setBodyPath('email/text/verified.txt');

                                $mailer->addMail($mail);
                                $mailer->prepare('verified');
                                if (!$mailer->dispatch('verified'))
                                    throw new QueException($mailer->getError('verified'));

                            } catch (QueException $e) {
                                $this->db()->transRollBack();
                                throw $this->baseException($e->getMessage(),
                                    "Verification failed", HTTP::EXPECTATION_FAILED, false);
                            }

                            $this->db()->transComplete();

                            return $this->http()->output()->json([
                                'status' => true,
                                'code' => HTTP::OK,
                                'title' => 'Verification Successful',
                                'message' => "Congratulation, your {$validator->getValue('email')} has been verified successfully",
                            ], HTTP::OK);

                            break;
                        default:
                            throw $this->baseException("Please select a valid validation action",
                                "Invalid validation action", HTTP::EXPECTATION_FAILED);
                            break;
                    }

                    break;
                case self::VERIFICATION_TYPE_PHONE:

                    throw $this->baseException("Sorry, we're not yet validating phone numbers",
                        "Unavailable validation", HTTP::EXPECTATION_FAILED);

                    break;
                default:
                    throw $this->baseException("Please select a valid validation type",
                        "Invalid validation type", HTTP::EXPECTATION_FAILED);
                    break;
            }
        } catch (BaseException $e) {

            return $this->http()->output()->json([
                'status' => $e->getStatus(),
                'code' => $e->getCode(),
                'title' => $e->getTitle(),
                'message' => $e->getMessage(),
                'error' => (object) $validator->getErrors()
            ], $e->getCode());
        }
    }
}