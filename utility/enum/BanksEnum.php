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
    const ABBEY_MORTGAGE_BANK = 3; // Abbey Mortgage Bank
    const ALAT_WEMA_BANK = 4; // ALAT by Wema
    const ASO_SAVINGS_AND_LOANS = 5; // ASO Savings and Loans
    const BOWEN_MICROFINANCE_BANK = 6; // Bowen Microfinance Bank
    const CEMCS_MICROFINANCE_BANK = 7; // CEMCS Microfinance Bank
    const CITIBANK_NIGERIA = 8; // Citibank Nigeria
    const ECO_BANK = 9; // EcoBank
    const FCM_BANK = 10; // FCMB
    const FIDELITY_BANK = 11; // Fidelity bank
    const FIRST_BANK = 12; // First bank
    const GT_BANK = 13; // GTBank
    const HERITAGE_BANK = 14; // Heritage Bank
    const KEYSTONE_BANK = 15; // Keystone Bank
    const KUDA_BANK = 16; // Kuda Bank
    const ONE_FINANCE = 17; // One Finance
    const PARALLEX_BANK = 18; // Parallex Bank
    const POLARIS_BANK = 19; // Polaris Bank
    const PROVIDUS_BANK = 20; // Providus Bank
    const STANBIC_IBTC_BANK = 21; // Stanbic IBTC Bank
    const STANDARD_CHARTERED_BANK = 22; // Standard Chartered Bank
    const STERLING_BANK = 23; // Sterling Bank
    const UNION_BANK = 24; // Union Bank of Nigeria
    const UBA_BANK = 25; // United Bank For Africa
    const UNITY_BANK = 26; // Unity Bank
    const WEMA_BANK = 27; // Wema Bank
    const ZENITH_BANK = 28; // Zenith bank

    private static array $banks = [
        self::ACCESS_BANK => [
            'id' => self::ACCESS_BANK,
            "name" => "Access Bank",
            "slug" => "access-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '044150149',
            'code' => '044'
        ],
        self::ACCESS_DIAMOND_BANK => [
            'id' => self::ACCESS_DIAMOND_BANK,
            "name" => "Access Bank (Diamond)",
            "slug" => "access-bank-diamond",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '063150162',
            'code' => '063'
        ],
        self::ABBEY_MORTGAGE_BANK => [
            'id' => self::ABBEY_MORTGAGE_BANK,
            "name" => "Abbey Mortgage Bank",
            "slug" => "abbey-mortgage-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '',
            'code' => '801'
        ],
        self::ALAT_WEMA_BANK => [
            'id' => self::ALAT_WEMA_BANK,
            "name" => "ALAT by WEMA",
            "slug" => "alat-by-wema",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '035150103',
            'code' => '035A'
        ],
        self::ASO_SAVINGS_AND_LOANS => [
            'id' => self::ASO_SAVINGS_AND_LOANS,
            "name" => "ASO Savings and Loans",
            "slug" => "asosavings",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '',
            'code' => '401'
        ],
        self::BOWEN_MICROFINANCE_BANK => [
            'id' => self::BOWEN_MICROFINANCE_BANK,
            "name" => "Bowen Microfinance Bank",
            "slug" => "bowen-microfinance-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '',
            'code' => '50931'
        ],
        self::CEMCS_MICROFINANCE_BANK => [
            'id' => self::CEMCS_MICROFINANCE_BANK,
            "name" => "CEMCS Microfinance Bank",
            "slug" => "cemcs-microfinance-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '',
            'code' => '50823'
        ],
        self::CITIBANK_NIGERIA => [
            'id' => self::CITIBANK_NIGERIA,
            "name" => "Citibank Nigeria",
            "slug" => "citibank-nigeria",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '023150005',
            'code' => '023'
        ],
        self::ECO_BANK => [
            'id' => self::ECO_BANK,
            "name" => "Ecobank Nigeria",
            "slug" => "ecobank-nigeria",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '050150010',
            'code' => '050'
        ],
        self::FCM_BANK => [
            'id' => self::FCM_BANK,
            "name" => "First City Monument Bank (FCMB)",
            "slug" => "first-city-monument-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '214150018',
            'code' => '214'
        ],
        self::FIDELITY_BANK => [
            'id' => self::FIDELITY_BANK,
            "name" => "Fidelity Bank",
            "slug" => "fidelity-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '070150003',
            'code' => '070'
        ],
        self::FIRST_BANK => [
            'id' => self::FIRST_BANK,
            "name" => "First Bank of Nigeria",
            "slug" => "first-bank-of-nigeria",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '011151003',
            'code' => '011'
        ],
        self::GT_BANK => [
            'id' => self::GT_BANK,
            "name" => "Guaranty Trust Bank",
            "slug" => "guaranty-trust-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '058152036',
            'code' => '058'
        ],
        self::HERITAGE_BANK => [
            'id' => self::HERITAGE_BANK,
            "name" => "Heritage Bank",
            "slug" => "heritage-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '030159992',
            'code' => '030'
        ],
        self::KEYSTONE_BANK => [
            'id' => self::KEYSTONE_BANK,
            "name" => "Keystone Bank",
            "slug" => "keystone-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '082150017',
            'code' => '082'
        ],
        self::KUDA_BANK => [
            'id' => self::KUDA_BANK,
            "name" => "Kuda Bank",
            "slug" => "kuda-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '',
            'code' => '50211'
        ],
        self::ONE_FINANCE => [
            'id' => self::ONE_FINANCE,
            "name" => "One Finance",
            "slug" => "one-finance",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '',
            'code' => '565'
        ],
        self::PARALLEX_BANK => [
            'id' => self::PARALLEX_BANK,
            "name" => "Parallex Bank",
            "slug" => "parallex-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '',
            'code' => '526'
        ],
        self::POLARIS_BANK => [
            'id' => self::POLARIS_BANK,
            "name" => "Polaris Bank",
            "slug" => "polaris-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '076151006',
            'code' => '076'
        ],
        self::PROVIDUS_BANK => [
            'id' => self::PROVIDUS_BANK,
            "name" => "Providus Bank",
            "slug" => "providus-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '',
            'code' => '101'
        ],
        self::STANBIC_IBTC_BANK => [
            'id' => self::STANBIC_IBTC_BANK,
            "name" => "Stanbic IBTC Bank",
            "slug" => "stanbic-ibtc-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '221159522',
            'code' => '221'
        ],
        self::STANDARD_CHARTERED_BANK => [
            'id' => self::STANDARD_CHARTERED_BANK,
            "name" => "Standard Chartered Bank",
            "slug" => "standard-chartered-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '068150015',
            'code' => '068'
        ],
        self::STERLING_BANK => [
            'id' => self::STERLING_BANK,
            "name" => "Sterling Bank",
            "slug" => "sterling-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '232150016',
            'code' => '232'
        ],
        self::UNION_BANK => [
            'id' => self::UNION_BANK,
            "name" => "Union Bank of Nigeria",
            "slug" => "union-bank-of-nigeria",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '032080474',
            'code' => '032'
        ],
        self::UBA_BANK => [
            'id' => self::UBA_BANK,
            "name" => "United Bank For Africa",
            "slug" => "united-bank-for-africa",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '033153513',
            'code' => '033'
        ],
        self::UNITY_BANK => [
            'id' => self::UNITY_BANK,
            "name" => "Unity Bank",
            "slug" => "unity-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '215154097',
            'code' => '215'
        ],
        self::WEMA_BANK => [
            'id' => self::WEMA_BANK,
            "name" => "Wema Bank",
            "slug" => "wema-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '035150103',
            'code' => '035'
        ],
        self::ZENITH_BANK => [
            'id' => self::ZENITH_BANK,
            "name" => "Zenith Bank",
            "slug" => "zenith-bank",
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'longcode' => '057150013',
            'code' => '057'
        ]
    ];

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
     * @return array
     */
    public static function getBankIDs(): array
    {
        $list = [];
        foreach (self::$banks as $bank) $list[] = $bank['id'];
        return $list;
    }

    /**
     * @param int $key
     * @return int|null
     */
    public static function getBankID(int $key): ?int
    {
        return self::$banks[$key]['id'] ?? null;
    }

    /**
     * @return array
     */
    public static function getBankCodes(): array
    {
        $list = [];
        foreach (self::$banks as $bank) $list[] = $bank['code'];
        return $list;
    }

    /**
     * @param int $key
     * @return string|null
     */
    public static function getBankCode(int $key): ?string
    {
        return self::$banks[$key]['code'] ?? null;
    }

    /**
     * @return array[]
     */
    public static function getBanks() : array
    {
        return self::$banks;
    }

    /**
     * @param int $key
     * @return array|null
     */
    public static function getBank(int $key) : ?array
    {
        return self::$banks[$key] ?? null;
    }
}
