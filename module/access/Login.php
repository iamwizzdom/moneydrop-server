<?php


namespace module\access;


use que\common\exception\BaseException;
use que\common\manager\Manager;
use que\common\structure\Api;
use que\common\validator\condition\Condition;
use que\common\validator\condition\ConditionError;
use que\database\interfaces\Builder;
use que\http\HTTP;
use que\http\input\Input;
use que\http\output\response\Html;
use que\http\output\response\Json;
use que\http\output\response\Jsonp;
use que\http\output\response\Plain;
use que\security\JWT\JWT;
use que\user\User;

class Login extends Manager implements Api
{

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.
        $validator = $this->validator($input);

        try {

            $validator->validate('email')->isEmail("Please enter a valid email address")
                ->isFoundInDB('users', 'email', 'That email is not associated with any account');

            $validator->validate('password')->isString('Please enter a valid password')
                ->hasMinLength(8, "Your password is expected to be at least %s characters long")
                ->hash('SHA512');

            if ($validator->hasError()) throw $this->baseException(
                "The inputted data is invalid", "Login Failed", HTTP::UNPROCESSABLE_ENTITY, false);

            $user = $this->db()->select('*')->table('users')
                ->where('email', $validator->getValue('email'))
                ->where('password', $validator->getValue('password'))->exec();

            if (!$user->isSuccessful()) throw $this->baseException(
                'Email and password do not match', 'Invalid Credentials', HTTP::UNAUTHORIZED, false);

            $user = $user->getFirst();
            User::login($user);

            return $this->http()->output()->json([
                'status' => true,
                'code' => HTTP::OK,
                'title' => 'Login Successful',
                'message'  => "You've been logged in successfully",
                'response' => [
                    'token' => JWT::fromUser($input->user()),
                    'user' => $user
                ]
            ],HTTP::OK);

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