define([
    'jquery',
    'ko',
    'uiComponent'
], function ($, ko, Component) {
    'use strict';

    var show_ship_entire_only_blockConfig = window.checkoutConfig.show_ship_entire_only_block;
    return Component.extend({
        defaults: {
            template: 'Ripen_OrderCustomAttributes/checkout/shipping/additional-block-ship-entire-only'
        },
        canVisibleBlock: show_ship_entire_only_blockConfig
    });
});
