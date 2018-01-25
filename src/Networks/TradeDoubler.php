<?php

namespace Padosoft\AffiliateNetwork\Networks;

use Padosoft\AffiliateNetwork\Product;
use Padosoft\AffiliateNetwork\Transaction;
use Padosoft\AffiliateNetwork\DealsResultset;
use Padosoft\AffiliateNetwork\Merchant;
use Padosoft\AffiliateNetwork\Stat;
use Padosoft\AffiliateNetwork\Deal;
use Padosoft\AffiliateNetwork\AbstractNetwork;
use Padosoft\AffiliateNetwork\NetworkInterface;
use Padosoft\AffiliateNetwork\TradeDoublerEx;
use Padosoft\AffiliateNetwork\ProductsResultset;

//if (!defined('COOKIES_BASE_DIR')){
//    define('COOKIES_BASE_DIR',public_path('upload/report'));
//}
/**
 * Class TradeDoubler
 * @package Padosoft\AffiliateNetwork\Networks
 */
class TradeDoubler extends AbstractNetwork implements NetworkInterface
{
    /**
     * @var object
     */
    private $_network = null;
    private $_apiClient = null;
    private $_username = '';
    private $_password = '';
    private $_logged    = false;
    protected $_tracking_parameter    = 'epi';

    /**
     * @method __construct
     */
    public function __construct(string $username, string $password,string $idSite='')
    {
        $this->_network = new TradeDoublerEx;
        $this->_username = $username;
        $this->_password = $password;
        $this->_apiClient = null;
        $this->login( $this->_username, $this->_password );
    }

    public function login(string $username, string $password,string $idSite=''): bool
    {
        $this->_logged = false;
        if (isNullOrEmpty( $username ) || isNullOrEmpty( $password )) {

            return false;
        }
        $this->_username = $username;
        $this->_password = $password;
        $credentials = array();
        $credentials["user"] = $this->_username;
        $credentials["password"] = $this->_password;
        $this->_network->login( $credentials );

        if ($this->_network->checkConnection()) {
            $this->_logged = true;

        }

        return $this->_logged;
    }

    /**
     * @return bool
     */
    public function checkLogin() : bool
    {
        return $this->_logged;;
    }

    /**
     * @return array of Merchants
     */
    public function getMerchants() : array
    {
        if (!$this->checkLogin()) {
            return array();
        }
        $arrResult = array();
        $merchantList = $this->_network->getMerchantList();
        foreach($merchantList as $merchant) {
            $Merchant = Merchant::createInstance();
            $Merchant->merchant_ID = $merchant['cid'];
            $Merchant->name = $merchant['name'];
            $arrResult[] = $Merchant;
        }

        return $arrResult;
    }

