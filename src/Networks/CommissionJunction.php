<?php

namespace Padosoft\AffiliateNetwork\Networks;

use Padosoft\AffiliateNetwork\Transaction;
use Padosoft\AffiliateNetwork\Merchant;
use Padosoft\AffiliateNetwork\Stat;
use Padosoft\AffiliateNetwork\Deal;
use Padosoft\AffiliateNetwork\AbstractNetwork;
use Padosoft\AffiliateNetwork\NetworkInterface;
use Padosoft\AffiliateNetwork\DealsResultset;
use Padosoft\AffiliateNetwork\ProductsResultset;
use Padosoft\AffiliateNetwork\Product;

// require "../vendor/fubralimited/php-oara/Oara/Network/Publisher/CommissionJunction/Zapi/ApiClient.php";

/**
 * Class CommissionJunction
 * @package Padosoft\AffiliateNetwork\Networks
 */
class CommissionJunction extends AbstractNetwork implements NetworkInterface
{
    /**
     * @var object
     */
    private $_network = null;
    // private $_apiClient = null;
    private $_username = '';
    private $_password = '';
    private $_passwordApi = '';
    private $_website_id = '';
    protected $_tracking_parameter = 'sid';

    protected $ProductMapper = [
        'name' => ['type' => 'string', 'prop' => 'name', 'default' => ''],
        'modified' => ['type' => 'string', 'prop' => 'modified', 'default' => ''],
        'merchant_ID' => ['type' => 'integer', 'prop' => 'advertiser-id', 'default' => 0],
        'merchant_name' => ['type' => 'string', 'prop' => 'advertiser-name', 'default' => ''],
        'price' => ['type' => 'float', 'prop' => 'sale-price', 'default' => 0.0],
        'currency' => ['type' => 'string', 'prop' => 'currency', 'default' => ''],
        'ppv' => ['type' => 'string', 'prop' => 'buy-url', 'default' => ''],
        'ppc' => ['type' => 'string', 'prop' => 'buy-url', 'default' => ''],
        'adspaceId' => ['type' => 'string', 'prop' => 'ad-id', 'default' => ''],
        'description' => ['type' => 'string', 'prop' => 'description', 'default' => ''],
        'manufacturer' => ['type' => 'string', 'prop' => 'manufacturer-name', 'default' => ''],
        'ean' => ['type' => 'string', 'prop' => 'ean', 'default' => ''],
        'deliveryTime' => ['type' => 'string', 'prop' => 'deliveryTime', 'default' => ''],
        'priceOld' => ['type' => 'float', 'prop' => 'price', 'default' => 0.0],
        'shippingCosts' => ['type' => 'string', 'prop' => 'shippingCosts', 'default' => ''],
        'shipping' => ['type' => 'string', 'prop' => 'shipping', 'default' => ''],
        'merchantCategory' => ['type' => 'string', 'prop' => 'advertiser-category', 'default' => ''],
        'merchantProductId' => ['type' => 'string', 'prop' => 'merchantProductId', 'default' => ''],
        'id' => ['type' => 'string', 'prop' => 'sku', 'default' => ''],
        'image' => ['type' => 'string', 'prop' => 'image-url', 'default' => ''],
    ];

    /**
     * @method __construct
     */
    public function __construct(string $username, string $password, string $passwordApi, string $idSite = '')
    {
        $this->_network = new \Oara\Network\Publisher\CommissionJunction;
        $this->_username = $username;
        $this->_password = $password;
        $this->_passwordApi = $passwordApi;
        $this->_website_id = $idSite;
        $this->login($this->_username, $this->_password, $this->_passwordApi, $this->_website_id);
        // $this->_apiClient = \ApiClient::factory(PROTOCOL_JSON);
    }

