<?php
 
namespace Storetransform\OrderSplit\Controller\Process;

class Index extends \Magento\Framework\App\Action\Action 
{
	protected $storeManager;
	protected $customerFactory;
	protected $customerRepository;
	protected $searchCriteriaBuilder;
	protected $filterBuilder;
	protected $checkoutSession;
	protected $messageManager;
	protected $OrderSplitModel;
	protected $objectManager;
	
    public function __construct(
        \Magento\Framework\App\Action\Context $context
    ) {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$this->objectManager =$objectManager;
		$this->storeManager     =  $objectManager->create('\Magento\Store\Model\StoreManagerInterface');
        $this->customerFactory  =  $objectManager->create('\Magento\Customer\Model\CustomerFactory');
        $this->customerRepository  = $objectManager->create('\Magento\Customer\Api\CustomerRepositoryInterface');
        $this->searchCriteriaBuilder  =$objectManager->create('\Magento\Framework\Api\SearchCriteriaBuilder');
        $this->filterBuilder  = $objectManager->create('\Magento\Framework\Api\FilterBuilder');
		$this->checkoutSession = $objectManager->create('Magento\Checkout\Model\Session');
		$this->messageManager = $objectManager->create('\Magento\Framework\Message\ManagerInterface');
		$this->OrderSplitModel = $objectManager->create('Storetransform\OrderSplit\Model\OrderSplit');
		$this->_resultPageFactory = $objectManager->create('\Magento\Framework\View\Result\PageFactory');
        parent::__construct($context);
    }

  
    /**
     * load adminhtml section
     */
    public function execute()
    {
		$active=$this->objectManager->create('Storetransform\OrderSplit\Helper\Data')->getConfig('se_ordersplit/ordersplit/active');
		
		 $postData = $this->getRequest()->getPost();
			if(is_object($postData))$postData=$postData->toArray();
			$action = $this->getRequest()->getParam('action') ;	
			if($action=='loadrule'){
				$this->getResponse()->setBody($this->loadrulehtml());
				return ;
			}
			if($action=='saverule'){
				$this->getResponse()->setBody($this->saverulehtml());
				return ;
			}
			if($action=='showpackage'){
				if(!$active) return;
				$this->getResponse()->setBody($this->showpackagehtml());
				return ;
			}
			if($action=='multishowpackage'){
				if(!$active) return;
				$this->getResponse()->setBody($this->multishowpackagehtml());
				return ;
			}
			
			if($action=='showpackagedetail'){
				if(!$active) return;
				$this->getResponse()->setBody($this->showpackagedetail());
				return ;
			}
			if($action=='showmultipackagedetail'){
				if(!$active) return;
				$this->getResponse()->setBody($this->showmultipackagedetail());
				return ;
			}
			if($action=='setShippingData'){
				$this->getResponse()->setBody($this->setShippingData());
				return ;
			}
		
			
			if($action=='test'){
				$this->OrderSplitModel->test($_GET['test']);
				return ;
			}
	}
	/**
     * load rule html section
     */
	public function loadrulehtml(){
	 		
				$shipping = $this->getRequest()->getParam('shipping');	
				$shipping_title = $this->getRequest()->getParam('shipping_title');	
				$RuleData=$this->OrderSplitModel->getShippingRuleData($shipping);
				
				$html='<form id="form_rule_'.$shipping.'" class="shipping_rule"><h2>'.$shipping_title.'</h2>';
				
				$html.='<div class="field"><span>'.__('Active').'</span><select name="active"><option value="1" '.((isset($RuleData['active'])&&$RuleData['active'])?'selected="selected"':'').'>'.__('Enable').'</option><option value="0" '.((!isset($RuleData['active'])||!$RuleData['active'])?'selected="selected"':'').'>'.__('Disable').'</option></select></div>';
				$html.='<div class="field"><span>'.__('Priority').'</span><input name="priority" value="'.(isset($RuleData['priority'])?$RuleData['priority']:'').'"></div>';
				$html.='<div class="field"><span>'.__('Package Max Weight').'</span><input name="package_max_weight" value="'.(isset($RuleData['package_max_weight'])?$RuleData['package_max_weight']:'').'"></div>';
				$html.='<div class="field"><span>'.__('Package Max Price').'</span><input name="package_max_price" value="'.(isset($RuleData['package_max_price'])?$RuleData['package_max_price']:'').'"></div>';
				$html.='<div class="field"><span>'.__('Package Max Quantity').'</span><input name="package_max_qty" value="'.(isset($RuleData['package_max_qty'])?$RuleData['package_max_qty']:'').'"></div>';
				$html.='<div class="field"><span>'.__('Shipping Price Formula').'</span><input name="shipping_price_formula" value="'.(isset($RuleData['shipping_price_formula'])?$RuleData['shipping_price_formula']:'').'"><div class="desc">'.__('e.g. 2.5*[weight]+10').'</div></div>';
				
			$path=str_replace('Controller'.DIRECTORY_SEPARATOR.'Process','Model'.DIRECTORY_SEPARATOR.'Tracking',dirname(__FILE__));

			$options='';
			if($handle = opendir($path)){
			  while (false !== ($file = readdir($handle))){
			  	if( $file!='.' && $file!='..'){
					$filename=str_replace('.php','',$file);
					$tracking= $this->objectManager->create('Storetransform\OrderSplit\Model\Tracking\\'.$filename);
					 
					 $options.='<option value="'.$filename.'" '.((isset($RuleData['tracking_url']) && $filename==$RuleData['tracking_url'])?'selected':'').'>'.$tracking->title.'</option>';
				}
			  }
			  closedir($handle);
			}
			//<input name="tracking_url" value="'.(isset($RuleData['tracking_url'])?$RuleData['tracking_url']:'').'"><div class="desc">'.__('e.g. http://www.xxx.com/?orderNo=[shipNumber]').'
				$html.='<div class="field furl"><span>'.__('Tracking Service').'</span>
				<select name="tracking_url"><option value="">'.__("   ").'</option>'.$options.'</select>
				</div></div>';
				$html.='<div class="field products">
				
				<div class="product_line heading">
				<div class="productfield allow"><input type="checkbox" onclick="checkall(this.checked)"></div>
				<div class="productfield name">'.__('Product Name').'</div>
				<div class="productfield sku">'.__('SKU').'</div>
				<div class="productfield mix">'.__('Can NOT Mix').'</div>
				<div class="productfield qty">'.__('MAX Qty').'</div>
				<div class="productfield weight">'.__('MAX Weight').'</div>
				</div>
				<div class="product_line heading filterheading">
				<div class="productfield allow">'.__('Filters').'</div>
				<div class="productfield name"><input id="product_search_name" onkeydown="return searchbutton(event);" title="'.__('Product Name').'"></div>
				<div class="productfield sku"><input id="product_search_sku" onkeydown="return searchbutton(event);" title="'.__('SKU').'"></div>
				<div class="productfield mix"><button onclick="return searchkeyword();">'.__('Apply').'</button></div>
				<div class="productfield qty"><button onclick="return resetfilter();">'.__('Reset').'</button></div>
				<div class="productfield weight"></div>
				</div>
				<div class="product_content">
				';
				
				
				foreach($this->OrderSplitModel->getAllProducts() as $pid=>$pName){
				$ProductRuleData=$this->OrderSplitModel->getProductRuleData($shipping,$pid);
				$html.='<div class="product_line_'.$pid.' '.(isset($ProductRuleData['product_id'])?'selected':'').' product_line">
				<span class="pid" style="display:none">'.$pid.'</span>
				<div class="productfield allow"><input name="product_allow['.$pid.']" '.(isset($ProductRuleData['product_id'])?'checked="checked"':'').' type="checkbox" id="product_allow_'.$pid.'" onclick="seletedproduct('.$pid.',this.checked)" value="1" ></div>
				<div class="productfield name"><label for="product_allow_'.$pid.'">#'.$pid.' - <span>'.$pName['name'].'</span></label></div>
				<div class="productfield sku"><label for="product_allow_'.$pid.'"><span>'.$pName['sku'].'</span></label></div>
				<div class="productfield mix"><input name="product_cant_mix['.$pid.']" '.((isset($ProductRuleData['product_cant_mix']) && $ProductRuleData['product_cant_mix'])?'checked="checked"':'').' type="checkbox" value="1" title="" class="relate" '.(isset($ProductRuleData['product_id'])?'"':'disabled="disabled"').' ></div>
				<div class="productfield qty"><input name="product_max_qty['.$pid.']"  placeholder="" class="relate"  value="'.(isset($ProductRuleData['product_max_qty'])?$ProductRuleData['product_max_qty']:'').'" '.(isset($ProductRuleData['product_id'])?'"':'disabled="disabled"').'></div>
				<div class="productfield weight"><input name="product_max_weight['.$pid.']" placeholder="" class="relate"  value="'.(isset($ProductRuleData['product_max_weight'])?$ProductRuleData['product_max_weight']:'').'" '.(isset($ProductRuleData['product_id'])?'"':'disabled="disabled"').'></div>
				</div>';
				}
				
				$html.='</div></div>
				 <div class="btn"><button class="action-default scalable primary" onclick="return saverule(\''.$shipping.'\')">'.__('Save').'</button></div>
				</form>
				
				';
				return $html;
	}
	
