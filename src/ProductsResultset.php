<?php
/**
 * Created by PhpStorm.
 * User: luna
 * Date: 07/04/17
 * Time: 16:57
 */

namespace Padosoft\AffiliateNetwork;


class ProductsResultset
{

    /**
     * @var int
     */
    public $page = 0;

    /**
     * @var int
     */
    public $items = 0;

    /**
     * @var int
     */
    public $total = 0;

    /**
     * @var array
     */
    public $products = [];

    /**
     * @method createInstance
     * @return static instance
     * @throws \Exception
     */
    public static function createInstance()
    {
        $obj = null;
        try {
            $obj = new ProductsResultset();
        } catch (\Exception $e) {
            throw new \Exception('Error creating instance ProductsResultset - ' . $e->getMessage());
        }
        return $obj;
    }
}