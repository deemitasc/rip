
define([
    'uiComponent',
    'ko',
    'jquery',
    'jquery/ui',
    'Magento_Customer/js/customer-data'
], function(Component, ko, $, ui, customerData) {
    "use strict";

    return Component.extend({
        saveCartLinkUrl: window.saveCartLinkConfig.saveCartLinkUrl,
        defaults: {
            template: 'Vekeryk_SaveCart/save-cart-link'
        },

        isVisible: function () {
            var cart = customerData.get('cart');
            return ko.observable(Array.isArray(cart().items) && cart().items.length > 0);
        }
    });
});
