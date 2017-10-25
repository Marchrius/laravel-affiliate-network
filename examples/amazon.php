<?php
define('COOKIES_BASE_DIR', __DIR__.'/network/report');
require __DIR__."/../vendor/autoload.php";

use Oara\Network\Publisher\Amazon;
use Padosoft\AffiliateNetwork\Networks\CommissionJunction;


$Amazon = new Amazon();
$Amazon->login(array('user'=>'andrea@lookhave.com', 'password'=> 'lookhave123'));
$Amazon->checkConnection();
$params = array('query'=>'scarpe', 'programId' => 700766031);

echo '<h1>Products list</h1>';
$products = $Amazon->getProducts($params);
echo '<pre>';
var_dump($products);
echo '</pre>';
/**
 * Merchants List
 */

echo '<h1>Merchants list</h1>';
$merchantList = $Amazon->getMerchantList();
echo '<pre>';
var_dump($merchantList);
echo '</pre>';

/**
 *Sales list
 */

echo '<h1>Sales</h1>';

$sales = $Amazon->getTransactionList($merchantList, new DateTime('2017-09-01'), new DateTime('2017-10-10'));
echo '<pre>';
var_dump($sales);
echo '</pre>';

//
///**
// * Stats list
// */
//
///*
//echo '<h1>Stats</h1>';
//$stats = $Amazon->getStats(new DateTime('2016-10-14'), new DateTime('2016-11-15'));
//echo '<pre>';
//var_dump($stats);
//echo '</pre>';
//*/
//
///**
// * Deals
// */
//
//echo '<h1>Deals</h1>';
//$deals = $Amazon->getDeals();
//echo '<pre>';
//var_dump($deals);
//echo '</pre>';
//
//echo '<h1>Single deal merchant id = 3857130</h1>';
//$deals = $Amazon->getDeals(3857130);
//echo '<pre>';
//var_dump($deals);
//echo '</pre>';
