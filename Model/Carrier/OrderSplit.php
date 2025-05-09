<?php

namespace Storetransform\OrderSplit\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Config;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Psr\Log\LoggerInterface;

class OrderSplit extends AbstractCarrier implements CarrierInterface
{
    /**
     * Carrier's code
     *
     * @var string
     */
    protected $_code = 'mtordersplit';

    /**
     * Whether this carrier has fixed rates calculation
     *
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * @var ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * Generates list of allowed carrier`s shipping methods
     * Displays on cart price rules page
     *
     * @return array
     * @api
     */
    public function getAllowedMethods()
    {
        return [$this->getCarrierCode() => __($this->getConfigData('name'))];
    }

    /**
     * Collect and get rates for storefront
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param RateRequest $request
     * @return DataObject|bool|null
     * @api
     */
    public function collectRates(RateRequest $request)
    {
        /**
         * Make sure that Shipping method is enabled
         */
        if (!$this->isActive()) {
            return false;
        }

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->_rateResultFactory->create();

        $method = $this->_rateMethodFactory->create();
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
		$core_write = $resource->getConnection();
		
		
		$final_code=$this->getCarrierCode();
		$final_title=$this->getConfigData('title');
		
		$checkoutSession = $objectManager->create('Magento\Checkout\Model\Session');
		
		 $cart = $objectManager->get('\Magento\Checkout\Model\Cart'); 
		// $quote = $cart->getQuote();
		 
		 $quote = $checkoutSession->getQuote();
		 
	
		 $addressId_prefix="";
			//multishipping
		 $addressId= "";//$shippingAddress->getId();
		
		
		
		//	if( stristr($_SERVER['REQUEST_URI'],'multishipping/checkout') )
	
		  
		 $totalItems = $quote->getItemsCount();
		$totalQuantity = $quote->getItemsQty();
		$subTotal = $quote->getSubtotal();
		$grandTotal = $quote->getGrandTotal();
		if( stristr($_SERVER['REQUEST_URI'],'multishipping/checkout') ){
		
			$checkout=$objectManager->get('Magento\Multishipping\Model\Checkout\Type\Multishipping');
			$Addresses=$checkout->getQuote()->getAllShippingAddresses();
			$addressId=$checkout->getQuote()->getShippingAddress()->getId();
			if($addressId>0)
			$addressId_prefix=$addressId."_";
			
		}
		
		$shippingPrice=(float)$checkoutSession->getData($addressId_prefix.'ShippingPrice');
		
		
	
		$OrderSplitModel = $objectManager->create('Storetransform\OrderSplit\Model\OrderSplit');
		$mtordersplit_package_data=$checkoutSession->getData($addressId_prefix.'mtordersplit_package_data');
		$mtordersplit_package_shipping=$checkoutSession->getData($addressId_prefix.'mtordersplit_package_shipping');
	/*
		$directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
		$base  =  $directory->getRoot();
		$dumpFile = @fopen($base .'/var/log/OrderSplit.log', 'a+') ;
		fwrite($dumpFile, "\r\n".date("Y-m-d H:i:s").	' multishipping - '.$addressId_prefix."\r\n");
		fwrite($dumpFile,' ShippingPrice '.$shippingPrice."\r\n");
		fwrite($dumpFile,' mtordersplit_package_data '.$mtordersplit_package_data."\r\n");
		fwrite($dumpFile,' mtordersplit_package_shipping '.$mtordersplit_package_shipping."\r\n");
		*/
		$customerSelectionPackage=$OrderSplitModel->customerSelectionPackage($mtordersplit_package_shipping);
		
		if(isset($customerSelectionPackage[0]) && sizeof($customerSelectionPackage)==1){
			$AllPackages=unserialize($mtordersplit_package_data);
			foreach($AllPackages as $p=> $package){
				foreach($package['shipping'] as $shipment){
					if($shipment['value']==$customerSelectionPackage[$p]['shipping_code']){
					$final_title=$shipment['name'];
					break;
					}
				}
			}
			
		}
		
	
	
	
        /**
         * Set carrier's method data
         */
        $method->setCarrier($final_code);
        $method->setCarrierTitle($final_title);

        /**
         * Displayed as shipping method under Carrier
         */
        $method->setMethod($final_code);
        $method->setMethodTitle($this->getConfigData('method_title'));

        $method->setPrice($shippingPrice);
        $method->setCost($shippingPrice);

        $result->append($method);

        return $result;
    }
	
	/**
     * Check if carrier has shipping tracking option available
     *
     * @return bool
     */
    public function isTrackingAvailable()
    {
        return false;
    }

}