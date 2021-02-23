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

                            $condition = $validator->validate('email')
                                ->isEmail("Please enter a valid email address")
                                ->isUniqueInDB('users', 'email', "That email is already taken.")
                                ->toLower();

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
                                ->where('is_active', true)->orderBy('desc', 'id')->exec();

                            if ($insert->isSuccessful()) {

                                if (($model = $insert->getFirstWithModel())->validate('expiration')->isDateLessThan(
                                    DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', APP_TIME)),
                                    'Y-m-d H:i:s')) $model->update(['is_active' => false]); else goto RESPOND;

                            }

                            $this->db()->transStart();

                            $insert = $this->db()->insert('verifications', [
                                'data' => $validator->getValue('email'),
                                'code' => mt_rand(11111, 99999),
                                'type' => self::VERIFICATION_TYPE_EMAIL,
                                'expiration' => date('Y-m-d H:i:s', APP_TIME + TIMEOUT_TEN_MIN),
                                'is_verified' => false,
                                'is_active' => true
                            ]);

                            if (!$insert->isSuccessful()) throw $this->baseException(
                                $insert->getQueryError() ?: "Sorry, we couldn't verify your email at this time, please try again later.",
                                "Verification failed", HTTP::EXPECTATION_FAILED);

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
                        case self::VERIFICATION_ACTION_VERIFY:

                            $validator->validate('old_email', true)->isEmail("Please enter a valid email address")->toLower()
                                ->isFoundInDB('users', 'email', "That email is not associated with any account.");

                            $condition = $validator->validate('email')->isEmail("Please enter a valid email address")->toLower()
                                ->isFoundInDB('verifications', 'data', "That email has not requested for verification yet.");

                            $validator->validate('code')->isNumber('Please enter a valid verification code')->hash()
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

                            if (!$verified?->isSuccessful()) {
                                $this->db()->transRollBack();
                                throw $this->baseException($verified->getQueryError() ?: "Sorry, we could not verify that email at this time, please try again later.",
                                    "Verification failed", HTTP::EXPECTATION_FAILED);
                            }

                            if ($input->_isset('old_email')) {

                                $user = $this->db()->find('users', $input['old_email'], 'email');

                                if (!$user->isSuccessful()) {
                                    $this->db()->transRollBack();
                                    throw $this->baseException($user->getQueryError() ?: "Sorry, we could not verify that email at this time, please try again later.",
                                        "Verification failed", HTTP::EXPECTATION_FAILED);
                                }

                                $user = $user->getFirstWithModel();

                                if (!($update = $user?->update(['email' => $input['email']]))->isSuccessful()) {
                                    $this->db()->transRollBack();
                                    throw $this->baseException($update->getQueryError() ?: "Sorry, we could not verify that email at this time, please try again later.",
                                        "Verification failed", HTTP::EXPECTATION_FAILED);
                                }
                            }

                            $this->db()->transComplete();

                            return $this->http()->output()->json([
                                'status' => true,
                                'code' => HTTP::OK,
                                'title' => 'Verification Successful',
                                'message' => "Congratulation, your email has been verified successfully",
                            ]);

                        default:
                            throw $this->baseException("Please select a valid validation action",
                                "Invalid validation action", HTTP::EXPECTATION_FAILED);
                    }

                case self::VERIFICATION_TYPE_PHONE:

                    switch ($input['route.params.action']) {
                        case self::VERIFICATION_ACTION_REQUEST:

                            $condition = $validator->validate('phone')
                                ->isPhoneNumber("Please enter a valid phone number")
                                ->isUniqueInDB('users', 'phone', "That phone number is already taken.")
                                ->toLower();

                            if ($validator->hasError()) throw $this->baseException(
                                "The inputted data is invalid", "Verification failed", HTTP::UNPROCESSABLE_ENTITY);

                            $condition->isNotFoundInDB('verifications', 'data',
                                'That phone number has already been verified.', function (Builder $builder) {
                                    $builder->where('type', self::VERIFICATION_TYPE_PHONE);
                                    $builder->where('is_verified', true);
                                    $builder->where('is_active', true);
                                });

                            if ($validator->hasError()) throw $this->baseException(
                                current($validator->getError('phone')), "Verified", HTTP::CONFLICT);

                            $insert = $this->db()->select('*')->table('verifications')
                                ->where('data', $validator->getValue('phone'))
                                ->where('type', self::VERIFICATION_TYPE_PHONE)
                                ->where('is_active', true)->orderBy('desc', 'id')->exec();

                            if ($insert->isSuccessful()) {

                                if (($model = $insert->getFirstWithModel())->validate('expiration')->isDateLessThan(
                                    DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', APP_TIME)),
                                    'Y-m-d H:i:s')) $model->update(['is_active' => false]); else goto RESPOND_;

                            }

                            $this->db()->transStart();

                            $insert = $this->db()->insert('verifications', [
                                'data' => $validator->getValue('phone'),
                                'code' => mt_rand(11111, 99999),
                                'type' => self::VERIFICATION_TYPE_PHONE,
                                'expiration' => date('Y-m-d H:i:s', APP_TIME + TIMEOUT_TEN_MIN),
                                'is_verified' => false,
                                'is_active' => true
                            ]);

                            if (!$insert->isSuccessful()) throw $this->baseException(
                                $insert->getQueryError() ?: "Sorry, we couldn't verify your phone number at this time, please try again later.",
                                "Verification failed", HTTP::EXPECTATION_FAILED);

                            $this->db()->transComplete();

                            RESPOND_:

                            $model = $insert->getFirstWithModel();

                            return $this->http()->output()->json([
                                'status' => true,
                                'code' => HTTP::OK,
                                'title' => 'Verification OTP Sent',
                                'message' => "Verify your phone number with the OTP we sent to {$validator->getValue('phone')}",
                                'expire' => strtotime($model->getValue('expiration')) - APP_TIME,
                                'email' => $model->getValue('data')
                            ]);
                        case self::VERIFICATION_ACTION_VERIFY:

                            $validator->validate('old_email', true)->isPhoneNumber("Please enter a valid phone number")->toLower()
                                ->isFoundInDB('users', 'phone', "That phone number is not associated with any account.");

                            $condition = $validator->validate('phone')->isPhoneNumber("Please enter a valid phone number")->toLower()
                                ->isFoundInDB('verifications', 'data', "That phone number has not requested for verification yet.");

                            $validator->validate('code')->isNumber('Please enter a valid verification code')->hash()
                                ->isFoundInDB('verifications', 'code', "That verification code does not exist");

                            if ($validator->hasError()) throw $this->baseException(
                                "The inputted data is invalid", "Verification failed", HTTP::UNPROCESSABLE_ENTITY);

                            $condition->isNotFoundInDB('verifications', 'data', 'That phone number has already been verified.',
                                function (Builder $builder) {
                                    $builder->where('type', self::VERIFICATION_TYPE_PHONE);
                                    $builder->where('is_verified', true);
                                });

                            if ($validator->hasError()) throw $this->baseException(
                                "The inputted data already exist", "Verified", HTTP::CONFLICT);

                            $verify = $this->db()->select()->table('verifications')
                                ->where('data', $validator->getValue('phone'))
                                ->where('code', $validator->getValue('code'))
                                ->where('type', self::VERIFICATION_TYPE_PHONE)
                                ->where('is_active', true)
                                ->exec();

                            if (!$verify->isSuccessful()) throw $this->baseException(
                                "No active verification request was found for that phone with the specified code.", "Verification failed", HTTP::NOT_FOUND);

                            $verify = $verify->getFirstWithModel();

                            if ($verify->validate('expiration')->isDateLessThan(
                                DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', APP_TIME)))) {

                                $verify->update(['is_active' => false]);

                                throw $this->baseException(
                                    "That verification code has expired.", "Verification failed", HTTP::EXPECTATION_FAILED);
                            }

                            $this->db()->transStart();

                            $verified = $verify->update(['is_verified' => true]);

                            if (!$verified?->isSuccessful()) {
                                $this->db()->transRollBack();
                                throw $this->baseException($verified->getQueryError() ?: "Sorry, we could not verify that phone at this time, please try again later.",
                                    "Verification failed", HTTP::EXPECTATION_FAILED);
                            }

                            if ($input->_isset('old_phone')) {

                                $user = $this->db()->find('users', $input['old_phone'], 'phone');

                                if (!$user->isSuccessful()) {
                                    $this->db()->transRollBack();
                                    throw $this->baseException($user->getQueryError() ?: "Sorry, we could not verify that phone at this time, please try again later.",
                                        "Verification failed", HTTP::EXPECTATION_FAILED);
                                }

                                $user = $user->getFirstWithModel();

                                if (!($update = $user?->update(['phone' => $input['phone']]))->isSuccessful()) {
                                    $this->db()->transRollBack();
                                    throw $this->baseException($update->getQueryError() ?: "Sorry, we could not verify that phone at this time, please try again later.",
                                        "Verification failed", HTTP::EXPECTATION_FAILED);
                                }
                            }

                            $this->db()->transComplete();

                            return $this->http()->output()->json([
                                'status' => true,
                                'code' => HTTP::OK,
                                'title' => 'Verification Successful',
                                'message' => "Congratulation, your phone number has been verified successfully",
                            ]);

                        default:
                            throw $this->baseException("Please select a valid validation action",
                                "Invalid validation action", HTTP::EXPECTATION_FAILED);
                    }

                default:
                    throw $this->baseException("Please select a valid validation type",
                        "Invalid validation type", HTTP::EXPECTATION_FAILED);
            }
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