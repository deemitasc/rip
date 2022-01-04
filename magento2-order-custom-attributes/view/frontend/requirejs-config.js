var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/model/shipping-save-processor/payload-extender': {
                'Ripen_OrderCustomAttributes/js/model/shipping-save-payload-extender': true
            },
            'Magento_Checkout/js/view/shipping': {
                'Ripen_OrderCustomAttributes/js/view/shipping-mixin': true
            }
        }
    }
};
