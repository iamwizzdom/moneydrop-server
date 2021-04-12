<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 4/11/2021
 * Time: 3:28 PM
 */

namespace location;


use que\common\manager\Manager;
use que\common\structure\Api;
use que\http\input\Input;
use que\http\output\response\Html;
use que\http\output\response\Json;
use que\http\output\response\Jsonp;
use que\http\output\response\Plain;

class State extends Manager implements Api
{

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.
        $states = $this->db()->findAll('states', true,'is_active');
        return $this->http()->output()->json([
            'status' => true,
            'title' => "States retrieved successfully",
            "message" => "States has been successfully retrieved",
            'response' => [
//                'total' => $states->getResponseSize(),
                'states' => $states->getAllWithModel()
            ]
        ]);
    }
}