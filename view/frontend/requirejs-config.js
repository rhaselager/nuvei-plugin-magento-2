var config = {
    paths: {
        'jquery.redirect': "Nuvei_Payments/js/jquery.redirect"
    },
    shim: {
        'jquery.redirect': {
            deps: ['jquery']
        },
    },
	'config': {
		'mixins': {
			'Magento_Checkout/js/action/set-shipping-information': {
                'Nuvei_Payments/js/scShippingHook': true
            }
		}
	}
};