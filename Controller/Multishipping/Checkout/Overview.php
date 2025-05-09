<?php
 
namespace Storetransform\OrderSplit\Controller\Multishipping\Checkout;
use Magento\Framework\Exception\LocalizedException;
use Magento\Multishipping\Model\Checkout\Type\Multishipping\State;
use Magento\Payment\Model\Method\AbstractMethod;
use Psr\Log\LoggerInterface;
class Overview extends \Magento\Multishipping\Controller\Checkout\Overview
{
	/**
     * Multishipping checkout place order page
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->_validateMinimumAmount()) {
            return;
        }
		
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$totalsCollector  =  $objectManager->create('\Magento\Quote\Model\Quote\TotalsCollector');
			$quote=$this->_getCheckout()->getQuote();
			$OrderSplitModel = $objectManager->create('Storetransform\OrderSplit\Model\OrderSplit');
			$checkoutSession = $objectManager->create('Magento\Checkout\Model\Session');
		foreach($quote->getAllShippingAddresses() as $address){
			$rate=$address->getShippingRateByCode($address->getShippingMethod());
			if ($rate) {

			$totalsCollector->collectAddressTotals($quote, $address);
			
			$addressId=$address->getId();
			$addressId_prefix=$addressId."_";
			$mtordersplit_package_data=$checkoutSession->getData($addressId_prefix.'mtordersplit_package_data');
			$mtordersplit_package_shipping=$checkoutSession->getData($addressId_prefix.'mtordersplit_package_shipping');
			
			$customerSelectionPackage=$OrderSplitModel->customerSelectionPackage($mtordersplit_package_shipping);
		
			$shippingPrice=(float)$checkoutSession->getData($addressId_prefix.'ShippingPrice');
			$final_title="";
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
				
			
			$rate->setPrice($shippingPrice);
			if($final_title!="")
			$rate->setMethodTitle($final_title);
			
			$quote->setTotalsCollectedFlag(true)->collectTotals();
			$totalsCollector->collectAddressTotals($quote, $address);

			}
		}

        $this->_getState()->setActiveStep(State::STEP_OVERVIEW);

        try {
            $payment = $this->getRequest()->getPost('payment', []);
            if (!empty($payment)) {
                $payment['checks'] = [
                    AbstractMethod::CHECK_USE_FOR_COUNTRY,
                    AbstractMethod::CHECK_USE_FOR_CURRENCY,
                    AbstractMethod::CHECK_ORDER_TOTAL_MIN_MAX,
                    AbstractMethod::CHECK_ZERO_TOTAL,
                ];
                $this->_getCheckout()->setPaymentMethod($payment);
            }
            $this->_getState()->setCompleteStep(State::STEP_BILLING);

            $this->_view->loadLayout();
            $this->_view->renderLayout();
        } catch (LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_redirect('*/*/billing');
        } catch (\Exception $e) {
            $this->_objectManager->get(LoggerInterface::class)->critical($e);
            $this->messageManager->addException($e, __('We cannot open the overview page.'));
            $this->_redirect('*/*/billing');
        }
    }}
