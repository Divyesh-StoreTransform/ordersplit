define(
    [
        'jquery',
        'underscore',
        'Magento_Ui/js/form/form',
        'ko',
        'Magento_Customer/js/model/customer',
        'Magento_Customer/js/model/address-list',
        'Magento_Checkout/js/model/address-converter',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/create-shipping-address',
        'Magento_Checkout/js/action/select-shipping-address',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-address/form-popup-state',
        'Magento_Checkout/js/model/shipping-service',
        'Magento_Checkout/js/action/select-shipping-method',
        'Magento_Checkout/js/model/shipping-rate-registry',
        'Magento_Checkout/js/action/set-shipping-information',
        'Magento_Checkout/js/model/step-navigator',
        'Magento_Ui/js/modal/modal',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'Magento_Checkout/js/checkout-data',
        'uiRegistry',
        'mage/translate',
        'Magento_Checkout/js/model/shipping-rate-service'
    ],function (
        $,
        _,
        Component,
        ko,
        customer,
        addressList,
        addressConverter,
        quote,
        createShippingAddress,
        selectShippingAddress,
        shippingRatesValidator,
        formPopUpState,
        shippingService,
        selectShippingMethodAction,
        rateRegistry,
        setShippingInformationAction,
        stepNavigator,
        modal,
        checkoutDataResolver,
        checkoutData,
        registry,
        $t) {
    'use strict';

    var mixin = {
		
		loadordersplit: function(){
			if(!useordersplit) return;
			var html='<div class="loader-03 loading"></div>';
			var address = quote.shippingAddress();
			jQuery('.ordersplit_container').html(html);
			jQuery.ajax({
				'url':siteBaseUrl+'/ordersplit/process/index/'+'?action=showpackage&countryId='+address.countryId,
				'success':function(result){
							jQuery('.ordersplit_container').html(result);
					 }
			});
			return ;
		},
		setShippingInformation: function(){
			 console.log("setShippingInformation overriden");
			 this._super();
			 
		},
		selectShippingMethod: function(){
			 console.log("selectShippingMethod overriden");
			 this._super();
			 
		},
		saveNewAddress: function(){
			 console.log("saveNewAddress overriden");
			 this._super();
			 this.loadordersplit();
		}
    };

    return function (target) { // target == Result that Magento_Ui/.../default returns.
    return target.extend(mixin); // new result that all other modules receive 
};
});