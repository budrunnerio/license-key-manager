<?php
/**
 * Outlet wireless options panel.
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Meta_Boxes/Views
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="wireless_outlet_options" class="panel woocommerce_options_panel">
	<div class="options_group">
		<p class="options_group_description"><?php esc_html_e( 'Enter the wireless details of your outlet. This is useful for shop managers who want to share their wireless internet conncectivity to paid customers by sharing their wireless details on the printed receipt.', 'woocommerce-point-of-sale' ); ?></p>
		<?php
			// Wi-Fi Network.
			woocommerce_wp_text_input(
				[
					'id'       => 'wifi_network',
					'label'    => __( 'Wi-Fi Network', 'woocommerce-point-of-sale' ),
					'type'     => 'text',
					'desc_tip' => false,
					'value'    => $outlet_object->get_wifi_network( 'edit' ),
				]
			);

			// Wi-Fi Password.
			woocommerce_wp_text_input(
				[
					'id'       => 'wifi_password',
					'label'    => __( 'Wi-Fi Password', 'woocommerce-point-of-sale' ),
					'type'     => 'text',
					'desc_tip' => false,
					'value'    => $outlet_object->get_wifi_password( 'edit' ),
				]
			);

			/**
			 * Outlet wireless options.
			 *
			 * @since 5.0.0
			 */
			do_action( 'wc_pos_outlet_options_wireless', $thepostid );
			?>
	</div>
</div>
