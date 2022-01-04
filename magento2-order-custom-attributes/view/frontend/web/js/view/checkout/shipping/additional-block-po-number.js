define([
    'jquery',
    'ko',
    'uiComponent'
], function ($, ko, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Ripen_OrderCustomAttributes/checkout/shipping/additional-block-po-number'
        },
        canVisibleBlock: window.checkoutConfig.show_po_number_block,
        isPoNumberRequired: window.checkoutConfig.po_number_required,

        poFieldValidation: function () {
            if (this.canVisibleBlock) {
                if (this.isPoNumberRequired && ! window.checkoutConfig.quoteData.po_number) {
                    throw 'Please enter your PO Number.';
                }
                if (/['"`~]/.test(window.checkoutConfig.quoteData.po_number)) {
                    throw 'PO Number cannot contain an apostrophe, quotation mark, backtick or tilde.';
                }
            }
            return true;
        }
    });
});
