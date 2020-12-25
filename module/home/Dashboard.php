<?php


namespace module\home;


use que\common\manager\Manager;
use que\common\structure\Api;
use que\database\interfaces\model\Model;
use que\http\HTTP;
use que\http\input\Input;
use que\http\output\response\Html;
use que\http\output\response\Json;
use que\http\output\response\Jsonp;
use que\http\output\response\Plain;
use utility\Wallet;

class Dashboard extends Manager implements Api
{
    use Wallet;

    public function process(Input $input)
    {
        // TODO: Implement process() method.
        $date = date('m/y');
        $converter = converter();

        $trans = $this->db()->select('id', 'transaction_state', 'amount', 'status', 'created_at')
            ->table('transactions')->where('user_id', $this->user('id'))
            ->orderBy('desc', 'id')->limit(4)->exec();

        $transactions = [];

        if ($trans->isSuccessful()) {

            $transactions = $trans->getAllWithModel();

            iterable_callback($transactions, function (Model $model) use ($converter) {
                $model->offsetRename("transaction_state", 'type');
                $model->offsetRename("created_at", 'date');
                $type = $converter->convertEnvConst($model['type'], "TRANSACTION_");
                $type = str_replace("_", "-", $type);
                $model->offsetSet("type", ucfirst($type));
                $model->offsetSet("status", strtolower($converter->convertEnvConst($model['status'], "APPROVAL_")));
                $model->offsetSet("date", get_date("d/m/y", $model['date']));
                return $model;
            });

            $transactions = $transactions->getArray();
        }

        return [
            'balance' => $this->getAvailableBalance(),
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
            'transactions' => $transactions
        ];
    }
}