<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 5/9/2020
 * Time: 7:20 PM
 */

namespace custom\model;

use que\database\interfaces\Builder;
use que\database\model\Model;

class UserModel extends Model
{
    protected string $modelKey = 'userModel';
    protected array $appends = ['verified', 'country_name', 'state_name'];

    public function getCountryName() {
        return converter()->convertCountry($this->getInt('country_id'), 'countryName');
    }

    public function getStateName() {
        return converter()->convertState($this->getInt('state_id'), 'stateName');
    }

    public function getVerified() {

        $emailVerification = db()->find('verifications', $this->getValue('email'),
            'data', function (Builder $builder) {
                $builder->where('type', 'email');
                $builder->where('is_verified', true);
                $builder->where('is_active', true);
            });

        $phoneVerification = db()->find('verifications', $this->getValue('phone'),
            'data', function (Builder $builder) {
                $builder->where('type', 'phone');
                $builder->where('is_verified', true);
                $builder->where('is_active', true);
            });

        return [
            'email' => $emailVerification->isSuccessful(),
            'phone' => $phoneVerification->isSuccessful()
        ];
    }
}
