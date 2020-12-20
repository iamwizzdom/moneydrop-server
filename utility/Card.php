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

trait Card
{

    /**
     * @param $cardID
     * @return Model|null
     */
    public function getMyCard($cardID): ?Model
    {
        $card = db()->find('cards', $cardID, 'id',
            function (Builder $builder) use ($cardID) {
                $builder->orWhere('uuid', $cardID);
                $builder->where('user_id', user('id'));
            });
        return $card->isSuccessful() ? $card->getFirstWithModel() : null;
    }

    /**
     * @return \que\database\model\ModelCollection|null
     */
    public function getAllMyCards(): ?\que\database\model\ModelCollection
    {
        $card = db()->findAll('cards', user('id'), 'user_id', function ($builder) {
            $builder->where('is_active', true);
        });
        return $card->isSuccessful() ? $card->getAllWithModel() : null;
    }

    /**
     * @param string $cardUUID
     * @return bool
     */
    public function removeCard(string $cardUUID): bool
    {
        $card = $this->getMyCard($cardUUID);
        if (!$card) return false;
        return $card->update(['is_active' => false]);
    }

    /**
     * @param $cardID
     * @return Model|null
     */
    public function getCard($cardID): ?Model
    {
        $card = db()->find('cards', $cardID, 'id',
            function (Builder $builder) use ($cardID) {
                $builder->orWhere('uuid', $cardID);
            });
        return $card->isSuccessful() ? $card->getFirstWithModel() : null;
    }

    /**
     * @param $cardID
     * @return mixed
     */
    public function getCardAuthCode($cardID): mixed
    {

        $card = db()->find('cards', $cardID, 'id',
            function (Builder $builder) use ($cardID) {
                $builder->selectJsonValue('auth', 'auth_code', '$.authorization_code');
                $builder->orWhere('uuid', $cardID);
            });
        return $card->isSuccessful() ? $card->getFirstWithModel()->getValue('authorization_code') : null;
    }
}