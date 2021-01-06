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
use que\database\interfaces\Builder;
use que\database\interfaces\model\Model;
use que\http\HTTP;
use que\http\input\Input;
use que\http\output\response\Html;
use que\http\output\response\Json;
use que\http\output\response\Jsonp;
use que\http\output\response\Plain;
use que\http\request\Request;
use que\support\Arr;
use que\support\Str;
use que\template\Pagination;

class Loan extends Manager implements Api
{

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.
        $validator = $this->validator($input);

        try {

            switch ($type = Request::getUriParam('type')) {
                case "offers":
                case "requests":

                    $loans = $this->db()->select("*")->table('loans')
                        ->where('user_id', $this->user('id'))
                        ->where('is_active', true)
                        ->where('loan_type', $type == "offers" ? \loan\Loan::LOAN_TYPE_OFFER : \loan\Loan::LOAN_TYPE_REQUEST)
                        ->orderBy('desc', 'id')->paginate(30);

                    $loans->setModelKey("loanModel");

                    $pagination = Pagination::getInstance();

                    return [
                        'pagination' => [
                            'page' => $pagination->getPaginator("default")->getPage(),
                            'totalRecords' => $pagination->getTotalRecords("default"),
                            'totalPages' => $pagination->getTotalPages("default"),
                            'nextPage' => $pagination->getNextPage("default", true),
                            'previousPage' => $pagination->getPreviousPage("default", true)
                        ],
                        'loans' => $loans->getAllWithModel() ?: []
                    ];
                default:
                    throw $this->baseException(
                        "Sorry, we're not sure what you're trying to do there.", "Loan Failed", HTTP::BAD_REQUEST);
            }

        } catch (BaseException $e) {

            return $this->http()->output()->json([
                'status' => $e->getStatus(),
                'code' => $e->getCode(),
                'title' => $e->getTitle(),
                'message' => $e->getMessage(),
                'error' => (object)$validator->getErrors()
            ], $e->getCode());
        }
    }
}
