<?php
 
namespace Storetransform\OrderSplit\Controller\Adminhtml\Rule;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context
     * @param \Magento\Framework\View\Result\PageFactory
     * @param \Magento\Framework\Registry
     */
    public function __construct(
        Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        parent::__construct($context);
    }

    /**
     * Check the permission to run it
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
		 return $this->_authorization->isAllowed('Storetransform_OrderSplit::ordersplit_rule');
    }

    public function execute()
    {
 		 if ($this->getRequest()->getParam('ajax')) {
           
            return;
        }
		
        $resultPage = $this->resultPageFactory->create();

      
        $resultPage->setActiveMenu("Storetransform_OrderSplit::ordersplit_rule");
        $resultPage->getConfig()->getTitle()->prepend(__('Order Split Rule'));

        $resultPage->addBreadcrumb(__('Storetransform_OrderSplit'),__('Order Split Rule'));
		
        return $resultPage;
		
    }
	
    
}