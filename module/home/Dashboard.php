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
            ->orderBy('desc', 'id')->limit(3)->exec();

        $trans->setModelKey("transactionModel");

        $loans = $this->db()->select("*")->table('loans')
            ->where('user_id', $this->user('id'))
            ->where('is_active', true)
            ->orderBy('desc', 'id')->limit(2)->exec();

        $loans->setModelKey("loanModel");

        return [
            'balance' => $this->getAvailableBalance(),
            'loans' => $loans->getAllWithModel(),
            'transactions' => $trans->getAllWithModel() ?: []
        ];
    }
}https://www.xvideos.com/video44138993/bangbros_-_horny_and_busty_pawg_lena_paul_wants_some_dick#
