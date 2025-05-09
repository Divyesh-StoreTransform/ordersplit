<?php

namespace Storetransform\OrderSplit\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;


class OrderSplitObserver implements ObserverInterface
 {
    protected $_responseFactory;
    protected $_url;
    protected $customerSession;
    public function __construct(
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->_responseFactory = $responseFactory;
        $this->_url = $url;
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
		// $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		// $storeManager     =  $objectManager->create('\Magento\Store\Model\StoreManagerInterface');
		// $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
		// $core_write = $resource->getConnection();
      	// $customerSession=$objectManager->get('Magento\Customer\Model\Session');
		// $customer_id=$customerSession->getCustomer()->getId();
      	// $checkoutSession=$objectManager->get('Magento\Checkout\Model\Session');
		
		// $mtordersplit_package_data=$checkoutSession->getData('mtordersplit_package_data');
		// $mtordersplit_package_shipping=$checkoutSession->getData('mtordersplit_package_shipping');
		
		
		// $directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
		// $base  =  $directory->getRoot();
		
		
		
		// $order = $observer->getEvent()->getOrder();
		// $incrementId = $order->getIncrementId();
		// $enable_log=false;
		
		
		// if($mtordersplit_package_data!="" && $mtordersplit_package_shipping!=""){
		
		// if($enable_log){
		
		//  $dumpFile = @fopen($base .'/var/log/OrderSplit.log', 'a+') ;
		// 	fwrite($dumpFile, "\r\n".date("Y-m-d H:i:s").	 ' OrderSplit Data   - '."\r\n");
		// 	fwrite($dumpFile,' incrementId '.$incrementId."\r\n");
		// 	fwrite($dumpFile,' mtordersplit_package_data : '.$mtordersplit_package_data.' - '."\r\n");
		// 	fwrite($dumpFile,' mtordersplit_package_shipping : '.$mtordersplit_package_shipping.' - '."\r\n");
		// 	fclose($dumpFile);
		// }
		
		
		// $mt_ordersplit_package_orders = $resource->getTableName('mt_ordersplit_package_orders');
		
		// $sql="insert into `$mt_ordersplit_package_orders` (`order_id`,`mtordersplit_package_data`,`mtordersplit_package_shipping`) values ('$incrementId','$mtordersplit_package_data','$mtordersplit_package_shipping') ";
		// $core_write->query($sql);
		// $OrderSplitModel = $objectManager->create('Storetransform\OrderSplit\Model\OrderSplit');
		// $AllPackages=unserialize($mtordersplit_package_data);
		// if(sizeof($AllPackages)>1){
		// $OrderSplitModel->createSubOrders($incrementId,$order);
		// 	/*$order_status_mainorder='closed';
		//  	 $order = $objManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($incrementId);
        //    $order->setState($order_status_mainorder)->setStatus($order_status_mainorder);
		//    $order->save();*/
		// }
		// $checkoutSession->setData('mtordersplit_package_data',"");
		// $checkoutSession->setData('mtordersplit_package_shipping',"");
			
			
		
		// }
	
	}
        
     
	
}