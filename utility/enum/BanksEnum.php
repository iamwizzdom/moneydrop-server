<?php


namespace utility\enum;


use Exception;
use ReflectionClass;

abstract class BanksEnum
{
    /**
     * Bank name
     */
    const ACCESS_BANK = 1; // Access bank
    const ACCESS_DIAMOND_BANK = 2; // Access bank (Diamond)
    const GT_BANK = 3; // GTBank
    const FIDELITY_BANK = 4; // Fidelity bank
    const ZENITH_BANK = 5; // Zenith bank
    const FIRST_BANK = 6; // First bank
    const ECO_BANK = 7; // EcoBank
    const STERLING_BANK = 8; // Sterling Bank
    const FCM_BANK = 9; // FCMB
    const ALAT_WEMA_BANK = 10; // ALAT by Wema

    private static $banks = [
        self::ACCESS_BANK => [
            'id' => 1,
            "name" => "Access Bank",
            "slug" => "access-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '044150149',
            'code' => '044'
        ],
        self::ACCESS_DIAMOND_BANK => [
            'id' => 3,
            "name" => "Access Bank (Diamond)",
            "slug" => "access-bank-diamond",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '063150162',
            'code' => '063'
        ],
        self::GT_BANK => [
            'id' => 9,
            "name" => "Guaranty Trust Bank",
            "slug" => "guaranty-trust-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '058152036',
            'code' => '058'
        ],
        self::FIDELITY_BANK => [
            'id' => 6,
            "name" => "Fidelity Bank",
            "slug" => "fidelity-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '070150003',
            'code' => '070'
        ],
        self::ZENITH_BANK => [
            'id' => 21,
            "name" => "Zenith Bank",
            "slug" => "zenith-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '057150013',
            'code' => '057'
        ],
        self::FIRST_BANK => [
            'id' => 7,
            "name" => "First Bank of Nigeria",
            "slug" => "first-bank-of-nigeria",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '011151003',
            'code' => '011'
        ],
        self::ECO_BANK => [
            'id' => 4,
            "name" => "Ecobank Nigeria",
            "slug" => "ecobank-nigeria",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '050150010',
            'code' => '050'
        ],
        self::STERLING_BANK => [
            'id' => 16,
            "name" => "Sterling Bank",
            "slug" => "sterling-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '232150016',
            'code' => '232'
        ],
        self::FCM_BANK => [
            'id' => 8,
            "name" => "First City Monument Bank (FCMB)",
            "slug" => "first-city-monument-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '214150018',
            'code' => '214'
        ],
        self::ALAT_WEMA_BANK => [
            'id' => 27,
            "name" => "ALAT by WEMA",
            "slug" => "alat-by-wema",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '035150103',
            'code' => '035A'
        ]
    ];

    /**
     * @return array
     */
    public static function getList(): array
    {
        return self::$banks;
    }

    /**
     * @return array
     */
    public static function getBankKeys(): array
    {
        try {
            return (new ReflectionClass(self::class))->getConstants();
        } catch (Exception $exception) {
            return [];
        }
    }

    /**
     * @param int $key
     * @return array|int|mixed
     */
    public static function getBankID(int $key = 0)
    {
        if ($key == 0) {
            $list = [];
            foreach (self::$banks as $bank) $list[] = $bank['id'];
            return $list;
        }
        return (array_key_exists($key, self::$banks) ? self::$banks[$key]['id'] : 0);
    }

    /**
     * @param int $key
     * @return array
     */
    public static function getBank(int $key = 0) : array
    {
        return $key == 0 ? self::$banks : (array_key_exists($key, self::$banks) ? self::$banks[$key] : []);
    }
}