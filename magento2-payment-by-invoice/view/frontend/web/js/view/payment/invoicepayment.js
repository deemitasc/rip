define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'invoicepayment',
                component: 'Ripen_InvoicePayment/js/view/payment/method-renderer/invoicepayment-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
