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

// require "../vendor/fubralimited/php-oara/Oara/Network/Publisher/Amazon/Zapi/ApiClient.php";

/**
 * Class Amazon
 * @package Padosoft\AffiliateNetwork\Networks
 */
class Amazon extends AbstractNetwork implements NetworkInterface
{
    /**
     * @var object
     */
    private $_credentials = null;

    /**
     * @method __construct
     */
    public function __construct($user, $password, $secretKey, $amazonKey, $associateTag)
    {
        $credentials = [
            'user' => $user,
            'password' => $password,
            'secretKey' => $secretKey,
            'amazonKey' => $amazonKey,
            'associateTag' => $associateTag
        ];

        $this->_network = new \Oara\Network\Publisher\Amazon;
        $this->_credentials = $credentials;

        $this->login( $this->_credentials);
        // $this->_apiClient = \ApiClient::factory(PROTOCOL_JSON);
    }

    /**
     * @return bool
     */
    public function login(array $credentials): bool
    {
        $this->_logged = false;
        if (!is_array($credentials) && empty( $credentials )) {

            return false;
        }
        //$credentials = array();
        $this->_network->login($credentials);
        $this->_logged = true;


        return $this->_logged;
    }

    /**
     * @return bool
     */
    public function checkLogin() : bool
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
        if (count( $arrMerchantID ) < 1) {
            $merchants = $this->getMerchants();
            foreach ($merchants as $merchant) {
                $arrMerchantID[$merchant->merchant_ID] = ['cid' => $merchant->merchant_ID, 'name' => $merchant->name];
            }
        }
        $transcationList = $this->_network->getTransactionList($arrMerchantID, $dateFrom,$dateTo);
        foreach($transcationList as $transaction) {
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
     * @param  array $params
     *
     * @return ProductsResultset
     */

    public function getProducts(array $params = []): ProductsResultset
    {

        $_params = array(
            'Keywords' => isset($params['query'])?$params['query']:null,
            'MinimumPrice' => isset($params['minPrice'])? $params['minPrice'] : null,
            'MaximumPrice' => isset($params['maxPrice'])? $params['maxPrice']:null,
            'currency' => 'EUR',
            'merchantID' => 'Amazon',
            'BrowseNode' =>isset($params['programId'])?$params['programId']:null,
            'ItemPage' => $params['page']?$params['page']:1,
            'AWSAccessKeyId' => $secret_key = $this->_network->_credentials['amazonKey'],
            'AssociateTag' => $secret_key = $this->_network->_credentials['associateTag'],
            'Operation'=>'ItemSearch',
            'ResponseGroup' => 'Images,ItemAttributes,Offers, Variations',
            'SearchIndex' => 'Apparel',
            'Timestamp' => isset($params['Timestamp'])?$params['Timestamp'] : gmdate('Y-m-d\TH:i:s\Z')

        );

        $products = simplexml_load_string($this->_apiCall($_params));
//        $products =  $this->_network->getProducts($_params);
        $set = ProductsResultset::createInstance();
        if (count($products) == 0 || (!property_exists($products, 'Items')) || !property_exists($products->Items, 'Item') )
        {
            return ProductsResultset::createInstance();
        }

        $set->page = (string)$products->Items->Request->ItemSearchRequest->ItemPage[0];
        $set->total = (string)$products->Items->TotalResults[0];
        $set->items = 10;

        foreach ($products->Items->Item as $productItem) {

            $Product = Product::createInstance();

            if (property_exists($productItem->ItemAttributes, 'Title')) {
                $Product->name = (string)$productItem->ItemAttributes->Title;//'Danava',
            }
            if (property_exists($productItem, 'modified')) {
                $Product->modified = (string)$productItem->modified; //'2016-11-24T11:52:03Z',
            }
            if (property_exists($productItem, 'advertiser-id')) {
                $Product->merchant_ID = (string)$productItem->{'advertiser-id'}; //'Twelve Thirteen DE'
                $Product->merchant_name = (string)$productItem->{'advertiser-name'}; //17434,
            }
            if (property_exists($productItem, 'DetailPageURL')) {
                $Product->ppv = (string)$productItem->{'DetailPageURL'};
                $Product->ppc = (string)$productItem->{'DetailPageURL'};
                $Product->adspaceId = (string)$productItem->{'ad-id'};
            }
            $productItem = $productItem->Variations[0]->Item[0];
            if (property_exists($productItem->ItemAttributes, 'ListPrice'))
                $Product->price = (string)($productItem->ItemAttributes->ListPrice->Amount)/100; //129.0
            if (property_exists($productItem->ItemAttributes->ListPrice, 'CurrencyCode'))
                $Product->currency = (string)$productItem->ItemAttributes->ListPrice->CurrencyCode; //'EUR'

            if (property_exists($productItem->ItemAttributes, 'Feature'))
                $Product->description = implode ( '. ', json_decode(json_encode((array)$productItem->ItemAttributes->Feature), TRUE)); //'Rosegold trifft auf puristisches Schwarz ? aufwendige und traditionelle Makramee Technik trifft auf Eleganz. Das neue Danava Buddha Armband besteht aus schwarzem Onyx, dieser Edelstein wird sehr gerne als Schmuckstein verwendet und viel lieber getragen. Der feingearbeitete rosegoldene Buddha verleiht diesem Armband einen fernöstlichen Stil. Es lässt sich wunderbar zu allen Anlässen Tragen und zu vielen Outfits kombinieren, da es Eleganz ausstrahlt. Das Symbol des Buddhas ist besonders in dieser Saison sehr gefragt.',
            if (property_exists($productItem->ItemAttributes, 'Brand'))
                $Product->manufacturer = (string)$productItem->ItemAttributes->Brand; //'Twelve Thirteen Jewelry'
            if (property_exists($productItem->ItemAttributes, 'EAN'))
                $Product->ean = (string)$productItem->ItemAttributes->EAN; //'0796716271505'
            if (property_exists($productItem, 'deliveryTime'))
                $Product->deliveryTime = (string)$productItem->deliveryTime; //'1-3 Tage'
            if (property_exists($productItem->ItemAttributes, 'price'))
                $Product->priceOld = (string)$productItem->ListPrice->Amount; //0.0
            if (property_exists($productItem, 'shippingCosts'))
                $Product->shippingCosts = (string)$productItem->shippingCosts; //'0.0'
            if (property_exists($productItem, 'shipping'))
                $Product->shipping = (string)$productItem->shipping; // '0.0'
            if (property_exists($productItem->ItemAttributes, 'ProductTypeName'))
                $Product->merchantCategory = (string)$productItem->ItemAttributes->ProductTypeName; //'Damen / Damen Armbänder / Buddha Armbänder'
            if (property_exists($productItem, 'merchantProductId'))
                $Product->merchantProductId = (string)$productItem->merchantProductId; //'BR018.M'
            if (property_exists($productItem, 'id'))
                $Product->id = (string)$productItem->id; //'1ed7c3b4ab79cdbbf127cb78ec2aaff4'
            if (property_exists($productItem, 'LargeImage')) {
                $Product->image = (string)$productItem->LargeImage->URL;
            }
            $set->products[] = $Product;
        }

        return $set;


        // TODO: Implement getProducts() method.
        throw new \Exception("Not implemented yet");
    }

    /**
     * Api call CommissionJunction
     */
    private function _apiCall($params)
    {

        $secret_key = $this->_network->_credentials['secretKey'];


        // The region you are interested in
        $endpoint = "webservices.amazon.it";

        $uri = "/onca/xml";

        ksort($params);

        $pairs = array();

        foreach ($params as $key => $value) {
            array_push($pairs, rawurlencode($key)."=".rawurlencode($value));
        }

        // Generate the canonical query
        $canonical_query_string = join("&", $pairs);

        // Generate the string to be signed
        $string_to_sign = "GET\n".$endpoint."\n".$uri."\n".$canonical_query_string;

        // Generate the signature required by the Product Advertising API
        $signature = base64_encode(hash_hmac("sha256", $string_to_sign, $secret_key, true));

        // Generate the signed URL
        $request_url = 'http://'.$endpoint.$uri.'?'.$canonical_query_string.'&Signature='.rawurlencode($signature);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_POST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
//        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: " . $this->_passwordApi));
        $curl_results = curl_exec($ch);
        curl_close($ch);
        return $curl_results;
    }

}
