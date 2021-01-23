<?php


namespace access;


use que\common\exception\BaseException;
use que\common\exception\QueException;
use que\common\manager\Manager;
use que\common\structure\Api;
use que\database\interfaces\Builder;
use que\http\HTTP;
use que\http\input\Input;
use que\utility\hash\Hash;

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
                "Forgot password Failed", HTTP::UNPROCESSABLE_ENTITY);

            $user = $this->db()->find('users', $input['email'], 'email')->getFirstWithModel();

            $response = $this->db()->find('password_resets', $user->getValue('id'), 'user_id',
                function (Builder $builder) {
                    $builder->where('expiration', date("Y-m-d H:i:s"), '>');
                    $builder->where('is_active', true);
                }
            );

            if ($response->isSuccessful()) {

                return $this->http()->output()->json([
                    'status' => true,
                    'code' => HTTP::CONFLICT,
                    'title' => 'Password OTP Sent',
                    'message' => "An OTP has already been sent to your email.",
                    'error' => (object)[]
                ], HTTP::CONFLICT);

            }

            $this->db()->update()->table('password_resets')
                ->where('user_id', $user->getValue('id'))
                ->where('is_active', true)
                ->columns(['is_active' => false])->exec();

            $response = $this->db()->insert('password_resets', [
                'user_id' => $user->getValue('id'),
                'code' => Hash::sha(($code = mt_rand(1111, 9999))),
                'expiration' => date("Y-m-d H:i:s", (APP_TIME + TIMEOUT_TEN_MIN)),
                'is_active' => true
            ]);

            if (!$response->isSuccessful()) throw $this->baseException(
                "Sorry, we couldn't send you an OTP at this time, please try again later",
                "Forgot password Failed", HTTP::EXPECTATION_FAILED);

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
                'error' => (object)$validator->getErrors()
            ], $e->getCode());
        }
    }
}