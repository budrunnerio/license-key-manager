<?php
/**
 * Database Update Script for 5.0.0
 *
 * @package WooCommerce_Point_Of_Sale/Updates
 */

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/upgrade.php';

global $wpdb;

$collate = '';
if ( $wpdb->has_cap( 'collation' ) ) {
	if ( ! empty( $wpdb->charset ) ) {
		$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
	}

	if ( ! empty( $wpdb->collate ) ) {
		$collate .= " COLLATE $wpdb->collate";
	}
}

$wpdb->hide_errors();

// Flush rewrite rules.
flush_rewrite_rules();

/*
 * Drop obsolete tables:
 * - wc_poin_of_sale_tabs
 * - wc_poin_of_sale_tabs_meta
 * - wc_point_of_sale_cache
 */
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wc_poin_of_sale_tabs" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wc_poin_of_sale_tabs_meta" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wc_point_of_sale_cache" );

// Post type 'pos_temp_register_or' renamed to 'pos_temp_order'.
$wpdb->query( "UPDATE {$wpdb->posts} SET post_type='pos_temp_order' WHERE post_type='pos_temp_register_or'" );
unregister_post_type( 'pos_temp_register_or' );

// Force hide the Products Grid column in Products page.
$admins = get_users( [ 'role' => 'administrator' ] );
foreach ( $admins as $admin ) {
	$colmnshidden = (array) get_user_meta( $admin->ID, 'manageedit-productcolumnshidden', true );

	if ( ! in_array( 'wc_pos_product_grid', $colmnshidden, true ) ) {
		$colmnshidden[] = 'wc_pos_product_grid';
		update_user_meta( $admin->ID, 'manageedit-productcolumnshidden', $colmnshidden );
	}
}

/*
 * Options removed.
 */
$options_removed = [
	'pos_enabled_gateways',
	'pos_exist_gateways',
	'test_delete_me',
	'pos_removed_posts_ids',
	'pos_removed_user_ids',
	'wc_pos_print_diner_option',
];
foreach ( $options_removed as $option ) {
	delete_option( $option );
}

/*
 * Options renamed.
 * 'old_option_name' => 'new_option_name'.
 */
$options_renamed = [
	'woocommerce_pos_order_filters'             => 'wc_pos_order_filters',
	'wc_pos_my_account'                         => 'wc_pos_enable_frontend_access',
	'woocommerce_pos_tax_number'                => 'wc_pos_tax_number',
	'woocommerce_pos_end_of_sale_order_status'  => 'wc_pos_fulfilled_order_status',
	'wc_pos_save_order_status'                  => 'wc_pos_parked_order_status',
	'wc_pos_load_order_status'                  => 'wc_pos_fetch_order_statuses',
	'wc_pos_load_web_order'                     => 'wc_pos_load_website_orders',
	'wc_pos_cash_management_order_status'       => 'wc_pos_cash_management_order_statuses',
	'wc_pos_rounding'                           => 'wc_pos_enable_currency_rounding',
	'wc_pos_rounding_value'                     => 'wc_pos_currency_rounding_value',
	'woocommerce_pos_register_discount_presets' => 'wc_pos_discount_presets',
];
foreach ( $options_renamed as $old => $new ) {
	$old_option = get_option( $old );

	if ( ! empty( $old_option ) ) {
		update_option( $new, $old_option );
	}

	delete_option( $old );
}

/*
 * Create default receipt.
 */
if ( ! get_option( 'wc_pos_default_receipt', false ) ) {
	$pid = wp_insert_post(
		[
			'post_type'   => 'pos_receipt',
			'post_status' => 'publish',
			'post_title'  => __( 'Default Receipt', 'woocommerce-point-of-sale' ),
			'meta_input'  => [
				'no_copies'                   => 1,
				'width'                       => 70,
				'order_date_format'           => 'm/d/Y',
				'order_date_format'           => 'g:i a',
				'cashier_name_format'         => 'display_name',
				'show_shop_name'              => 'yes',
				'show_outlet_address'         => 'yes',
				'show_outlet_contact_details' => 'yes',
				'show_order_date'             => 'yes',
				'show_product_sku'            => 'yes',
				'show_product_cost'           => 'yes',
			],
		]
	);

	if ( $pid ) {
		update_option( 'wc_pos_default_receipt', $pid );
	}
}

