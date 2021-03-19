<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 11/17/2019
 * Time: 9:43 PM
 */

namespace utility\enum;

use Exception;
use ReflectionClass;

abstract class EmploymentEnum
{
    const UNEMPLOYED = -1;
    const EMPLOYED = 1;
    const SELF_EMPLOYED = 2;
    const PENSIONER = 3;

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