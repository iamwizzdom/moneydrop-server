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
        $card = db()->find('cards', $cardID, is_numeric($cardID) ? 'id' : 'uuid',
            function (Builder $builder) use ($cardID) {
                $builder->where('user_id', user('id'));
            });
        $card->setModelKey('cardModel');
        return $card->getFirstWithModel();
    }

    /**
     * @return \que\database\model\ModelCollection|null
     */
    public function getAllMyCards(): ?\que\database\model\ModelCollection
    {
        $card = db()->findAll('cards', user('id'), 'user_id', function (Builder $builder) {
            $builder->where('is_active', true);
            $builder->orderBy("desc", "id");
        });
        $card->setModelKey('cardModel');
        return $card->getAllWithModel();
    }

    /**
     * @param string $cardUUID
     * @return bool
     */
    public function removeCard(string $cardUUID): bool
    {
        $card = $this->getMyCard($cardUUID);
        if (!$card) return false;
        return !!$card->update(['is_active' => false])?->isSuccessful();
    }

    /**
     * @param $cardID
     * @return Model|null
     */
    public function getCard($cardID): ?Model
    {
        $card = db()->find('cards', $cardID, is_numeric($cardID) ? 'id' : 'uuid');
        $card->setModelKey('cardModel');
        return $card->getFirstWithModel();
    }

    /**
     * @param $cardID
     * @return mixed
     */
    public function getCardAuthCode($cardID): mixed
    {
        $card = db()->find('cards', $cardID, is_numeric($cardID) ? 'id' : 'uuid',
            function (Builder $builder) use ($cardID) {
                $builder->selectJsonValue('auth', 'auth_code', '$.authorization_code');
            });
        return $card->getFirstWithModel()?->getValue('auth_code');
    }
}