/*
 * Create default outlet.
 */
if ( ! get_option( 'wc_pos_default_outlet', false ) ) {
	$default_outlet = wp_insert_post(
		[
			'post_type'   => 'pos_outlet',
			'post_status' => 'publish',
			'post_title'  => __( 'Default Outlet', 'woocommerce-point-of-sale' ),
			'meta_input'  => [
				'address_1' => WC()->countries->get_base_address(),
				'address_2' => WC()->countries->get_base_address_2(),
				'city'      => WC()->countries->get_base_city(),
				'postcode'  => WC()->countries->get_base_postcode(),
				'country'   => WC()->countries->get_base_country(),
				'state'     => '*' === WC()->countries->get_base_state() ? '' : WC()->countries->get_base_state(),
			],
		]
	);

	if ( $default_outlet ) {
		update_option( 'wc_pos_default_outlet', $default_outlet );
	}
}

/*
 * Create default register.
 */
if ( ! get_option( 'wc_pos_default_register', false ) ) {
	$pid = wp_insert_post(
		[
			'post_type'   => 'pos_register',
			'post_status' => 'publish',
			'post_title'  => __( 'Default Register', 'woocommerce-point-of-sale' ),
			'meta_input'  => [
				'receipt' => (int) get_option( 'wc_pos_default_receipt' ),
				'outlet'  => (int) get_option( 'wc_pos_default_outlet' ),
			],
		]
	);

	if ( $pid ) {
		update_option( 'wc_pos_default_register', $pid );
	}
}

// Create grid tiles table.
$sql = "
	CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wc_pos_grid_tiles (
		id BIGINT UNSIGNED NOT NULL auto_increment,
		type varchar(200) NOT NULL DEFAULT '',
		item_id BIGINT UNSIGNED NOT NULL,
		display_order BIGINT UNSIGNED NOT NULL,
		grid_id BIGINT UNSIGNED NOT NULL,
		PRIMARY KEY (id),
		KEY grid_id (grid_id)
	) $collate;
";

dbDelta( $sql );

/*
 * Update grids and tiles.
 */
$current_grids = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wc_poin_of_sale_grids" );
$map_grids     = [];

if ( $current_grids ) {
	foreach ( $current_grids as $grid ) {
		// Create a new gird of type pos_grid.
		$pid = wp_insert_post(
			[
				'post_type'   => 'pos_grid',
				'post_status' => 'publish',
				'post_title'  => $grid->name,
				'meta_input'  => [
					'sort_by' => 'name' === $grid->sort_order ? 'name' : 'custom',
				],
			]
		);

		// Map grid IDs.
		$map_grids[ intval( $grid->ID ) ] = $pid;

		// Add tiles.
		$tiles = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT product_id, style FROM {$wpdb->prefix}wc_poin_of_sale_tiles WHERE grid_id = %d ORDER BY order_position ASC",
				$grid->ID
			)
		);

		if ( $tiles ) {
			$new_grid = wc_pos_get_grid( $pid );

			foreach ( $tiles as $tile ) {
				$new_grid->add_tile(
					[
						'type'    => 'auto' === $tile->style ? 'product_cat' : 'product',
						'item_id' => $tile->product_id,
					]
				);
			}

			$new_grid->save();
		}
	}
}

/*
 * Update receipts.
 */
$current_receipts = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wc_poin_of_sale_receipts" );
$map_receipts     = [];

