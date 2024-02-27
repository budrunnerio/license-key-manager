/**
 * Front end JS
 *
 * @package Self-service Dashboard for WooCommerce Subscriptions
 * @since 1.0.0
 */

jQuery(
    function ($) {
        let add_new_form_content = $( '.wpr-add-new-form-content' );

        // Add new simple product next shipment event.
        add_new_form_content.on(
            'click',
            '.wpr-add-simple-product-next-shipment',
            function (e) {
                e.preventDefault();

                let $sub_id   = $( this ).data( 'subscription-in' );
                let $order_id = $( this ).data( 'id' );

                $.ajax(
                    {
                        url: sf_nx_js.ajax_url,
                        type: 'post',
                        data: {
                            action: 'wpr_add_simple_product_next_shipment',
                            nonce: sf_nx_js.nonce,
                            order_id: $order_id,
                            sub_id: $sub_id,
                        },
                        complete: function () {

                        },
                        success: function (response) {
                            $('.wpr-add-new-subscription').slideUp(500).remove();
                            $('.wpr-add-product').show();
                            $('.wpr-cancel-add-product').remove();

                            $(response.data.html).insertBefore('.ssd-search-modal-header').slideDown(500);
                            setTimeout(function () {
                                location.reload();
                            }, 5000);
                        },
                        error: function (XMLHttpRequest, textStatus, errorThrown) {
                            if (XMLHttpRequest.status == 0) {
                                console.error( 'Network connectivity error.' );
                            } else {
                                console.error( XMLHttpRequest.responseText );
                            }
                        }
                    }
                );
            }
        );

        // Add new variable product event.
        add_new_form_content.on(
            'click',
            '.wpr-add-variable-product-next-shipment',
            function (e) {
                e.preventDefault();

                let $sub_id    = $( this ).data( 'subscription-in' );
                let $order_id  = $( this ).data( 'id' );
                let serialized = $( '.wpr-add-product-' + $sub_id ).serialize();

                let $empty_var = true;

                $( this ).closest( '.wpr-product-add-button' ).find( 'form.wpr-add-product-' + $sub_id + ' select' ).each(
                    function (index, el) {
                        if ($.trim( $( this ).val() ).length === 0) {
                            $empty_var = false;
                        }
                    }
                );

                if ( ! $empty_var) {
                    alert( wpr_sd_js.wpr_product_variations_alert );
                    return;
                }

                $.ajax(
                    {
                        url: sf_nx_js.ajax_url,
                        type: 'post',
                        data: {
                            action: 'wpr_add_variable_product_next_shipment',
                            nonce: sf_nx_js.nonce,
                            data: serialized,
                            order_id: $order_id,
                            sub_id: $sub_id,
                        },
                        complete: function () {

                        },
                        success: function (response) {
                            $('.wpr-add-new-subscription').slideUp(500).remove();
                            $('.wpr-add-product').show();
                            $('.wpr-cancel-add-product').remove();

                            $(response.data.html).insertBefore('.ssd-search-modal-header').slideDown(500);
                            setTimeout(function () {
                                location.reload();
                            }, 5000);
                        },
                        error: function (XMLHttpRequest, textStatus, errorThrown) {
                            if (XMLHttpRequest.status == 0) {
                                console.error( 'Network connectivity error.' );
                            } else {
                                console.error( XMLHttpRequest.responseText );
                            }
                        }
                    }
                );
            }
        );

        add_new_form_content.on(
            'click',
            '.wpr-add-bundle-product-next-shipment',
            function (e) {
                e.preventDefault();

                let $sub_id = $(this).data('subscription-in');
                let $order_id = $(this).data('id');
                let serialized = $('.wpr-add-bundle-' + $sub_id).serialize();

                let $empty_var = true;

                $(this).closest('.wpr-add-new-subscription').find('form.wpr-add-bundle-' + $sub_id + ' select').each(
                    function (index, el) {
                        if ($.trim($(this).val()).length === 0) {
                            $empty_var = false;
                        }
                    }
                );

                if (!$empty_var) {
                    alert(wpr_sd_js.wpr_product_variations_alert);
                    return;
                }

                $.ajax(
                    {
                        url: sf_nx_js.ajax_url,
                        type: 'post',
                        data: {
                            action: 'wpr_add_bundle_product_next_shipment',
                            nonce: sf_nx_js.nonce,
                            data: serialized,
                            order_id: $order_id,
                            sub_id: $sub_id,
                        },
                        complete: function () {

                        },
                        success: function (response) {
                            $('.wpr-add-new-subscription').remove();
                            $('.wpr-add-product').show();
                            $('.wpr-cancel-add-product').remove();

                            $(response.data.html).insertAfter('.ssd-search-modal-header').slideDown(500);
                            if (response.error) {
                                setTimeout(function () {
                                    $('.ssd-error-display-notification').remove();
                                }, 5000);
                            } else {
                                setTimeout(function () {
                                    location.reload();
                                }, 5000);
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

        add_new_form_content.on(
            'click',
            '.wpr-add-composite-product-next-shipment',
            function (e) {
                e.preventDefault();

                let $sub_id = $(this).data('subscription-in');
                let $order_id = $(this).data('id');
                let serialized = $('.wpr-add-composite-' + $sub_id).serialize();

                let $empty_var = true;

                $(this).closest('.wpr-product-add-button').find('form.wpr-add-composite-' + $sub_id + ' select:not(.component_options_select)').each(
                    function (index, el) {
                        if ($.trim($(this).val()).length === 0) {
                            $empty_var = false;
                        }
                    }
                );

                if (!$empty_var) {
                    alert(wpr_sd_js.wpr_product_variations_alert);
                    return;
                }

                $.ajax(
                    {
                        url: sf_nx_js.ajax_url,
                        type: 'post',
                        data: {
                            action: 'wpr_add_composite_product_next_shipment',
                            nonce: sf_nx_js.nonce,
                            data: serialized,
                            order_id: $order_id,
                            sub_id: $sub_id,
                        },
                        complete: function () {

                        },
                        success: function (response) {
                            $('.wpr-add-new-subscription').remove();
                            $('.wpr-add-product').show();
                            $('.wpr-cancel-add-product').remove();

                            $(response.data.html).insertAfter('.ssd-search-modal-header').slideDown(500);
                            if (response.error) {
                                setTimeout(function () {
                                    $('.ssd-error-display-notification').remove();
                                }, 5000);
                            } else {
                                setTimeout(function () {
                                    location.reload();
                                }, 5000);
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
    }
);
