<?php

include_once 'config.php';

use Padosoft\AffiliateNetwork\Networks\TradeDoubler;

$TradeDoubler = new TradeDoubler('LookHave', 'Start_123');
$isLogged = $TradeDoubler->checkLogin();

if($isLogged) {
    /**
     * Merchants List
     */
    echo '<h1>Merchants list</h1>';
    $merchantList = $TradeDoubler->getMerchants();
    echo '<pre>';
    var_dump($merchantList);
    echo '</pre>';

    /**
     * Sales list
     */
//    echo '<h1>Sales</h1>';
//    $merchantList = array(
//        array('cid' => '25384', 'name' => 'Ottodame IT')
//    );
//    $sales = $TradeDoubler->getSales(new DateTime('2017-12-17'), new DateTime('NOW'), $merchantList);
//    echo '<pre>';
//    var_dump($sales);
//    echo '</pre>';
    /**
     * Sales list
     */
    echo '<h1>Product</h1>';
    $merchantList = array(
        array('cid' => '25384', 'name' => 'Ottodame IT')
    );
    $sales = $TradeDoubler->getProducts(["id"=>26088, "page"=>1, "item"=>1000]);
    echo '<pre>';
    var_dump($sales);
    echo '</pre>';

    /**
     * Deals
     */

//    echo '<h1>Deals</h1>';
//    $deals = $TradeDoubler->getSales(new DateTime('2016-10-17'), new DateTime("NOW"), [283943]);
//    echo '<pre>';
//    var_dump($deals);
//    echo '</pre>';

//    echo '<h1>Single deal merchant id = 258805</h1>';
//    $deals = $TradeDoubler->getDeals(258805);
//    echo '<pre>';
//    var_dump($deals);
//    echo '</pre>';
}
