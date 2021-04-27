<?php

use model\Loan;
use model\LoanApplication;
use que\utility\money\Item;

require '../app.settings.php';
require '../app.misc.php';

//$user = db()->find('users', 48);
//\que\user\User::login($user->getFirst());

//$item = new \que\utility\money\Item("100000.00");

//$item = \que\utility\money\Item::cents("100000.00");

//debug_print([$item->getFactor()]);

//class Notice {
//    use \utility\firebase\Firebase;
//}

//$notice = new Notice();
//$push = new \model\Push();

//$n = db()->find('notifications', 217);
//$n->setModelKey('notificationModel');
//$model = $n->getFirstWithModel();
//$push->setTitle($model->title);
//$push->setMessage($model->message);
//$push->setImage($model->image);
//$push->setPayload(['activity' => $model->activity, 'data' => (object) $model->payload]);
//$n = $notice->send($model->user->pn_token, $push);
//$n = $notice->send(user('pn_token'), $push);

//debug_print($model->user->pn_token, $n->getResponseArray());

//$t = db()->update()->table('loans')
//    ->columns(['is_fund_raiser' => true])
//    ->where('loan_type', -7)->exec();

//$t = db()->insert('transactions', [
//    'uuid' => \que\support\Str::uuidv4(),
//    'user_id' => user('id'),
//    'transaction_state' => TRANSACTION_CHARGE,
//    'transaction_type' => TRANSACTION_DEBIT,
//    'amount' => 1100,
//    'status' => APPROVAL_SUCCESSFUL
//]);

//debug_print($t);

//$date1 = date_create("2007-03-24");
//$date2 = date_create("2009-06-26");
//
//$dateDifference = date_diff($date1, $date2)->days / 7;
//
//echo $dateDifference;


//$date = new DateTime("2021-03-16 11:31:05");
//$to = new DateTime('now');
//$days = $date->diff($to)->days;
//$weeks = $days / 7;
//$months = (12 * $date->diff($to)->y) + $date->diff($to)->m;
//echo debug_print([$days, $weeks, $months, $date->diff($to)->y, $date->diff($to)->m]);

//echo \que\utility\hash\Hash::sha('K/r3L&Y6$dq52YFn-197.210.178.67-AzZY?9QWmn29QjDK');

$count = db()->select('max(l.amount) as max_amount')->table('loan_applications as la')
    ->join('loans as l', 'la.loan_id', 'l.uuid')
    ->where('l.loan_type', Loan::LOAN_TYPE_OFFER)
    ->where('la.user_id', 48)
    ->where('la.status', LoanApplication::STATUS_REPAID)
    ->limit(1)
    ->exec();

debug_print([$count->getQueryResponse(), $count->getQueryString()]);

//if (preg_match('/(.*?)\((.*?)\)/', "max(l.amount)", $matches)) {
//    debug_print($matches);
//}