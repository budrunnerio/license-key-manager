/**
 * Front end JS
 *
 * @package Self-service Dashboard for WooCommerce Subscriptions
 * @since 1.0.0
 */

jQuery(
    function ($) {
        let sub_list = $('.shop_table .product-name');
        let sub_details = $('.shop_table.subscription_details');
        let add_new_form_content = $('.wpr-add-new-form-content');

        // Display Qty input and Save/Cancel buttons.
        sub_list.on(
            'click',
            '.wpr-quantity-update',
            function (e) {
                e.preventDefault();
                $('.wpr-qty-input,.wpr-change-subscription-variation,.wpr-add-new-subscription,.wpr-cancel-qty').remove();
                let $current_qty = $(this).data('item-qty');

                $('<input type="number" class="wpr-qty-input input-text qty text" value="' + $current_qty + '" step="1" min="1" name="wpr-quantity" value="1" title="Qty" size="4" inputmode="numeric">').insertBefore($(this));
                $(
                    '<a></a>',
                    {
                        href: '#',
                        text: wpr_sd_js.wpr_cancel_qty,
                        class: 'button wpr-cancel-qty'
                    }
                ).insertAfter($(this));
                $(this).toggleClass('wpr-quantity-update wpr-quantity-save').html(wpr_sd_js.wpr_save_qty);
                $(this).addClass('disable-click');
            }
        );

        sub_list.on('change paste keyup', '.wpr-qty-input', function() {
            $('.wpr-quantity-save').removeClass('disable-click');
        });

        // Save Qty event.
        sub_list.on(
            'click',
            '.wpr-quantity-save',
            function (e) {
                e.preventDefault();

                let $order_id = $(this).data('id');
                let $item_id = $(this).data('item-id');
                let $qty = $('.wpr-qty-input').val();

                wpr_update_subscription_quantity($order_id, $item_id, $qty);
            }
        );

        // Cancel Qty button event.
        sub_list.on(
            'click',
            '.wpr-cancel-qty',
            function (e) {
                e.preventDefault();

                $('.wpr-qty-input').remove();
                $(this).remove();
                $('.wpr-quantity-save').toggleClass('wpr-quantity-save wpr-quantity-update').html(wpr_sd_js.wpr_change_qty).removeClass('disable-click');
            }
        );

        // Pause - Cancel button event.
        sub_details.on(
            'click',
            '#wpr-subscription-pause-cancel',
            function (e) {
                e.preventDefault();

                $('#wpr-pause-date-subscription').remove();
                $('.pause_subscription').show();
            }
        );

        // Cancel subscription update event.
        sub_list.on(
            'click',
            '.wpr-subscription-cancel-submit',
            function (e) {
                e.preventDefault();

                $('.wpr-change-subscription-variation').remove();
                $('.composite_form').remove();
                $('.ssd_bundle_form').remove();
                $('.wpr-subscription-update').show();
            }
        );

        // Cancel add new subscription add new event.
        add_new_form_content.on(
            'click',
            '.wpr-subscription-cancel-add',
            function (e) {
                e.preventDefault();

                $('.wpr-add-new-subscription').remove();
                $('.wpr-add-product').show();
                $('.wpr-cancel-add-product').remove();
            }
        );

        // Cancel add new subscription add new event.
        $('.woocommerce').on(
            'click',
            '.wpr-cancel-add-product',
            function (e) {
                e.preventDefault();

                $('.wpr-add-new-subscription').remove();
                $('.wpr-add-product').show();
                $('.wpr-cancel-add-product').remove();
            }
        );

        // Cancel subscription add new event.
        add_new_form_content.on(
            'click',
            '.wpr-select-add-variable',
            function (e) {
                e.preventDefault();

                $(this).hide().closest('.wpr-product-add-button').find('.variations_form').show();
            }
        );

        // Composite subscription add new event.
        add_new_form_content.on(
            'click',
            '.wpr-select-add-composite',
            function (e) {
                e.preventDefault();

                $(this).hide().closest('.wpr-product-add-button').find('.composite_form').show();
            }
        );

        // Bundle subscription add new event.
        add_new_form_content.on(
            'click',
            '.wpr-select-add-bundle',
            function (e) {
                e.preventDefault();

                $(this).hide().closest('.wpr-product-add-button').find('.bundle_form').show();
            }
        );

        // Cancel subscription add new event.
        add_new_form_content.on(
            'click',
            '.wpr-subscription-cancel-submit',
            function (e) {
                e.preventDefault();

                $(this).closest('.variations_form').hide();
                $(this).closest('.bundle_form').hide();
                $(this).closest('.composite_form').hide();
                $(this).closest('.wpr-product-add-button').find('.wpr-select-add-variable').show();
                $(this).closest('.wpr-product-add-button').find('.wpr-select-add-bundle').show();
                $(this).closest('.wpr-product-add-button').find('.wpr-select-add-composite').show();
            }
        );

        // Search for products on add new event.
        add_new_form_content.on(
            'click',
            '#ssd-submit-search',
            function (e) {
                e.preventDefault();

                ssd_trigger_search_ajax($('#ssd-modal-content').find('#ssd-search-products').val(), $('#ssd-modal-content').find('#ssd-search-products').data('id'));
            }
        );

        add_new_form_content.on('keydown', '#ssd-search-products', function (e) {
            var key = e.which;
            switch (key) {
                case 13:
                    $('#ssd-submit-search').click();
                    break;
                default:
                    break;
            }
        });

        // Submit subscription update event.
        sub_list.on(
            'click',
            '.wpr-subscription-update',
            function (e) {
                e.preventDefault();

                $('.wpr-qty-input,.wpr-change-subscription-variation,.wpr-add-new-subscription,.wpr-cancel-qty').remove();
                $(this).closest('td.product-name').find('.wpr-quantity-save').toggleClass('wpr-quantity-save wpr-quantity-update').html(wpr_sd_js.wpr_change_qty);

                let $order_id = $(this).data('id');
                let $item_id = $(this).data('item-id');
                let $sub_item = $(this);

                $.ajax(
                    {
                        url: wpr_sd_js.ajax_url,
                        type: 'post',
                        data: {
                            action: 'wpr_display_variation',
                            nonce: wpr_sd_js.nonce,
                            item_id: $item_id,
                            order_id: $order_id,
                        },
                        complete: function () {
                            $sub_item.delay(1000).hide();
                        },
                        success: function (response) {
                            $('.wpr-variable-form-' + $item_id).html(response.data.html);
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

        // Submit subscription update event.
        sub_list.on(
            'click',
            '.wpr-subscription-update-submit',
            function (e) {
                e.preventDefault();

                let $item_id = $(this).data('item-id');
                let $order_id = $(this).data('id');
                let serialized = $('.variations_form').serialize();

                var $empty_var = true;
                $('form.variations_form select').each(
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
                        url: wpr_sd_js.ajax_url,
                        type: 'post',
                        data: {
                            action: 'wpr_update_variable',
                            nonce: wpr_sd_js.nonce,
                            data: serialized,
                            order_id: $order_id,
                            item_id: $item_id,
                        },
                        complete: function () {

                        },
                        success: function (response) {
                            $('.wpr-change-subscription-variation').remove();
                            if (response.error) {
                                $('.wpr-variable-form-' + $item_id).append(response.data.html);
                                setTimeout(function () {
                                    $('.ssd-error-display-notification').remove();
                                }, 5000);
                            } else {
                                $('.wpr-variable-form-' + $item_id).append(response.data.html);
                                setTimeout(function () {
                                    location.reload();
                                }, 5000);
                            }
                            $('.wpr-subscription-update-submit').attr('disabled',true);
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

        // Submit subscription bundle update event.
        sub_list.on(
            'click',
            '.wpr-subscription-bundle-update-submit',
            function (e) {
                e.preventDefault();

                let $item_id = $(this).data('item-id');
                let $order_id = $(this).data('id');
                let serialized = $('.ssd_bundle_form').serialize();

                var $empty_var = true;
                $('form.ssd_bundle_form select').each(
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
                        url: wpr_sd_js.ajax_url,
                        type: 'post',
                        data: {
                            action: 'wpr_update_bundle',
                            nonce: wpr_sd_js.nonce,
                            data: serialized,
                            order_id: $order_id,
                            item_id: $item_id,
                        },
                        complete: function () {

                        },
                        success: function (response) {
                            $('.wpr-change-subscription-variation').remove();
                            if (response.error) {
                                $('.wpr-variable-form-' + $item_id).append(response.data.html);
                                setTimeout(function () {
                                    $('.ssd-error-display-notification').remove();
                                }, 5000);
                            } else {
                                $('.wpr-variable-form-' + $item_id).append(response.data.html);
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

        // Submit subscription composite update event.
        sub_list.on(
            'click',
            '.wpr-subscription-composite-update-submit',
            function (e) {
                e.preventDefault();

                let $item_id = $(this).data('item-id');
                let $order_id = $(this).data('id');
                let serialized = $('.composite_form').serialize();

                $.ajax(
                    {
                        url: wpr_sd_js.ajax_url,
                        type: 'post',
                        data: {
                            action: 'wpr_update_composite',
                            nonce: wpr_sd_js.nonce,
                            data: serialized,
                            order_id: $order_id,
                            item_id: $item_id,
                        },
                        complete: function () {

                        },
                        success: function (response) {
                            $('.wpr-change-subscription-variation').remove();
                            if (response.error) {
                                $('.wpr-variable-form-' + $item_id).append(response.data.html);
                                setTimeout(function () {
                                    $('.ssd-error-display-notification').remove();
                                }, 5000);
                            } else {
                                $('.wpr-variable-form-' + $item_id).append(response.data.html);
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

        // Add new product button event.
        $('.woocommerce').on(
            'click',
            '.wpr-add-product',
            function (e) {
                e.preventDefault();

                $('.wpr-qty-input,.wpr-change-subscription-variation,.wpr-add-new-subscription,.wpr-cancel-qty').remove();
                $(this).closest('td.product-name').find('.wpr-quantity-save').toggleClass('wpr-quantity-save wpr-quantity-update').html(wpr_sd_js.wpr_change_qty);

                let $order_id = $(this).data('id');

                $.ajax(
                    {
                        url: wpr_sd_js.ajax_url,
                        type: 'post',
                        data: {
                            action: 'wpr_add_new_product',
                            nonce: wpr_sd_js.nonce,
                            order_id: $order_id,
                        },
                        complete: function () {

                        },
                        success: function (response) {
                            let modalContent = $('#ssd-modal-content');

                            $('.ssd-modal-wrapper').addClass('ssd-show', 1000, "easeOutSine");
                            modalContent.html("loading...");
                            modalContent.html(response.data.html);
//                            $('html, body').animate({
//                                scrollTop: 0
//                            }, 'slow');
                            return false;

                            if (response.error) {
                                setTimeout(function () {
                                    $('.ssd-error-display-notification').remove();
                                }, 5000);
                            } else {
                                // TO DO: SHOW HIDE add/cancel
                                if ($('.wpr-cancel-add-product').length === 0) {
                                    $('.wpr-add-product').hide();
                                    $(wpr_sd_js.wpr_cancel_add_new).insertAfter('.wpr-add-new-form-content');
                                }
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

        // Add new simple product event.
        add_new_form_content.on(
            'click',
            '.wpr-add-simple-product',
            function (e) {
                e.preventDefault();

                let $sub_id = $(this).data('subscription-in');
                let $order_id = $(this).data('id');

                $.ajax(
                    {
                        url: wpr_sd_js.ajax_url,
                        type: 'post',
                        data: {
                            action: 'wpr_add_simple_product',
                            nonce: wpr_sd_js.nonce,
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

        // Add new variable product event.
        add_new_form_content.on(
            'click',
            '.wpr-subscription-add-submit',
            function (e) {
                e.preventDefault();

                let $sub_id = $(this).data('subscription-in');
                let $order_id = $(this).data('id');
                let serialized = $('.wpr-add-product-' + $sub_id).serialize();

                let $empty_var = true;

                $(this).closest('.wpr-product-add-button').find('form.wpr-add-product-' + $sub_id + ' select').each(
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
                        url: wpr_sd_js.ajax_url,
                        type: 'post',
                        data: {
                            action: 'wpr_add_variable_product',
                            nonce: wpr_sd_js.nonce,
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

        // Add new bundle product event.
        add_new_form_content.on(
            'click',
            '.wpr-subscription-bundle-add-submit',
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
                        url: wpr_sd_js.ajax_url,
                        type: 'post',
                        data: {
                            action: 'wpr_add_bundle_product',
                            nonce: wpr_sd_js.nonce,
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

        // Add new bundle product event.
        add_new_form_content.on(
            'click',
            '.wpr-subscription-composite-add-submit',
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
                        url: wpr_sd_js.ajax_url,
                        type: 'post',
                        data: {
                            action: 'wpr_add_composite_product',
                            nonce: wpr_sd_js.nonce,
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

        // Pause subscription button event.
        sub_details.on(
            'click',
            '.pause_subscription',
            function (e) {
                e.preventDefault();

                $('#wpr-pause-date-subscription').remove();


                let url = new URL($(this).attr('href'));
                let searchParams = new URLSearchParams(url.search);
                let $order_id = searchParams.get('subscription');
                let $pause_item = $(this);

                $.ajax(
                    {
                        url: wpr_sd_js.ajax_url,
                        type: 'post',
                        data: {
                            action: 'wpr_pause_subscription',
                            nonce: wpr_sd_js.nonce,
                            order_id: $order_id,
                        },
                        complete: function () {
                            $pause_item.delay(1000).hide();
                        },
                        success: function (response) {
                            if ($('#wpr-pause-date-subscription').length === 0) {
                                if ($('.subscription_details .button.cancel').length > 0) {
                                    $(response.data.html).insertBefore('.button.cancel');
                                } else {
                                    $(response.data.html).insertBefore('.button.pause_subscription');
                                }
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

        // Submit Pause subscription event.
        sub_details.on(
            'click',
            '#wpr-subscription-pause-submit',
            function (e) {
                e.preventDefault();

                let serialized = $('#ssd-pause-form').serialize();

                $.ajax(
                    {
                        url: wpr_sd_js.ajax_url,
                        type: 'post',
                        data: {
                            action: 'wpr_pause_subscription_submit',
                            nonce: wpr_sd_js.nonce,
                            data: serialized,
                        },
                        complete: function () {

                        },
                        success: function (response) {
                            $('#wpr-pause-date-subscription').remove();
                            if ($('.subscription_details .button.cancel').length > 0) {
                                $(response.data.html).insertBefore('.button.cancel');
                            } else {
                                $(response.data.html).insertBefore('.button.pause_subscription');
                            }
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

        // Trigger Product Qty. update.
        function wpr_update_subscription_quantity($order_id, $item_id, $qty) {
            $.ajax(
                {
                    url: wpr_sd_js.ajax_url,
                    type: 'post',
                    data: {
                        action: 'wpr_update_qty',
                        nonce: wpr_sd_js.nonce,
                        order_id: $order_id,
                        item_id: $item_id,
                        qty: $qty,
                    },
                    complete: function () {

                    },
                    success: function (response) {
                        $('.wpr-qty-input,.wpr-cancel-qty,.wpr-quantity-save').remove();

                        $(response.data.html).insertBefore('.wpr-variable-form-' + $item_id);
                        if (response.error) {
                            setTimeout(function () {
                                $('.ssd-error-display-notification').remove();
                                location.reload();
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

        function ssd_trigger_search_ajax($search_for, $order_id) {
            $.ajax(
                {
                    url: wpr_sd_js.ajax_url,
                    type: 'post',
                    data: {
                        action: 'wpr_search_products',
                        nonce: wpr_sd_js.nonce,
                        order_id: $order_id,
                        search_for: $search_for,
                    },
                    complete: function () {

                    },
                    success: function (response) {
                        $('.wpr-add-new-form-content').empty().html(response.data.html);
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

        // Modal loading
        $('.ssd-modal-wrapper').on('click tap', '.ssd-close-modal-link',function (e) {
            e.preventDefault();
            $('.ssd-modal-wrapper').toggleClass('ssd-show');
        });

        $( document ).on('woocommerce_variation_has_changed', '.variations_form', function(){
            $('.wpr-subscription-update-submit').attr('disabled',false);
        });
    }
);
