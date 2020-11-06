<?php

use module\access\ForgotPassword;
use module\access\Login;
use module\access\PasswordReset;
use module\access\Register;
use module\access\Verification;
use module\home\Dashboard;
use que\route\Route;
use que\route\RouteEntry;

require 'app.settings.php';

Route::register()->groupApi('api/v1', function () {

    return [
        function (RouteEntry $entry) {
            $entry->allowPostRequest();
            $entry->forbidCSRF(false);
            $entry->setUri('/login');
            $entry->setModule(Login::class);
        },
        function (RouteEntry $entry) {
            $entry->allowPostRequest();
            $entry->forbidCSRF(false);
            $entry->setUri('/register');
            $entry->where('id', "alpha");
            $entry->setModule(Register::class);
        },
        function (RouteEntry $entry) {
            $entry->allowGetRequest();
            $entry->forbidCSRF(false);
            $entry->setMiddleware('user.auth');
            $entry->setUri('/dashboard');
            $entry->setModule(Dashboard::class);
        },
        function (RouteEntry $entry) {
            $entry->allowPostRequest();
            $entry->forbidCSRF(false);
            $entry->setUri('/verification/{type:alpha}/{action:alpha}');
            $entry->setModule(Verification::class);
        },
        function (RouteEntry $entry) {
            $entry->allowPostRequest();
            $entry->forbidCSRF(false);
            $entry->setUri('/password/forgot');
            $entry->setModule(ForgotPassword::class);
        },
        function (RouteEntry $entry) {
            $entry->allowPostRequest();
            $entry->forbidCSRF(false);
            $entry->setUri('/password/reset');
            $entry->setModule(PasswordReset::class);
        }
    ];

});

Route::init();
