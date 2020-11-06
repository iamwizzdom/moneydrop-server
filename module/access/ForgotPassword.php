<?php


namespace module\access;


use que\common\exception\BaseException;
use que\common\exception\QueException;
use que\common\manager\Manager;
use que\common\structure\Api;
use que\http\HTTP;
use que\http\input\Input;

class ForgotPassword extends Manager implements Api
{

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        $validator = $this->validator($input);
        try {

            $validator->validate('email')->isEmail('Please enter a valid email address')
                ->isFoundInDB('users', 'email', 'That email address is not associated to any account');

            if ($validator->hasError()) throw $this->baseException("The inputted data is invalid",
                "Forgot password Error", HTTP::UNPROCESSABLE_ENTITY);

            if ($code = $this->session()->getQueKip()->get($validator->getValue('email'))) {

                return $this->http()->output()->json([
                    'status' => true,
                    'code' => HTTP::CONFLICT,
                    'title' => 'Password OTP Sent',
                    'message' => "An OTP has already been sent to your email.",
                    'error' => (object) []
                ], HTTP::CONFLICT);

            }

            $code = mt_rand(1111, 9999);

            $this->session()->getQueKip()->set($validator->getValue('email'), $code, TIMEOUT_TEN_MIN);

            try {

                $mailer = $this->mailer();

                $mail = $this->mail('forgot');
                $mail->addRecipient($validator->getValue('email'));
                $mail->setSubject("Forgot Password");
                $mail->setData([
                    'title' => 'Forgot Password',
                    'otp' => $code,
                    'expire' => date("h:i a M jS, Y", (APP_TIME + TIMEOUT_TEN_MIN))
                ]);
                $mail->setHtmlPath('email/html/forgot-password-otp.tpl');
                $mail->setBodyPath('email/text/forgot-password-otp.txt');

                $mailer->addMail($mail);
                $mailer->prepare('forgot');
                if (!$mailer->dispatch('forgot'))
                    throw new QueException($mailer->getError('forgot'));

            } catch (QueException $e) {
                throw $this->baseException($e->getMessage(),
                    "Forgot password Error", HTTP::EXPECTATION_FAILED, false);
            }

            return $this->http()->output()->json([
                'status' => true,
                'code' => HTTP::OK,
                'title' => 'Password OTP Sent',
                'message' => "An OTP has been sent successfully to your email, use it to reset your password."
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