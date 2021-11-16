var config = {
    paths: {
        'jquery.redirect': "Nuvei_Checkout/js/jquery.redirect"
    },
    shim: {
        'jquery.redirect': {
            deps: ['jquery']
        },
    },
	'config': {
		'mixins': {
			'Magento_Checkout/js/action/set-shipping-information': {
                'Nuvei_Checkout/js/scShippingHook': true
            }
		}
	}
};