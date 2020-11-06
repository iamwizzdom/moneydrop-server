<?php


namespace utility\enum;

use Exception;
use ReflectionClass;

abstract class BankAccountTypeEnum
{
    /**
     * Bank account types
     */
    const SAVINGS_ACCOUNT = 1; // Savings account
    const CURRENT_ACCOUNT = 2; // Current account

    /**
     * @return array
     */
    public static function getList(): array
    {
        try {
            return (new ReflectionClass(self::class))->getConstants();
        } catch (Exception $exception) {
            return [];
        }
    }

    /**
     * @param null $key
     * @return array|string|null
     */
    public static function convertKey($key = null)
    {
        $elements = array_flip(self::getList());
        array_callback($elements, function ($value) {
            return ucwords(preg_replace("/_/", " ", strtolower($value)));
        });
        return is_null($key) ? $elements : (array_key_exists($key, $elements) ? $elements[$key] : null);
    }
}