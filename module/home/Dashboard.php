<?php


namespace module\home;


use que\common\manager\Manager;
use que\common\structure\Api;
use que\http\input\Input;
use que\http\output\response\Html;
use que\http\output\response\Json;
use que\http\output\response\Jsonp;
use que\http\output\response\Plain;

class Dashboard extends Manager implements Api
{

    public function process(Input $input)
    {
        // TODO: Implement process() method.
        return $input->user()->getUserArray();
    }
}