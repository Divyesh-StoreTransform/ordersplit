define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'jquery/jquery-storageapi'
], function ($, storage) {
    'use strict';

    var mixin = {

        setSelectedShippingAddress: function (data) {
            console.log("setSelectedShippingAddress overriden"+data);
            setSelectedShippingAddress(shippingMethod);
            return true;
        }
    };

    return function (target) { // target == Result that Magento_Ui/.../default returns.
   	 return target.extend(mixin); // new result that all other modules receive 
	};
});