<?php
/**
 * Outlet contact options panel.
 *
 * @package WooCommerce_Point_Of_Sale/Meta_Boxes/Views
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="contact_outlet_options" class="panel woocommerce_options_panel">
	<div class="options_group">
		<p class="options_group_description"><?php esc_html_e( 'Enter the contact details of the outlet as this will appear on receipts that are printed from registers at this outlet.', 'woocommerce-point-of-sale' ); ?></p>
		<?php
			// Email Address.
			woocommerce_wp_text_input(
				[
					'id'          => 'email',
					'class'       => 'wc_pos_input_email',
					'label'       => __( 'Email Address', 'woocommerce-point-of-sale' ),
					'description' => __( 'Enter an email address for this outlet.', 'woocommerce-point-of-sale' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'value'       => $outlet_object->get_email( 'edit' ),
				]
			);

			// Phone Number.
			woocommerce_wp_text_input(
				[
					'id'          => 'phone',
					'class'       => 'wc_pos_input_phone',
					'label'       => __( 'Phone Number', 'woocommerce-point-of-sale' ),
					'description' => __( 'Enter a phone number for this outlet.', 'woocommerce-point-of-sale' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'value'       => $outlet_object->get_phone( 'edit' ),
				]
			);

			// Fax Number.
			woocommerce_wp_text_input(
				[
					'id'          => 'fax',
					'class'       => 'wc_pos_input_fax',
					'label'       => __( 'Fax Number', 'woocommerce-point-of-sale' ),
					'description' => __( 'Enter a fax number for this outlet.', 'woocommerce-point-of-sale' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'value'       => $outlet_object->get_fax( 'edit' ),
				]
			);

			// Website.
			woocommerce_wp_text_input(
				[
					'id'          => 'website',
					'class'       => 'wc_pos_input_url',
					'label'       => __( 'Website', 'woocommerce-point-of-sale' ),
					'description' => __( 'Enter a URL for this outlet.', 'woocommerce-point-of-sale' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'value'       => $outlet_object->get_website( 'edit' ),
				]
			);

			/**
			 * Outlet general options.
			 *
			 * @since 5.0.0
			 */
			do_action( 'wc_pos_outlet_options_general', $thepostid );
			?>
	</div>
</div>
