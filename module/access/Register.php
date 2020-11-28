<?php

namespace module\access;

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
                ->hasMinLength(7, "Your phone number must be at least %s digits long.")
                ->hasMaxLength(15, "Your phone number must not be more than %s digits.")
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
                    \DateTime::createFromFormat('Y-m-d', date('Y-m-d', strtotime('-15years'))),
                    "You must be at least 15 years old to use " . config('template.app.header.name'));

            $validator->validate('gender', true)->isNumber("Please select a valid gender")
                ->isEqualToAny([GENDER_MALE, GENDER_FEMALE], "Please select a valid gender");

            $validator->validate('address', true)->isNotEmpty("Please enter a valid address")
                ->hasMinWord(5, "Your address is expected to have at least %s words");

            $validator->validate('country_id', true)->isNumber("Please select a valid country")
                ->isFoundInDB('countries', 'id', "That country does not exist on this platform");

            $validator->validate('state_id', true)->isNumber("Please select a valid state")
                ->isFoundInDB('states', 'id', "That state does not exist on this platform");

            if ($validator->hasError()) throw $this->baseException(
                "The inputted data is invalid", "Registration Failed", HTTP::UNPROCESSABLE_ENTITY, false);

            $user = $this->db()->insert('users', $validator->getValidated());

            if (!$user->isSuccessful()) throw $this->baseException(
                "Failed to create account at this time, please try again later",
                "Registration Failed", HTTP::EXPECTATION_FAILED, false);

            $user = $user->getFirstWithModel();
            User::login($user->getObject());

            $emailVerification = $this->db()->find('verifications', $user['email'],
                'data', function (Builder $builder) {
                    $builder->where('type', 'email');
                    $builder->where('is_verified', true);
                    $builder->where('is_active', true);
                });

            $phoneVerification = $this->db()->find('verifications', $user['phone'],
                'data', function (Builder $builder) {
                    $builder->where('type', 'phone');
                    $builder->where('is_verified', true);
                    $builder->where('is_active', true);
                });

            $user->offsetSet('verified', [
                'email' => $emailVerification->isSuccessful(),
                'phone' => $phoneVerification->isSuccessful()
            ]);

            $user->offsetSet('country_id', $this->converter()->convertCountry($user['country_id'] ?: 0, 'countryName'));
            $user->offsetSet('state_id', $this->converter()->convertState($user['state_id'] ?: 0, 'stateName'));
            $user->offsetRename('country_id', 'country');
            $user->offsetRename('state_id', 'state');

            return $this->http()->output()->json([
                'status' => true,
                'code' => HTTP::CREATED,
                'title' => 'Signup Successful',
                'message' => "You have been signed up successfully.",
                'response' => [
                    'token' => JWT::fromUser($input->user()),
                    'user' => $user->getArray()
                ],
                'error' => (object) []
            ], HTTP::CREATED);

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