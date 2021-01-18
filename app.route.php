<?php

use loan\LoanApplication;
use module\access\ForgotPassword;
use module\access\Login;
use module\access\PasswordReset;
use module\access\Register;
use module\access\Verification;
use module\home\Dashboard;
use notification\Notification;
use profile\Bank;
use profile\Card;
use profile\Loan;
use profile\Transaction;
use profile\Update;
use profile\Wallet;
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

    Route::register()->groupApi("{$prefix}/user", function ($prefix) {

        return [
            function (RouteEntry $entry) {
                $entry->allowPostRequest()->allowGetRequest();
                $entry->setMiddleware('user.auth');
                $entry->setUri('/card/{type:alpha}/{subtype:alpha|uuid}');
                $entry->setModule(Card::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest()->allowGetRequest();
                $entry->setMiddleware('user.auth');
                $entry->setUri('/bank/{type:/^[a-zA-Z0-9-]+$/}/{?id:alpha|uuid}');
                $entry->setModule(Bank::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest()->allowGetRequest();
                $entry->setMiddleware('user.auth');
                $entry->setUri('/wallet/{type:/^[a-zA-Z0-9-]+$/}/{id:uuid|/^[a-zA-Z0-9_]+$/}');
                $entry->setModule(Wallet::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest()->allowGetRequest();
                $entry->setMiddleware('user.auth');
                $entry->setUri('/loan/{type:alpha}');
                $entry->setModule(Loan::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest()->allowGetRequest();
                $entry->setMiddleware('user.auth');
                $entry->setUri('/transactions');
                $entry->setModule(Transaction::class);
            },
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
            $entry->setUri('/loan/{type:alpha}');
            $entry->setModule(\loan\Loan::class);
        },
        function (RouteEntry $entry) {
            $entry->allowPostRequest()->allowGetRequest();
            $entry->setMiddleware('user.auth');
            $entry->setUri('/loan/{id:uuid}/{type:alpha}');
            $entry->setModule(LoanApplication::class);
        },
        function (RouteEntry $entry) {
            $entry->allowPostRequest();
            $entry->setMiddleware('user.auth');
            $entry->setUri('/loan/{id:uuid}/{type:alpha}/{_id:uuid}/grant');
            $entry->setModule(LoanApplication::class);
        },
        function (RouteEntry $entry) {
            $entry->allowGetRequest();
            $entry->setMiddleware('user.auth');
            $entry->setUri('/notifications');
            $entry->setModule(Notification::class);
        },
    ];

});

Route::init();
