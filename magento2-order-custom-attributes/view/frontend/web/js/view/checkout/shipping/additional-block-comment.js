define([
    'jquery',
    'ko',
    'uiComponent'
], function ($, ko, Component) {
    'use strict';

    var show_comment_blockConfig = window.checkoutConfig.show_comment_block;
    return Component.extend({
        defaults: {
            template: 'Ripen_OrderCustomAttributes/checkout/shipping/additional-block-comment'
        },
        canVisibleBlock: show_comment_blockConfig
    });
});
