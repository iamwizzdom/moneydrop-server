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
    const INCOME_TYPE_REGULAR = 1;
    const INCOME_TYPE_IRREGULAR = -1;

    protected string $modelKey = 'bankAccountModel';
    protected array $copy = ['account_number' => 'acct_no'];
    protected array $casts = ['is_active' => 'bool', 'created_at' => 'date:d/m/y'];
    protected array $hidden = ['account_id', 'account_number', 'income'];

    public function addCasts(): ?array
    {
        if (empty($this->acct_no)) return null;
        $limit = strlen($this->getValue('account_number')) - 4;
        return ['acct_no' => "func::hide_number,:subject,0,$limit"];
    }

    public function getUser() {
        return $this->belongTo('users', 'user_id');
    }
}
