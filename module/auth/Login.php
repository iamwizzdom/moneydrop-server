<?php


namespace auth;


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
use que\support\Arr;
use que\user\User;
use utility\Card;
use utility\enum\BanksEnum;

class Login extends Manager implements Api
{
    use Card;

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

            $validator->validate('pn_token', true)->isNotEmpty("Please enter a valid token");

            if ($validator->hasError()) throw $this->baseException(
                "The inputted data is invalid", "Login Failed", HTTP::UNPROCESSABLE_ENTITY, false
            );

            $user = $this->db()->select('*')->table('users')
                ->where('email', $validator->getValue('email'))
                ->where('password', $validator->getValue('password'))
                ->exec();

            $isFingerprintAuth = $input->_isset('is_fingerprint_auth') && $input->validate('is_fingerprint_auth')->isEqual(true);

            if (!$user->isSuccessful()) throw $this->baseException(
                $isFingerprintAuth ? "Authentication failed, please try logging in with your password once again."
                    : 'Email and password do not match', 'Invalid Credentials', HTTP::UNAUTHORIZED, false);

            $user->setModelKey('userModel');
            $user = $user->getFirstWithModel();

            if (!$user->getBool('is_active')) {
                throw $this->baseException("Sorry, you can't login to an inactive account.", 'Login Failed', HTTP::UNAUTHORIZED, false);
            }

            if ($validator->has('pn_token')) {

                $previousPnTokenUser = $this->db()->find('users', $validator->getValue('pn_token'), 'pn_token',
                    function (Builder $builder) use ($user) {
                        $builder->where('id', $user->id, '!=');
                    });

                if ($previousPnTokenUser->isSuccessful()) {
                    $previousPnTokenUser->getFirstWithModel()->update(['pn_token' => null]);
                }

                $user->update(['pn_token' => $validator->getValue('pn_token')]);
            }

            User::login($user);

            $user->set('token', JWT::fromUser($input->user()));

            $bankAccounts = $this->db()->findAll('bank_accounts', user('id'), 'user_id', function (Builder $builder) {
                $builder->where('is_active', true);
                $builder->orderBy("desc", "id");
            });

            $bankAccounts->setModelKey('bankAccountModel');

            return $this->http()->output()->json([
                'status' => true,
                'code' => HTTP::OK,
                'title' => 'Login Successful',
                'message' => "Hi {$user['firstname']}, welcome.",
                'response' => [
                    'user' => $user,
                    'cards' => $this->getAllMyCards() ?: [],
                    'bank-accounts' => $bankAccounts->getAllWithModel() ?: []
                ]
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
