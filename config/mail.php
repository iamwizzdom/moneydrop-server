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
        'reply' => 'obinnawisdom95@gmail.com',
        'default' => 'obinnawisdom95@gmail.com'
    ],
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => '465',
        'username' => 'wisdomemenike70@gmail.com',
        'password' => 'Godzpower@123',
        'transport' => 'ssl',
        'timeout' => 100,
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