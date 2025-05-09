<?php

namespace Storetransform\OrderSplit\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;


class OrderSplitSuccessObserver implements ObserverInterface
 {
    protected $_responseFactory;
    protected $_url;
    protected $customerSession;
	protected $_orderRepositoryInterface;
    public function __construct(
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url,
		\Magento\Sales\Api\OrderRepositoryInterface $orderRepositoryInterface
    ) {
        $this->_responseFactory = $responseFactory;
        $this->_url = $url;
		$this->_orderRepositoryInterface = $orderRepositoryInterface;
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$storeManager     =  $objectManager->create('\Magento\Store\Model\StoreManagerInterface');
		$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
		$core_write = $resource->getConnection();
      	$customerSession=$objectManager->get('Magento\Customer\Model\Session');
		$customer_id=$customerSession->getCustomer()->getId();
      	$checkoutSession=$objectManager->get('Magento\Checkout\Model\Session');
		

		
		$directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
		$base  =  $directory->getRoot();
		
		
		$orderids = $observer->getEvent()->getOrderIds();
		/*
			$dumpFile = @fopen($base .'/var/log/OrderSplit.log', 'a+') ;
			fwrite($dumpFile, "\r\n".date("Y-m-d H:i:s").	 ' OrderSplit Success   - '."\r\n");
			foreach($orderids as $orderid){
			   fwrite($dumpFile,' orderId '.$orderid."\r\n");
			}
			*/
		$order = $objectManager->create('\Magento\Sales\Model\Order')->load($orderids[0]);
		$incrementId = $order->getIncrementId();
		$enable_log=true;
		
		
			//fwrite($dumpFile,' incrementId '.$incrementId."\r\n");
	
		
		
		$mt_ordersplit_package_orders = $resource->getTableName('mt_ordersplit_package_orders');
		
		$selectsql=("SELECT sub_order_ids FROM   `$mt_ordersplit_package_orders`  where order_id='$incrementId'" );
		$sub_order_ids=$core_write->fetchOne($selectsql);
		if($sub_order_ids !== null && sizeof(explode(',',$sub_order_ids))>1){
			$order_status_mainorder='closed';
		 	// $order = $objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($incrementId);
          	 $order->setState($order_status_mainorder)->setStatus($order_status_mainorder);
		   	//fwrite($dumpFile,' status '.$order_status_mainorder.$sub_order_ids."\r\n");
		   	$order->save();
			//fwrite($dumpFile,' saved '."\r\n");
		}
		
		//	fclose($dumpFile);
		
		
	
	
	}
        
     
	
}