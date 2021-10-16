<?php


namespace auth;


use DateTime;
use que\common\exception\BaseException;
use que\common\exception\QueException;
use que\common\manager\Manager;
use que\common\structure\Api;
use que\database\interfaces\Builder;
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
                ->hasMinLength(5, "The OTP must be %s digits")
                ->hasMaxLength(5, "The OTP must not be greater than %s digits");

            $validator->validate('password')->isNotEmpty("Please enter a valid password")->hasMinLength(
                8, "Your password must be at least %s characters long")->isAlphaNumeric(
                "Your password is not strong enough (make it alpha-numeric)")
                ->isConfirmed("Password do not match")->hash('SHA512');

            if ($validator->hasError()) throw $this->baseException("The inputted data is invalid",
                "Password Reset Failed", HTTP::UNPROCESSABLE_ENTITY);

            $user = $this->db()->find('users', $input['email'], 'email')->getFirstWithModel();

            $reset = $this->db()->find('password_resets', $user->getValue('id'), 'user_id',
                function (Builder $builder) {
                    $builder->where('is_active', true);
                }
            );

            if (!$reset->isSuccessful()) {
                throw $this->baseException("Seems a password reset process has not been initiated for that account.", "Password Reset Failed", HTTP::EXPECTATION_FAILED);
            }

            $reset = $reset->getFirstWithModel();

            if ($input->validate('otp')->hash()->isNotEqual($reset->getValue('code'))) {
                $validator->addConditionError('otp', 'OTP do not match');
                throw $this->baseException("The inputted data is invalid", "Password Reset Failed", HTTP::UNPROCESSABLE_ENTITY);
            }

            if ($reset->validate('expiration')->isDateLessThan(
                DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', APP_TIME)))) {

                $reset->update(['is_active' => false]);
                $validator->addConditionError('otp', 'That OTP has expired');
                throw $this->baseException("The inputted data is invalid", "Password Reset Failed", HTTP::UNPROCESSABLE_ENTITY);
            }

            $validator->validate('password')->isEqual($validator->getValue('password'), 'Use a stronger password.');

            if ($validator->hasError()) {
                throw $this->baseException("The inputted data is invalid", "Password Reset Failed", HTTP::UNPROCESSABLE_ENTITY);
            }

            $update = $user->update(['password' => $validator->getValue('password')]);

            if (!$update?->isSuccessful()) throw $this->baseException(
                $update->getQueryError() ?: "Sorry, we couldn't reset your password at this time. Please try gain later",
                "Password Reset Failed", HTTP::EXPECTATION_FAILED);

            $reset->update(['is_active' => false]);

            return $this->http()->output()->json([
                'status' => true,
                'code' => HTTP::OK,
                'title' => 'Password Reset Successful',
                'message' => "Your password has been reset successfully, you may now log in."
            ]);

        } catch (BaseException $e) {
            return $this->http()->output()->json([
                'status' => $e->getStatus(),
                'code' => $e->getCode(),
                'title' => $e->getTitle(),
                'message' => $e->getMessage(),
                'errors' => (object) $validator->getErrors()
            ], $e->getCode());
        }
    }
}