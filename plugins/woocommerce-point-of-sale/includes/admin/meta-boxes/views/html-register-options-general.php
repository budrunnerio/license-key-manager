<?php
/**
 * Register general options panel.
 *
 * @var object $register_object
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Meta_Boxes/Views
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="general_register_options" class="panel woocommerce_options_panel">
	<?php
		// Outlet.
		$outlet = $register_object->get_outlet( 'edit' );
		woocommerce_wp_select(
			[
				'id'          => 'outlet',
				'value'       => $outlet ? $outlet : get_option( 'wc_pos_default_outlet' ),
				'label'       => __( 'Outlet', 'woocommerce-point-of-sale' ),
				'options'     => wc_pos_get_register_outlet_options(),
				'desc_tip'    => true,
				'description' => __( 'Select the outlet that this register is assigned to.', 'woocommerce-point-of-sale' ),
			]
		);

		// Product Grid.
		$grid = $register_object->get_grid( 'edit' );
		woocommerce_wp_select(
			[
				'id'          => 'grid',
				'value'       => $grid ? $grid : 0,
				'label'       => __( 'Product Grid', 'woocommerce-point-of-sale' ),
				'options'     => wc_pos_get_register_grid_options(),
				'desc_tip'    => true,
				'description' => __( 'Select the product grid that this register will use.', 'woocommerce-point-of-sale' ),
			]
		);

		// Grid Layout.
		woocommerce_wp_select(
			[
				'id'          => 'grid_layout',
				'value'       => $register_object->get_grid_layout( 'edit' ),
				'label'       => __( 'Grid Layout', 'woocommerce-point-of-sale' ),
				/**
				 * Register grid layouts.
				 *
				 * @since 5.0.0
				 */
				'options'     => apply_filters(
					'wc_pos_register_grid_layouts',
					[
						'grid'        => __( 'Grid', 'woocommerce-point-of-sale' ),
						'rectangular' => __( 'Rectangular', 'woocommerce-point-of-sale' ),
						'list'        => __( 'List', 'woocommerce-point-of-sale' ),
					]
				),
				'desc_tip'    => true,
				'description' => __( 'Select the layout of the grid when the register loads.', 'woocommerce-point-of-sale' ),
			]
		);

		// Receipt Template.
		$receipt = $register_object->get_receipt( 'edit' );
		woocommerce_wp_select(
			[
				'id'          => 'receipt',
				'value'       => $receipt ? $receipt : get_option( 'wc_pos_default_receipt' ),
				'label'       => __( 'Receipt Template', 'woocommerce-point-of-sale' ),
				'options'     => wc_pos_get_register_receipt_options(),
				'desc_tip'    => true,
				'description' => __( 'Select the receipt template that this register will use.', 'woocommerce-point-of-sale' ),
			]
		);

		// Prefix.
		woocommerce_wp_text_input(
			[
				'id'          => 'prefix',
				'label'       => __( 'Prefix', 'woocommerce-point-of-sale' ),
				'description' => __( 'Enter the prefix of the orders from this register.', 'woocommerce-point-of-sale' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'value'       => $register_object->get_prefix( 'edit' ),
			]
		);

		// Suffix.
		woocommerce_wp_text_input(
			[
				'id'          => 'suffix',
				'label'       => __( 'Suffix', 'woocommerce-point-of-sale' ),
				'description' => __( 'Enter the suffix of the orders from this register.', 'woocommerce-point-of-sale' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'value'       => $register_object->get_suffix( 'edit' ),
			]
		);

		// Customer.
		$customer_options = [];
		$customer_id      = $register_object->get_customer( 'edit' );
		if ( $customer_id ) {
			$customer_options = [ '' => esc_html__( 'Guest', 'woocommerce-point-of-sale' ) ];
			$customer         = get_user_by( 'id', $customer_id );

			if ( $customer ) {
				$customer_options[ $customer_id ] = implode( ' ', [ esc_html( $customer->first_name ), esc_html( $customer->last_name ) ] );
			} else {
				$register_object->set_customer( 0 );
				$register_object->save();
			}
		}
		woocommerce_wp_select(
			[
				'id'                => 'customer',
				'class'             => 'wc-customer-search short',
				'value'             => $customer_id,
				'options'           => $customer_options,
				'label'             => __( 'Customer', 'woocommerce-point-of-sale' ),
				'desc_tip'          => true,
				'description'       => __( 'Select what you want the default customer to be when the register is opened.', 'woocommerce-point-of-sale' ),
				'custom_attributes' => [
					'data-allow_clear' => 'true',
					'data-placeholder' => esc_attr__( 'Guest', 'woocommerce-point-of-sale' ),
				],
			]
		);

		// Cash Management.
		woocommerce_wp_checkbox(
			[
				'id'          => 'cash_management',
				'label'       => __( 'Cash Management', 'woocommerce-point-of-sale' ),
				'description' => __( 'Check this box if you want to manage the float of cash in the register.', 'woocommerce-point-of-sale' ),
				'desc_tip'    => false,
				'value'       => wc_bool_to_string( $register_object->get_cash_management( 'edit' ) ),
			]
		);

		// Dining Option.
		woocommerce_wp_select(
			[
				'id'          => 'dining_option',
				'value'       => $register_object->get_dining_option( 'edit' ),
				'label'       => __( 'Dining Option', 'woocommerce-point-of-sale' ),
				/**
				 * Register email receipt options.
				 *
				 * @since 5.0.0
				 */
				'options'     => apply_filters(
					'wc_pos_register_email_receipt_options',
					[
						'none'      => __( 'None', 'woocommerce-point-of-sale' ),
						'eat_in'    => __( 'Eat In', 'woocommerce-point-of-sale' ),
						'take_away' => __( 'Take Away', 'woocommerce-point-of-sale' ),
						'delivery'  => __( 'Delivery', 'woocommerce-point-of-sale' ),
					]
				),
				'desc_tip'    => true,
				'description' => __( 'Select the dining option you want to be used by default in the register.', 'woocommerce-point-of-sale' ),
			]
		);

		// Default mode.
		woocommerce_wp_select(
			[
				'id'          => 'default_mode',
				'value'       => $register_object->get_default_mode( 'edit' ),
				'label'       => __( 'Default Mode', 'woocommerce-point-of-sale' ),
				'options'     => [
					'search' => __( 'Search products', 'woocommerce-point-of-sale' ),
					'scan'   => __( 'Scan product SKU', 'woocommerce-point-of-sale' ),
				],
				'desc_tip'    => true,
				'description' => __( 'Select the default mode for this register.', 'woocommerce-point-of-sale' ),
			]
		);

		/**
		 * Register general options.
		 *
		 * @since 5.0.0
		 */
		do_action( 'wc_pos_register_options_general', $thepostid );
		?>
</div>
