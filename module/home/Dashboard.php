<?php


namespace module\home;


use que\common\manager\Manager;
use que\common\structure\Api;
use que\http\input\Input;
use que\http\output\response\Html;
use que\http\output\response\Json;
use que\http\output\response\Jsonp;
use que\http\output\response\Plain;
use utility\wallet\Wallet;

class Dashboard extends Manager implements Api
{
    use Wallet;

    public function process(Input $input)
    {
        // TODO: Implement process() method.
        $date = date('m/y');
        $converter = converter();

        return [
            'balance' => 12500.45,
            'loan_requests' => [
                [
                    'id' => 1,
                    'type' => 'Loan request',
                    'amount' => 20000,
                    'status' => $converter->convertEnvConst(STATE_PENDING, "STATE_"),
                    'date' => $date
                ],
                [
                    'id' => 2,
                    'type' => 'Loan request',
                    'amount' => 65000,
                    'status' => $converter->convertEnvConst(STATE_SUCCESSFUL, "STATE_"),
                    'date' => $date
                ]
            ],
            'transactions' => [
                [
                    'id' => 1,
                    'type' => 'Wallet top-up',
                    'amount' => 19200.25,
                    'status' => $converter->convertEnvConst(STATE_FROZEN, "STATE_"),
                    'date' => $date
                ],
                [
                    'id' => 2,
                    'type' => 'Wallet withdrawal',
                    'amount' => 18500,
                    'status' => $converter->convertEnvConst(STATE_FAILED, "STATE_"),
                    'date' => $date
                ]
            ]
        ];
    }
}