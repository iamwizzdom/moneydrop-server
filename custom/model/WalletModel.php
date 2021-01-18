<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 5/9/2020
 * Time: 7:20 PM
 */

namespace custom\model;

use loan\Loan;
use que\database\model\Model;

class WalletModel extends Model
{
    protected string $modelKey = 'walletModel';

    public function getUser() {
        return $this->belongTo('users', 'user_id');
    }

}
