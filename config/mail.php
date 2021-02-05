<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 4/21/2020
 * Time: 1:07 PM
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Mail Configuration
    |--------------------------------------------------------------------------
    |
    | Here are each of the mail configuration setup for your application.
    |
    */
    'address' => [
        'reply' => '',
        'default' => ''
    ],
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => '465',
        'username' => '',
        'password' => '',
        'transport' => 'ssl',
        'timeout' => 120,
        'debug' => '',
        'options' => [

            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ],
        'auth' => true,
        'remote' => true
    ]
];