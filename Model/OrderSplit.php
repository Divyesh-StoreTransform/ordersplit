<?php

namespace Storetransform\OrderSplit\Model;

class Ordersplit 
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
   
    public function __construct(
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $region,
        \Magento\Framework\ObjectManagerInterface $objectManager
        ) {
		$this->objectManager = $objectManager;
		$this->_scopeConfig =  $this->objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
		$this->priceHelper = $this->objectManager->create('Magento\Framework\Pricing\Helper\Data'); // Instance of Pricing Helper
      
		$this->resource = $this->objectManager->get('Magento\Framework\App\ResourceConnection');
		$this->checkoutSession = $this->objectManager->create('Magento\Checkout\Model\Session');
		$this->core_write = $this->resource->getConnection();
    }
	
	
	/**
     * get All installed shipping method
     * @return array
     */
	public function getAllShipping()
    {
		$objectManager=$this->objectManager;	
		$shippingHelper = $objectManager->get('Magento\Shipping\Helper\Data');
		$shippingConfig = $objectManager->get('Magento\Shipping\Model\Config');
		$activeShippingMethods = $shippingConfig->getActiveMethods();
		$activeShipping = $objectManager->create('\Magento\Shipping\Model\Config\Source\Allmethods')->toOptionArray(true);    
		$shippingMethods = array();
				foreach ($activeShipping as $k=> $option) { 
					$methoded=array();
					 if (is_array($option['value'])) {
						foreach ($option['value'] as &$method) {
							$methoded['label'] = preg_replace('#^\[.+?\]\s#', '', $method['label']);
							$methoded['value'] = $method['value'];
							if($methoded['label']=="") $methoded['label']=$option['label'];
						}
						if(isset($methoded['value']))
						  $shippingMethods[] = array('code' => $methoded['value'], 'name' => $methoded['label']);      
					  }        
				} 
		return $shippingMethods;    
	}
	
	/**
     * get All product data
     * @return array
     */
	public function getAllProducts()
    {
		$objectManager=$this->objectManager;	
		$resource=$this->resource;
		$core_write = $this->core_write;
		$catalog_product_entity_varchar = $resource->getTableName('catalog_product_entity_varchar');
		$catalog_product_entity = $resource->getTableName('catalog_product_entity');
    	$selectsql=("SELECT nametable.value, nametable.entity_id, cpe.sku  FROM   `$catalog_product_entity_varchar` AS nametable LEFT JOIN $catalog_product_entity cpe  ON nametable.entity_id = cpe.entity_id WHERE  nametable.attribute_id = (SELECT attribute_id   FROM   `eav_attribute` WHERE  `entity_type_id` = 4  AND `attribute_code` LIKE 'name') order by nametable.value" );
		$rows = $core_write->fetchAll($selectsql);
		$products=array();
		foreach($rows as $row){
			$products[$row['entity_id']]=array('name'=>$row['value'],'sku'=>$row['sku']);
		}
		//for($i=11;$i<=2011;$i++)$products[$i]='TestTMP Product'.$i;
		return $products;
	}
	
	/**
     * save all rules data
     * @return string
     */
	public function saveRule($shipping_code,$data)
    {	
		$resource=$this->resource;
		$core_write = $this->core_write;
		/*
		//debug use
		$directory = $this->objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
		$base  =  $directory->getRoot();$dumpFile = @fopen($base .'/var/log/OrderSplit.log', 'a+') ;
		fwrite($dumpFile,' save '.$shipping_code.serialize($data)."\r\n");
		*/
		if($shipping_code=="" || !isset($data['priority'])) return;
		
		$mt_ordersplit_package_rule = $resource->getTableName('mt_ordersplit_package_rule');
		$mt_ordersplit_package_rule_product = $resource->getTableName('mt_ordersplit_package_rule_product');
		$dbsql=("delete from $mt_ordersplit_package_rule where shipping_code='$shipping_code'" );
		$core_write->query($dbsql);
		$dbsql=("delete from $mt_ordersplit_package_rule_product where shipping_code='$shipping_code'" );
		$core_write->query($dbsql);
		
		$priority=$data['priority'];
		$active=$data['active'];
		$package_max_weight=$data['package_max_weight'];
		$package_max_price=$data['package_max_price'];
		$package_max_qty=$data['package_max_qty'];
		$shipping_price_formula=$data['shipping_price_formula'];
		$tracking_url=$data['tracking_url'];
		$dbsql=("insert into $mt_ordersplit_package_rule (active,priority,shipping_code,package_max_weight,package_max_price,package_max_qty,shipping_price_formula,tracking_url) values ('$active','$priority','$shipping_code','$package_max_weight','$package_max_price','$package_max_qty','$shipping_price_formula','$tracking_url')" );
		$core_write->query($dbsql);
		$products=array();
		/*print_r($data["product_max_qty"]);print_r($data["product_max_weight"]);print_r($data["product_cant_mix"]);
		return sizeof($data["product_allow"]); return;*/
		if(isset($data['product_allow']) && sizeof($data['product_allow'])>0){
			foreach($data['product_allow'] as $product_id=>$t){
				$product_max_qty= @$data['product_max_qty'][$product_id];
				$product_max_weight=$data['product_max_weight'][$product_id];
				$product_cant_mix=isset($data['product_cant_mix'][$product_id])?1:0;
				$dbsql=("insert into $mt_ordersplit_package_rule_product (shipping_code,product_id,product_max_qty,product_max_weight,product_cant_mix) values ('$shipping_code','$product_id','$product_max_qty','$product_max_weight','$product_cant_mix')" );
				
				$core_write->query($dbsql);
			}
		}
		
		return __('Success');
	}
	
	
	/**
     * get all shipping rule data
     * @return array
     */
	public function getShippingRuleData($shipping_code)
    {
		$resource=$this->resource;
		$core_write = $this->core_write;
		$mt_ordersplit_package_rule = $resource->getTableName('mt_ordersplit_package_rule');
    	$selectsql=("SELECT * FROM   `$mt_ordersplit_package_rule` where shipping_code='$shipping_code'" );
		$rows = $core_write->fetchAll($selectsql);
		return isset($rows[0])?$rows[0]:array();
	}
	
	
	/**
     * get signle product rule data
     * @return array
     */
	public function getProductRuleData($shipping_code,$product_id=0)
    {
		$resource=$this->resource;
		$core_write = $this->core_write;
		$mt_ordersplit_package_rule_product = $resource->getTableName('mt_ordersplit_package_rule_product');
    	$selectsql=("SELECT * FROM   `$mt_ordersplit_package_rule_product` where shipping_code='$shipping_code' ".($product_id>0?" and product_id='$product_id'":'') );
		$rows = $core_write->fetchAll($selectsql);
		if($product_id>0 && sizeof($rows)==1) return $rows[0];
		return $rows;
	}
	
	
	/**
     * Pack Packages From cart
     * @return array
     */
	public function packPackages($customershipping="",$multiQuoteItems=false,$addressId=false,$AddressCountryId=false)
    {
		$resource=$this->resource;
		$core_write = $this->core_write;
		$mt_ordersplit_package_rule = $resource->getTableName('mt_ordersplit_package_rule');
		$mt_ordersplit_package_rule_product = $resource->getTableName('mt_ordersplit_package_rule_product');
		
		 $cart = $this->objectManager->get('\Magento\Checkout\Model\Cart'); 
		 $request=$this->objectManager->get('\Magento\Framework\App\RequestInterface'); 
		 $countryId = $request->getParam('countryId');	
		 
		 $customerSession = $this->objectManager->get('Magento\Customer\Model\Session');
		 if($countryId=="" || !$countryId){
		 		$lastselectedCountryId=$customerSession->getData('lastselectedCountryId');
				
		 if(isset($_GET['debug']))
					echo '<br>-----------<br>lastselectedCountryId: '.$lastselectedCountryId.' ';
				if($lastselectedCountryId==""){
				$customerId=(int)$customerSession->getCustomer()->getId();
				if($customerId>0){
					$customerObj = $this->objectManager->create('Magento\Customer\Model\Customer')->load($customerId);
						$tmpddressId= $customerObj->getDefaultShipping();
						$address = $this->objectManager->get('Magento\Customer\Model\AddressFactory')->create()->load($tmpddressId);
						
						$countryId=	($address->getData('country_id'));
						
		 if(isset($_GET['debug']))
					echo '<br>-----------<br>ddcountryId: '.$countryId.' ';
					}
				}else
				$countryId=$lastselectedCountryId;
		 }
		 
		 if($AddressCountryId)  $countryId = $AddressCountryId;
		 
		 	 if(isset($_GET['debug']))
					echo '<br>-----------<br>countryId: '.$countryId.' ';
		/*	 $ShippingAddressData =$cart->getQuote()->getShippingAddress()->getData();
		 $ShippingAddress=$this->objectManager->get('\Magento\Quote\Model\Quote\Address\RateRequest'); 
		 $ShippingAddress->setData($ShippingAddressData);*/
		 $ShippingAddress= $cart->getQuote()->getShippingAddress();
		 
		 if($countryId!=""){
		 $ShippingAddress->setCountryId($countryId);
		 $customerSession->setData('lastselectedCountryId',$countryId);
		 
		 	 if(isset($_GET['debug']))
					echo '<br>-----------<br>set lastselectedCountryId: '.$countryId.' ';
		 }
		 
		 
		 $items=$cart->getQuote()->getAllVisibleItems();
		 if($multiQuoteItems) $items=$multiQuoteItems;
		 
		 $packageContents=$tmppackage=$abandon_item=$tmp_abandon_item=array();
		 //package number
		 $p=0;
		 $packageContents[$p]=array();
		 $packageShippings[$p]=array();
		 $packageWeight[$p]=$tmppackageWeight=0;
		 $packageQty[$p]=$tmppackageQty=0;
		 $packageProductQty[$p]=array();
		 $packageProductWeight[$p]=array();
		 $last_shpiping_code=$last_product_id='';
		 
		 //put all items in abandon box
		 $i=0;
		 foreach ($items as $item) {
		 	$abandon_item[$i++]=$item;
		 }
		//$tmp_abandon_item=$abandon_item;
		
		//use session seletion
		$customerselected=false;
		if($customershipping!=""){
			$customerselected=true;
			$this->checkoutSession->setData('CustomerShipping',$customershipping);
			}
			$customershipping=$this->checkoutSession->getData('CustomerShipping');
			
		if($customershipping!=''){
		$selectAllshippingsql=("SELECT * FROM   `$mt_ordersplit_package_rule` where active=1 order by (case when shipping_code='".$customershipping."' THEN 1 ELSE 2 END), priority" );
		}else
    	$selectAllshippingsql=("SELECT * FROM   `$mt_ordersplit_package_rule` where active=1 order by priority" );
		$allPackageShipping = $core_write->fetchAll($selectAllshippingsql);
		
		
		$countryWarning=false;
		$countryBan=array();
		foreach($allPackageShipping as $s=> $shippingRule){
			
			//check countryId 
			$carriercodes=explode('_',$shippingRule['shipping_code']);
			$carriercode=$carriercodes[0];
			
			
			$active=$this->objectManager->create('Storetransform\OrderSplit\Helper\Data')->getConfig('carriers/'.$carriercode.'/active');
			if(!$active) continue;
			$speCountriesAllow=$this->objectManager->create('Storetransform\OrderSplit\Helper\Data')->getConfig('carriers/'.$carriercode.'/sallowspecific');
			if($speCountriesAllow){
				$specificcountry=$this->objectManager->create('Storetransform\OrderSplit\Helper\Data')->getConfig('carriers/'.$carriercode.'/specificcountry');
				$availableCountries = explode(',', $specificcountry);
				if(!in_array($countryId,$availableCountries)){
					if($customerselected!="" && $customershipping==$shippingRule['shipping_code'])
					$countryWarning=$shippingRule['shipping_code'];
					
					
					$countryBan[]=$shippingRule['shipping_code'];
					continue;
				}
			
			}
	
			
		
			
			//put left item into abandon box
			//$abandon_item=array_merge($abandon_item,$tmp_abandon_item);
			$tmp_abandon_item=array();
			
			
			 //change package if shipping method changed
			if($last_shpiping_code!='' && $last_shpiping_code!=$shippingRule['shipping_code'] &&  (isset($packageContents[$p]) && sizeof($packageContents[$p])>0) ){
				$p++;$packageContents[$p]=array();$packageShippings[$p]=array();
				$packageQty[$p]=$packageWeight[$p]=0;$packageProductQty[$p]=array();$packageProductWeight[$p]=array();
			}
			//check all pack extra shipping
			$packageShippings=$this->checkExistsPackageShipping($p,$packageContents,$packageShippings,$shippingRule['shipping_code'],$packageQty,$packageWeight,$packageProductQty,$packageProductWeight);
			
			//mix product put last
			$notmixitem=$mixitem=array();
			foreach ($abandon_item as $k=> $item) {
				$product_id=$item->getProductId();
				$selectsql=("SELECT * FROM   `$mt_ordersplit_package_rule` as opr,`$mt_ordersplit_package_rule_product` as oprp where  opr.shipping_code=oprp.shipping_code and oprp.shipping_code='".$shippingRule['shipping_code']."' and oprp.product_id='$product_id' ");
				$productruledatas = $core_write->fetchAll($selectsql);
				$productruledata=isset($productruledatas[0])?$productruledatas[0]:false;
				if(isset($productruledata['product_cant_mix']) && $productruledata['product_cant_mix']>0)
				$notmixitem[]=$item;
				else
				$mixitem[]=$item;
			}
			$abandon_item=array_merge($notmixitem,$mixitem);
			
			
			foreach ($abandon_item as $k=> $item) {
			//start pack each item 
			
			for($i=1;$i<=$item->getQty();$i++){
			
		 	$product_id=$item->getProductId();
			$selectsql=("SELECT * FROM   `$mt_ordersplit_package_rule` as opr,`$mt_ordersplit_package_rule_product` as oprp where  opr.shipping_code=oprp.shipping_code and oprp.shipping_code='".$shippingRule['shipping_code']."' and oprp.product_id='$product_id' ");
			
			$productruledatas = $core_write->fetchAll($selectsql);
			$productruledata=isset($productruledatas[0])?$productruledatas[0]:false;
				
			
			if(!$productruledata){
				// shipping method not for this item
			 	continue;
			 }
			 
			
			 //change package if product changed when it can not be mixed
			//echo $selectsql.'=='.$p.'-'.isset($packageCantMix[$p]).$item->getProductId().'-'.$last_product_id.'-'.$productruledata['product_cant_mix'].'<br>';
 			 if(isset($packageCantMix[$p]) && $last_product_id!=$item->getProductId() && (isset($packageContents[$p]) && sizeof($packageContents[$p])>0) ){
			 //	echo 'Change package mixed';
			 	$p++;$packageContents[$p]=array();$packageShippings[$p]=array();
				$packageQty[$p]=$packageWeight[$p]=0;$packageProductQty[$p]=array();$packageProductWeight[$p]=array();
			 }
			 
			
		
			
			 $last_shpiping_code=$productruledata['shipping_code'];
			 $last_product_id=$item->getProductId();
			 
			   //try to put this item to existing packages
			  $put2exit=false;
			 if($p>0 && $productruledata['product_cant_mix']<=0){
			 	for($e=0;$e<=$p;$e++){
					if(!isset($packageCantMix[$e])  && (isset($packageContents[$e]) && sizeof($packageContents[$e])>0)  && $packageShippings[$e][0]==$productruledata['shipping_code']){
							$tmpitemArr=array('itemId'=>$item->getId(),'itemWeight'=>$item->getWeight(),'productId'=>$item->getProductId(),'name'=>$item->getName(),'price'=>$item->getPrice());
							$tmppackage=$packageContents[$e];
							$tmppackage[]=$tmpitemArr;
							$tmppackageWeight=$packageWeight[$e]+$item->getWeight();
							$tmppackageQty=$packageQty[$e]+1;
							if(!isset($packageProductQty[$e][$item->getProductId()])) $packageProductQty[$e][$item->getProductId()]=0;
							$tmppackageProductQty=$packageProductQty[$e][$item->getProductId()]+1;
							if(!isset($packageProductWeight[$e][$item->getProductId()])) $packageProductWeight[$e][$item->getProductId()]=0;
							$tmppackageProductWeight=$packageProductWeight[$e][$item->getProductId()]+1;
							
							if($this->checkPackageQty($tmppackageQty,$productruledata['package_max_qty'],$tmppackageProductQty,$productruledata['product_max_qty']) && $this->checkPackageWeight($tmppackageWeight,$productruledata['package_max_weight'],$tmppackageProductWeight,$productruledata['product_max_weight']) && $this->checkPackagePrice($tmppackage,$productruledata['package_max_price']) ){
								//put item into package
								if(isset($_GET['debug']))
								echo '<br>-----------<br>PUT Mix Item: '.$item->getName().' ('.$i.') into package '.$e.' Rule: '.json_encode($productruledata);
								$packageContents[$e][]=$tmpitemArr;
								if(!in_array($productruledata['shipping_code'],$packageShippings[$e]))
								$packageShippings[$e][]=$productruledata['shipping_code'];
								$packageQty[$e]+=1;
								$packageWeight[$e]+=$item->getWeight();
								$packageProductQty[$e][$item->getProductId()]+=1;
								 //remove from abandon box
								unset($abandon_item[$k]);
								 $put2exit=true;
								break;
							}
							
					
					}
				}
			 }
			 if($put2exit) continue;
			 //try to put this item to existing packages end
			 
		
				$tmpitemArr=array('itemId'=>$item->getId(),'itemWeight'=>$item->getWeight(),'productId'=>$item->getProductId(),'name'=>$item->getName(),'price'=>$item->getPrice());
				$tmppackage=$packageContents[$p];
				$tmppackage[]=$tmpitemArr;
				$tmppackageWeight=$packageWeight[$p]+$item->getWeight();
				$tmppackageQty=$packageQty[$p]+1;
				if(!isset($packageProductQty[$p][$item->getProductId()])) $packageProductQty[$p][$item->getProductId()]=0;
				$tmppackageProductQty=$packageProductQty[$p][$item->getProductId()]+1;
				if(!isset($packageProductWeight[$p][$item->getProductId()])) $packageProductWeight[$p][$item->getProductId()]=0;
				$tmppackageProductWeight=$packageProductWeight[$p][$item->getProductId()]+1;
				//check qty //check weight 
				//echo 'checkPackageWeight'.$this->checkPackageWeight($tmppackage,$tmppackageWeight,$productruledata['package_max_weight'],$productruledata['product_max_weight']).'<br>';
				
			
				
				if($this->checkPackageQty($tmppackageQty,$productruledata['package_max_qty'],$tmppackageProductQty,$productruledata['product_max_qty']) && $this->checkPackageWeight($tmppackageWeight,$productruledata['package_max_weight'],$tmppackageProductWeight,$productruledata['product_max_weight']) && $this->checkPackagePrice($tmppackage,$productruledata['package_max_price'])){
						//put item into package
						if(isset($_GET['debug']))
						echo '<br>-----------<br>PUT Item: '.$item->getName().' ('.$i.') into package '.$p.' Rule: '.json_encode($productruledata);
						$packageContents[$p][]=$tmpitemArr;
						if(!in_array($productruledata['shipping_code'],$packageShippings[$p]))
						$packageShippings[$p][]=$productruledata['shipping_code'];
						$packageQty[$p]+=1;
						$packageWeight[$p]+=$item->getWeight();
						$packageProductQty[$p][$item->getProductId()]+=1;
						if($productruledata['product_cant_mix']>0)$packageCantMix[$p]=1;
						 //remove from abandon box
							unset($abandon_item[$k]);
			 
						
					}else{
						
						if(sizeof($packageContents[$p])==0){
							//emtpy package not fit
							//$tmp_abandon_item[]=array('itemId'=>$item->getId(),'item'=>$item,'productId'=>$item->getProductId(),'productruledatas'=>$productruledatas);
							$tmp_abandon_item[]=$item;
							continue;
						}
					//change package
					//echo $p.' Change package not fit<br>';
					$p++;$packageContents[$p]=array();$packageShippings[$p]=array();
					$packageQty[$p]=$packageWeight[$p]=0;$packageProductQty[$p]=array();$packageProductWeight[$p]=array();
					
						//put item into new package 
						if(isset($_GET['debug']))
						echo '<br>-----------<br>PUT Item: '.$item->getName().' ('.$i.') into NEW package '.$p.' Rule: '.json_encode($productruledata);
						$packageContents[$p][]=$tmpitemArr;
						
						if(!in_array($productruledata['shipping_code'],$packageShippings[$p]))
						$packageShippings[$p][]=$productruledata['shipping_code'];
						$packageQty[$p]+=1;
						$packageWeight[$p]+=$item->getWeight();
						if(!isset($packageProductQty[$p][$item->getProductId()])) $packageProductQty[$p][$item->getProductId()]=0;
						$packageProductQty[$p][$item->getProductId()]+=1;
						if($productruledata['product_cant_mix']>0)$packageCantMix[$p]=1;
						
						 //remove from abandon box
							unset($abandon_item[$k]);
				}
				
			}
			
		
			//$packageContents[]=$item;		
		}
		
	
		}
		
		if(isset($_GET['debug'])){
					die();
			}
	
		if(sizeof($abandon_item)>0){
			//some item can't be delivered
			return $this->CantShippingAllHtml($abandon_item);
		}
		//merge all package data
		$AllPackages=array();
		foreach($packageContents as $p=> $packageContent){
			if(sizeof($packageContent)<=0) continue;
			$allitems=array();
			foreach($packageContent as $package){
				if(!isset($qty[$p][$package['itemId']])) $qty[$p][$package['itemId']]=0;
				$qty[$p][$package['itemId']]++;
				
				$allitems[$package['itemId']]=array('itemId'=>$package['itemId'],'productId'=>$package['productId'],'name'=>$package['name'],'qty'=>$qty[$p][$package['itemId']]);
			}
			$allshpping=array();
			foreach($packageShippings[$p] as $shipping_code){
			//foreach($allPackageShipping as $shippingRule)
				$allshpping[]=$this->getShippingData($ShippingAddress,$allitems,$shipping_code,$packageWeight[$p]);
				
			}
			$AllPackages[$p]=array(
				'title'=>__('Your Package').' '.($p+1),
				'weight'=>$packageWeight[$p].' '.$this->objectManager->create('Storetransform\OrderSplit\Helper\Data')->getConfig('general/locale/weight_unit'),
				'products'=>$allitems,
				'shipping'=>$allshpping
			);
		}
		
		return $this->ShippingAllHtml($AllPackages,$addressId,$countryId,$countryWarning,$countryBan);
	}
	
	
	
	/**
     * @return bool
     */
	public function checkPackageQty($tmppackageQty,$package_max_qty,$tmppackageProductQty,$product_max_qty)
    {
			//empty means no limit
			if($package_max_qty<=0) $package_max_qty=99999999999;
			if($product_max_qty<=0) $product_max_qty=99999999999;
			
			if($tmppackageQty<=$package_max_qty && $tmppackageProductQty<=$product_max_qty)
			return true;
			else
			return false;
	}
	
	/**
     * @return bool
     */
	public function checkPackageWeight($tmppackageWeight,$package_max_weight,$tmppackageProductWeight,$product_max_weight)
    {
			//empty means no limit
			if($package_max_weight<=0) $package_max_weight=99999999999;
			if($product_max_weight<=0) $product_max_weight=99999999999;
			
			if($tmppackageWeight<=$package_max_weight && $tmppackageProductWeight<=$product_max_weight)
			return true;
			else
			return false;
	}
	/**
     * check package total price
     * @return bool
     */
	public function checkPackagePrice($tmppackage,$package_max_price)
    {		
			$totalprice=0;
			if(!is_array($tmppackage)) return true;
			foreach($tmppackage as $item){
				$totalprice+=$item['price'];
			}
		
			if($totalprice>$package_max_price && $package_max_price>0)
			return false;
			else
			return true;
			
	}
	/**
     * caluate package shipping price
     * @return bool
     */
	public function caluatePackageShippingPrice($ShippingAddress,$allitems,$shipping_code,$tmppackageWeight,$shipping_price_formula)
    {
				$shipping_price_formula=str_replace(array(' ',"[weight]"),array('',$tmppackageWeight),$shipping_price_formula);
				if($shipping_price_formula=="") 
				return $this->caluatePackageShippingPriceByShippingCode($ShippingAddress,$shipping_code,$allitems,$tmppackageWeight);
				//echo $tmppackageWeight.' '.$shipping_price_formula.'='.$shipping_price.'<br>';
				$shipping_price=eval("return $shipping_price_formula;");
				return $shipping_price;
			
	}
	
	/**
     * caluate package shipping price use default shipping 
     * @return bool
     */
	public function caluatePackageShippingPriceByShippingCode($ShippingAddress,$shipping_code,$allitems,$tmppackageWeight)
    {
				$OrderSplitShippingModel= $this->objectManager->create('Storetransform\OrderSplit\Model\Shipping');
				$shipping_price=$OrderSplitShippingModel->getShippingPrice($ShippingAddress,$shipping_code,$allitems,$tmppackageWeight);
				return $shipping_price;
			
	}
	
	
	
	/**
     * check if new shipping code is avaliable for exists package
     * @return bool
     */
	public function checkExistsPackageShipping($p,$packageContents,$packageShippings,$shipping_code,$packageQty,$packageWeight,$packageProductQty,$packageProductWeight)
    {
		//if(!stristr($shipping_code,'custom')) return $packageShippings;
		if($p==0) return $packageShippings;
		$resource=$this->resource;
		$core_write = $this->core_write;
		$mt_ordersplit_package_rule = $resource->getTableName('mt_ordersplit_package_rule');
		$mt_ordersplit_package_rule_product = $resource->getTableName('mt_ordersplit_package_rule_product');
		
		for($i=0;$i<=$p;$i++){
			
			$canuse=true;
			//check basic rule
			$ruledatas =$this->getShippingRuleData($shipping_code);
			//echo $i.$shipping_code.'s<br>'. $ruledatas['package_max_qty'].'<'.$packageQty[$i].'b<br>'. $ruledatas['package_max_weight'].'<'.$packageWeight[$i];
			if( ( !isset($ruledatas['package_max_qty']) || ($ruledatas['package_max_qty']>0 && $ruledatas['package_max_qty']<$packageQty[$i]) ) || ($ruledatas['package_max_weight']>0 && $ruledatas['package_max_weight']<$packageWeight[$i])  || ( !$this->checkPackagePrice($packageWeight[$i],$ruledatas['package_max_price']) ) ){ // 
			
				$canuse=false;
				continue;
			}
			
			//echo 'canuse'.$canuse.'<br>';
			//check all items 
			$allitems=array();
			foreach($packageContents[$i] as $package){
				if(!isset($qty[$i][$package['itemId']])) $qty[$i][$package['itemId']]=0;
				$qty[$i][$package['itemId']]++;
				
				if(!isset($weight[$i][$package['itemId']])) $weight[$i][$package['itemId']]=0;
				$weight[$i][$package['itemId']]+=$package['itemWeight'];
				
				$allitems[$package['itemId']]=array('itemId'=>$package['itemId'],'productId'=>$package['productId'],'name'=>$package['name'],'qty'=>$qty[$i][$package['itemId']],'weight'=>$weight[$i][$package['itemId']]);
			}
			foreach($allitems as $tmpitem){
				$productruledatas =$this->getProductRuleData($shipping_code,$tmpitem['productId']);
				if( !isset($productruledatas['product_max_qty']) || ($productruledatas['product_max_qty']>0 && $productruledatas['product_max_qty']<$tmpitem['qty']) ||  ($productruledatas['product_max_weight']>0 && $productruledatas['product_max_weight']<$tmpitem['weight'] )){
				
					$canuse=false;
					break;
				}
			}
			//echo 'canuse'.$canuse.'<br>';
		
			if(!in_array($shipping_code,$packageShippings[$i]) && $canuse){
				if(isset($_GET['debug']))
					echo '<br>-----------<br>AddShipping '.$shipping_code.': to package '.$i;
			$packageShippings[$i][]=$shipping_code;
			}
		}
	//	die();
		return $packageShippings;
			
	}
	
	
	

	/**
     * get Shipping Price data for package with weight
     * @return bool
     */
	public function getShippingData($ShippingAddress,$allitems,$shipping_code,$packageWeight){
		$ruledatas =$this->getShippingRuleData($shipping_code);
		$price=$this->caluatePackageShippingPrice($ShippingAddress,$allitems,$shipping_code,$packageWeight,$ruledatas['shipping_price_formula']);

		foreach($this->getAllShipping() as $shipping){
			if($shipping['code']==$shipping_code){
				$cShipping=$shipping;
				break;
			}
		}
	//	if($price===false) $price=0;
		//echo $shipping['code'].'-'.$price.'<br>';
		if($price!==false) 
		return array(
						'value'=>$shipping_code,
						'name'=>isset($cShipping['name'])?$cShipping['name']:$shipping_code,
						'price'=>$price
					);
	}
	/**
     * error when some product not in package
     * @return bool
     */
	public function CantShippingAllHtml($abandon_item){
		$names=array();
		foreach($abandon_item as $item)
		$names[]=$item->getName();
		return '<script>jQuery("#shipping-method-buttons-container button").attr("disabled",true);</script>
		<div class="errormsg" style="margin:10px;">'.__('Some Products Can Not be delivered, Please remove following products and try again.').'<br>'.implode(',',$names).'</div>';
	}

	
	/**
     * if shipping can not use for any product
     * @return bool
     */
	public function CanNotShipAnyProduct($shipping_code,$productIds){
		$resource=$this->resource;
		$core_write = $this->core_write;
		$mt_ordersplit_package_rule_product = $resource->getTableName('mt_ordersplit_package_rule_product');
    	$selectsql=("SELECT count(*) as total FROM   `$mt_ordersplit_package_rule_product` where shipping_code='$shipping_code' and product_id in (".implode(',',$productIds).")" );
		$total = $core_write->fetchOne($selectsql);
		return $total<=0?true:false;
	}
	
	/**
     * show all packages 
     * @return bool
     */
	public function ShippingAllHtml($AllPackages,$addressId,$countryBan,$countryId=false,$countryWarning=false){
		$packhtml="";$customershippings='';$customershippingsselect="";$customershippings_arr=array();
		//customer slection format 0:flatrate_flatrate-15|
		
		$customershippingsselect.='<div class="seletiontitle">'.__('Select shipping company your perfer').'</div>';
		$customershippingsselect.='<select class="selectperfershipping" onchange="setcustomershipping(this.value,'.(int)$addressId.')">';	
		$allproductids=array();
		foreach($AllPackages as $p=> $package){
		$packhtml.= '<div class="package">
		<div class="head">
			<div class="title">'.$package['title'].'</div>
			<div class="addinfo">'.__('Weight').': '.$package['weight'].'</div>
		</div>
		<div class="products_list">';
		foreach($package['products'] as $product){
				if(!in_array($product['productId'],$allproductids)) $allproductids[]=$product['productId'];
			$packhtml.= '<div class="product">'.$product['name'] . ' x '.$product['qty'].'</div>';
		}
		$packhtml.= '</div>
		
		<div class="shipping_list">';
			$s=0;
			//default set as max number 9999999999999
			$lastprice=9999999999999;$cheapestshipping=$package['shipping'][0]['value'];
		foreach($package['shipping'] as $shipping){
			if($shipping['value']=="") continue;
			if(!in_array($shipping['value'],$customershippings_arr)){
				$customershippings_arr[]=$shipping['value'];
				if(sizeof($customershippings_arr)==1)$cheapestshipping=$shipping['value'];
				
				$customershippingsselect.='<option value="'.$shipping['value'].'" '.(sizeof($customershippings_arr)==1?'selected="selected"':'').'>'.$shipping['name'].'</option>';
				$customershippings.='<div class="customer_shipping '.(sizeof($customershippings_arr)==1?'selected':'').'" onclick="setcustomershipping(\''.$shipping['value'].'\')">'.$shipping['name'].''.(sizeof($customershippings_arr)==1?'<input type="hidden" name="'.($addressId>0?$addressId.'_':'').'package_default_shipping" id="'.($addressId>0?$addressId.'_':'').'package_default_shipping" value="'.$shipping['value'].'">':'').'</div>';
			}
			if($shipping['price']<$lastprice){
				 $lastprice=$shipping['price'];
				 $cheapestshipping=$shipping['value'];
			}
			
			$packhtml.= '<div class="PackageShipping PackageShipping_'.($p).'" onclick="setPackageShipping('.$addressId.')" ><label><input class="packageshipping_radio" type="radio" name="'.($addressId>0?$addressId.'_':'').'shipping_'.($p).'" value="'.$shipping['value'].'" /><span>'.$shipping['name'].'</span> <span class="price">'.$this->priceHelper->currency($shipping['price'], true, false).'</span></label><input type="hidden" name="'.($addressId>0?$addressId.'_':'').'shipping_price_'.($p).'_'.$shipping['value'].'" id="'.($addressId>0?$addressId.'_':'').'shipping_price_'.($p).'_'.$shipping['value'].'" value="'.$shipping['price'].'"></div>';
			
		}
	
		$packhtml.='<input type="hidden" name="'.($addressId>0?$addressId.'_':'').'package_cheapest_shipping_'.($p).'" id="'.($addressId>0?$addressId.'_':'').'package_cheapest_shipping_'.($p).'" value="'.$cheapestshipping.'">';
		$packhtml.='<input type="hidden" name="'.($addressId>0?$addressId.'_':'').'package_number_'.($p).'" id="'.($addressId>0?$addressId.'_':'').'package_number_'.($p).'" class="package_number" value="'.$p.'">';
		$packhtml.= '</div>
		</div>';
		
		}
		
		//add missing shipping
		foreach($this->getAllShipping() as $shipping){
			if(is_array($countryBan) && in_array($shipping['code'],$countryBan))continue;
			//do not add no product used shipping
			if($this->CanNotShipAnyProduct($shipping['code'],$allproductids)) continue;
			
			
			$ruledatas =$this->getShippingRuleData($shipping['code']);
			if(!in_array($shipping['code'],$customershippings_arr) && isset($ruledatas['active']) && $ruledatas['active'] ){
				$customershippingsselect.='<option value="'.$shipping['code'].'" >'.$shipping['name'].'</option>';
				$customershippings.='<div class="customer_shipping '.(sizeof($customershippings_arr)==1?'selected':'').'" onclick="setcustomershipping(\''.$shipping['code'].'\')">'.$shipping['name'].''.(sizeof($customershippings_arr)==1?'<input type="hidden" name="'.($addressId>0?$addressId.'_':'').'package_default_shipping" id="'.($addressId>0?$addressId.'_':'').'package_default_shipping" value="'.$shipping['code'].'">':'').'</div>';
			}
		}
		
		$customershippingsselect.='</select>';
		if(isset($p) && $p)
		$customershippingsselect.='<div class="seletiontitle">'.sprintf(__('Your order will be split into %s packages.'),$p+1).'</div>';
		
		$html='<style>
				.package_list{width:100%;}
				.package_list .package{margin-bottom:10px;border:1px solid #ccc;}
				.package_list .package .head{padding:10px; background:#333;color:#fff;border-bottom:1px solid #ccc; }
				.package_list .package .head .title{  display:inline}
				.package_list .package .head .addinfo{ float:right }
				.package_list .package .products_list{ clear:both;padding:10px;border-bottom:1px solid #ccc; background:#eee}
				.package_list .package .shipping_list{ padding:10px; background:#fff}
				.package_list .package .shipping_list .shipping,.package_list .package .shipping_list .PackageShipping {margin:10px;}
				.package_list .package　.noavliabe{color:#f00;}
				.package_list .package .shipping_list .shipping .price{ text-align:right;font-weight:bold}
				.package_list .seletiontitle {margin:10px 0px;font-weight: 600;}
				.package_list .finalshippingprice{ font-weight:600;font-size:20px;  text-align:left; margin:10px 0px;}
				.package_list .finalshippingprice span{margin-right:20px;}
				.package_list .selectperfershipping {width:400px;max-width:100%;padding:10px;margin:10px 0px 20px;height:50px;} 
				.package_list .customershipping .customer_shipping {display:none;float:left;width:30%;margin:1%; background:#ccc;cursor:pointer;padding:15px;border:1px solid #666;text-align:center;font-weight:bold;}
				.package_list .customershipping .customer_shipping.selected,.package_list .customershipping .customer_shipping:hover{background:#d3f2bf}
				.package_list .allpackages{clear:both}
				.countryWarning{margin: 20px 0px; background: #fff4dc; padding: 10px;}
				.table-checkout-shipping-method{display:none;}
				</style>
				
				<input name="'.($addressId>0?$addressId.'_':'').'mtordersplit_package_data" type="hidden" id="'.($addressId>0?$addressId.'_':'').'mtordersplit_package_data"  value=\''.serialize($AllPackages).'\'>
				<input name="'.($addressId>0?$addressId.'_':'').'mtordersplit_package_shipping" type="hidden" id="'.($addressId>0?$addressId.'_':'').'mtordersplit_package_shipping"  value="">
				'.($addressId>0?'<input name="addressId" type="hidden" value="'.$addressId.'" >':'').'
				<div class="customershipping">';
				if($countryWarning)$html.='<div class="countryWarning">'.__("The shipping you selected is not available for your country.").'</div>';
				$html.=$customershippingsselect.$customershippings;
				$html.='</div>
				<div class="allpackages">
				'.$packhtml.'
				</div>
				<div class="finalshippingprice"><span>'.__("Total Shipping").' ： </span><span class="'.($addressId>0?$addressId.'_':'').'mtordersplit_shipping_price_formatprice">'.'</span><span class="mtordersplit_shipping_price_format" style="display:none">'.$this->priceHelper->currency(0, true, false).'</span><input style="display:none" name="'.($addressId>0?$addressId.'_':'').'mtordersplit_shipping_price" id="'.($addressId>0?$addressId.'_':'').'mtordersplit_shipping_price"  value=""></div>
				';
				$useCheapestShipping=$this->objectManager->create('Storetransform\OrderSplit\Helper\Data')->getConfig('se_ordersplit/ordersplit/default_cheapest');
				$html.='<script>var useCheapestShipping='.($useCheapestShipping?'true':'false').';</script>';
				if($addressId>0)
				$checkoutjs=$this->getMultiCheckoutJs($addressId,$countryId);
				else
				$checkoutjs=$this->getCheckoutJs($countryId);
				
				$html.=$checkoutjs;
				
		return $html;
	}
	
	
	
	/**
     * multi checkout page js
     * @return array
     */
	public function getMultiCheckoutJs($addressId,$countryId){
		$html='<script>
				function setShippingData(addressId){
					jQuery(".actions-toolbar button").attr("disabled",true);
					jQuery.post({
					"url":siteBaseUrl+"/ordersplit/process/index/"+"?action=setShippingData&ShippingPrice="+jQuery("#"+addressId+"_mtordersplit_shipping_price").val(),
					"data":"addressId="+addressId+"&mtordersplit_package_data="+jQuery("#"+addressId+"_mtordersplit_package_data").val()+"&mtordersplit_package_shipping="+jQuery("#"+addressId+"_mtordersplit_package_shipping").val(),
					"success":function(result){jQuery(".actions-toolbar button").attr("disabled",false);}
					});
				}
				
				function setcustomershipping(shipping,addressId){
					var html="<div class=\'loader-03 loading\'></div>";
							jQuery(".ordersplit_container_"+addressId).html(html);
							jQuery.ajax({
								"url":siteBaseUrl+"/ordersplit/process/index/"+"?action=multishowpackage&customershipping="+shipping+"&addressId="+addressId+"&countryId='.($countryId?$countryId:'').'",
								"success":function(result){
											jQuery(".ordersplit_container_"+addressId).html(result);
									 }
							});
							return ;
				}
				
				function setPackageShipping(addressId){
					caluTotalShipping(addressId);
				}
				
				function caluTotalShipping(addressId){
					var totalprice=0;
					var mtordersplit_package_shipping="";
					var cancheckout=true;
					jQuery(".ordersplit_container_"+addressId+" .package_list .package").each(function(){
						var package_number=jQuery(this).find(".package_number").val();
						
						if(jQuery(this).find("input[type=radio]:checked").length<=0){
							jQuery(this).find(".shipping_list").append("<div class=\'noavliabe\'>'.__("No available Shipping. Please try change your shipping.").'</div>");
							cancheckout=false;
							
						}else{
						var selectedShipping=jQuery(this).find("input[type=radio]:checked").val(); 
						var shippingprice=jQuery("#"+addressId+"_shipping_price_"+package_number+"_"+selectedShipping).val();
						mtordersplit_package_shipping=mtordersplit_package_shipping+package_number+":"+selectedShipping+"-"+shippingprice+"|";
						totalprice=parseFloat(totalprice)+parseFloat(shippingprice);
						}
					});
					totalprice=totalprice.toFixed(2);
					jQuery("#"+addressId+"_mtordersplit_shipping_price").val(totalprice);
					jQuery("."+addressId+"_mtordersplit_shipping_price_formatprice").html(jQuery(".mtordersplit_shipping_price_format").html().replace("0.00",totalprice));
					jQuery("#"+addressId+"_mtordersplit_package_shipping").val(mtordersplit_package_shipping);
					
					if(cancheckout)
					setShippingData(addressId);
					else
					jQuery(".actions-toolbar button").attr("disabled",true);
				}	
					
				
					jQuery(".ordersplit_container_'.$addressId.' .package_list .package ").each(function(){
						var package_number=jQuery(this).find(".package_number").val();
						var package_default_shipping=jQuery("#'.$addressId.'_package_default_shipping").val();
						var package_cheapest_shipping=jQuery("#'.$addressId.'_package_cheapest_shipping_"+package_number).val();
						console.log("'.$addressId.'__"+package_number+"__"+package_cheapest_shipping);
						if(useCheapestShipping){
							if(jQuery(this).find("input[name=\''.$addressId.'_shipping_"+package_number+"\'][value=\'"+package_cheapest_shipping+"\']").length>0)
							jQuery(this).find("input[name=\''.$addressId.'_shipping_"+package_number+"\'][value=\'"+package_cheapest_shipping+"\']").attr("checked",true).prop("checked", true);
							else
							jQuery(this).find("input[name=\''.$addressId.'_shipping_"+package_number+"\'][value=\'"+package_default_shipping+"\']").attr("checked",true).prop("checked", true);
						}else{
							if(jQuery(this).find("input[name=\''.$addressId.'_shipping_"+package_number+"\'][value=\'"+package_default_shipping+"\']").length>0)
							jQuery(this).find("input[name=\''.$addressId.'_shipping_"+package_number+"\'][value=\'"+package_default_shipping+"\']").attr("checked",true).prop("checked", true);
							else
							jQuery(this).find("input[name=\''.$addressId.'_shipping_"+package_number+"\'][value=\'"+package_cheapest_shipping+"\']").attr("checked",true).prop("checked", true);
						}
						
				
					});
					
					jQuery(".ordersplit_container_'.$addressId.'").parent().find(".methods-shipping input[value=\'mtordersplit_mtordersplit\']").attr("checked",true).prop("checked", true).trigger("click");
					caluTotalShipping('.$addressId.');
					</script>
				';	
		return $html;
	}
	/**
     * checkout page js
     * @return array
     */
	public function getCheckoutJs($countryId){
		$html='<script>
				function setShippingData(){
					jQuery("#shipping-method-buttons-container button").attr("disabled",true);
					jQuery.post({
					"url":siteBaseUrl+"/ordersplit/process/index/"+"?action=setShippingData&ShippingPrice="+jQuery("#mtordersplit_shipping_price").val(),
					"data":"mtordersplit_package_data="+jQuery("#mtordersplit_package_data").val()+"&mtordersplit_package_shipping="+jQuery("#mtordersplit_package_shipping").val(),
					"success":function(result){jQuery("#shipping-method-buttons-container button").attr("disabled",false);}
					});
				}
				
				function setcustomershipping(shipping,addressId){
					var html="<div class=\'loader-03 loading\'></div>";
							jQuery(".ordersplit_container").html(html);
							jQuery.ajax({
								"url":siteBaseUrl+"/ordersplit/process/index/"+"?action=showpackage&customershipping="+shipping+"&countryId='.($countryId?$countryId:'').'",
								"success":function(result){
											jQuery(".ordersplit_container").html(result);
									 }
							});
							return ;
				}
				
				function setPackageShipping(){
					caluTotalShipping();
				}
				
				function caluTotalShipping(){
					var totalprice=0;
					var mtordersplit_package_shipping="";
					var cancheckout=true;
					jQuery(".package_list .package").each(function(){
						var package_number=jQuery(this).find(".package_number").val();
						
						if(jQuery(this).find("input[type=radio]:checked").length<=0){
							jQuery(this).find(".shipping_list").append("<div class=\'noavliabe\'>'.__("No available Shipping. Please try change your shipping.").'</div>");
							cancheckout=false;
							
						}else{
						var selectedShipping=jQuery(this).find("input[type=radio]:checked").val(); 
						var shippingprice=jQuery("#shipping_price_"+package_number+"_"+selectedShipping).val();
						mtordersplit_package_shipping=mtordersplit_package_shipping+package_number+":"+selectedShipping+"-"+shippingprice+"|";
						totalprice=parseFloat(totalprice)+parseFloat(shippingprice);
						}
						
					});
					totalprice=totalprice.toFixed(2);
					jQuery("#mtordersplit_shipping_price").val(totalprice);
					jQuery(".mtordersplit_shipping_price_formatprice").html(jQuery(".mtordersplit_shipping_price_format").html().replace("0.00",totalprice));
					jQuery("#mtordersplit_package_shipping").val(mtordersplit_package_shipping);
					if(jQuery(".allpackages .package").length==1 && 1==2){
						var selectedsingle=jQuery(".allpackages .package input[type=radio]:checked").val();
						jQuery(".table-checkout-shipping-method input[value=\'"+selectedsingle+"\']").attr("checked",true).prop("checked", true).trigger("click");
					}
					if(cancheckout)
					setShippingData();
					else
					jQuery("#shipping-method-buttons-container button").attr("disabled",true);
					
				}	
					
				
					jQuery(".package_list .package ").each(function(){
						var package_number=jQuery(this).find(".package_number").val();
						var package_default_shipping=jQuery("#package_default_shipping").val();
						var package_cheapest_shipping=jQuery("#package_cheapest_shipping_"+package_number).val();
						if(useCheapestShipping){
							if(jQuery(this).find("input[name=\'shipping_"+package_number+"\'][value=\'"+package_cheapest_shipping+"\']").length>0)
							jQuery(this).find("input[name=\'shipping_"+package_number+"\'][value=\'"+package_cheapest_shipping+"\']").attr("checked",true).prop("checked", true);
							else
							jQuery(this).find("input[name=\'shipping_"+package_number+"\'][value=\'"+package_default_shipping+"\']").attr("checked",true).prop("checked", true);
						}else{
							if(jQuery(this).find("input[name=\'shipping_"+package_number+"\'][value=\'"+package_default_shipping+"\']").length>0)
							jQuery(this).find("input[name=\'shipping_"+package_number+"\'][value=\'"+package_default_shipping+"\']").attr("checked",true).prop("checked", true);
							else
							jQuery(this).find("input[name=\'shipping_"+package_number+"\'][value=\'"+package_cheapest_shipping+"\']").attr("checked",true).prop("checked", true);
						}
				
					});
					jQuery(".table-checkout-shipping-method").hide();
					
					jQuery(".table-checkout-shipping-method input[value=\'mtordersplit_mtordersplit\']").attr("checked",true).prop("checked", true).trigger("click");
					caluTotalShipping();
					</script>
				';	
		return $html;
	}
	
	/**
     * success package
     * @return array
     */
	 public function customerSelectionPackage($selection=""){
		$customerSelectionPackage=array();
			if($selection!=""){
				$customerSelections=explode('|',$selection);
				foreach($customerSelections as $customerSelection){
					$customerSelectionArr=explode(':',$customerSelection);
					if(isset($customerSelectionArr[1])){
						$customerSelectionShippingArr=explode('-',$customerSelectionArr[1]);
						$customerSelectionPackage[$customerSelectionArr[0]]=array('shipping_code'=>$customerSelectionShippingArr[0],'shipping_price'=>$customerSelectionShippingArr[1]);
					}
				}
			}
		return $customerSelectionPackage;
	}
	
	/**
     * convert all package to html
     * @return string
     */
	public function ShippingAllDetailHtml($AllPackagesStr,$selection=""){
		$AllPackages=unserialize($AllPackagesStr);
		$packhtml="";$customershippings='';$customershippings_arr=array();
		//customer slection format 0:flatrate_flatrate-15|
		$customerSelectionPackage=$this->customerSelectionPackage($selection);
		if(!is_array($AllPackages))return "";
		foreach($AllPackages as $p=> $package){
		$packhtml.= '<div class="package">
		<div class="head">
			<div class="title">'.$package['title'].'</div>
			<div class="addinfo">'.__('Weight').': '.$package['weight'].'</div>
		</div>
		<div class="products_list">';
		foreach($package['products'] as $product){
			$packhtml.= '<div class="product">'.$product['name'] . ' x '.$product['qty'].'</div>';
		}
		$packhtml.= '</div>
		
		<div class="shipping_list">';
		
		foreach($package['shipping'] as $shipping){
			if(isset($customerSelectionPackage[$p]) && $customerSelectionPackage[$p]['shipping_code']==$shipping['value']){
				$packhtml.='<div class="PackageShipping"><span>'.$shipping['name'].'</span> <span class="price">'.$this->priceHelper->currency($shipping['price'], true, false).'</span></div>';
			}
			
		}
	
		$packhtml.= '</div>
		</div>';
		
		}
		
		
			
		$html='<style>
			.package_list_detail{width:100%;}
			.package_list_detail .package{margin-bottom:10px;border:1px solid #ccc;}
			.package_list_detail .package .head{padding:10px; background:#333;color:#fff;border-bottom:1px solid #ccc; }
			.package_list_detail .package .head .title{  display:inline}
			.package_list_detail .package .head .addinfo{ float:right }
			.package_list_detail .package .products_list{ clear:both;padding:10px;border-bottom:1px solid #ccc; background:#eee}
			.package_list_detail .package .shipping_list{ padding:10px; background:#fff}
			.package_list_detail .package .shipping_list .shipping{margin:10px;}
			.package_list_detail .package .shipping_list .shipping .price{ text-align:right;font-weight:bold}
			.package_list_detail .finalshippingprice{ font-size:16px; text-align:center;margin:10px;}
			.package_list_detail .showpackagedetail{background: #fff; border: 1px solid #ccc;    padding: 10px;    text-align: center;    cursor: pointer;    color: #000;}
			.package_list_detail .showpackagedetail:hover{background: #000; color:#fff}
			.package_list_detail .allpackages{clear:both;display:none}
			</style>
			
			<div class="showpackagedetail" onclick="toggleallpackages()">'.__("Show All Packages").'</div>
			<div class="allpackages">
			'.$packhtml.'
			</div>
			<script>
			function toggleallpackages(){
				jQuery(".package_list_detail .allpackages").toggle();
			}
			</script>
			';
					
		return $html;
	}
	
	/**
     * create suborders from db
     * @return bool
     */
	public function createSubOrders($incrementId,$order){
	
			
		$directory = $this->objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
		$base  =  $directory->getRoot();
		
		$resource=$this->resource;
		$core_write = $this->core_write;
		$mt_ordersplit_package_orders = $resource->getTableName('mt_ordersplit_package_orders');
    	$selectsql=("SELECT * FROM   `$mt_ordersplit_package_orders` where order_id='$incrementId' and (sub_order_ids is NULL or sub_order_ids='')" );
		$rows = $core_write->fetchAll($selectsql);
		$order_data= isset($rows[0])?$rows[0]:array();
			
		
		
		
		if(!isset($order_data['order_id'])) return false;
		$mtordersplit_package_data=$this->checkoutSession->getData('mtordersplit_package_data');
		$mtordersplit_package_shipping=$this->checkoutSession->getData('mtordersplit_package_shipping');
		if(isset($order_data['mtordersplit_package_data']))$mtordersplit_package_data=$order_data['mtordersplit_package_data'];
		if(isset($order_data['mtordersplit_package_shipping']))$mtordersplit_package_shipping=$order_data['mtordersplit_package_shipping'];
		
		$AllPackages=unserialize($mtordersplit_package_data);
	
		$customerSelectionPackage=$this->customerSelectionPackage($mtordersplit_package_shipping);
		
		$this->checkoutSession->setData('mtordersplit_package_data',"");
		$this->checkoutSession->setData('mtordersplit_package_shipping',"");
		
		//
		
		if(!$order)
		$MainOrder = $this->objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($incrementId);
		else
		$MainOrder = $order;
		
		$customerId=(int)$MainOrder->getCustomerId();
		
		$customerEmail=$MainOrder->getCustomerEmail();
		
		//debug use
		$enable_log=true;
		if($enable_log){
			$dumpFile = @fopen($base .'/var/log/OrderSplit.log', 'a+') ;
			fwrite($dumpFile, "\r\n".date("Y-m-d H:i:s").	 $incrementId.' OrderSplit createSubOrders   - '.$order_data['order_id']."\r\n");
			fwrite($dumpFile,' customerId '.$customerId."\r\n");
			fwrite($dumpFile,' customerEmail '.$customerEmail."\r\n");
		}
		
			//fwrite($dumpFile,' createing '.serialize($package['products'])."\r\n");
			//fwrite($dumpFile,' createing '.serialize($customerSelectionPackage[$p])."\r\n");
		
		
	
		$sub_order_ids=array();
		foreach($AllPackages as $p=> $package){
			foreach($package['shipping'] as $shipment){
				if($shipment['value']==$customerSelectionPackage[$p]['shipping_code']){
				$customerSelectionPackage[$p]['shipping_title']=$shipment['name'];
				break;
				}
			}
			@$string = join(', ', $package['products']);
			@$string1 = join(', ', $customerSelectionPackage[$p]);
			fwrite($dumpFile,' CreateOrderFromPackage '.$customerId.', '.$customerEmail.', '.$string.', customerSelectionPackage: '.$string1."\r\n");
			$order_id=$this->CreateOrderFromPackage($MainOrder,$customerId,$customerEmail,$package['products'],$customerSelectionPackage[$p]);
			if($order_id)
			$sub_order_ids[]=$order_id;
		}
		$selectsql=("update  `$mt_ordersplit_package_orders` set sub_order_ids='".implode(',',$sub_order_ids)."' where order_id='$incrementId' and sub_order_ids is NULL" );
		$core_write->query($selectsql);
		
		return true;
	}
	
	/**
     * create suborder
     * @return bool
     */
	public function CreateOrderFromPackage($MainOrder,$customerId,$customerEmail,$products,$shippingArr){
		
		$storeManager= $this->objectManager->get('Magento\Store\Model\StoreManagerInterface');
		
        //init the store id and website id @todo pass from array
        $store = $storeManager->getStore();
        $websiteId = $storeManager->getStore()->getWebsiteId();
		
		$CustomerRepositoryInterface = $this->objectManager->get('Magento\Customer\Api\CustomerRepositoryInterfaceFactory');
	
		if($customerId>0)
		$customer= $CustomerRepositoryInterface->create()->getById($customerId);
		else{
			if($customerEmail!=''){
				$CustomerFactory = $this->objectManager->get('Magento\Customer\Model\CustomerFactory');
				$customer=$CustomerFactory->create();
				$customer->setWebsiteId($websiteId);
				$customer->loadByEmail($customerEmail);// load customet by email address
				  if(!$customer->getEntityId()){
					//If not avilable then create this customer
					$customer->setWebsiteId($websiteId)
						->setStore($store)
						->setFirstname($MainOrder->getShippingAddress()->getFirstname())
						->setLastname($MainOrder->getShippingAddress()->getLastname())
						->setEmail($customerEmail)
						->setPassword($customerEmail);
					$customer->save();
				}
				if($customer->getEntityId())
				$customer= $CustomerRepositoryInterface->create()->getById($customer->getEntityId());

			}else
			return;
		}
       //init the quote
	   	$cartRepositoryInterface= $this->objectManager->create('Magento\Quote\Api\CartRepositoryInterface');
		$cartManagementInterface=$this->objectManager->create('Magento\Quote\Api\CartManagementInterface');
        $cart_id = $cartManagementInterface->createEmptyCart();
		$directory = $this->objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
		$base  =  $directory->getRoot();
		$dumpFile = @fopen($base .'/var/log/OrderSplit.log', 'a+') ;
		fwrite($dumpFile,' cart_id: '.$cart_id."\r\n");
        $cart =$cartRepositoryInterface->get($cart_id);
        $cart->setStore($store);
        // if you have already buyer id then you can load customer directly
		
	
        $cart->setCurrency();
		
        $cart->assignCustomer($customer); //Assign quote to customer
	
		foreach ($MainOrder->getAllItems() as $item) {
				
				$quoteItemId= $item->getQuoteItemId();
				foreach($products as $pro){
					if($pro['itemId']==$quoteItemId){
					//	$options=($item->getProductOptions());
							$product = $item->getProduct();
							$cart->addProduct(
								$product,
								intval($pro['qty'])
							);
							//$cart->addProduct($product, $options['info_buyRequest']);
						
						break;
					}
				}
		}
		
        //Set Address to quote @todo add section in order data for seperate billing and handle it
        $cart->getBillingAddress()->addData($MainOrder->getBillingAddress()->getData());
        $cart->getShippingAddress()->addData($MainOrder->getShippingAddress()->getData());
        // Collect Rates and Set Shipping & Payment Method
		$shippingRate= $this->objectManager->create('Magento\Quote\Model\Quote\Address\Rate');
      
         $shippingRate->setCode($shippingArr['shipping_code'])->setPrice($shippingArr['shipping_price'])->setMethodTitle($shippingArr['shipping_title']);
		$cart->getShippingAddress()->setCollectShippingRates(false)->collectShippingRates()->addShippingRate($shippingRate)->setShippingMethod($shippingArr['shipping_code'])->setShippingDescription($shippingArr['shipping_code']);
		
		
		$cart->setShippingAmount($shippingArr['shipping_price'])->setBaseShippingAmount($shippingArr['shipping_price']);
        $cart->setPaymentMethod($MainOrder->getPayment()->getMethod()); //payment method
        //@todo insert a variable to affect the invetory
        $cart->setInventoryProcessed(false);
        // Set sales order payment
	
        $cart->getPayment()->importData(['method' => $MainOrder->getPayment()->getMethod()]);
        // Collect total and saeve
        $cart->collectTotals();
        // Submit the quote and create the order
        $cart->save();
        $cart = $cartRepositoryInterface->get($cart->getId());
        $order_id = $cartManagementInterface->placeOrder($cart->getId());
		$subOrder = $this->objectManager->create('\Magento\Sales\Model\Order')->load($order_id);
		$orderIncrementId = $subOrder->getIncrementId();
        return $orderIncrementId;
	}
	
	
	
	/**
     * get customer sub orders
     * @return array
     */
	public function getCustomerSubOrders()
    {
		$customerSession = $this->objectManager->get('Magento\Customer\Model\Session');
		if(!$customerSession->isLoggedIn()) return array();
		$customerId=(int)$customerSession->getCustomer()->getId();
		$resource=$this->resource;
		$core_write = $this->core_write;
		$mt_ordersplit_package_orders = $resource->getTableName('mt_ordersplit_package_orders');
		$sales_order = $resource->getTableName('sales_order');
    	$selectsql=("SELECT spo.order_id,spo.sub_order_ids FROM   `$mt_ordersplit_package_orders` spo,`$sales_order` so where so.increment_id=spo.order_id and so.customer_id='$customerId' and spo.sub_order_ids!='' " );
		$rows = $core_write->fetchAll($selectsql);
		$customerMainOrders=array();
		foreach($rows as $row)
		$customerMainOrders[$row['order_id']]=$row['sub_order_ids'];
		return $customerMainOrders;
	}
	
	/**
     * display sub orders data
     * @return string
     */
	public function showSubOrders($order_id)
    {
		$customerSession = $this->objectManager->get('Magento\Customer\Model\Session');
		if(!$customerSession->isLoggedIn()) return "";
		$customerId=(int)$customerSession->getCustomer()->getId();
		$resource=$this->resource;
		$core_write = $this->core_write;
		$mt_ordersplit_package_orders = $resource->getTableName('mt_ordersplit_package_orders');
		$sales_order = $resource->getTableName('sales_order');
    	$selectsql=("SELECT spo.order_id,spo.sub_order_ids FROM   `$mt_ordersplit_package_orders` spo,`$sales_order` so where so.increment_id=spo.order_id and so.customer_id='$customerId' and spo.order_id='$order_id' spo.sub_order_ids!='' " );
		$rows = $core_write->fetchAll($selectsql);
		$html="";
		foreach($rows as $row){
		$sub_order_ids=explode(',',$row['sub_order_ids']);
			foreach($sub_order_ids as $suborderid)
			$html.=$suborderid;
		}
		return $html;
	}

	
	/**
     * get tracking service /Model/Tracking
     * @return string
     */
	public function getTrackingContent($shipId,$title,$trackNumber)
    {
		$resource=$this->resource;
		$core_write = $this->core_write;
		$sales_shipment = $resource->getTableName('sales_shipment');
		$sales_order = $resource->getTableName('sales_order');
		$mt_ordersplit_package_rule = $resource->getTableName('mt_ordersplit_package_rule');
		$selectsql=("select so.shipping_method from `$sales_shipment` ss,`$sales_order` so where ss.increment_id='$shipId' and ss.order_id=so.entity_id " );
		$shipping_method = $core_write->fetchOne($selectsql);
		
		if($shipping_method=='mtcustomshipping_sfcustomshippin') $shipping_method='mtcustomshipping_mtcustomshipping';
		
    	$selectsql=("select tracking_url from `$mt_ordersplit_package_rule`  where shipping_code='$shipping_method'" );
		$tracking_url = $core_write->fetchOne($selectsql);
		
			$path=dirname(__FILE__).'/Tracking';
			if($handle = opendir($path)){
			  while (false !== ($file = readdir($handle))){
			  	if( $file!='.' && $file!='..'){
					$filename=str_replace('.php','',$file);
					$tmptracking= $this->objectManager->create('Storetransform\OrderSplit\Model\Tracking\\'.$filename);
					if($title==$tmptracking->title){
					 	return $tmptracking->getTrackingHtml($trackNumber);
					 }
				}
			  }
			  closedir($handle);
			}
			
		if(!isset($tracking_url)) return "";
		
		//return $tracking_url.'-'.$title.'='.$trackNumber;
		$html="";
		$tracking= $this->objectManager->create('Storetransform\OrderSplit\Model\Tracking\\'.$tracking_url);
		
		$html=$tracking->getTrackingHtml($trackNumber);
		return $html;
	}
	
	
	
	/**
     * multishipping checkout packing
     * @return string
     */
	public function multipackPackages($customershipping,$addressId)
    {
		$html="";
		
		$checkout=$this->objectManager->get('\Magento\Multishipping\Model\Checkout\Type\Multishipping');
		$Addresses=$checkout->getQuote()->getAllShippingAddresses();
		foreach($Addresses as $address){
			if($addressId != $address->getId()) continue;
			$html=$this->packPackages($customershipping,$address->getAllVisibleItems(),$addressId,$address->getCountryId());
			
		}
		return $html;
		
	
	}
	
	
	
	
}
