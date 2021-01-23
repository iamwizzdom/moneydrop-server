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
    protected ?Model $wallet = null;

    public function __construct()
    {
        $this->loadWallet();
    }

    /**
     * @param int|null $userID
     * @param int|null $walletID
     */
    protected function loadWallet(?int $userID = null, int $walletID = null): void {

        $restarted = false;

        restart:

        $wallet = db()->find('wallets', $walletID ?: ($userID ?: user('id')), $walletID ? 'id' : 'user_id');

        if (!$wallet->isSuccessful() && !$restarted) {
            if (!$walletID) $this->createWallet(($userID ?: user('id')));
            $restarted = true;
            goto restart;
        }

        $this->wallet = $wallet->isSuccessful() ? $wallet->getFirstWithModel() : null;
    }

    /**
     * @param int|null $userID
     * @return bool
     */
    private function createWallet(int $userID = null): bool
    {
        return db()->insert('wallets', [
            'uuid' => Str::uuidv4(),
            'user_id' => $userID
        ])->isSuccessful();
    }

    /**
     * @return float
     */
    public function getBalance(): float
    {
        return $this->wallet && $this->wallet->validate(
            'is_active')->isEqual(true) ? $this->wallet->getFloat('balance') : 0.0;
    }

    /**
     * @return float
     */
    public function getAvailableBalance(): float
    {
        return $this->wallet  && $this->wallet->validate(
            'is_active')->isEqual(true) ? $this->wallet->getFloat('available_balance') : 0.0;
    }

    /**
     * @param float $amount
     * @return false|float
     * @throws Exception
     */
    public function creditWallet(float $amount): float|bool
    {
        $balance = ($this->getBalance() + $amount);
        $availableBalance = ($this->getAvailableBalance() + $amount);
        if ($this->updateBothBalance($balance, $availableBalance, true)) return $availableBalance;
        return false;
    }

    /**
     * @param float $amount
     * @return false|float
     * @throws Exception
     */
    public function debitWallet(float $amount): float|bool
    {
        $balance = $this->getBalance();
        $availableBalance = $this->getAvailableBalance();
        if ($amount > $availableBalance) throw new Exception("Insufficient fund");
        if ($this->updateBothBalance(($balance - $amount), $bal = ($availableBalance - $amount))) return $bal;
        return false;
    }

    /**
     * @param float $amount
     * @return float|bool
     * @throws Exception
     */
    public function lockFund(float $amount): float|bool
    {
        $availableBalance = $this->getAvailableBalance();
        if ($amount > $availableBalance) throw new Exception("Insufficient fund");
        $availableBalance = ($availableBalance - $amount);
        if ($this->updateAvailableBalance($availableBalance, true)) return $availableBalance;
        return false;
    }

    /**
     * @param float $amount
     * @return float|bool
     * @throws Exception
     */
    public function unlockFund(float $amount): float|bool
    {
        $availableBalance = $this->getAvailableBalance();
        $balance = $this->getBalance();
        if ($amount > $balance) throw new Exception("Insufficient fund");
        $balance = ($balance - $amount);
        $availableBalance = ($availableBalance + $amount);
        if ($this->updateBothBalance($balance, $availableBalance,true)) return $balance;
        return false;
    }

    /**
     * @param float $amount
     * @return float|bool
     * @throws Exception
     */
    public function debitLockedFund(float $amount): float|bool
    {
        $balance = $this->getBalance();
        $lockedFund = ($balance - $this->getAvailableBalance());
        if ($amount > $lockedFund) throw new Exception("Insufficient fund");
        if ($this->updateBalance($balance = ($balance - $amount))) return $balance;
        return false;
    }

    /**
     * @param float $balance
     * @param bool $forceUpdate
     * @return bool
     * @throws Exception
     */
    private function updateBalance(float $balance, bool $forceUpdate = false): bool
    {
        if (!$this->wallet) throw new Exception("No wallet found");

        if (!$forceUpdate && $this->wallet->validate('is_active')->isNotEqual(true))
            throw new Exception("Wallet is deactivated");

        if (!$forceUpdate && $this->wallet->validate('is_frozen')->isEqual(true))
            throw new Exception("Wallet is frozen");

        return !!$this->wallet->update(['balance' => $balance])?->isSuccessful();
    }

    /**
     * @param float $balance
     * @param bool $forceUpdate
     * @return bool
     * @throws Exception
     */
    private function updateAvailableBalance(float $balance, bool $forceUpdate = false): bool
    {
        if (!$this->wallet) throw new Exception("No wallet found");

        if (!$forceUpdate && $this->wallet->validate('is_active')->isNotEqual(true))
            throw new Exception("Wallet is deactivated");

        if (!$forceUpdate && $this->wallet->validate('is_frozen')->isEqual(true))
            throw new Exception("Wallet is frozen");

        return !!$this->wallet->update(['available_balance' => $balance])?->isSuccessful();
    }

    /**
     * @param float $balance
     * @param float $availableBalance
     * @param bool $forceUpdate
     * @return bool
     * @throws Exception
     */
    private function updateBothBalance(float $balance, float $availableBalance, bool $forceUpdate = false): bool
    {
        if (!$this->wallet) throw new Exception("No wallet found");

        if (!$forceUpdate && $this->wallet->validate('is_active')->isNotEqual(true))
            throw new Exception("Wallet is deactivated");

        if (!$forceUpdate && $this->wallet->validate('is_frozen')->isEqual(true))
            throw new Exception("Wallet is frozen");

        return !!$this->wallet->update([
            'balance' => $balance,
            'available_balance' => $availableBalance
        ])?->isSuccessful();
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isFrozenWallet(): bool
    {
        if (!$this->wallet) throw new Exception("No wallet found");
        return $this->wallet->getBool('is_frozen');
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isActiveWallet(): bool
    {
        if (!$this->wallet) throw new Exception("No wallet found");
        return $this->wallet->getBool('is_active');
    }

    /**
     * @return bool
     */
    public function refreshWallet(): bool
    {
        return $this->wallet ? $this->wallet->refresh() : false;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function freezeWallet(): bool
    {
        if (!$this->wallet) throw new Exception("No wallet found");
        return !!$this->wallet->update(['is_frozen' => true])?->isSuccessful();
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function deactivateWallet(): bool
    {
        if (!$this->wallet) throw new Exception("No wallet found");
        return !!$this->wallet->update(['is_active' => false])?->isSuccessful();
    }
}
