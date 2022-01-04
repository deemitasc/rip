define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Customer/js/model/customer',
    ],
    function (Component, customer) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Ripen_InvoicePayment/payment/invoicepayment'
            },

            initialize: function () {
                this._super();
                this.selectPaymentMethod();
            },
        });
    }
);
