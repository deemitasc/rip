
define([
    "jquery",
    "jquery/ui",
    "Magento_Ui/js/modal/confirm"
], function($, ui, confirmation) {
    "use strict";

    $.widget('mage.deleteCart', {
        options: {
            submitButton: '.delete'
        },

        /**
         * Initialize store credit events
         * @private
         */
        _create: function() {
            var form = this.element;
            var submitBtn = $(form).find(this.options.submitButton);
            submitBtn.on('click', function(e) {
                confirmation({
                    content: 'Are you sure you would like to remove this shopping cart.',
                    actions: {
                        confirm: function() {
                            form.submit();
                        },
                        cancel: function(){},
                        always: function(){}
                    }
                });
            });
        }
    });

    return $.mage.deleteCart;
});
