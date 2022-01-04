
define([
    "jquery",
    "jquery/ui",
    "Magento_Ui/js/modal/confirm"
], function($, ui, confirmation) {
    "use strict";

    $.widget('mage.setCart', {
        options: {
            bindSubmit: true,
            processStart: null,
            processStop: null,
            minicartSelector: '[data-block="minicart"]',
            submitButton: '.add-to-cart',
            useAjax: false
        },

        /**
         * Initialize store credit events
         * @private
         */
        _create: function() {
            if (this.options.bindSubmit) {
                this._bindSubmit();
            }
        },

        _bindSubmit: function() {
            var self = this;
            var form = this.element;
            var submitBtn = form.find(this.options.submitButton);
            submitBtn.on('click', function(e) {
                confirmation({
                    content: 'The saved cart contents will be transferred to the main cart. This action will clear the existing main cart items, if any, and replace them with the saved cart items.',
                    actions: {
                        confirm: function(){
                            self.submitForm(form);
                        },
                        cancel: function(){},
                        always: function(){}
                    }
                });
            });
        },

        submitForm: function (form) {
            var self = this;
            if (self.options.useAjax) {
                this.ajaxSubmit(form);
            } else {
                form.submit();
            }
        },

        isLoaderEnabled: function() {
            return this.options.processStart && this.options.processStop;
        },

        ajaxSubmit: function(form) {
            var self = this;
            $(self.options.minicartSelector).trigger('contentLoading');
            //self.disableAddToCartButton(form);

            $.ajax({
                url: form.attr('action'),
                data: form.serialize(),
                type: 'post',
                dataType: 'json',
                beforeSend: function() {
                    if (self.isLoaderEnabled()) {
                        $('body').trigger(self.options.processStart);
                    }
                },
                success: function(res) {
                    if (self.isLoaderEnabled()) {
                        $('body').trigger(self.options.processStop);
                    }

                    /*if (res.backUrl) {
                        window.location = res.backUrl;
                        return;
                    }
                    if (res.messages) {
                        $(self.options.messagesSelector).html(res.messages);
                    }

                    if (res.product && res.product.statusText) {
                        $(self.options.productStatusSelector)
                            .removeClass('available')
                            .addClass('unavailable')
                            .find('span')
                            .html(res.product.statusText);
                    }
                    self.enableAddToCartButton(form);*/
                }
            });
        }
    });

    return $.mage.setCart;
});
