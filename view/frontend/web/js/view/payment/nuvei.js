/**
 * Nuvei Payments js component.
 *
 * @category Nuvei
 * @package  Nuvei_Payments
 */
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';
        
        rendererList.push({
			type: 'nuvei',
			component: 'Nuvei_Payments/js/view/payment/method-renderer/nuvei'
		});

        return Component.extend({});
    }
);