if ( $current_receipts ) {
	foreach ( $current_receipts as $receipt ) {
		$cashier_name_format = [
			'username'     => 'user_login',
			'display_name' => 'display_name',
			'nickname'     => 'user_nicename',
		];

		$pid = wp_insert_post(
			[
				'post_type'   => 'pos_receipt',
				'post_status' => 'publish',
				'post_title'  => $receipt->name,
				'meta_input'  => [
					'show_title'                     => 'yes',
					'title_position'                 => $receipt->title_position,
					'num_copies'                     => $receipt->print_copies_count,
					'width'                          => $receipt->receipt_width,
					'type'                           => $receipt->print_by_pos_printer,
					'logo'                           => $receipt->logo,
					'logo_position'                  => $receipt->logo_position,
					'logo_size'                      => $receipt->logo_size,
					'outlet_details_position'        => $receipt->contact_position,
					'show_shop_name'                 => $receipt->show_site_name,
					'show_outlet_name'               => $receipt->show_outlet,
					'show_outlet_address'            => $receipt->print_outlet_address,
					'show_outlet_contact_details'    => $receipt->print_outlet_contact_details,
					'social_details_position'        => $receipt->socials_display_option,
					'show_social_twitter'            => $receipt->show_twitter,
					'show_social_facebook'           => $receipt->show_facebook,
					'show_social_instagram'          => $receipt->show_instagram,
					'show_social_snapchat'           => $receipt->show_snapchat,
					'show_wifi_details'              => $receipt->print_wifi,
					'show_tax_number'                => $receipt->print_tax_number,
					'tax_number_label'               => $receipt->tax_number_label,
					'tax_number_position'            => $receipt->tax_number_position,
					'show_order_date'                => $receipt->print_order_time,
					'order_date_format'              => $receipt->order_date_format,
					'order_time_format'              => 'g:i a',
					'show_customer_name'             => $receipt->print_customer_name,
					'show_customer_email'            => $receipt->print_customer_email,
					'show_customer_phone'            => $receipt->print_customer_phone,
					'show_customer_shipping_address' => $receipt->print_customer_ship_address,
					'show_cashier_name'              => $receipt->print_server,
					'show_register_name'             => $receipt->show_register,
					'cashier_name_format'            => $cashier_name_format[ $receipt->served_by_type ],
					'show_product_image'             => $receipt->show_image_product,
					'show_product_sku'               => $receipt->show_sku,
					'show_product_cost'              => $receipt->show_cost,
					'show_product_discount'          => $receipt->show_discount,
					'show_no_items'                  => $receipt->print_number_items,
					'show_tax_summary'               => $receipt->tax_summary,
					'show_order_barcode'             => $receipt->print_barcode,
					'text_size'                      => $receipt->text_size,
					'header_text'                    => $receipt->header_text,
					'footer_text'                    => $receipt->footer_text,
					'custom_css'                     => $receipt->custom_css,
				],
			]
		);

		$map_receipts[ intval( $receipt->ID ) ] = $pid;
	}
}

/*
 * Update outlets.
 */
$current_outlets = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wc_poin_of_sale_outlets" );
$map_outlets     = [];

if ( $current_outlets ) {
	foreach ( $current_outlets as $outlet ) {
		$contact = json_decode( $outlet->contact );
		$social  = json_decode( $outlet->social );

		$pid = wp_insert_post(
			[
				'post_type'   => 'pos_outlet',
				'post_status' => 'publish',
				'post_title'  => $outlet->name,
				'meta_input'  => [
					'address_1'       => $contact->address_1,
					'address_2'       => $contact->address_2,
					'city'            => $contact->city,
					'postcode'        => $contact->postcode,
					'country'         => $contact->country,
					'state'           => $contact->state,
					'email'           => $social->email,
					'phone'           => $social->phone,
					'fax'             => $social->fax,
					'website'         => $social->website,
					'wifi_network'    => $contact->wifi_network,
					'wifi_password'   => $contact->wifi_password,
					'social_accounts' => [
						'twitter'   => str_replace( '@', '', $social->twitter ),
						'facebook'  => $social->facebook,
						'instagram' => $social->instagram,
						'snapchat'  => $social->snapchat,
					],
				],
			]
		);

		$map_outlets[ intval( $outlet->ID ) ] = $pid;
	}
}

