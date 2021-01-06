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

class CardModel extends Model
{
    protected string $modelKey = 'cardModel';
    protected array $appends = ['card_type', 'last4', 'brand', 'exp_month', 'exp_year'];
    protected array $casts = ['is_active' => 'bool', 'created_at' => 'date:d/m/y'];

    public function getCardType() {
        return $this->getValue('auth.card_type');
    }

    public function getLast4() {
        return $this->getValue('auth.last4');
    }

    public function getBrand() {
        return $this->getValue('auth.brand');
    }

    public function getExpMonth() {
        return $this->getValue('auth.exp_month');
    }

    public function getExpYear() {
        return $this->getValue('auth.exp_year');
    }

}