	/**
     * save rule html section
     */
	public function saverulehtml(){
				$shipping = $this->getRequest()->getParam('shipping');	
				$postData = $this->getRequest()->getPost();if(is_object($postData))$postData=$postData->toArray();
				return $this->OrderSplitModel->saveRule($shipping,$postData);
				
	}
	/**
     * show package html section in checkout step
     */
	public function showpackagehtml(){
		$customershipping = $this->getRequest()->getParam('customershipping');	
		$packages='<div class="package_list">'. $this->OrderSplitModel->packPackages($customershipping).'</div>';

		return $packages;
	}
	/**
     * show package html section in multishippin checkout step
     */
	public function multishowpackagehtml(){
		$customershipping = $this->getRequest()->getParam('customershipping');	
		$addressId = $this->getRequest()->getParam('addressId');	
		$packages='<div class="package_list">'. $this->OrderSplitModel->multipackPackages($customershipping,$addressId).'</div>';

		return $packages;
	}
	/**
     * show package detail in order view section
     */
	public function showpackagedetail(){
		$AllPackages=$this->checkoutSession->getData('mtordersplit_package_data');
		$mtordersplit_package_shipping=$this->checkoutSession->getData('mtordersplit_package_shipping');
		return '<div class="package_list_detail">'. $this->OrderSplitModel->ShippingAllDetailHtml($AllPackages,$mtordersplit_package_shipping).'</div>';
	}
	/**
     * show package detail in multishipping order view 
     */
	public function showmultipackagedetail(){
		$addressId = $this->getRequest()->getParam('addressId');	
		$AllPackages=$this->checkoutSession->getData($addressId.'_mtordersplit_package_data');
		$mtordersplit_package_shipping=$this->checkoutSession->getData($addressId.'_mtordersplit_package_shipping');
		return '<div class="package_list_detail package_list_detail_'.$addressId.'">'. $this->OrderSplitModel->ShippingAllDetailHtml($AllPackages,$mtordersplit_package_shipping).'</div>';
	}
	/**
     * set Shipping Data
     */
	public function setShippingData(){
		 $postData = $this->getRequest()->getPost();
		if(is_object($postData))$postData=$postData->toArray();
		$ShippingPrice = $this->getRequest()->getParam('ShippingPrice');
	
		$addressId_prefix="";
		if(isset($postData['addressId'])){
			//multishipping
			if($postData['addressId']>0){
			$addressId_prefix=$postData['addressId']."_";
			/*$checkout=$this->objectManager->get('\Magento\Multishipping\Model\Checkout\Type\Multishipping');
			$Addresses=$checkout->getQuote()->getAllShippingAddresses();
				foreach($Addresses as $address){
					if($postData['addressId'] != $address->getId()){
						$address->setShippingAmount($ShippingPrice);
						continue;
					}
				}*/
			}
		}
		
			$this->checkoutSession->setData($addressId_prefix.'ShippingPrice',$ShippingPrice);
			if(isset($postData['mtordersplit_package_data']))
			$this->checkoutSession->setData($addressId_prefix.'mtordersplit_package_data',$postData['mtordersplit_package_data']);
			if(isset($postData['mtordersplit_package_shipping']))
			$this->checkoutSession->setData($addressId_prefix.'mtordersplit_package_shipping',$postData['mtordersplit_package_shipping']);
		
		return $this->checkoutSession->getData($addressId_prefix.'ShippingPrice');
	}
	
	
	/**
     *  redirect page
     */
	public function redirect($url){
		$resultRedirect = $this->resultRedirectFactory->create();
		$resultRedirect->setPath($url);
		return $resultRedirect;
	}
	

   
}
 
 