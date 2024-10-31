<?php

namespace Netcash\PayNow\Types;

/**
 * Class BasicEnum
 *
 * Assist in using Enums
 *
 * Inspired by: https://stackoverflow.com/questions/254514/php-and-enumerations
 *
 */
abstract class BasicEnum
{
    private static $constCacheArray = null;

    /**
     * Get the defined constants
     *
     * @return array The array of constants
     * @throws \ReflectionException
     */
    private static function getConstants()
    {
        if (null == self::$constCacheArray) {
            self::$constCacheArray = [];
        }
        $calledClass = get_called_class();
        if (!array_key_exists($calledClass, self::$constCacheArray)) {
            $reflect = new \ReflectionClass($calledClass);
            self::$constCacheArray[$calledClass] = $reflect->getConstants();
        }
        return self::$constCacheArray[$calledClass];
    }

    /**
     * @param string $name The constant name
     *
     * @return mixed|null The value if $name exists as constant. False otherwise
     * @throws \ReflectionException
     */
    public static function getConstantValue($name)
    {
        $constants = self::getConstants();
        foreach ($constants as $k => $v) {
            if (strtolower($k) == strtolower($name)) {
                return $v;
            }
        }
        return null;
    }

    /**
     * @param string $name The const name
     * @param bool $strict
     *
     * @return bool True if $name exists as a const. False if not
     * @throws \ReflectionException
     */
    public static function isValidName($name, $strict = false)
    {
        $constants = self::getConstants();

        if ($strict) {
            return array_key_exists($name, $constants);
        }

        $keys = array_map('strtolower', array_keys($constants));
        return in_array(strtolower($name), $keys);
    }

    /**
     * @param mixed $value The const value
     * @param bool $strict
     *
     * @return bool True if $value exists as a const value. False if not
     * @throws \ReflectionException
     */
    public static function isValidValue($value, $strict = true)
    {
        $values = array_values(self::getConstants());
        return in_array($value, $values, $strict);
    }
}
