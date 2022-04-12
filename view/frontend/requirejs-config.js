var config = {
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
	},
    urlArgs: "bust=" + (new Date()).getTime() // Disable require js cache
};