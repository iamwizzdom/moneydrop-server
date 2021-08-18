<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 11/20/2020
 * Time: 3:59 PM
 */

namespace profile;


use que\common\manager\Manager;
use que\common\structure\Api;
use que\http\input\Input;
use que\http\output\response\Html;
use que\http\output\response\Json;
use que\http\output\response\Jsonp;
use que\http\output\response\Plain;
use que\template\Pagination;

class Transaction extends Manager implements Api
{

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.

        $trans = $this->db()->select('*')
            ->table('transactions')->where('user_id', $this->user('id'))
            ->orderBy('desc', 'id')->paginate(headers('X-PerPage', PAGINATION_PER_PAGE));

        $trans->setModelKey("transactionModel");

//        $pagination = Pagination::getInstance();

//        return [
//            'pagination' => [
//                'page' => $pagination->getPaginator("default")->getPage(),
//                'totalRecords' => $pagination->getTotalRecords("default"),
//                'totalPages' => $pagination->getTotalPages("default"),
//                'nextPage' => $pagination->getNextPage("default", true),
//                'previousPage' => $pagination->getPreviousPage("default", true)
//            ],
//            'transactions' => $trans->getAllWithModel() ?: []
//        ];
        return $trans->getAllWithModel();
    }
}
