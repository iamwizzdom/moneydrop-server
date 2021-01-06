<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 5/9/2020
 * Time: 7:20 PM
 */

namespace custom\model;

use que\database\model\Model;

class TransactionModel extends Model
{
    protected string $modelKey = 'transactionModel';
    protected array $appends = ['card', 'status', 'type', 'direction', 'date_time'];
    protected array $casts = ['comment' => 'string', 'fees' => 'double', 'created_at' => 'date:d/m/y'];
    protected array $hidden = ['updated_at'];
    protected array $renames = ['created_at' => 'date'];

    public function getCard(): ?\que\database\interfaces\model\Model
    {
        return $this->belongTo('cards', 'card_id', 'uuid', 'cardModel');
    }

    public function getStatus() {
        $status = converter()->convertEnvConst($this->getValue('status'), "APPROVAL_");
        return ucfirst(strtolower($status));
    }

    public function getType() {
        $type = converter()->convertEnvConst($this->getValue('type'), "TRANSACTION_");
        return ucfirst(str_replace("_", "-", $type));
    }

    public function getDirection() {
        switch ($this->getValue('direction')) {
            case 'w2w':
                return "Wallet to wallet";
            case 'w2b':
                return "Wallet to bank";
            case 'b2w':
                return "Bank to wallet";
            case 'w2s':
                return "Wallet to system";
            case 's2w':
                return "System to wallet";
            default:
                return "Unknown";
        }
    }

    public function getDateTime() {
        return get_date("jS, M 'y h:i A", $this->getValue('created_at'));
    }

}
