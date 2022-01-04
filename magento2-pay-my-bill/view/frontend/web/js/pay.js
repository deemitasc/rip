define([
    "jquery",
    "mage/mage"
    ],
function($) {
    "use strict";

    $('.open-invoices .addon').change(function(){
        var checkedAmount = $(this).attr('data-amount');
        var amountToPay = $('#invoice_payment_amount').val() == '' ? 0 : $('#invoice_payment_amount').val();

        if(this.checked){
            var newAmount = parseFloat(amountToPay) + parseFloat(checkedAmount);
        } else {
            var newAmount = parseFloat(amountToPay) - parseFloat(checkedAmount);
        }

        if (newAmount < 0)
            newAmount = 0;

        $('#invoice_payment_amount').val(newAmount.toFixed(2));
    });

    var self = {
        'Ripen_PayMyBill/js/pay': function(config) {

            var dataForm = $('#payment-form');
            var status = dataForm.mage('validation', {
                "rules": {
                    "cc_number": {
                        "creditcard":true
                    },
                    "cc_exp": {
                        "required":true
                    }
                }
            });

            dataForm.submit(function(){

                var status = dataForm.validation('isValid');
                if(status) {
                    var url = config.url;

                    var invoices = [];
                    $('.open-invoices .addon').each(function (index) {
                        if ($(this).is(":checked")) {
                            invoices.push($(this).val());
                        }
                    });

                    var data = dataForm.serializeArray();
                    data.push({'name': 'invoices', 'value': invoices});

                    $.ajax({
                        showLoader: true,
                        url: url,
                        data: data,
                        type: "POST"
                    }).success(function (response) {
                        if (response.message) {
                            $('#invoice_payment_response').html(response.message);
                        }
                        if (response.url) {
                            window.location.href = response.url;
                        }
                    });
                    return false;
                }
            });
        }
    };
    return self;
});
