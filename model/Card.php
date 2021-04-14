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

class Card extends Model
{
    protected string $modelKey = 'cardModel';
    protected array $fillable = ['uuid', 'auth', 'name', 'brand', 'exp_year', 'exp_month',
                                'last4digits', 'user_id', 'status', 'is_active'];
    protected array $casts = ['is_active' => 'bool', 'created_at' => 'date:d/m/y'];

    public function getUser() {
        return $this->belongTo('users', 'user_id');
    }
}
