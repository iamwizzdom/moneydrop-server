<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 11/7/2020
 * Time: 1:02 PM
 */

namespace utility;


use Exception;
use model\Transaction;
use que\database\interfaces\model\Model;
use que\database\model\ModelQueryResponse;
use que\database\QueryResponse;
use que\support\Str;
use que\utility\money\Item;

trait Wallet
{
    protected ?Model $wallet = null;

    public function __construct()
    {
        $this->loadWallet();
    }

    public static function charge(float $amount, float $fee, ?string $reference, string $narration = null): QueryResponse
    {
        return db()->insert('transactions', [
            'uuid' => Str::uuidv4(),
            'user_id' => user('id'),
            'type' => Transaction::TRANS_TYPE_CHARGE,
            'gateway_reference' => $reference,
            'direction' => "w2s",
            'amount' => $amount,
            'fee' => $fee,
            'status' => Transaction::TRANS_STATUS_SUCCESSFUL,
            'narration' => $narration
        ]);
    }

    public static function reverseTransaction(Model $model): ?ModelQueryResponse
    {
        return $model->update(['status' => Transaction::TRANS_STATUS_REVERSED]);
    }

    /**
     * @param int|null $userID
     * @param int|null $walletID
     */
    protected function loadWallet(?int $userID = null, int $walletID = null): void {

        $restarted = false;

        restart:

        $wallet = db()->find('wallets', $walletID ?: ($userID ?: user('id')), $walletID ? 'id' : 'user_id');

        $wallet->setModelKey('walletModel');

        if (!$wallet->isSuccessful() && !$restarted) {
            if (!$walletID) $this->createWallet(($userID ?: user('id')));
            $restarted = true;
            goto restart;
        }

        $this->wallet = $wallet->isSuccessful() ? $wallet->getFirstWithModel()?->load('user') : null;
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
            'is_active')->isEqual(true) ? Item::cents(
            $this->wallet->getFloat('balance') ?: 0)->getFactor() : 0.0;
    }

    /**
     * @return float
     */
    public function getAvailableBalance(): float
    {
        return $this->wallet  && $this->wallet->validate(
            'is_active')->isEqual(true) ? Item::cents(
                $this->wallet->getFloat('available_balance') ?: 0)->getFactor() : 0.0;
    }

    /**
     * @param float $amount
     * @return false|float
     * @throws Exception
     */
    public function creditWallet(float $amount): float|bool
    {
        $balance = ((float) Item::factor($this->getBalance())->getCents() + $amount);
        $availableBalance = ((float) Item::factor($this->getAvailableBalance())->getCents() + $amount);
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
        $balance = (float) Item::factor($this->getBalance())->getCents();
        $availableBalance = (float) Item::factor($this->getAvailableBalance())->getCents();
        if ($amount > $availableBalance) throw new Exception("Insufficient fund");
        if ($this->updateBothBalance(($balance - $amount), $bal = ($availableBalance - $amount))) return $bal;
        return false;
    }

    /**
     * @param float $amount
     * @param bool $forceLock
     * @return float|bool
     * @throws Exception
     */
    public function lockFund(float $amount, bool $forceLock = true): float|bool
    {
        $availableBalance = Item::factor($this->getAvailableBalance())->getCents();
        if ($amount > $availableBalance) throw new Exception("Insufficient fund");
        $availableBalance = ($availableBalance - $amount);
        if ($this->updateAvailableBalance($availableBalance, $forceLock)) return $availableBalance;
        return false;
    }

    /**
     * @param float $amount
     * @return float|bool
     * @throws Exception
     */
    public function unlockFund(float $amount): float|bool
    {
        $availableBalance = (float) Item::factor($this->getAvailableBalance())->getCents();
        $balance = (float) Item::factor($this->getBalance())->getCents();
        if ($amount > $balance) throw new Exception("Insufficient fund");
        if ($availableBalance > ($balance - $amount)) throw new Exception("Unable to unlock more funds than were locked");
        $availableBalance = ($availableBalance + $amount);
        if ($this->updateAvailableBalance($availableBalance,true)) return $balance;
        return false;
    }

    /**
     * @param float $amount
     * @return float|bool
     * @throws Exception
     */
    public function debitLockedFund(float $amount): float|bool
    {
        $balance = (float) Item::factor($this->getBalance())->getCents();
        $lockedFund = ($balance - (float) Item::factor($this->getAvailableBalance())->getCents());
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

        if (!$forceUpdate) $this->validateWallet();

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

        if (!$forceUpdate) $this->validateWallet();

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

        if (!$forceUpdate) $this->validateWallet();

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

    /**
     * @throws Exception
     */
    private function validateWallet() {

        if ($this->wallet->validate('is_active')->isNotEqual(true)) {
            if ($this->wallet->user->id == user('id')) {
                throw new Exception("Your wallet is deactivated");
            } else {
                throw new Exception("{$this->wallet->user->firstname}'s wallet is deactivated");
            }
        }

        if ($this->wallet->validate('is_frozen')->isEqual(true)) {
            if ($this->wallet->user->id == user('id')) {
                throw new Exception("Your wallet is frozen");
            } else {
                throw new Exception("{$this->wallet->user->firstname}'s wallet is frozen");
            }
        }
    }
}
