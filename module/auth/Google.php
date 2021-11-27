<?php


namespace auth;


use Google_Client;
use que\common\exception\BaseException;
use que\common\manager\Manager;
use que\common\structure\Api;
use que\database\interfaces\Builder;
use que\http\HTTP;
use que\http\input\Input;
use que\http\output\response\Html;
use que\http\output\response\Json;
use que\http\output\response\Jsonp;
use que\http\output\response\Plain;
use que\security\jwt\JWT;
use que\user\User;
use que\utility\hash\Hash;
use utility\Card;

class Google extends Manager implements Api
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

            $validator->validate('google_id')->isNotEmpty("Please enter a valid google ID");

            $validator->validate('token_id')->isNotEmpty("Please enter a valid google token ID")->if(function () {

                $client = new Google_Client(['client_id' => env('GOOGLE_CLIENT_ID')]);  // Specify the CLIENT_ID of the app that accesses the backend
                $payload = $client->verifyIdToken(\input('token_id'));
                return $payload && $payload['email'] == \input('email');

            }, "Sorry, that's an invalid google token ID");

            $validator->validate('pn_token', true)->isNotEmpty("Please enter a valid token");

            if ($validator->hasError()) throw $this->baseException(
                "The inputted data is invalid", "Sign in Failed", HTTP::UNPROCESSABLE_ENTITY
            );

            $user = $this->db()->find('users', Hash::sha($input['google_id']), 'google_id');

            if (!$user->isSuccessful()) {

                if (!$validator->has('email')) {
                    throw $this->baseException(
                        "Sorry, no account was found registered with the given ID", "Sign in Failed", HTTP::NOT_FOUND
                    );
                }

                $user = $this->db()->find('users', $input['email'], 'email');

                if (!$user->isSuccessful()) {
                    throw $this->baseException(
                        "Sorry, you don't have an account on MoneyDrop yet, you must first sign up.", "Sign in Failed", HTTP::NOT_FOUND
                    );
                }

                $user->getFirstWithModel()->update(['google_id' => Hash::sha($input['google_id'])]);

            }

            $user->setModelKey('userModel');
            $user = $user->getFirstWithModel();

            if (!$user->getBool('is_active')) {
                throw $this->baseException("Sorry, you can't login to an inactive account.", 'Sign in Failed', HTTP::UNAUTHORIZED, false);
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
                'title' => 'Sign in Successful',
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