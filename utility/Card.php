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

trait Card
{

    /**
     * @param $cardID
     * @return Model|null
     */
    public function getMyCard($cardID): ?Model
    {
        $card = db()->find('cards', $cardID, is_numeric($cardID) ? 'id' : 'uuid',
            function (Builder $builder) {
                $builder->where('gateway', GATEWAY);
                $builder->where('user_id', user('id'));
            });
        $card->setModelKey('cardModel');
        return $card->getFirstWithModel();
    }

    /**
     * @param null $userID
     * @return ModelCollection|null
     */
    public function getAllMyCards($userID = null): ?ModelCollection
    {
        $card = db()->findAll('cards', $userID ?: user('id'), 'user_id', function (Builder $builder) {
            $builder->where('gateway', GATEWAY);
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
        $card = db()->find('cards', $cardID, is_numeric($cardID) ? 'id' : 'uuid', function ($builder) {
            $builder->where('gateway', GATEWAY);
        });
        $card->setModelKey('cardModel');
        return $card->getFirstWithModel();
    }

    /**
     * @param $cardID
     * @return mixed
     */
    public function getCardAuthCode($cardID): mixed
    {
        $card = db()->find('cards', $cardID, is_numeric($cardID) ? 'id' : 'uuid', function ($builder) {
            $builder->where('gateway', GATEWAY);
        });
        return $card->getFirstWithModel()?->getValue('auth');
    }
}
