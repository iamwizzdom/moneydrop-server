<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 3/23/2021
 * Time: 2:10 PM
 */

namespace model;


use que\database\model\Model;

class BankStatement extends Model
{
    protected array $fillable = ['uuid', 'file', 'file_name', 'user_id', 'expires_at'];
    protected array $copy = ['expires_at' => 'expiration'];
    protected array $casts = ['expiration' => "date:jS M 'y"];
    protected array $appends = ['is_expired'];

    public function isExpired() {
        return $this->getValue('expires_at') && $this->validate('expires_at')->isDateLessThan(new \DateTime('now'));
    }
}