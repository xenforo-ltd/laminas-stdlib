<?php

/**
 * @see       https://github.com/laminas/laminas-stdlib for the canonical source repository
 * @copyright https://github.com/laminas/laminas-stdlib/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-stdlib/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Stdlib;

use Traversable;

abstract class AbstractOptions implements ParameterObjectInterface
{
    /**
     * We use the __ prefix to avoid collisions with properties in
     * user-implementations.
     *
     * @var bool
     */
    protected $__strictMode__ = true;

    /**
     * @param  array|Traversable|null $options
     */
    public function __construct($options = null)
    {
        if (null !== $options) {
            $this->setFromArray($options);
        }
    }

    /**
     * @param  array|Traversable $options
     * @throws Exception\InvalidArgumentException
     * @return AbstractOptions Provides fluent interface
     */
    public function setFromArray($options)
    {
        if (!is_array($options) && !$options instanceof Traversable) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Parameter provided to %s must be an array or Traversable',
                __METHOD__
            ));
        }

        foreach ($options as $key => $value) {
            $this->__set($key, $value);
        }
        return $this;
    }

    /**
     * Cast to array
     *
     * @return array
     */
    public function toArray()
    {
        $array = array();
        $transform = function ($letters) {
            $letter = array_shift($letters);
            return '_' . strtolower($letter);
        };
        foreach ($this as $key => $value) {
            if ($key === '__strictMode__') continue;
            $normalizedKey = preg_replace_callback('/([A-Z])/', $transform, $key);
            $array[$normalizedKey] = $value;
        }
        return $array;
    }

    /**
     * @see ParameterObject::__set()
     * @param string $key
     * @param mixed $value
     * @throws Exception\BadMethodCallException
     * @return void
     */
    public function __set($key, $value)
    {
        $setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
        if ($this->__strictMode__ && !method_exists($this, $setter)) {
            throw new Exception\BadMethodCallException(
                'The option "' . $key . '" does not '
                . 'have a matching ' . $setter . ' setter method '
                . 'which must be defined'
            );
        } elseif (!$this->__strictMode__ && !method_exists($this, $setter)) {
            return;
        }
        $this->{$setter}($value);
    }

    /**
     * @see ParameterObject::__get()
     * @param string $key
     * @throws Exception\BadMethodCallException
     * @return mixed
     */
    public function __get($key)
    {
        $getter = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
        if (!method_exists($this, $getter)) {
            throw new Exception\BadMethodCallException(
                'The option "' . $key . '" does not '
                . 'have a matching ' . $getter . ' getter method '
                . 'which must be defined'
            );
        }

        return $this->{$getter}();
    }

    /**
     * @see ParameterObject::__isset()
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        return null !== $this->__get($key);
    }

    /**
     * @see ParameterObject::__unset()
     * @param string $key
     * @throws Exception\InvalidArgumentException
     * @return void
     */
    public function __unset($key)
    {
        try {
            $this->__set($key, null);
        } catch (Exception\BadMethodCallException $e) {
            throw new Exception\InvalidArgumentException(
                'The class property $' . $key . ' cannot be unset as'
                    . ' NULL is an invalid value for it',
                0,
                $e
            );
        }
    }
}
