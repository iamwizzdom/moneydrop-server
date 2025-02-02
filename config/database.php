<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 4/20/2020
 * Time: 9:34 AM
 */

use model\BankAccount;
use model\BankStatement;
use model\Card;
use model\LoanApplication;
use model\LoanRepayment;
use model\Notification;
use model\Review;
use model\User;
use model\Loan;
use model\Transaction;
use model\Wallet;
use observers\BankAccountObserver;
use observers\LoanApplicationObserver;
use observers\LoanObserver;
use observers\LoanRepaymentObserver;
use observers\NotificationObserver;
use observers\TransactionObserver;
use observers\UserObserver;
use observers\VerificationObserver;
use observers\WalletObserver;
use que\database\drivers\mysql\MySqlDriver;
use que\database\model\CentralModel;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Operation Config
    |--------------------------------------------------------------------------
    |
    | Here you may specify a default driver and model
    |
    */
    'default' => [

        /*
        |--------------------------------------------------------------------------
        | Default Database Driver
        |--------------------------------------------------------------------------
        |
        | Here you may specify which of the database drivers below you wish
        | to use as your default driver for all database work.
        |
        */
        'driver' => env('DB_DRIVER'),

        /*
        |--------------------------------------------------------------------------
        | Default Database Model
        |--------------------------------------------------------------------------
        |
        | Here you may specify which of the database models below you wish
        | to use as your default model for all database work.
        |
        */
        'model' => 'que'
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Drivers
    |--------------------------------------------------------------------------
    |
    | Here are a list of database drivers you can use for database works.
    | Of course, you can add your own custom drivers to this list.
    |
    */
    'drivers' => [
        'mysql' => MySqlDriver::class
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Models
    |--------------------------------------------------------------------------
    |
    | Here are a list of database models you can use for database works.
    | Of course, you can add your own custom models to this list.
    |
    */
    'models' => [
        'que' => CentralModel::class,
        'walletModel' => Wallet::class,
        'loanModel' => Loan::class,
        'transactionModel' => Transaction::class,
        'userModel' => User::class,
        'cardModel' => Card::class,
        'bankAccountModel' => BankAccount::class,
        'loanApplicationModel' => LoanApplication::class,
        'loanRepaymentModel' => LoanRepayment::class,
        'reviewModel' => Review::class,
        'notificationModel' => Notification::class,
        'bankStatement' => BankStatement::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Observers
    |--------------------------------------------------------------------------
    |
    | Here are a list of database observers used for observing your database tables.
    | Make sure to use your database table name to register each observer.
    |
    */
    'observers' => [
        'users' => UserObserver::class,
        'wallets' => WalletObserver::class,
        'transactions' => TransactionObserver::class,
        'loans' => LoanObserver::class,
        'loan_applications' => LoanApplicationObserver::class,
        'loan_repayments' => LoanRepaymentObserver::class,
        'notifications' => NotificationObserver::class,
        'verifications' => VerificationObserver::class,
        'bank_accounts' => BankAccountObserver::class
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples and explanation of configuring each database platform that is
    | supported by Que is shown below to make development simple.
    |
    |
    | All system database work in Que are done through the PHP PDO facility,
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    | Of course, you can always create custom Que database drivers, and
    | use a connection facility of your choice other than the PHP PDO facility.
    |
    */
    'connections' => [

        'mysql' => [

            /*
             | database name
             */
            'dbname' => env('DB_DATABASE'),

            /*
             | db username
             */
            'username' => env('DB_USERNAME'),

            /*
             | db password
             */
            'password' => env('DB_PASSWORD'),

            /*
             | host / ip address
             */
            'host' => env('DB_HOST'),

            /*
             | MySQL port
             */
            'port' => env('DB_PORT'),

            /*
             | The MySQL Unix socket (shouldn't be used with host or port).
             | The character set. See the character set concepts documentation for more information.
             */
            'unix_socket' => null,

            /*
             | The character set. See the [character set](https://dev.mysql.com/doc/refman/5.7/en/charset-unicode-utf8mb4.html)
             | documentation for more information.
             */
            'charset' => null,

            /*
             | The collation set.
             */
            'collation' => null,

            /*
             | The MySQL database engine
             */
            'engine' => null,

            /*
             * Database timezone
             */
//            'timezone'  => '+00:00',
            'timezone'  => null,

            /*
             | Set for PDO::MYSQL SSL connection
             | For more info see: https://www.php.net/manual/en/ref.pdo-mysql.php
             */
            'ssl' => [

                /*
                 | The file path to the SSL key.
                 */
                'key' => null,

                /*
                 | The file path to the SSL certificate.
                 */
                'cert' => null,

                /*
                 | The file path to the SSL certificate authority.
                 */
                'ca' => null,

                /*
                 | The file path to the directory that contains the trusted
                 | SSL CA certificates, which are stored in PEM format.
                 */
                'capath' => null,

                /*
                 | A list of one or more permissible ciphers to use for SSL encryption,
                 | in a format understood by OpenSSL.
                 | For example: DHE-RSA-AES256-SHA:AES128-SHA
                 */
                'cipher' => null,

                /*
                 | Provides a way to disable verification of the server SSL certificate.
                 */
                'verify_server_cert' => null,
            ],

            /*
             | Set to [bool](true) to enable persistent connection,
             | otherwise [bool](false) to disable
             */
            'persist' => false,

            /*
             | Set to [bool](true) to shutdown Que and output all MySQL/SQL errors,
             | otherwise [bool](false) to output only FATAL errors
             */
            'debug' => !LIVE,

            /*
             * Array of more PDO config options
             */
            'options' => []
        ]

    ],

    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    |
    | Here are each of the database table setup for your application.
    | These tables are used natively by Que, so sure make to create them in your database
    |
    */
    'tables' => [

        /*
         | Your application user table
         */
        'user' => [

            /*
             | user table name
             */
            'name' => 'users',

            /*
             | user table primary key name
             */
            'primary_key' => 'id'

        ],

        /*
         | Your application country table
         */
        'country' => [

            /*
             | country table name
             */
            'name' => 'countries',

            /*
             | country table primary key name
             */
            'primary_key' => 'id'
        ],

        /*
         | Your application country states table
         */
        'state' => [

            /*
             | state table name
             */
            'name' => 'states',

            /*
             | state table primary key name
             */
            'primary_key' => 'id'
        ],

        /*
         | Your application state LGA table
         */
        'area' => [

            /*
             | LGA table name
             */
            'name' => '',

            /*
             | LGA table primary key name
             */
            'primary_key' => ''
        ],

        /*
         | Your application language table
         */
        'language' => [

            /*
             | language table name
             */
            'name' => '',

            /*
             | language table primary key name
             */
            'primary_key' => ''
        ]

    ],

    /*
    | Table column name for managing record status.
    | This column should be present in all tables in your application.
    | @Note: It's strongly recommended that you make this a boolean column with tinyint(1)
    */
    'table_status_key' => 'is_active'
];
