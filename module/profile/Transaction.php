<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 11/20/2020
 * Time: 3:59 PM
 */

namespace profile;


use que\common\exception\BaseException;
use que\common\manager\Manager;
use que\common\structure\Api;
use que\database\interfaces\model\Model;
use que\http\HTTP;
use que\http\input\Input;
use que\http\output\response\Html;
use que\http\output\response\Json;
use que\http\output\response\Jsonp;
use que\http\output\response\Plain;
use que\http\request\Request;
use que\support\Arr;
use que\template\Pagination;

class Transaction extends Manager implements Api
{

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.
        $converter = converter();

        try {

            $trans = $this->db()->select('id', 'transaction_state', 'amount', 'status', 'created_at')
                ->table('transactions')->where('user_id', $this->user('id'))
                ->orderBy('desc', 'id')->paginate(30);

            if (!$trans->isSuccessful()) throw $this->baseException("No transaction was found for your account",
                "Transactions Failed", HTTP::EXPECTATION_FAILED);

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

            $pagination = Pagination::getInstance();

            return [
                'pagination' => [
                    'page' => $pagination->getPaginator("default")->getPage(),
                    'totalRecords' => $pagination->getTotalRecords("default"),
                    'totalPages' => $pagination->getTotalPages("default"),
                    'nextPage' => $pagination->getNextPage("default", true),
                    'previousPage' => $pagination->getPreviousPage("default", true)
                ],
                'transactions' => $transactions->getArray()
            ];

        } catch (BaseException $e) {

            return $this->http()->output()->json([
                'status' => $e->getStatus(),
                'code' => $e->getCode(),
                'title' => $e->getTitle(),
                'message' => $e->getMessage(),
                'error' => (object) []
            ], $e->getCode());
        }
    }
}