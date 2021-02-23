<?php

namespace access;

use que\common\exception\BaseException;
use que\common\exception\QueException;
use que\common\manager\Manager;
use que\common\structure\Api;
use que\database\interfaces\Builder;
use que\http\HTTP;
use que\http\input\Input;
use que\security\JWT\JWT;
use que\user\User;

class Register extends Manager implements Api
{

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.
        $validator = $this->validator($input);

        try {

            $validator->validate('firstname')->isNotEmpty('Please enter a valid first name')
                ->hasMinLength(3, "Your first name must be at least %s characters long");

            $validator->validate('middlename', true)->isNotEmpty('Please enter a valid middle name')
                ->hasMinLength(3, "Your middle name must be at least %s characters long");

            $validator->validate('lastname')->isNotEmpty('Please enter a valid last name')
                ->hasMinLength(3, "Your last name must be at least %s characters long");

            $validator->validate('phone')->isPhoneNumber("Please enter a valid phone number")
                ->startsWithAny(['+234', '234'], "Sorry, we only support nigerian phone numbers for now.")
                ->hasMinLength(13, "Enter your phone number with your country code, and it must be at least %s digits long")
                ->isUniqueInDB("users", "phone", "That phone number already exist");

            $validator->validate('email')->isEmail("Please enter a valid email address")->toLower()
                ->isFoundInDB('verifications', 'data', "That email has not been verified.",
                    function (Builder $builder) {
                    $builder->where('is_verified', true);
                    $builder->where('is_active', true);
                })->isUniqueInDB("users", "email", "That email address already exist");

            $validator->validate('password')->isNotEmpty("Please enter a valid password")->hasMinLength(
                8, "Your password must be at least %s characters long")->isAlphaNumeric(
                "Your password is not strong enough (make it alpha-numeric)")->hash('SHA512');

            $validator->validate('dob')->isDate("Please enter a valid date of birth", 'Y-m-d')
                ->isDateLessThanOrEqual(\DateTime::createFromFormat('Y-m-d', date('Y-m-d')),
                    "Sorry, we don't accept people that were born in the future")->isDateLessThanOrEqual(
                    \DateTime::createFromFormat('Y-m-d', date('Y-m-d', strtotime('-15 years'))),
                    "You must be at least 15 years old to use " . config('template.app.header.name'));

            $validator->validate('gender', true)->isNumber("Please select a valid gender")
                ->isEqualToAny([GENDER_MALE, GENDER_FEMALE], "Please select a valid gender");

            $validator->validate('address', true)->isNotEmpty("Please enter a valid address")
                ->hasMinWord(5, "Your address is expected to have at least %s words");

            $validator->validate('country_id', true)->isNumber("Please select a valid country")
                ->isFoundInDB('countries', 'id', "That country does not exist on this platform");

            $validator->validate('state_id', true)->isNumber("Please select a valid state")
                ->isFoundInDB('states', 'id', "That state does not exist on this platform");

            $validator->validate('pn_token')->isNotEmpty("Please enter a valid token");

            if ($validator->hasError()) throw $this->baseException(
                "The inputted data is invalid", "Registration Failed", HTTP::UNPROCESSABLE_ENTITY, false);

            $user = $this->db()->insert('users', $validator->getValidated());

            if (!$user->isSuccessful()) throw $this->baseException(
                "Failed to create account at this time, please try again later",
                "Registration Failed", HTTP::EXPECTATION_FAILED, false);

            $user->setModelKey('userModel');

            $user = $user->getFirstWithModel();

            User::login($user->getObject());

            $user->set('token', JWT::fromUser($input->user()));

            return $this->http()->output()->json([
                'status' => true,
                'code' => HTTP::CREATED,
                'title' => 'Signup Successful',
                'message' => "You have been signed up successfully.",
                'response' => [
                    'user' => $user
                ],
                'errors' => (object) []
            ], HTTP::CREATED);

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
