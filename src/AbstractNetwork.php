<?php

namespace Padosoft\AffiliateNetwork;

/**
 * Class AbstractNetwork
 * @package Padosoft\AffiliateNetwork
 */
abstract class AbstractNetwork
{
    /**
     * username
     * @var string
     */
    protected $username = '';

    /**
     * password
     * @var string
     */
    protected $password = '';

    protected $ProductMapper = [];

    /**
     * AbstractNetwork constructor.
     * @param string $username
     * @param string $password
     */
    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Call protected/private property of a class.
     * @param $object
     * @param $propertyName
     *
     * @return mixed
     */
    public function invokeProperty(&$object, $propertyName)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    protected function propertyMapper($mapper, Product &$productSetItem, $productItem)
    {
        foreach ($mapper as $prop => $map) {
            $productSetItem->{$prop} = $this->visitObject($productItem, $map['prop'], $map['default']);
        }
    }

    protected function mapProduct(Product &$product, $productItem)
    {
        $this->propertyMapper($this->ProductMapper, $product, $productItem);
    }

    private function isDotFirst($str)
    {
        $posDot = strpos($str, '.');
        $posSquare = strpos($str, '[');
        $posDot = $posDot === false ? PHP_INT_MAX : $posDot;
        $posSquare = $posSquare === false ? PHP_INT_MAX : $posSquare;
        return $posDot < $posSquare;
    }

    private function isSquareFirst($str)
    {
        $posDot = strpos($str, '.');
        $posSquare = strpos($str, '[');
        $posDot = $posDot === false ? PHP_INT_MAX : $posDot;
        $posSquare = $posSquare === false ? PHP_INT_MAX : $posSquare;
        return $posSquare < $posDot;
    }

    private function extractIndexKey($str)
    {
        $start = strpos($str, '[') + 1;
        $end = strpos($str, ']', $start);
        $indexKey = substr($str, $start, $end - $start);
        if (is_numeric($indexKey))
            return intval($indexKey);
        return $indexKey;
    }

    /**
     * @param $object
     * @param $path
     * @param null $default
     * @param int $deep
     * @return mixed|null
     * @throws \RuntimeException
     */
    private function visitObject($object, $path, $default = null, $deep = 0)
    {
        if ($deep > 255) {
            throw new \RuntimeException("Too deep. More than 255.");
        }
        $deep++;
        // no path. single property
        if (null == $path || '' == $path)
            return $object;
        // no path. single property
        if ($this->isDotFirst($path)) { // path is like path.to.property
            $explodedPaths = explode('.', $path);
            $segment = array_shift($explodedPaths);
            if ($this->isSquareFirst($segment)) { // has array reference?
                $indexKey = $this->extractIndexKey($segment);
                $backInArray = '[' . $indexKey . ']';
                $segment = str_replace($backInArray, '', $segment);
                array_unshift($explodedPaths, $backInArray);
            }
            $path = implode('.', $explodedPaths);
            if (!is_object($object) || !property_exists($object, $segment))
                return $default;
            return $this->visitObject($object->{$segment}, $path, $default, $deep);
        } else if ($this->isSquareFirst($path)) { // path is like [0]
            if (($sPos = strpos($path, '[')) > 0 && $sPos !== false) {
                $segment = substr($path, 0, $sPos);
                $path = str_replace($segment, '', $path);
                if (!is_object($object) || !property_exists($object, $segment))
                    return $default;
                return $this->visitObject($object->{$segment}, $path, $default, $deep);
            }
            $explodedPaths = explode('.', $path);
            $segment = array_shift($explodedPaths);
            $indexKey = $this->extractIndexKey($segment);
            $path = implode('.', $explodedPaths);
            if (!is_array($object) && isset($object[$indexKey]))
                return $default;
            return $this->visitObject($object[$indexKey], $path, $default, $deep);
        } else { // path
            if (is_object($object) && property_exists($object, $path)) {
                return $object->{$path};
            }
            if (is_array($object) && isset($object[$path])) {
                return $object[$path];
            }
            return $default;
        }
    }

    public function mapPropertyByType($value, $type, $default)
    {
        try {
            switch (mb_strtolower($type)) {
                case 'long':
                case 'integer':
                case 'int':
                    return intval($value);
                case 'float':
                    return floatval($value);
                case 'double':
                    return doubleval($value);
                case 'string':
                    return (string)$value;
                case 'bool':
                case 'boolean':
                    return boolval($value);
            }
        } catch (\Exception $e) {
        }
        return $default;
    }

}
