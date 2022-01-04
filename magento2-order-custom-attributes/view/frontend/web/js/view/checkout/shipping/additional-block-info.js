define([
    'jquery',
    'ko',
    'uiComponent'
], function ($, ko, Component) {
    'use strict';

    var show_info_blockConfig = window.checkoutConfig.show_info_block;
    return Component.extend({
        defaults: {
            template: 'Ripen_OrderCustomAttributes/checkout/shipping/additional-block-info'
        },
        canVisibleBlock: show_info_blockConfig
    });
});
