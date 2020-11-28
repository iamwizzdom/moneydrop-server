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
    public function getCard($cardID)
    {
        $card = db()->find('cards', $cardID, 'id',
            function (Builder $builder) use ($cardID) {
                $builder->orWhere('uuid', $cardID);
            });
        return $card->isSuccessful() ? $card->getFirstWithModel() : null;
    }

    /**
     * @param $cardID
     * @return mixed|null
     */
    public function getCardAuthCode($cardID) {

        $card = db()->find('cards', $cardID, 'id',
            function (Builder $builder) use ($cardID) {
                $builder->selectJsonValue('auth', 'auth_code', '$.authorization_code');
                $builder->orWhere('uuid', $cardID);
            });
        return $card->isSuccessful() ? $card->getFirstWithModel()->getValue('authorization_code') : null;
    }
}