    /**
     * @return bool
     */
    public function login(string $username, string $password, string $passwordApi, string $idSite = ''): bool
    {
        $this->_logged = false;
        if (isNullOrEmpty($username) && isNullOrEmpty($password)) {

            return false;
        }
        $this->_username = $username;
        $this->_password = $password;
        $this->_passwordApi = $passwordApi;
        $credentials = array();
        $credentials["user"] = $this->_username;
        $credentials["apipassword"] = $this->_passwordApi;
        $credentials["password"] = $this->_password;
        $this->_network->login($credentials);
        if ($this->_network->checkConnection()) {
            $this->_logged = true;
        }

        return $this->_logged;
    }

    /**
     * @return bool
     */
    public function checkLogin(): bool
    {
        return $this->_logged;
    }

    /**
     * @return array of Merchants
     */
    public function getMerchants(): array
    {
        $arrResult = array();
        $merchantList = $this->_network->getMerchantList();
        foreach ($merchantList as $merchant) {
            $Merchant = Merchant::createInstance();
            $Merchant->merchant_ID = $merchant['cid'];
            $Merchant->name = $merchant['name'];
            $arrResult[] = $Merchant;
        }

        return $arrResult;
    }

    /**
     * @param int $merchantID
     * @return array of Deal
     */
    public function getDeals($merchantID = NULL, int $page = 0, int $items_per_page = 10): DealsResultset
    {
        $response = $this->_apiCall('https://link-search.api.cj.com/v2/link-search?website-id=' . $this->_website_id . '&promotion-type=coupon&advertiser-ids=joined');
        if (\preg_match("/error/", $response)) {
            return false;
        }
        $arrResult = new DealsResultset();

        $arrResponse = xml2array($response);
        if (!is_array($arrResponse) || count($arrResponse) <= 0) {
            return $arrResult;
        }
        $arrCoupon = $arrResponse['cj-api']['links']['link'];
        foreach ($arrCoupon as $coupon) {
            $Deal = Deal::createInstance();
            $Deal->merchant_ID = $coupon['advertiser-id'];
            $Deal->merchant_name = $coupon['advertiser-name'];
            $Deal->ppc = $coupon['click-commission'];
            $Deal->description = $coupon['description'];
            $startDate = new \DateTime($coupon['promotion-start-date']);
            $Deal->startDate = $startDate;
            $endDate = new \DateTime($coupon['promotion-end-date']);
            $Deal->endDate = $endDate;
            $Deal->code = $coupon['coupon-code'];
            if ($merchantID > 0) {
                if ($merchantID == $coupon['advertiser-id']) {
                    $arrResult->deals[] = $Deal;
                }
            } else {
                $arrResult->deals[] = $Deal;
            }
        }

        return $arrResult;
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     * @return array of Transaction
     */
    public function getSales(\DateTime $dateFrom, \DateTime $dateTo, array $arrMerchantID = array()): array
    {
        $arrResult = array();
        if (count($arrMerchantID) < 1) {
            $merchants = $this->getMerchants();
            foreach ($merchants as $merchant) {
                $arrMerchantID[$merchant->merchant_ID] = ['cid' => $merchant->merchant_ID, 'name' => $merchant->name];
            }
        }
        $transcationList = $this->_network->getTransactionList($arrMerchantID, $dateFrom, $dateTo);
        foreach ($transcationList as $transaction) {
            $Transaction = Transaction::createInstance();
            $Transaction->status = $transaction['status'];
            $Transaction->amount = $transaction['amount'];
            $Transaction->custom_ID = $transaction['custom_id'];
            $Transaction->unique_ID = $transaction['unique_id'];
            $Transaction->commission = $transaction['commission'];
            $date = new \DateTime($transaction['date']);
            $Transaction->date = $date; // $date->format('Y-m-d H:i:s');
            $Transaction->merchant_ID = $transaction['merchantId'];
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
    public function getStats(\DateTime $dateFrom, \DateTime $dateTo, int $merchantID = 0): array
    {
        return array();
        /*
        $this->_apiClient->setConnectId($this->_username);
        $this->_apiClient->setSecretKey($this->_password);
        $dateFromIsoEngFormat = $dateFrom->format('Y-m-d');
        $dateToIsoEngFormat = $dateTo->format('Y-m-d');
        $response = $this->_apiClient->getReportBasic($dateFromIsoEngFormat, $dateToIsoEngFormat);
        $arrResponse = json_decode($response, true);
        $reportItems = $arrResponse['reportItems'];
        $Stat = Stat::createInstance();
        $Stat->reportItems = $reportItems;

        return array($Stat);
        */
    }

    /**
     * @param  array $params this array can contains these keys
     *                        string      query          search string
     *                        string      searchType     search type (optional) (contextual or phrase)
     *                        string      region         limit search to region (optional)
     *                        string      currency       limit search to currency (optional)
     *                        int         categoryId     limit search to categorys (optional)
     *                        array       programId      limit search to program list of programs (optional)
     *                        boolean     hasImages      products with images (optional)
     *                        float       minPrice       minimum price (optional)
     *                        float       maxPrice       maximum price (optional)
     *                        int         adspaceId      adspace id (optional)
     *                        int         page           page of result set (optional)
     *                        int         items          items per page (optional)
     *
     * @return ProductsResultset
     * @throws \Exception
     */
    public function getProducts(array $params = []): ProductsResultset
    {


//
//        $_params = array_merge([
//            'website-id' => $this->_website_id,
//            'advertiser-ids' => $params['programId'],
//            'keywords' => isset($params['query']),
//            'serviceable-area' => null,
//            'isbn' => null,
//            'upc' => null,
//            'manufacturer-name' => null,
//            'manufacturer-sku' => null,
//            'advertiser-sku' => null,
//            'low-price' => isset($params['minPrice'])? $params['minPrice'] : null,
//            'high-price' => isset($params['maxPrice']),
//            'low-sale-price' => isset($params['minPrice']),
//            'high-sale-price' => isset($params['maxPrice']),
//            'currency' => null,
//            'sort-by' => null,
//            'sort-order' => null,
//            'page-number' => $params['page'],
//            'records-per-page' => $params['items'],
//        ], $params);
//
//        $suppKeys = ['website-id', 'advertiser-ids', 'keywords', 'serviceable-area', 'isbn', 'upc', 'manufacturer-name', 'manufacturer-sku', 'advertiser-sku', 'low-price', 'high-price', 'low-sale-price', 'high-sale-price', 'currency', 'sort-by', 'sort-order', 'page-number', 'records-per-page']
//        foreach ($params as $key => $param) {
//            if (!in_array($key, $suppKeys)) {
//                unset($params[$key]);
//            }
//        }

        $_params = array(
            'website-id' => $this->_website_id,
            'advertiser-ids' => implode(',', $params['programId']),
            'keywords' => isset($params['query']) ? $params['query'] : null,
            'serviceable-area' => null,
            'isbn' => null,
            'upc' => null,
            'manufacturer-name' => null,
            'manufacturer-sku' => null,
            'advertiser-sku' => null,
            'low-price' => isset($params['minPrice']) ? $params['minPrice'] : null,
            'high-price' => isset($params['maxPrice']) ? $params['maxPrice'] : null,
            'low-sale-price' => isset($params['minPrice']) ? $params['minPrice'] : null,
            'high-sale-price' => isset($params['maxPrice']) ? $params['maxPrice'] : null,
            'currency' => 'EUR',
            'sort-by' => null,
            'sort-order' => null,
            'page-number' => $params['page'],
            'records-per-page' => $params['items'],
        );

        $__url = 'https://product-search.api.cj.com/v2/product-search?' . http_build_query($_params);
        $products = simplexml_load_string($this->_apiCall($__url));
//        $products =  $this->_network->getProducts($_params);
        $set = ProductsResultset::createInstance();
        if (count($products) == 0 || (!property_exists($products, 'products') || !property_exists($products->products, 'product'))) {
            return ProductsResultset::createInstance();
        }

        $set->page = (string)$products->products->attributes()->{'page-number'};
        $set->items = (string)$products->products->attributes()->{'records-returned'};
        $set->total = (string)$products->products->attributes()->{'total-matched'};

        foreach ($products->products->product as $productItem) {
            $Product = Product::createInstance();
            $this->propertyMapper($Product, $productItem);

            if (property_exists($productItem, 'name')) {
                $Product->name = (string)$productItem->name;//'Danava',
            }
            if (property_exists($productItem, 'modified')) {
                $Product->modified = (string)$productItem->modified; //'2016-11-24T11:52:03Z',
            }
            if (property_exists($productItem, 'advertiser-id')) {
                $Product->merchant_ID = intval((string)$productItem->{'advertiser-id'}); //17434
                $Product->merchant_name = (string)$productItem->{'advertiser-name'}; //'Twelve Thirteen DE',
            }
            if (property_exists($productItem, 'sale-price'))
                $Product->price = floatval((string)$productItem->{'sale-price'}); //129.0
            if (property_exists($productItem, 'currency'))
                $Product->currency = (string)$productItem->currency; //'EUR'
            if (property_exists($productItem, 'buy-url')) {
                $Product->ppv = (string)$productItem->{'buy-url'};
                $Product->ppc = (string)$productItem->{'buy-url'};
                $Product->adspaceId = (string)$productItem->{'ad-id'};
            }
            if (property_exists($productItem, 'description'))
                $Product->description = (string)$productItem->description; //'Rosegold trifft auf puristisches Schwarz ? aufwendige und traditionelle Makramee Technik trifft auf Eleganz. Das neue Danava Buddha Armband besteht aus schwarzem Onyx, dieser Edelstein wird sehr gerne als Schmuckstein verwendet und viel lieber getragen. Der feingearbeitete rosegoldene Buddha verleiht diesem Armband einen fernöstlichen Stil. Es lässt sich wunderbar zu allen Anlässen Tragen und zu vielen Outfits kombinieren, da es Eleganz ausstrahlt. Das Symbol des Buddhas ist besonders in dieser Saison sehr gefragt.',
            if (property_exists($productItem, 'manufacturer-name'))
                $Product->manufacturer = (string)$productItem->{'manufacturer-name'}; //'Twelve Thirteen Jewelry'
            if (property_exists($productItem, 'ean'))
                $Product->ean = (string)$productItem->ean; //'0796716271505'
            if (property_exists($productItem, 'deliveryTime'))
                $Product->deliveryTime = (string)$productItem->deliveryTime; //'1-3 Tage'
            if (property_exists($productItem, 'price'))
                $Product->priceOld = (string)$productItem->price; //0.0
            if (property_exists($productItem, 'shippingCosts'))
                $Product->shippingCosts = (string)$productItem->shippingCosts; //'0.0'
            if (property_exists($productItem, 'shipping'))
                $Product->shipping = (string)$productItem->shipping; // '0.0'
            if (property_exists($productItem, 'advertiser-category'))
                $Product->merchantCategory = (string)$productItem->{'advertiser-category'}; //'Damen / Damen Armbänder / Buddha Armbänder'
            if (property_exists($productItem, 'merchantProductId'))
                $Product->merchantProductId = (string)$productItem->merchantProductId; //'BR018.M'
            if (property_exists($productItem, 'sku'))
                $Product->id = (string)$productItem->{'sku'}; //'1ed7c3b4ab79cdbbf127cb78ec2aaff4'
            if (property_exists($productItem, 'image-url')) {
                $Product->image = (string)$productItem->{'image-url'};
            }
            $set->products[] = $Product;
        }

        return $set;
    }


    /**
     * Api call CommissionJunction
     */
    private function _apiCall($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: " . $this->_passwordApi));
        $curl_results = curl_exec($ch);
        curl_close($ch);
        return $curl_results;
    }

    public function getTrackingParameter()
    {
        return $this->_tracking_parameter;
    }
}
