define([
    'underscore',
    'Magento_Ui/js/grid/columns/select'
], function (_, Column) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'Nuvei_Checkout/ui/grid/cells/status_content'
        },
        
		orderHasSubscr: function(row) {
			return row.has_nuvei_subscr;
		}
    });
});