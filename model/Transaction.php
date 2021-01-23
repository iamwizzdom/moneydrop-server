<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 5/9/2020
 * Time: 7:20 PM
 */

namespace model;

use que\database\model\Model;

class Transaction extends Model
{
    protected string $modelKey = 'transactionModel';
    protected array $appends = ['card', 'status_readable', 'type_readable', 'direction_readable', 'date', 'date_time'];
    protected array $casts = ['narration' => 'string', 'fees' => 'double'];
    protected array $hidden = ['updated_at'];

    public function getUser(): ?\que\database\interfaces\model\Model
    {
        return $this->belongTo('users', 'user_id', 'id', 'userModel');
    }

    public function getCard(): ?\que\database\interfaces\model\Model
    {
        return $this->belongTo('cards', 'card_id', 'uuid', 'cardModel');
    }

    public function getToWallet(): ?\que\database\interfaces\model\Model
    {
        return $this->belongTo('wallets', 'to_wallet_id', 'id', 'walletModel');
    }

    public function getFromWallet(): ?\que\database\interfaces\model\Model
    {
        return $this->belongTo('wallets', 'from_wallet_id', 'id', 'walletModel');
    }

    public function getStatusReadable() {
        $status = converter()->convertEnvConst($this->getValue('status'), "APPROVAL_");
        return ucfirst(strtolower($status));
    }

    public function getTypeReadable() {
        $type = converter()->convertEnvConst($this->getValue('type'), "TRANSACTION_");
        return ucfirst(str_replace("_", "-", $type));
    }

    public function getDirectionReadable() {
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

    public function getDate() {
        $date = $this->getValue('created_at');
        return $date ? get_date("d/m/y", $date) : $date;
    }

    public function getDateTime() {
        $date = $this->getValue('created_at');
        return $date ? get_date("jS, M 'y h:i A", $date) : $date;
    }

}
