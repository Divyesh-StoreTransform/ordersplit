<?php
 
namespace Storetransform\OrderSplit\Block\Adminhtml\Order;


class View extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    private $_objectManager;

    /**
     * View constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $data
    ) {
        parent::__construct($context, $registry, $adminHelper, $data);
        $this->_objectManager = $objectManager;
    }

    /**
     * @return array|\string[]
     */
    public function getAdditionalInformation()
    {
        return $this->getPackages();
    }

    private function getPackages()
    {
        $resource = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $coreWrite = $resource->getConnection();
		
		$incrementId  = $this->getOrder()->getRealOrderId();
				
		$mt_ordersplit_package_orders = $resource->getTableName('mt_ordersplit_package_orders');
		$selectsql=("SELECT sub_order_ids FROM   `$mt_ordersplit_package_orders`  where order_id='$incrementId'" );
		$sub_order_ids = $coreWrite->fetchOne($selectsql);
        return $sub_order_ids;
    }
}
