define([
    'jquery',
    'mage/utils/wrapper'
], function($, wrapper) {
    'use strict';

    return function(payloadExtender) {

        return wrapper.wrap(payloadExtender, function(proceed) {
            var payload = proceed();
            var attributes = payload.addressInformation['extension_attributes'];

            attributes.comments = $('#comments').val();
            attributes.po_number = $('#po_number').val();
            attributes.ship_entire_only = $('#ship_entire_only').is(':checked');
            return payload;
        });
    };
});
