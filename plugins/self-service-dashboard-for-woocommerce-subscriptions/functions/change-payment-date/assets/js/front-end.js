/**
 * Front end JS
 *
 * @package SForce for WooCommerce Subscriptions
 * @since 1.0.0
 */

jQuery(
    function ($) {
        let sub_details = $('.shop_table.subscription_details');

        // Change Date - Cancel button event.
        sub_details.on(
            'click',
            '#sf-subscription-change-date-cancel',
            function (e) {
                e.preventDefault();

                $('#sf-change-date-subscription').remove();
                $('.sf-change-payment-date.button').show();
            }
        );

        // Change date subscription button event.
        sub_details.on(
            'click',
            '.sf-change-payment-date',
            function (e) {
                e.preventDefault();

                $('#sf-change-date-subscription').remove();

                let $subscription_id = $(this).attr('data-id');

                $.ajax(
                    {
                        url: sf_sf_js.ajax_url,
                        type: 'post',
                        data: {
                            action: 'sf_change_payment_date_subscription',
                            nonce: sf_sf_js.nonce,
                            subscription_id: $subscription_id,
                        },
                        complete: function () {

                        },
                        success: function (response) {
                            if ($('.subscription_details .sf-change-payment-date').length > 0 && $('#sf-change-date-subscription').length === 0 ) {
                                $('.sf-change-payment-date.button').hide();
                                $(response.data.html).insertAfter('.sf-change-payment-date');
                            }
                        },
                        error: function (XMLHttpRequest, textStatus, errorThrown) {
                            if (XMLHttpRequest.status == 0) {
                                console.error('Network connectivity error.');
                            } else {
                                console.error(XMLHttpRequest.responseText);
                            }
                        }
                    }
                );
            }
        );

        sub_details.on(
            'click',
            '#sf-subscription-change-date-submit',
            function (e) {
                e.preventDefault();

                let serialized = $('#sf-change-date-form').serialize();

                $.ajax(
                    {
                        url: sf_sf_js.ajax_url,
                        type: 'post',
                        data: {
                            action: 'sf_change_date_subscription_submit',
                            nonce: sf_sf_js.nonce,
                            data: serialized,
                        },
                        complete: function () {

                        },
                        success: function (response) {
                            $('#sf-change-date-subscription').slideUp(500).remove();
                            $(response.data.html).insertAfter('.sf-change-payment-date').slideDown(500);
                            setTimeout(function () {
                                location.reload();
                            }, 5000);
                        },
                        error: function (XMLHttpRequest, textStatus, errorThrown) {
                            if (XMLHttpRequest.status == 0) {
                                console.error('Network connectivity error.');
                            } else {
                                console.error(XMLHttpRequest.responseText);
                            }
                        }
                    }
                );
            }
        );
    }
);

