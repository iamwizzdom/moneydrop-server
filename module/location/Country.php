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

class Country extends Manager implements Api
{

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.
        $countries = $this->db()->findAll('countries', true,'is_active');
        return $this->http()->output()->json([
            'status' => true,
            'title' => "Countries retrieved successfully",
            "message" => "Countries has been successfully retrieved",
            'response' => [
//                'total' => $countries->getResponseSize(),
                'countries' => $countries->getAllWithModel()
            ]
        ]);
    }
}