    /**
     * @param int|null $merchantID
     * @param int $page
     * @param int $items_per_page
     *
     * @return DealsResultset
     */
    public function getDeals($merchantID,int $page=0,int $items_per_page=10) : DealsResultset
    {
        if (!isIntegerPositive($items_per_page)){
            $items_per_page=10;
        }
        $result=DealsResultset::createInstance();
        if (!$this->checkLogin()) {
            return $result;
        }
        $arrResult = array();
        $jsonVouchers = file_get_contents("https://api.tradedoubler.com/1.0/vouchers.json;voucherTypeId=1?token=".$_ENV['TRADEDOUBLER_TOKEN']);
        $arrVouchers = json_decode($jsonVouchers, true);

        foreach($arrVouchers as $vouchers) {
            $Deal = Deal::createInstance();
            $Deal->deal_ID = $vouchers['id'];
            $Deal->merchant_ID = $vouchers['programId'];
            $Deal->merchant_name = $vouchers['programName'];
            $Deal->code = $vouchers['code'];
            $Deal->name = $vouchers['title'];
            $Deal->short_description = $vouchers['shortDescription'];
            $Deal->description = $vouchers['description'];
            $Deal->deal_type = $vouchers['voucherTypeId'];
            $Deal->default_track_uri = $vouchers['defaultTrackUri'];
            $Deal->default_track_uri = $vouchers['landingUrl'];
            $Deal->discount_amount = $vouchers['discountAmount'];
            $Deal->is_percentage = $vouchers['isPercentage'];
            $Deal->currency_initial = $vouchers['currencyId'];
            $Deal->logo_path = $vouchers['logoPath'];
            if($merchantID > 0) {
                if($vouchers['programId'] == $merchantID) {
                    $arrResult[] = $Deal;
                }
            }
            else {
                $arrResult[] = $Deal;
            }
        }
        $result->deals[]=$arrResult;
        return $result;
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     * @return array of Transaction
     */
    public function getSales(\DateTime $dateFrom, \DateTime $dateTo, array $arrMerchantID = array()) : array
    {
        if (!$this->checkLogin()) {
            return array();
        }
        $arrResult = array();
        if (count( $arrMerchantID ) < 1) {
            $merchants = $this->getMerchants();
            foreach ($merchants as $merchant) {
                $arrMerchantID[$merchant->merchant_ID] = ['cid' => $merchant->merchant_ID, 'name' => $merchant->name];
            }
        }
        $transcationList = $this->_network->getTransactionList($arrMerchantID, $dateFrom, $dateTo);
        foreach($transcationList as $transaction) {
            $Transaction = Transaction::createInstance();
            $Transaction->merchant_ID = $transaction['merchantId'];
            $date = new \DateTime($transaction['date']);
            $Transaction->date = $date; // $date->format('Y-m-d H:i:s');
            $Transaction->unique_ID = $transaction['unique_id'];
            array_key_exists_safe( $transaction,
                'custom_id' ) ? $Transaction->custom_ID = $transaction['custom_id'] : $Transaction->custom_ID = '';
            $Transaction->status = $transaction['status'];
            $Transaction->amount = $transaction['amount'];
            $Transaction->commission = $transaction['commission'];
            $arrResult[] = $Transaction;
        }

        return $arrResult;
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     * @return array of Stat
     */
    public function getStats(\DateTime $dateFrom, \DateTime $dateTo, int $merchantID = 0) : array
    {
        return array();
    }


    /**
     * @param  array $params
     *
     * @return ProductsResultset
     */
    public function getProducts(array $params = []): ProductsResultset
    {

        $arrResult = array();
        $jsonProducts = file_get_contents("http://api.tradedoubler.com/1.0/productsUnlimited.json|empty;pageSize=".$params["items"].";page=".$params["page"].";fid=".$params["query"]."?token=".$_ENV['TRADEDOUBLER_TOKEN']);
        $products = json_decode($jsonProducts, true);

        $set = ProductsResultset::createInstance();
        if (count($products["products"]) == 0) {
            return ProductsResultset::createInstance();
        }
        foreach ($products["products"] as $productItem) {
            $Product = Product::createInstance();
            if (!empty($productItem["name"])) {
                $Product->name = (string)$productItem["name"];//'Danava',
            }
            if (!empty($productItem["offers"][0]["modified"])){
                $Product->modified = (string)$productItem["offers"][0]["modified"]; //'2016-11-24T11:52:03Z',
            }
            if (!empty($productItem["categories"][0])){
                $Product->category = (string)$productItem["categories"][0]["name"]; //'2016-11-24T11:52:03Z',
            }
            if (!empty($productItem['fields']['0']['value'])){
                $Product->gender = (string)$productItem['fields']['0']['value']; //'2016-11-24T11:52:03Z',
            }

            if (!empty($productItem["offers"][0]["feedId"])) {
                $Product->merchant_ID = intval((string)$productItem["offers"][0]["feedId"]); //17434
                $Product->merchant_name = (string)$productItem["offers"][0]["programName"]; //'Twelve Thirteen DE',
            }
            if (!empty($productItem["offers"][0]["priceHistory"][0])){
                if($productItem["offers"][0]["priceHistory"][0]["price"]["currency"]=="EUR"){
                    $Product->currency = (string)$productItem["offers"][0]["priceHistory"][0]["price"]["currency"];
                    $Product->price = floatval($productItem["offers"][0]["priceHistory"][0]["price"]["value"]);
                }
            }
            if (!empty($productItem["offers"][0]["productUrl"])) {
                $Product->ppv = (string)$productItem["offers"][0]["productUrl"];
                $Product->ppc = (string)$productItem["offers"][0]["productUrl"];
                $Product->adspaceId = (string)$productItem["offers"][0]["id"];
            }
            if (!empty($productItem["description"]))
                $Product->description = (string)$productItem["description"]; //'Rosegold trifft auf puristisches Schwarz ? aufwendige und traditionelle Makramee Technik trifft auf Eleganz. Das neue Danava Buddha Armband besteht aus schwarzem Onyx, dieser Edelstein wird sehr gerne als Schmuckstein verwendet und viel lieber getragen. Der feingearbeitete rosegoldene Buddha verleiht diesem Armband einen fernöstlichen Stil. Es lässt sich wunderbar zu allen Anlässen Tragen und zu vielen Outfits kombinieren, da es Eleganz ausstrahlt. Das Symbol des Buddhas ist besonders in dieser Saison sehr gefragt.',
            if (!empty($productItem["brand"]))
                $Product->manufacturer = (string)$productItem["brand"]; //'Twelve Thirteen Jewelry'
            if (!empty($productItem["identifiers"]["ean"]))
                $Product->ean = (string)$productItem["identifiers"]["ean"]; //'0796716271505'
//            if (property_exists($productItem, 'deliveryTime'))
//                $Product->deliveryTime = (string)$productItem->deliveryTime; //'1-3 Tage'
//            if (property_exists($productItem, 'price'))
//                $Product->priceOld = (string)$productItem->price; //0.0
            if (!empty($productItem["offers"][0]["shippingCost"]))
                $Product->shippingCosts = (string)$productItem["offers"][0]["shippingCost"]; //'0.0'
//            if (property_exists($productItem, 'shipping'))
//                $Product->shipping = (string)$productItem->shipping; // '0.0'
//            if (property_exists($productItem, 'advertiser-category'))
//                $Product->merchantCategory = (string)$productItem->{'advertiser-category'}; //'Damen / Damen Armbänder / Buddha Armbänder'
            if (!empty($productItem["offers"][0]["sourceProductId"]))
                $Product->merchantProductId = (string)$productItem["offers"][0]["sourceProductId"]; //'BR018.M'
            if (!empty($productItem["identifiers"]["sku"]))
                $Product->id = (string)$productItem["identifiers"]["sku"]; //'1ed7c3b4ab79cdbbf127cb78ec2aaff4'
            if (!empty($productItem["productImage"])) {
                $Product->image = (string)$productItem["productImage"]["url"];
            }
            $set->products[] = $Product;
        }

        return $set;

    }

    public function getTrackingParameter(){
        return $this->_tracking_parameter;
    }
}
