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
use que\http\HTTP;
use que\http\input\Input;
use que\http\output\response\Html;
use que\http\output\response\Json;
use que\http\output\response\Jsonp;
use que\http\output\response\Plain;
use que\http\request\Request;

class Loan extends Manager implements Api
{

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.
        $date = date('m/y');
        $converter = converter();

        try {

            switch (Request::getUriParam('type')) {
                case "requests":
                    $list = [];
                    for ($i = 0; $i < 30; $i++) {
                        $list[] = [
                            'id' => $i,
                            'type' => 'Loan request',
                            'amount' => 65000,
                            'status' => $converter->convertEnvConst(STATE_SUCCESSFUL, "STATE_"),
                            'date' => $date
                        ];
                    }
                    return [
                        'page' => get('page') + 1,
                        'loan_requests' => $list
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
                'error' => (object) []
            ], $e->getCode());
        }
    }
}