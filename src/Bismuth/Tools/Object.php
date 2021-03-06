<?php

namespace Bismuth\Tools;

/**
 * Base class for creating dynamic objects
 *
 * (Slightly modified by Andrew Heebner)
 *
 * @author Petr Trofimov <petrofimov@yandex.ru>
 * @author Andrew Heebner <andrew.heebner@gmail.com>
 * @see https://github.com/ptrofimov/jslikeobject
 */
class Object
{
    /**
     * Parent object for current one
     *
     * @var object|null
     */
    private $parent;

    /**
     * Array of private properties and methods
     *
     * @var array
     */
    private $object = [];

    /**
     * Constructor
     *
     * Usage:
     * - create new object: new Object($propertiesAndMethods)
     * - inherit parent object: new Object($parentObject)
     * - extend parent object: new Object($parentObject, $propertiesAndMethods)
     */
    public function __construct()
    {
        $args = func_get_args();
        if (count($args) == 2 && is_object($args[0]) && is_array($args[1])) {
            list($this->parent, $this->object) = $args;
        } elseif (count($args) == 1 && is_array($args[0])) {
            list($this->object) = $args;
        }
    }

    /**
     * Returns parent object
     *
     * @return object|null
     */
    public function parent()
    {
        return $this->parent;
    }

    /**
     * Magic method to get property of object by key
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public function __get($key)
    {
        $value = null;
        if (array_key_exists($key, $this->object)) {
            $value = $this->object[$key];
        } elseif (is_object($this->parent)) {
            $value = $this->parent->{$key};
        }

        return $value;
    }

    /**
     * Magic method to set property of object by key
     *
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value)
    {
        $this->object[$key] = $value;
    }

    /**
     * Magic method to invoke method of object
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed|null
     */
    public function __call($method, array $args)
    {
        return is_callable($this->{$method}) ? call_user_func_array($this->{$method}->bindTo($this), $args) : null;
    }

}

?>