<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 11/7/2020
 * Time: 1:02 PM
 */

namespace utility;


use Exception;
use que\database\interfaces\model\Model;
use que\support\Str;

trait Wallet
{
    private ?Model $wallet = null;

    public function __construct()
    {
        $restarted = false;

        restart:

        $wallet = db()->find('wallets', user('id'), 'user_id');

        if (!$wallet->isSuccessful() && !$restarted) {
            $this->createWallet();
            $restarted = true;
            goto restart;
        }

        $this->wallet = $wallet->isSuccessful() ? $wallet->getFirstWithModel() : null;
    }

    /**
     * @return bool
     */
    private function createWallet() {
        return db()->insert('wallets', [
            'uuid' => Str::uuidv4(),
            'user_id' => user('id')
        ])->isSuccessful();
    }

    /**
     * @return float
     */
    public function getBalance()
    {
        return $this->wallet && $this->wallet->validate(
            'is_active')->isEqual(true) ? $this->wallet->getFloat('balance') : 0.0;
    }

    /**
     * @return float
     */
    public function getAvailableBalance()
    {
        return $this->wallet  && $this->wallet->validate(
            'is_active')->isEqual(true) ? $this->wallet->getFloat('available_balance') : 0.0;
    }

    /**
     * @param float $amount
     * @return false|float
     * @throws Exception
     */
    public function creditWallet(float $amount) {
        $balance = ($this->getBalance() + $amount);
        $avail_bal = (($bal = $this->getAvailableBalance()) + $amount);
        if ($this->updateBothBalance($balance, $avail_bal)) return $avail_bal;
        return false;
    }

    /**
     * @param float $amount
     * @return false|float
     * @throws Exception
     */
    public function debitWallet(float $amount) {
        $balance = $this->getBalance();
        $avail_bal = $this->getAvailableBalance();
        if ($amount > $avail_bal) throw new Exception("Insufficient fund");
        if ($this->updateBothBalance(($balance - $amount), $bal = ($avail_bal - $amount))) return $bal;
        return false;
    }

    /**
     * @param float $amount
     * @return float
     * @throws Exception
     */
    public function lockFund(float $amount) {
        $balance = $this->getAvailableBalance();
        if ($amount > $balance) throw new Exception("Insufficient fund");
        if ($this->updateAvailableBalance($balance = ($balance - $amount))) return $balance;
        return false;
    }

    /**
     * @param float $amount
     * @return float
     * @throws Exception
     */
    public function retrieveLockedFund(float $amount) {
        $balance = $this->getBalance();
        $avail_bal = $this->getAvailableBalance();
        $lockedFund = ($balance - $avail_bal);
        if ($amount > $lockedFund) throw new Exception("Insufficient fund");
        if ($this->updateBalance($balance = ($balance - ($lockedFund - $amount)))) return $balance;
        return false;
    }

    /**
     * @param float $balance
     * @return bool
     * @throws Exception
     */
    private function updateBalance(float $balance)
    {
        if (!$this->wallet) throw new Exception("No wallet found");

        if ($this->wallet->validate('is_active')->isNotEqual(true))
            throw new Exception("Wallet is deactivated");

        if ($this->wallet->validate('is_frozen')->isEqual(true))
            throw new Exception("Wallet is frozen");

        return $this->wallet->update(['balance' => $balance]);
    }

    /**
     * @param float $balance
     * @return bool
     * @throws Exception
     */
    private function updateAvailableBalance(float $balance)
    {
        if (!$this->wallet) throw new Exception("No wallet found");

        if ($this->wallet->validate('is_active')->isNotEqual(true))
            throw new Exception("Wallet is deactivated");

        if ($this->wallet->validate('is_frozen')->isEqual(true))
            throw new Exception("Wallet is frozen");

        return $this->wallet->update(['available_balance' => $balance]);
    }

    /**
     * @param float $balance
     * @param float $avail_bal
     * @return bool
     * @throws Exception
     */
    private function updateBothBalance(float $balance, float $avail_bal)
    {
        if (!$this->wallet) throw new Exception("No wallet found");

        if ($this->wallet->validate('is_active')->isNotEqual(true))
            throw new Exception("Wallet is deactivated");

        if ($this->wallet->validate('is_frozen')->isEqual(true))
            throw new Exception("Wallet is frozen");

        return $this->wallet->update([
            'balance' => $balance,
            'available_balance' => $avail_bal
        ]);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isFrozenWallet() {
        if (!$this->wallet) throw new Exception("No wallet found");
        return $this->wallet->getBool('is_frozen');
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isActiveWallet() {
        if (!$this->wallet) throw new Exception("No wallet found");
        return $this->wallet->getBool('is_active');
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function freezeWallet()
    {
        if (!$this->wallet) throw new Exception("No wallet found");
        return $this->wallet->update(['is_frozen' => true]);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function deactivateWallet()
    {
        if (!$this->wallet) throw new Exception("No wallet found");
        return $this->wallet->update(['is_active' => false]);
    }
}