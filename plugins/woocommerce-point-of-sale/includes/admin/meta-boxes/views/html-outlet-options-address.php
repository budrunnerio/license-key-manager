<?php
/**
 * Outlet address options panel.
 *
 * @var object $outlet_object
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Meta_Boxes/Views
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="address_outlet_options" class="panel woocommerce_options_panel">
	<p class="options_group_description">
	<?php
	/* translators: %1$s opening anchor tag %2$s closing anchor tag */
	echo wp_kses_post( sprintf( __( 'Enter the address details of the outlet or %1$sclick here%2$s to fill out the fields from the store address.', 'woocommerce-point-of-sale' ), '<a href="" id="use-store-address">', '</a>' ) );
	?>
	</p>
	<?php
		// Address line 1.
		woocommerce_wp_text_input(
			[
				'id'       => 'address_1',
				'label'    => __( 'Address Line 1', 'woocommerce-point-of-sale' ),
				'type'     => 'text',
				'desc_tip' => false,
				'value'    => $outlet_object->get_address_1( 'edit' ),
			]
		);

		// Address line 2.
		woocommerce_wp_text_input(
			[
				'id'       => 'address_2',
				'label'    => __( 'Address Line 2', 'woocommerce-point-of-sale' ),
				'type'     => 'text',
				'desc_tip' => false,
				'value'    => $outlet_object->get_address_2( 'edit' ),
			]
		);

		// City.
		woocommerce_wp_text_input(
			[
				'id'       => 'city',
				'label'    => __( 'City', 'woocommerce-point-of-sale' ),
				'type'     => 'text',
				'desc_tip' => false,
				'value'    => $outlet_object->get_city( 'edit' ),
			]
		);

		// Postcode/ZIP.
		woocommerce_wp_text_input(
			[
				'id'       => 'postcode',
				'label'    => __( 'Postcode/ZIP', 'woocommerce-point-of-sale' ),
				'type'     => 'text',
				'desc_tip' => false,
				'value'    => $outlet_object->get_postcode( 'edit' ),
			]
		);

		// Country.
		woocommerce_wp_select(
			[
				'id'       => 'country',
				'class'    => 'js_field-country select short',
				'value'    => $outlet_object->get_country( 'edit' ),
				'options'  => [ '' => __( 'Select a country&hellip;', 'woocommerce-point-of-sale' ) ] + WC()->countries->get_allowed_countries(),
				'label'    => __( 'Country', 'woocommerce-point-of-sale' ),
				'desc_tip' => false,
			]
		);

		// State/County.
		woocommerce_wp_text_input(
			[
				'id'          => 'state',
				'class'       => 'js_field-state select short',
				'value'       => $outlet_object->get_state( 'edit' ),
				'date_type'   => 'text',
				'label'       => __( 'State/County', 'woocommerce-point-of-sale' ),
				'description' => __( 'State/County or state code.', 'woocommerce-point-of-sale' ),
				'desc_tip'    => true,
			]
		);

		/**
		 * Outlet options address.
		 *
		 * @since 5.0.0
		 */
		do_action( 'wc_pos_outlet_options_address', $thepostid );
		?>
</div>
