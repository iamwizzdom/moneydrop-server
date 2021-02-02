<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 5/9/2020
 * Time: 7:20 PM
 */

namespace model;

use loan\Loan;
use que\database\model\Model;

class Wallet extends Model
{
    protected string $modelKey = 'walletModel';
    protected array $fillable = ['uuid', 'balance', 'available_balance', 'user_id', 'is_frozen', 'is_active'];
    protected array $casts = ['is_frozen', 'is_active'];

    public function getUser() {
        return $this->belongTo('users', 'user_id');
    }

}
