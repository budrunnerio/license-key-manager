/**
 * Admin JS
 *
 * @package Self-service Dashboard for WooCommerce Subscriptions
 * @since 1.0.8
 */

jQuery(
	function ($) {
		let ssd_notice = $( '.ssd-notice-wrap' );
		let ssd_upsell_notice = $( '.ssd-notice-upsell-wrap' );

		// Dismiss notice.
		ssd_notice.on(
			'click',
			'button.notice-dismiss',
			function () {
				ssd_dismiss_notice('notice');
			}
		);

		ssd_upsell_notice.on(
			'click',
			'button.notice-dismiss',
			function () {
				ssd_dismiss_notice('upsell');
			}
		);

		// Register dismiss
		function ssd_dismiss_notice(type) {
			$.ajax(
				{
					url: wpr_sd_ajs.ajax_url,
					type: 'post',
					data: {
						action: 'ssd_dismiss_notice',
						nonce: wpr_sd_ajs.nonce,
						type: type,
					},
					complete: function () {

					},
					success: function (state) {
						location.reload();
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

		// Add new product type toggle

			// Default state
			if ($('#settings_tabs_hide_add_new_product').is(":checked")) {
				$('.conditional_addnewproduct').parents('tr').show();
			} else {
				$('.conditional_addnewproduct').parents('tr').hide();
			}

			// Event state
			$('#settings_tabs_hide_add_new_product').on('change', function( x ) {
				if ($('#settings_tabs_hide_add_new_product').is(":checked")) {
					$('.conditional_addnewproduct').parents('tr').show();
				} else {
					$('.conditional_addnewproduct').parents('tr').hide();
				}
			});
	}
);
