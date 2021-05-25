<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 11/26/2020
 * Time: 12:24 PM
 */

namespace utility;


use que\database\interfaces\Builder;
use que\database\interfaces\model\Model;
use que\database\model\ModelCollection;

trait BankAccount
{

    /**
     * @param $bankID
     * @return Model|null
     */
    public function getMyBankAccount($bankID): ?Model
    {
        $bank = db()->find('bank_accounts', $bankID, is_numeric($bankID) ? 'id' : 'uuid',
            function (Builder $builder) {
                $builder->where('user_id', user('id'));
            });
        $bank->setModelKey('bankAccountModel');
        return $bank->getFirstWithModel();
    }

    /**
     * @param null $userID
     * @return ModelCollection|null
     */
    public function getAllMyBankAccounts($userID = null): ?ModelCollection
    {
        $bank = db()->findAll('bank_accounts', $userID ?: user('id'), 'user_id', function (Builder $builder) {
            $builder->where('is_active', true);
            $builder->orderBy("desc", "id");
        });
        $bank->setModelKey('bankAccountModel');
        return $bank->getAllWithModel();
    }

    /**
     * @param string $bankUUID
     * @return bool
     */
    public function removeBankAccount(string $bankUUID): bool
    {
        $bank = $this->getMyBankAccount($bankUUID);
        if (!$bank) return false;
        return !!$bank->update(['is_active' => false])?->isSuccessful();
    }

    /**
     * @param $bankID
     * @return Model|null
     */
    public function getBankAccount($bankID): ?Model
    {
        $bank = db()->find('bank_accounts', $bankID, is_numeric($bankID) ? 'id' : 'uuid');
        $bank->setModelKey('bankAccountModel');
        return $bank->getFirstWithModel();
    }

    /**
     * @param $bankID
     * @return mixed
     */
    public function getBankAccountID($bankID): mixed
    {
        $bank = db()->find('bank_accounts', $bankID, is_numeric($bankID) ? 'id' : 'uuid');
        return $bank->getFirstWithModel()?->getValue('account_id');
    }
}
