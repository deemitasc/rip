define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
],
function (Component, rendererList) {
    'use strict';

    rendererList.push(
        {
            type: 'ripen_vantivintegratedpayments',
            component: 'Ripen_VantivIntegratedPayments/js/view/payment/method-renderer/vantivintegratedpayments'
        }
    );

    return Component.extend({});
});
