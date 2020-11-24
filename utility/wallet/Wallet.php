<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 11/7/2020
 * Time: 1:02 PM
 */

namespace utility\wallet;


use Exception;
use que\database\interfaces\Builder;
use que\database\interfaces\model\Model;
use que\database\model\ModelCollection;
use que\support\Str;

trait Wallet
{
    private ?ModelCollection $wallets = null;

    public function __construct()
    {
        $wallets = db()->findAll('wallets', user('id'), 'user_id',
            function (Builder $builder) {
                $builder->where('is_active', true);
            });
        $this->wallets = $wallets->isSuccessful() ? $wallets->getAllWithModel() : null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function createWallet(string $name) {
        return db()->insert('wallets', [
            'uuid' => Str::uuidv4(),
            'name' => $name,
            'user_id' => user('id')
        ])->isSuccessful();
    }

    /**
     * @param int|null $walletID
     * @param bool $get_available_bal
     * @return float|int
     */
    public function getBalance($walletID = null, bool $get_available_bal = false)
    {

        $balance = 0;

        if ($this->wallets) {

            if ($walletID) {

                $wallet = $this->wallets->find(function (Model $model) use ($walletID) {
                    return $model->validate('id')->isEqual($walletID) ||
                        $model->validate('uuid')->isEqual($walletID);
                });

                if ($wallet) $balance = $wallet->getFloat(
                    !$get_available_bal ? 'balance' : 'available_balance');

            } else {

                $balance = $this->wallets->sum(function (Model $wallet) use ($get_available_bal) {
                    return $wallet->getFloat(
                        !$get_available_bal ? 'balance' : 'available_balance');
                });

            }
        }

        return $balance;
    }

    /**
     * @param mixed $walletID
     * @param float $balance
     * @return bool
     * @throws Exception
     */
    public function updateBalance($walletID, float $balance)
    {
        if (!$this->wallets) throw new \Exception("No wallet found");

        $wallet = $this->wallets->find(function (Model $model) use ($walletID) {
            return $model->validate('id')->isEqual($walletID) ||
                $model->validate('uuid')->isEqual($walletID);
        });

        return $wallet ? $wallet->update(['balance' => $balance]) : false;
    }

    /**
     * @param mixed $walletID
     * @param float $balance
     * @return bool
     * @throws Exception
     */
    public function updateAvailableBalance($walletID, float $balance)
    {
        if (!$this->wallets) throw new Exception("No wallet found");

        $wallet = $this->wallets->find(function (Model $model) use ($walletID) {
            return $model->validate('id')->isEqual($walletID) ||
                $model->validate('uuid')->isEqual($walletID);
        });

        return $wallet ? $wallet->update(['available_balance' => $balance]) : false;
    }

    /**
     * @param mixed $walletID
     * @return bool
     * @throws Exception
     */
    public function deactivateWallet($walletID)
    {
        if (!$this->wallets) throw new Exception("No wallet found");

        $wallet = $this->wallets->find(function (Model $model) use ($walletID) {
            return $model->validate('id')->isEqual($walletID) ||
                $model->validate('uuid')->isEqual($walletID);
        });

        if ($status = $wallet ? $wallet->update(['is_active' => false]) : false) {
            $this->wallets->offsetUnset(function (Model $wallet) {
                return $wallet->validate('is_active')->isEqual(false);
            });
        }

        return $status;
    }
}