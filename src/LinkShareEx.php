<?php
/**
 * Copyright (c) Padosoft.com 2017.
 */


namespace Padosoft\AffiliateNetwork;
use Oara\Network\Publisher\LinkShare as LinkShareOara;

class LinkShareEx extends LinkShareOara
{
    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object,$methodName, array $parameters = array())
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
    public function invokeProperty(&$object,$propertyName)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    public function getProducts(array $params = [], $iteration = 0)
    {
        // @TODO
        print 'LinkShare get products';
        die;
    }

}