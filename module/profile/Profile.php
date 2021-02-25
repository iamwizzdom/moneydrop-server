<?php


namespace module\profile;


use que\common\manager\Manager;
use que\common\structure\Api;
use que\http\HTTP;
use que\http\input\Input;
use que\http\output\response\Html;
use que\http\output\response\Json;
use que\http\output\response\Jsonp;
use que\http\output\response\Plain;

class Profile extends Manager implements Api
{

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.
        return $this->http()->output()->json([
            'status' => true,
            'code' => HTTP::OK,
            'title' => 'Profile Successful',
            'message' => "Retrieved user data successfully.",
            'response' => [
                'user' => $this->user()->getUserArray()
            ]
        ]);
    }
}