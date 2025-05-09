var config = {
    map: {
        '*': {
          'Magento_Checkout/template/shipping-address/shipping-method-list.html': 'Storetransform_OrderSplit/template/shipping-address/shipping-method-list.html'
        },
	
  },
 config: {
	mixins: {
                'Magento_Checkout/js/view/shipping': {
                    'Storetransform_OrderSplit/js/view/shipping': true
                },
				'Magento_Checkout/js/view/shipping-address/address-renderer/default': {
                    'Storetransform_OrderSplit/js/view/shipping-address/address-renderer/default': true
                }
            }
 }
};
