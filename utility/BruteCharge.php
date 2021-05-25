<?php


namespace utility;


use que\security\jwt\JWT;
use que\user\User;

class BruteCharge
{
    use Card, BankAccount;

    public function charge($amount, $userID) {

        $formerUser = user();

        if (user('id') != $userID) {
            $user = db()->find('users', $userID)?->getFirst();
            User::logout_silently();
            User::login($user);
        }

        $cards = $this->getAllMyCards($userID);
        $curl = http()->curl_request();

        $successful = false;

        if ($cards) {
            foreach ($cards as $card) {
                $curl->setUrl(route('web-top-top', ['type' => 'top-up', 'id' => $card->uuid]));
                $curl->setHeaders([
                    'Content-Type' => 'application/json',
                    'Auth-Token' => JWT::fromUser(\user())
                ]);
                $curl->setPosts([
                    'amount' => ($amount + 100),
                    'force-top-up' => true
                ]);
                $response = $curl->send()->getResponseArray();
                if (($response['status'] ?? false) == true) {
                    $successful = true;
                    break;
                }
            }
        }

        if ($successful == false) {
            $banks = $this->getAllMyBankAccounts($userID);
            foreach ($banks as $bank) {
                
            }
        }
    }
}