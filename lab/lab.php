<?php
require '../app.settings.php';

$user = db()->find('users', 48);
\que\user\User::login($user->getFirst());

$t = db()->update()->table('transactions')
    ->columns(['amount' => 8500.50])
    ->where('id', 2454)->exec();

//$t = db()->insert('transactions', [
//    'uuid' => \que\support\Str::uuidv4(),
//    'user_id' => user('id'),
//    'transaction_state' => TRANSACTION_CHARGE,
//    'transaction_type' => TRANSACTION_DEBIT,
//    'amount' => 1100,
//    'status' => APPROVAL_SUCCESSFUL
//]);

debug_print($t);
