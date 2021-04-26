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

class BankAccount extends Model
{
    protected string $modelKey = 'bankAccountModel';
    protected array $casts = ['is_active' => 'bool', 'created_at' => 'date:d/m/y'];
    protected array $hidden = ['income'];

    public function getUser() {
        return $this->belongTo('users', 'user_id');
    }
}
