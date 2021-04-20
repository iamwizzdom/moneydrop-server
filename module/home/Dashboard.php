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

        $trans = $this->db()->select('*')
            ->table('transactions')->where('user_id', $this->user('id'))
            ->orderBy('desc', 'id')->limit(6)->exec();

        $trans->setModelKey("transactionModel");

        $loans = $this->db()->select("*")->table('loans')
            ->where('user_id', $this->user('id'))
            ->where('is_active', true)
            ->orderBy('desc', 'id')->limit(6)->exec();

        $loans->setModelKey("loanModel");

        return [
            'balance' => $this->getBalance(),
            'available_balance' => $this->getAvailableBalance(),
            'loans' => $loans->getAllWithModel() ?: [],
            'transactions' => $trans->getAllWithModel()?->load('user') ?: []
        ];
    }
}
