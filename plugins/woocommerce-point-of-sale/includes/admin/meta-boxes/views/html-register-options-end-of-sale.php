<?php
/**
 * Register end of sale options panel.
 *
 * @package WooCommerce_Point_Of_Sale/Meta_Boxes/Views
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="end_of_sale_register_options" class="panel woocommerce_options_panel">
	<?php
		// Print Receipt.
		woocommerce_wp_checkbox(
			[
				'id'          => 'print_receipt',
				'label'       => __( 'Print Receipt', 'woocommerce-point-of-sale' ),
				'description' => __( 'Check this box to print receipt at end of sale.', 'woocommerce-point-of-sale' ),
				'type'        => 'text',
				'desc_tip'    => false,
				'value'       => wc_bool_to_string( $register_object->get_print_receipt( 'edit' ) ),
			]
		);

		// Gift Receipt.
		woocommerce_wp_checkbox(
			[
				'id'          => 'gift_receipt',
				'label'       => __( 'Gift Receipt', 'woocommerce-point-of-sale' ),
				'description' => __( 'Check this box to print gift receipt at end of sale.', 'woocommerce-point-of-sale' ),
				'type'        => 'text',
				'desc_tip'    => false,
				'value'       => wc_bool_to_string( $register_object->get_gift_receipt( 'edit' ) ),
			]
		);

		// Email Receipt.
		woocommerce_wp_select(
			[
				'id'          => 'email_receipt',
				'value'       => $register_object->get_email_receipt( 'edit' ),
				'label'       => __( 'Email Receipt', 'woocommerce-point-of-sale' ),
				/**
				 * Register email receipt options.
				 *
				 * @since 5.0.0
				 */
				'options'     => apply_filters(
					'wc_pos_register_email_receipt_options',
					[
						'no'        => __( 'No', 'woocommerce-point-of-sale' ),
						'all'       => __( 'Yes, for all customers', 'woocommerce-point-of-sale' ),
						'non_guest' => __( 'Yes, for non-guest customers only', 'woocommerce-point-of-sale' ),
					]
				),
				'desc_tip'    => true,
				'description' => __( 'Select whether to email receipt at end of sale.', 'woocommerce-point-of-sale' ),
			]
		);

		// Note Request.
		woocommerce_wp_select(
			[
				'id'          => 'note_request',
				'value'       => $register_object->get_note_request( 'edit' ),
				'label'       => __( 'Note Request', 'woocommerce-point-of-sale' ),
				/**
				 * Register note request options.
				 *
				 * @since 5.0.0
				 */
				'options'     => apply_filters(
					'wc_pos_register_note_request_options',
					[
						'none'         => __( 'None', 'woocommerce-point-of-sale' ),
						'on_save'      => __( 'On save', 'woocommerce-point-of-sale' ),
						'on_all_sales' => __( 'On all sales', 'woocommerce-point-of-sale' ),
					]
				),
				'desc_tip'    => true,
				'description' => __( 'Select whether to add a note at end of sale.', 'woocommerce-point-of-sale' ),
			]
		);

		// Change User.
		woocommerce_wp_checkbox(
			[
				'id'          => 'change_user',
				'label'       => __( 'Change Cashier', 'woocommerce-point-of-sale' ),
				'description' => __( 'Check this box if you want the user to be changed at end of sale.', 'woocommerce-point-of-sale' ),
				'type'        => 'text',
				'desc_tip'    => false,
				'value'       => wc_bool_to_string( $register_object->get_change_user( 'edit' ) ),
			]
		);

		/**
		 * Register's end-of-sale options.
		 *
		 * @since 5.0.0
		 */
		do_action( 'wc_pos_register_options_end_of_sale', $thepostid );
		?>
</div>