/*
 * Update registers.
 */
$current_registers = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wc_poin_of_sale_registers" );

if ( $current_registers ) {
	foreach ( $current_registers as $register ) {
		$detail   = json_decode( $register->detail );
		$settings = json_decode( $register->settings );

		$pid = wp_insert_post(
			[
				'post_type'   => 'pos_register',
				'post_status' => 'publish',
				'post_title'  => $register->name,
				'meta_input'  => [
					'date_opened'     => wc_string_to_timestamp( get_gmt_from_date( gmdate( 'Y-m-d H:i:s', wc_string_to_timestamp( $register->opened ) ) ) ),
					'date_closed'     => wc_string_to_timestamp( get_gmt_from_date( gmdate( 'Y-m-d H:i:s', wc_string_to_timestamp( $register->closed ) ) ) ),
					'open_last'       => $register->_edit_last,
					'temp_order'      => $register->order_id,
					'grid'            => ( 'all' === $detail->grid_template || 'categories' === $detail->grid_template ) ? 0 : $map_grids[ intval( $detail->grid_template ) ],
					'receipt'         => $map_receipts[ intval( $detail->receipt_template ) ],
					'prefix'          => $detail->prefix,
					'suffix'          => $detail->suffix,
					'outlet'          => $map_outlets[ intval( $register->outlet ) ],
					'customer'        => empty( $register->default_customer ) ? 0 : $register->default_customer,
					'cash_management' => '1' === $detail->float_cash_management ? 'yes' : 'no',
					'dining_option'   => $detail->dining_option_default,
					'change_user'     => '1' === $settings->change_user ? 'yes' : 'no',
					'email_receipt'   => '0' === $settings->email_receipt ? 'no' : ( '1' === $settings->email_receipt ? 'all' : 'non_guest' ),
					'print_receipt'   => '1' === $settings->print_receipt ? 'yes' : 'no',
					'gift_receipt'    => '1' === $settings->gift_receipt ? 'yes' : 'no',
					'note_request'    => '0' === $settings->note_request ? 'none' : ( '1' === $settings->note_request ? 'on_save' : 'on_all_sales' ),
				],
			]
		);

		if ( $pid ) {
			// Update associated orders.
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = 'wc_pos_id_register' AND meta_value = %s ",
					$pid,
					$register->ID
				)
			);
		}
	}
}

// Now drop the tables.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wc_poin_of_sale_grids" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wc_poin_of_sale_tiles" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wc_poin_of_sale_receipts" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wc_poin_of_sale_outlets" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wc_poin_of_sale_registers" );

// Update user outlets.
$user_outlets = $wpdb->get_results( "SELECT user_id AS id, meta_value AS outlets FROM {$wpdb->usermeta} WHERE meta_key = 'outlet'" );
if ( $user_outlets ) {
	foreach ( $user_outlets as $user ) {
		$outlets = maybe_unserialize( $user->outlets );
		$outlets = is_array( $outlets ) ? $outlets : [ $outlets ];
		$outlets = array_map(
			function( $outlet ) use ( $map_outlets ) {
				return isset( $map_outlets[ intval( $outlet ) ] ) ? $map_outlets[ intval( $outlet ) ] : $outlet;
			},
			$outlets
		);

		update_user_meta( $user->id, 'outlet', $outlets );
	}
}

// Assign installing user to the default outlet if they are not assigned to any.
if ( isset( $default_outlet ) && empty( get_user_meta( get_current_user_id(), 'outlet', true ) ) ) {
	update_user_meta( get_current_user_id(), 'outlet', [ (string) $default_outlet ] );
}
