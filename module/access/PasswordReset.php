<?php


namespace module\access;


use que\common\exception\BaseException;
use que\common\exception\QueException;
use que\common\manager\Manager;
use que\common\structure\Api;
use que\http\HTTP;
use que\http\input\Input;

class PasswordReset extends Manager implements Api
{

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        $validator = $this->validator($input);

        try {

            $validator->validate('email')->isEmail('Please enter a valid email address')
                ->isFoundInDB('users', 'email', 'That email address is invalid');

            $validator->validate('otp')->isNumber('The OTP must be numeric')
                ->hasMinLength(4, "The OTP must be %s digits")
                ->hasMaxLength(4, "The OTP must not be greater than %s digits");

            $validator->validate('password')->isNotEmpty("Please enter a valid password")->hasMinLength(
                8, "Your password must be at least %s characters long")->isAlphaNumeric(
                "Your password is not strong enough (make it alpha-numeric)")
                ->isConfirmed("Password do not match")->hash('SHA512');

            if ($validator->hasError()) throw $this->baseException("The inputted data is invalid",
                "Password Reset Error", HTTP::UNPROCESSABLE_ENTITY);

            $otp = $this->session()->getQueKip()->get($validator->getValue('email'));

            if (empty($otp)) {
                $validator->addConditionError('otp', 'The OTP has either expired or does not exist');
                throw $this->baseException("The inputted data is invalid", "Password Reset Error", HTTP::UNPROCESSABLE_ENTITY);
            }

            if ($input->validate('otp')->isNotEqual($otp)) {
                $validator->addConditionError('otp', 'OTP do not match');
                throw $this->baseException("The inputted data is invalid", "Password Reset Error", HTTP::UNPROCESSABLE_ENTITY);
            }

            $user = $this->db()->find('users', $input->get('email'), 'email');

            $update = ($model = $user->getFirstWithModel())->update([
                'password' => $validator->getValue('password')
            ]);

            if (!$update) throw $this->baseException(
                "Sorry, we couldn't reset your password at this time. Please try gain later",
                "Password Reset Error", HTTP::EXPECTATION_FAILED);

            try {

                $mailer = $this->mailer();

                $mail = $this->mail('reset');
                $mail->addRecipient($validator->getValue('email'));
                $mail->setSubject("Password Reset");
                $mail->setData([
                    'title' => 'Password Reset',
                    'name' => "{$model->getValue('firstname')} {$model->getValue('lastname')}",
                    'app_name' => config('template.app.header.name')
                ]);
                $mail->setHtmlPath('email/html/reset-password.tpl');
                $mail->setBodyPath('email/text/reset-password.txt');

                $mailer->addMail($mail);
                $mailer->prepare('reset');
                if (!$mailer->dispatch('reset')) throw new QueException($mailer->getError('reset'));

            } catch (QueException $e) {
                throw $this->baseException($e->getMessage(),
                    "Password Reset Error", HTTP::EXPECTATION_FAILED, false);
            }

            $this->session()->getQueKip()->delete($validator->getValue('email'));

            return $this->http()->output()->json([
                'status' => true,
                'code' => HTTP::OK,
                'title' => 'Password Reset Successful',
                'message' => "Your password has been reset successfully, you may now log in."
            ], HTTP::OK);

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