<?php

use auth\Google;
use history\History;
use loan\LoanApplication;
use auth\ForgotPassword;
use auth\Login;
use auth\PasswordReset;
use auth\Register;
use auth\Verification;
use loan\LoanApprove;
use loan\LoanDecline;
use loan\Repayment;
use location\Country;
use location\State;
use module\home\Dashboard;
use module\profile\Profile;
use module\profile\Rate;
use module\review\Review;
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

Route::register()->groupApi('api/v1/m-app', function ($prefix) {

    Route::register()->groupApi("{$prefix}/auth", function ($prefix) {

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
                $entry->setUri('/login-with-google');
                $entry->setModule(Google::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest();
                $entry->forbidCSRF(false);
                $entry->setUri('/register');
                $entry->where('id', "alpha");
                $entry->setModule(Register::class);
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

    Route::register()->groupApi("{$prefix}/user", function ($prefix) {

        return [
            function (RouteEntry $entry) {
                $entry->allowGetRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/profile/info');
                $entry->setModule(Profile::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPutRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/profile/update/{type:alpha|/^[a-zA-Z0-9\-_]+$/}');
                $entry->setModule(Update::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest()->allowGetRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/card/{type:alpha}/{subtype:alpha|uuid}');
                $entry->setModule(Card::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest()->allowGetRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/bank/{type:/^[a-zA-Z0-9-]+$/}/{?id:alpha|uuid}');
                $entry->setModule(Bank::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest()->allowGetRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/wallet/{type:/^[a-zA-Z0-9-]+$/}/{id:uuid|/^[a-zA-Z0-9_]+$/}');
                $entry->setModule(Wallet::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest()->allowGetRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/loan/{type:alpha}');
                $entry->setModule(Loan::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest()->allowGetRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/transactions');
                $entry->setModule(Transaction::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/rate');
                $entry->setModule(Rate::class);
            },
            function (RouteEntry $entry) {
                $entry->allowGetRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/{id:uuid}/reviews');
                $entry->setModule(Review::class);
                $entry->setModuleMethod("viewReviews");
            },
        ];
    });

    Route::register()->groupApi("{$prefix}/loan", function ($prefix) {

        return [
            function (RouteEntry $entry) {
                $entry->allowPostRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/application/{id:uuid}/repayment');
                $entry->setModule(Repayment::class);
            },
            function (RouteEntry $entry) {
                $entry->allowGetRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/application/{id:uuid}/repayment/history');
                $entry->setModuleMethod("history");
                $entry->setModule(Repayment::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest()->allowGetRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/{type:alpha}');
                $entry->setModule(\loan\Loan::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/{id:uuid}/revoke');
                $entry->setModuleMethod('revoke');
                $entry->setModule(\loan\Loan::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest()->allowGetRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/{id:uuid}/{type:alpha}');
                $entry->setModule(LoanApplication::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPutRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/{id:uuid}/application/{_id:uuid}/grant');
                $entry->setModule(LoanApplication::class);
                $entry->setModuleMethod("grantApplication");
            },
            function (RouteEntry $entry) {
                $entry->allowDeleteRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/{id:uuid}/application/{_id:uuid}/cancel');
                $entry->setModule(LoanApplication::class);
                $entry->setModuleMethod("cancelApplication");
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/application/{id:uuid}/review');
                $entry->setModule(Review::class);
            },
        ];
    });

    return [
        function (RouteEntry $entry) {
            $entry->allowGetRequest();
            $entry->forbidCSRF(false);
            $entry->setMiddleware('user.auth');
            $entry->setUri('/dashboard');
            $entry->setModule(Dashboard::class);
        },
        function (RouteEntry $entry) {
            $entry->allowGetRequest();
            $entry->forbidCSRF(false);
            $entry->setMiddleware('user.auth');
            $entry->setUri('/notifications');
            $entry->setModule(Notification::class);
        },
        function (RouteEntry $entry) {
            $entry->allowGetRequest();
            $entry->forbidCSRF(false);
            $entry->setMiddleware('user.auth');
            $entry->setUri('/history');
            $entry->setModule(History::class);
        },
        function (RouteEntry $entry) {
            $entry->allowPutRequest();
            $entry->forbidCSRF(false);
            $entry->setMiddleware('user.auth');
            $entry->setUri('/review/{id:uuid}/edit');
            $entry->setModule(Review::class);
            $entry->setModuleMethod('editReview');
        },
        function (RouteEntry $entry) {
            $entry->allowDeleteRequest();
            $entry->forbidCSRF(false);
            $entry->setMiddleware('user.auth');
            $entry->setUri('/review/{id:uuid}/delete');
            $entry->setModule(Review::class);
            $entry->setModuleMethod('deleteReview');
        },
        function (RouteEntry $entry) {
            $entry->allowGetRequest();
            $entry->forbidCSRF(false);
            $entry->setUri('/import/countries');
            $entry->setModule(Country::class);
        },
        function (RouteEntry $entry) {
            $entry->allowGetRequest();
            $entry->forbidCSRF(false);
            $entry->setUri('/import/states');
            $entry->setModule(State::class);
        },
    ];

});

Route::register()->groupApi('api/v1/w-app', function ($prefix) {

    Route::register()->groupApi("{$prefix}/auth", function ($prefix) {

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
                $entry->setUri('/login-with-google');
                $entry->setModule(Google::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest();
                $entry->forbidCSRF(false);
                $entry->setUri('/register');
                $entry->where('id', "alpha");
                $entry->setModule(Register::class);
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
    }, function (RouteEntry $entry) {
        $entry->setMiddleware('expire.jwt');
        $entry->allowOptionsRequest();
    });

    Route::register()->groupApi("{$prefix}/user", function ($prefix) {

        return [
            function (RouteEntry $entry) {
                $entry->allowGetRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/profile/info');
                $entry->setModule(Profile::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPutRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/profile/update/{type:alpha|/^[a-zA-Z0-9\-_]+$/}');
                $entry->setModule(Update::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest()->allowGetRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/card/{type:alpha}/{subtype:alpha|uuid}');
                $entry->setModule(Card::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest()->allowGetRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/bank/{type:/^[a-zA-Z0-9-]+$/}/{?id:alpha|uuid}');
                $entry->setModule(Bank::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest()->allowGetRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/wallet/{type:/^[a-zA-Z0-9-]+$/}/{id:uuid|/^[a-zA-Z0-9_]+$/}');
                $entry->setName('web-top-top');
                $entry->setModule(Wallet::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest()->allowGetRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/loan/{type:alpha}');
                $entry->setModule(Loan::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest()->allowGetRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/transactions');
                $entry->setModule(Transaction::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/rate');
                $entry->setModule(Rate::class);
            },
            function (RouteEntry $entry) {
                $entry->allowGetRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/{id:uuid}/reviews');
                $entry->setModule(Review::class);
                $entry->setModuleMethod("viewReviews");
            },
        ];
    }, function (RouteEntry $entry) {
        $entry->setMiddleware('expire.jwt');
        $entry->allowOptionsRequest();
    });

    Route::register()->groupApi("{$prefix}/loan", function ($prefix) {

        return [
            function (RouteEntry $entry) {
                $entry->allowPostRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/application/{id:uuid}/repayment');
                $entry->setModule(Repayment::class);
            },
            function (RouteEntry $entry) {
                $entry->allowGetRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/application/{id:uuid}/repayment/history');
                $entry->setModuleMethod("history");
                $entry->setModule(Repayment::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest()->allowGetRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/{type:alpha}');
                $entry->setModule(\loan\Loan::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/{id:uuid}/revoke');
                $entry->setModuleMethod('revoke');
                $entry->setModule(\loan\Loan::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest()->allowGetRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/{id:uuid}/{type:alpha}');
                $entry->setModule(LoanApplication::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPutRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/{id:uuid}/application/{_id:uuid}/grant');
                $entry->setModule(LoanApplication::class);
                $entry->setModuleMethod("grantApplication");
            },
            function (RouteEntry $entry) {
                $entry->allowDeleteRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/{id:uuid}/application/{_id:uuid}/cancel');
                $entry->setModule(LoanApplication::class);
                $entry->setModuleMethod("cancelApplication");
            },
            function (RouteEntry $entry) {
                $entry->allowPostRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('user.auth');
                $entry->setUri('/application/{id:uuid}/review');
                $entry->setModule(Review::class);
            },
        ];
    }, function (RouteEntry $entry) {
        $entry->setMiddleware('expire.jwt');
        $entry->allowOptionsRequest();
    });

    return [
        function (RouteEntry $entry) {
            $entry->allowGetRequest();
            $entry->forbidCSRF(false);
            $entry->setMiddleware('user.auth');
            $entry->setUri('/dashboard');
            $entry->setModule(Dashboard::class);
        },
        function (RouteEntry $entry) {
            $entry->allowGetRequest();
            $entry->forbidCSRF(false);
            $entry->setMiddleware('user.auth');
            $entry->setUri('/notifications');
            $entry->setModule(Notification::class);
        },
        function (RouteEntry $entry) {
            $entry->allowGetRequest();
            $entry->forbidCSRF(false);
            $entry->setMiddleware('user.auth');
            $entry->setUri('/history');
            $entry->setModule(History::class);
        },
        function (RouteEntry $entry) {
            $entry->allowPutRequest();
            $entry->forbidCSRF(false);
            $entry->setMiddleware('user.auth');
            $entry->setUri('/review/{id:uuid}/edit');
            $entry->setModule(Review::class);
            $entry->setModuleMethod('editReview');
        },
        function (RouteEntry $entry) {
            $entry->allowDeleteRequest();
            $entry->forbidCSRF(false);
            $entry->setMiddleware('user.auth');
            $entry->setUri('/review/{id:uuid}/delete');
            $entry->setModule(Review::class);
            $entry->setModuleMethod('deleteReview');
        },
        function (RouteEntry $entry) {
            $entry->allowGetRequest();
            $entry->forbidCSRF(false);
            $entry->setUri('/import/countries');
            $entry->setModule(Country::class);
        },
        function (RouteEntry $entry) {
            $entry->allowGetRequest();
            $entry->forbidCSRF(false);
            $entry->setUri('/import/states');
            $entry->setModule(State::class);
        },
    ];

}, function (RouteEntry $entry) {
    $entry->setMiddleware('expire.jwt');
    $entry->allowOptionsRequest();
});

Route::register()->groupApi('api/v1/admin', function ($prefix) {

    Route::register()->groupApi("{$prefix}/loan", function ($prefix) {

        return [
            function (RouteEntry $entry) {
                $entry->allowPutRequest();
                $entry->allowPostRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('admin.auth');
                $entry->setUri('/{id:uuid}/approve');
                $entry->setAllowedIPs(['127.0.0.1', '172.31.18.17']);
                $entry->setModule(LoanApprove::class);
            },
            function (RouteEntry $entry) {
                $entry->allowPutRequest();
                $entry->forbidCSRF(false);
                $entry->setMiddleware('admin.auth');
                $entry->setUri('/{id:uuid}/decline');
                $entry->setAllowedIPs(['127.0.0.1', '172.31.18.17']);
                $entry->setModule(LoanDecline::class);
            }
        ];
    });

    return [];
});

Route::init();
