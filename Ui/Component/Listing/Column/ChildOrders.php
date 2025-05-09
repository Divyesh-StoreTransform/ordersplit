<?php

namespace Storetransform\OrderSplit\Ui\Component\Listing\Column;

use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Framework\Api\SearchCriteriaBuilder;

class ChildOrders extends Column
{
    protected $_orderRepository;
    protected $_searchCriteria;

    public function __construct(ContextInterface $context, UiComponentFactory $uiComponentFactory, OrderRepositoryInterface $orderRepository, SearchCriteriaBuilder $criteria, array $components = [], array $data = [])
    {
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteria  = $criteria;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
		$core_write = $resource->getConnection();
		$url = $objectManager->get('\Magento\Framework\UrlInterface'); 
		$sales_order = $resource->getTableName('sales_order');
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {

                $incrementId  = ($item["increment_id"]);
				
				$mt_ordersplit_package_orders = $resource->getTableName('mt_ordersplit_package_orders');
				$selectsql=("SELECT sub_order_ids FROM   `$mt_ordersplit_package_orders`  where order_id='$incrementId'" );
				$sub_order_ids="";
				$rows = $core_write->fetchAll($selectsql);
				foreach($rows as $row) $sub_order_ids=$row['sub_order_ids'];
                $childorderhtml="";
                if(!empty($sub_order_ids)){
                    foreach(explode(',',$sub_order_ids) as $corderid){
                        $selectsql=("SELECT entity_id FROM   `$sales_order` where increment_id='$corderid' " );
                        $orderrealid = $core_write->fetchOne($selectsql);
                        if($orderrealid!="")
                        $childorderhtml.=' <a href="'.$url->getUrl('sales/order/view/order_id/'.$orderrealid).'" target="_blank">'.$corderid.'</a> ,';
                    }
                }
                // $this->getData('name') returns the name of the column so in this case it would return export_status
                $item[$this->getData('name')] = trim($childorderhtml,',');
            }
        }

        return $dataSource;
    }
}