<?php

use module\access\ForgotPassword;
use module\access\Login;
use module\access\PasswordReset;
use module\access\Register;
use module\access\Verification;
use module\home\Dashboard;
use profile\Card;
use profile\Loan;
use profile\Transaction;
use profile\Update;
use que\route\Route;
use que\route\RouteEntry;

require 'app.settings.php';

Route::register()->groupApi('api/v1', function ($prefix) {

    Route::register()->groupApi("{$prefix}/auth", function ($prefix) {

        return [
            function (RouteEntry $entry) {
                $entry->allowPostRequest();
                $entry->setUri('/login');
                $entry->setModule(Login::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest();
                $entry->setUri('/register');
                $entry->where('id', "alpha");
                $entry->setModule(Register::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest();
                $entry->setUri('/verification/{type:alpha}/{action:alpha}');
                $entry->setModule(Verification::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest();
                $entry->setUri('/password/forgot');
                $entry->setModule(ForgotPassword::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest();
                $entry->setUri('/password/reset');
                $entry->setModule(PasswordReset::class);
            }
        ];
    });

    return [
        function (RouteEntry $entry) {
            $entry->allowGetRequest();
            $entry->setMiddleware('user.auth');
            $entry->setUri('/dashboard');
            $entry->setModule(Dashboard::class);
        },
        function (RouteEntry $entry) {
            $entry->allowPostRequest();
            $entry->setMiddleware('user.auth');
            $entry->setUri('/profile/update/{type:alpha}');
            $entry->setModule(Update::class);
        },
        function (RouteEntry $entry) {
            $entry->allowPostRequest()->allowGetRequest();
            $entry->setMiddleware('user.auth');
            $entry->setUri('/user/card/{type:alpha}/{subtype:alpha|uuid}');
            $entry->setModule(Card::class);
        },
        function (RouteEntry $entry) {
            $entry->allowPostRequest()->allowGetRequest();
            $entry->setMiddleware('user.auth');
            $entry->setUri('/user/loan/{type:alpha}/');
            $entry->setModule(Loan::class);
        },
        function (RouteEntry $entry) {
            $entry->allowPostRequest()->allowGetRequest();
            $entry->setMiddleware('user.auth');
            $entry->setUri('/user/transactions');
            $entry->setModule(Transaction::class);
        },
    ];

});

Route::init();
