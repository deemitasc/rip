define([
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'mage/translate',
    'Ripen_OrderCustomAttributes/js/view/checkout/shipping/additional-block-po-number'
], function(wrapper, quote, $t, poNumberBlock) {
    'use strict';

    var mixin = {
        validateShippingInformation: function () {
            try {
                poNumberBlock().poFieldValidation()
            } catch (message) {
                this.errorValidationMessage($t(message));
                return false;
            }

            // clear error message
            if (quote.shippingMethod() && poNumberBlock().poFieldValidation()) {
                this.errorValidationMessage(false);
            }

            return this._super();
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
