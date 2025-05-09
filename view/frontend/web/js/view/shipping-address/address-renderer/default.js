define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/shipping-address/form-popup-state',
    'Magento_Checkout/js/checkout-data',
    'Magento_Customer/js/customer-data',
    'Storetransform_OrderSplit/js/view/shipping'
], function ($, ko, Component, selectShippingAddressAction, quote, formPopUpState, checkoutData, customerData,sfshipping) {
    'use strict';

   var mixin = {
		 selectAddress: function () {
           // if (!this.isSelected()) {
                this._super();
				console.log("reload");
                this.loadordersplit();
           // }
        },
			
		loadordersplit: function(){
			if(!useordersplit) return;
			var address = quote.shippingAddress();
			var html='<div class="loader-03 loading"></div>';
			jQuery('.ordersplit_container').html(html);
			jQuery.ajax({
				'url':siteBaseUrl+'/ordersplit/process/index/'+'?action=showpackage&countryId='+address.countryId,
				'success':function(result){
							jQuery('.ordersplit_container').html(result);
					 }
			});
			return ;
		},
    };

    return function (target) { // target == Result that Magento_Ui/.../default returns.
    return target.extend(mixin); // new result that all other modules receive 
};
});
