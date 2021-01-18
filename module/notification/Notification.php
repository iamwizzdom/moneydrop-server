<?php


namespace notification;


use que\http\input\Input;
use que\http\output\response\Html;
use que\http\output\response\Json;
use que\http\output\response\Jsonp;
use que\http\output\response\Plain;

class Notification extends \que\common\manager\Manager implements \que\common\structure\Api
{

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.
        return [
            'pagination' => [
                'page' => 1,
                'totalRecords' => 1,
                'totalPages' => 1,
                'nextPage' => '#',
                'previousPage' => '#'
            ],
            'notifications' => [
                [
                    'id' => 1,
                    'uuid' => '4cfb8a36-05fa-4912-b4f0-a833e3e119e4',
                    'notice' => "Welcome to moneydrop. Money is now made easy to access.",
                    'date_time' => date(DATE_FORMAT_MYSQL)
                ],
                [
                    'id' => 2,
                    'uuid' => '4cfb8a36-05fa-4912-b4f0-a833e3e119e4',
                    'notice' => "Welcome to moneydrop. Money is now made easy to access.",
                    'date_time' => date(DATE_FORMAT_MYSQL)
                ],
                [
                    'id' => 3,
                    'uuid' => '4cfb8a36-05fa-4912-b4f0-a833e3e119e4',
                    'notice' => "Welcome to moneydrop. Money is now made easy to access.",
                    'date_time' => date(DATE_FORMAT_MYSQL)
                ],
                [
                    'id' => 4,
                    'uuid' => '4cfb8a36-05fa-4912-b4f0-a833e3e119e4',
                    'notice' => "Welcome to moneydrop. Money is now made easy to access.",
                    'date_time' => date(DATE_FORMAT_MYSQL)
                ],
            ]
        ];
    }
}
