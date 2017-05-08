<?php

namespace Padosoft\AffiliateNetwork\Networks;

use Padosoft\AffiliateNetwork\DealsResultset;
use Padosoft\AffiliateNetwork\LinkShareEx;
use Padosoft\AffiliateNetwork\Product;
use Padosoft\AffiliateNetwork\ProductsResultset;
use Padosoft\AffiliateNetwork\Merchant;
use Padosoft\AffiliateNetwork\AbstractNetwork;
use Padosoft\AffiliateNetwork\NetworkInterface;

/**
 * Class LinkShare
 * @package Padosoft\AffiliateNetwork\Networks
 */
class LinkShare extends AbstractNetwork implements NetworkInterface
{
    /**
     * @var object
     */
    private $_network   = null;
    private $_username  = '';
    private $_password  = '';
    private $_logged    = false;


    /**
     * @method __construct
     */
    public function __construct(string $username, string $password,string $idSite='')
    {
        $this->_network = new LinkShareEx;
        $this->_username = $username;
        $this->_password = $password;
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
        $credentials["connectid"] = $this->_username;
        $credentials["secretkey"] = $this->_password;
        // @TODO 
        print 'Login Linkshare';
        die;
        return false;
    }

    public function getDeals($merchantID, int $page = 0, int $items_per_page = 10) : DealsResultset {
        // TODO: Implement getDeals() method.
    }
    
    public function getStats(\DateTime $dateFrom, \DateTime $dateTo, int $merchantID = 0) : array {
        // TODO: Implement getStats() method.
    }

    public function getSales(\DateTime $dateFrom, \DateTime $dateTo, array $arrMerchantID = array()) : array {
        // TODO: Implement getSales() method.
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
        if (!$this->checkLogin()) {
            return array();
        }
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

    public function getProducts(array $params = []): ProductsResultset
    {
        
    }
}
