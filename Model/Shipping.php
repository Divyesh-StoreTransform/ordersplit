<?php

namespace Storetransform\OrderSplit\Model;

use Magento\Framework\DataObject;
class Shipping 
{
	/**
     * @Magento\Framework\App\ResourceConnection
     */
    public $resource;
	

	/**
     * @Magento\Checkout\Model\Session
     */
	public $checkoutSession;
	
	/**
     * @Magento\Framework\ObjectManagerInterface
     */
    public $objectManager ;
	
	/**
     * @Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $_scopeConfig ;
	
	/**
     * @Magento\Framework\App\ResourceConnection getConnection
     */
    public $core_write ;
	

	/**
     * @Magento\Framework\Pricing\Helper\Data
     */
	public $priceHelper;
	
	
	/**
     * @Magento\Quote\Model\Quote\Address\RateRequest
     */
	public $rateRequestFactory;
	
	
	/**
     * @Magento\Quote\Model\Quote\Address\RateRequest
     */
	public $shipping;
   
    public function __construct(
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $region,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Quote\Model\Quote\Address\RateRequest $rateRequestFactory,
        \Magento\Shipping\Model\Shipping $shipping
        ) {
		$this->objectManager = $objectManager;
		$this->_scopeConfig =  $this->objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
		$this->priceHelper = $this->objectManager->create('Magento\Framework\Pricing\Helper\Data'); // Instance of Pricing Helper
      
		$this->resource = $this->objectManager->get('Magento\Framework\App\ResourceConnection');
		$this->checkoutSession = $this->objectManager->create('Magento\Checkout\Model\Session');
		$this->core_write = $this->resource->getConnection();
		$this->rateRequestFactory = $rateRequestFactory;
		$this->shipping = $shipping;
		
    }
	
	
	/**
     * caluate package shipping price use default shipping 
     * @return string
     */
	public function getShippingPrice($ShippingAddress,$shipping_code,$allitems,$tmppackageWeight)
    {
		$shippingPrice=0;
		
	/*	 $address = $this->objectManager->create(DataObject::class, [
            'data' => [
                'region_id' => 'CA',
                'postcode' => '11111',
                'lastname' => 'John',
                'firstname' => 'Doe',
                'street' => 'Some street',
                'city' => 'Los Angeles',
                'email' => 'john.doe@example.com',
                'telephone' => '11111111',
                'country_id' => 'CN',
                'item_qty' => 1
            ]
        ]);*/
	
		$cart = $this->objectManager->get('\Magento\Checkout\Model\Cart'); 
		//$address =$cart->getQuote()->getShippingAddress();
		$address = $ShippingAddress;
		$items=$cart->getQuote()->getAllVisibleItems();
		$tmpitems=array();$totalqty=0;$totalprice=0;
		
		
		$carriercodes=explode('_',$shipping_code);
		$carriercode=$carriercodes[0];
		$carriermethod=$carriercodes[1];
		$tmpitemsids=array();
		foreach ($items as $item) {
			//	if($carriercode=='freeshipping')$item->setFreeShipping(true);else $item->setFreeShipping(false);
				$quoteItemId= $item->getId();
				$quoteItemPrice= $item->getPrice();
				$tmpitemsqtys[$quoteItemId]=0;
				foreach($allitems as $pro){
					if($pro['itemId']==$quoteItemId){
						$totalqty+=$pro['qty'];
						$totalprice+=$pro['qty']*$quoteItemPrice;
						$item->setQty($pro['qty']);
						//if($carriercode=='freeshipping')$item->setFreeShipping(true);
						$tmpitems[]=$item;
						$tmpitemsids[]=$quoteItemId;
					}
				}
		}
		/*foreach ($items as $i=>$item) {
				$items[$i]->isDeleted(false);
				if(!in_array($items[$i]->getId(),$tmpitemsids))$items[$i]->isDeleted(true);
		}*/
		if($carriercode=='freeshipping')$address->setFreeShipping(true);else $address->setFreeShipping(false);
		
		 $address->setItemQty($totalqty);
		 $address->setBaseSubtotal($totalprice);
		 $address->setBaseSubtotalWithDiscount($totalprice);
		 $address->setPackageValueWithDiscount($totalprice);
		 $address->setWeight($tmppackageWeight);
		 $address->setAllItems($tmpitems);
		
		
		
        /** @var Shipping $result */
        $result = $this->shipping->resetResult()->collectRatesByAddress($address, $carriercode);
		
	
		//print_r($result->getResult());
		 $rates = $result->getResult()->getAllRates();
		//  $rate = array_pop($rates);
		$rate=false;
		 foreach ($rates as $method) {
		 	//echo $method->getMethod().'=='.$method->getPrice().' all'.count($address->getAllItems()).'<br>';
		 	if($method->getMethod()== $carriermethod)
				$rate=$method;
		 }
		 
	
		 if($rate ){//&& $rate->getCarrier()== $carriercode
		  	$shippingPrice=$rate->getData('price');
			//echo $totalprice.'>>>'.$rate->getMethod().'--'.$address->getBaseSubtotalWithDiscount().'---'.$shippingPrice.'<br>';
		   } else {
		  $shippingPrice=false;
		  	//echo $totalprice.'>eeee>>'.$carriermethod.$shippingPrice.'<br>';
		 }
			//echo $carriercode.'>'.$shipping_code.$shippingPrice.'ssss<br>';
			//echo '<br><br>';
		return $shippingPrice;
	
	}
	
	 /**
     * Collect rates by address
     *
     * @param \Magento\Framework\DataObject $address
     * @param null|bool|array $limitCarrier
     * @return $this
     */
    public function collectRatesByAddress(\Magento\Framework\DataObject $address, $limitCarrier = null)
    {
        /** @var $request \Magento\Quote\Model\Quote\Address\RateRequest */
        $request = $this->rateRequestFactory->create();
        $request->setAllItems($address->getAllItems());
        $request->setDestCountryId($address->getCountryId());
        $request->setDestRegionId($address->getRegionId());
        $request->setDestPostcode($address->getPostcode());
        $request->setPackageValue($address->getBaseSubtotal());
        $request->setPackageValueWithDiscount($address->getBaseSubtotalWithDiscount());
        $request->setPackageWeight($address->getWeight());
        $request->setFreeMethodWeight($address->getFreeMethodWeight());
        $request->setPackageQty($address->getItemQty());

        /** @var \Magento\Store\Api\Data\StoreInterface $store */
        $store = $this->_storeManager->getStore();
        $request->setStoreId($store->getId());
        $request->setWebsiteId($store->getWebsiteId());
        $request->setBaseCurrency($store->getBaseCurrency());
        $request->setPackageCurrency($store->getCurrentCurrency());
        $request->setLimitCarrier($limitCarrier);

        $request->setBaseSubtotalInclTax($address->getBaseSubtotalInclTax());

        return $this->collectRates($request);
    }
	
	
	
	
}
