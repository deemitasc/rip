define([
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Payment/js/model/credit-card-validation/validator',
        'Ripen_VantivIntegratedPayments/js/lib/cleave'
    ],
    function ($, Component, validator, Cleave) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Ripen_VantivIntegratedPayments/payment/vantivintegratedpayments'
            },

            initializeMasks: function () {
                this.cleave = new Cleave('.cleave-cc-number', {
                    creditCard: true
                });

                this.cleave = new Cleave('.cleave-cvv', {
                    blocks: [4],
                    numericOnly: true
                });
            },

            context: function() {
                return this;
            },

            getCode: function() {
                return 'ripen_vantivintegratedpayments';
            },

            isActive: function() {
                return true;
            },

            validate: function () {
                var form = '#ripen_vantivintegratedpayments-form';

                return $(form).validation() && $(form).validation('isValid');
            }
        });
    }
);
