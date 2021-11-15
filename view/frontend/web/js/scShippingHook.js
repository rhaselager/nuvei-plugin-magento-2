define([
    'jquery',
	'Magento_Customer/js/model/customer',
	'jquery/jquery.cookie'
], function ($, customer) {
    'use strict';
    
    $(document).ready(function () {
		if(!customer.isLoggedIn()) {
			$(document).on('change', "#customer-email", function () {
				var date	= new Date();
				var minutes	= 15;
				
				date.setTime(date.getTime() + (minutes * 60 * 1000));
				$.cookie('guestSippingMail', $(this).val(), {expires: date});
			});
		}
    });
	// I have no idea why we need this, but without it the code won't work ;)
	return function (targetModule) {
        targetModule.crazyPropertyAddedHere = 'yes';
        return targetModule;
    };
});