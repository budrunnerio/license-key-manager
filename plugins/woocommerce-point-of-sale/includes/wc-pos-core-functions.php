<?php
/**
 * Core Functions
 *
 * General core functions available on both the front-end and admin.
 *
 * @todo All functions here must have the wc_pos prefix. We may also need to group related functions
 *  in one single wc-pos-[group]-functions.php file.
 *
 * @package WooCommerce_Point_Of_Sale/Functions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns the order statuses with the wc- prefix stripped off.
 *
 * @since 5.0.0
 * @return array
 */
function wc_pos_get_order_statuses_no_prefix() {
	foreach ( wc_get_order_statuses() as $key => $value ) {
		$statuses[ substr( $key, 3 ) ] = $value;
	}

	return $statuses;
}

/**
 * Returns the payment gateway IDes.
 *
 * @since 5.0.0
 *
 * @param boolean $available Only return the available (enabled) gateways.
 * @return array List of payment gateways IDs.
 */
function wc_pos_get_payment_gateways_ids( $available = false ) {
	$gateways = WC()->payment_gateways()->payment_gateways();
	$results  = [];

	foreach ( $gateways as $id => $gateway ) {
		if ( $available && 'yes' !== $gateway->enabled ) {
			continue;
		}

		array_push( $results, $id );
	}

	return $results;
}

/**
 * Get all the screen ids that are created/modified by the plugin.
 *
 * @since 5.0.0
 * @return array
 */
function wc_pos_get_screen_ids() {
	$wc_pos_screen_id = WC_POS()->plugin_screen_id();

	$screen_ids = [
		'toplevel_page_' . $wc_pos_screen_id,
		$wc_pos_screen_id . '_page_wc-pos-barcodes',
		$wc_pos_screen_id . '_page_wc-pos-stock-controller',
		'edit-shop_order',
		'edit-product',
		'edit-pos_register',
		'edit-pos_outlet',
		'edit-pos_grid',
		'edit-pos_receipt',
		'edit-pos_report',
		'shop_order',
		'product',
		'pos_register',
		'pos_outlet',
		'pos_grid',
		'pos_receipt',
		'pos_report',
		'profile',
		'user-edit',
	];

	/**
	 * Screen IDs.
	 *
	 * @since 5.0.0
	 */
	return apply_filters( 'wc_pos_screen_ids', $screen_ids );
}

/**
 * Check if a specific post is the default one.
 *
 * @param int    $post_id   Post ID.
 * @param string $post_type Post type.
 *
 * @return bool
 */
function wc_pos_is_default_post( $post_id, $post_type ) {
	if ( ! in_array( $post_type, [ 'pos_register', 'pos_outlet', 'pos_receipt' ] ) ) {
		return false;
	}

	return (int) get_option( 'wc_pos_default_' . str_replace( 'pos_', '', $post_type ), 0 ) === (int) $post_id ? true : false;
}

function wc_pos_get_outlet_location( $id_register = 0 ) {
	$location = [];
	if ( ! $id_register && ! isset( $_GET['register'] ) ) {
		return $location;
	}

	$register_id = $id_register > 0 ? $id_register : wc_clean( $_GET['register'] );
	$register    = wc_pos_get_register( $register_id );

	if ( $register ) {
		$location = WC_POS_App::instance()->get_outlet_data( $register->get_outlet() );
	}

	return $location;
}

function wc_pos_get_shop_location() {
	return [
		'country'  => WC()->countries->get_base_country(),
		'state'    => WC()->countries->get_base_state(),
		'postcode' => WC()->countries->get_base_postcode(),
		'city'     => WC()->countries->get_base_city(),
	];
}

/**
 * Returns a list of registers that are assigned to a specific outlet.
 *
 * @param int|string $outlet_id Outlet ID.
 * @return array List of register IDs.
 */
function wc_pos_get_registers_by_outlet( $outlet_id = 0 ) {
	$registers = [];

	$get_posts = get_posts(
		[
			'numberposts' => -1,
			'post_type'   => 'pos_register',
			'meta_key'    => 'outlet',
			'meta_value'  => $outlet_id,
		]
	);

	foreach ( $get_posts as $post ) {
		$registers[] = $post->ID;
	}

	return $registers;
}

function wc_pos_is_register_page() {
	global $wp;

	if ( isset( $wp->query_vars ) ) {
		$q = $wp->query_vars;

		if ( isset( $q['page'] ) && 'wc-pos-register' === $q['page'] && isset( $q['action'] ) && 'view' === $q['action'] ) {
			return true;
		}
	}

	return false;
}

function wc_pos_get_available_payment_gateways( $pos = false ) {
	$available_gateways = [];
	foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $gateway ) {
		if ( 'pos_' === substr( $gateway->id, 0, 4 ) ) {
			array_push(
				$available_gateways,
				(object) [
					'id'    => $gateway->id,
					'title' => $gateway->get_title(),
				]
			);
		}
	}

	return $available_gateways;
}

function wc_pos_is_pos_referer() {
	$referer = wp_get_referer();
	$pos_url = get_home_url() . '/point-of-sale/';
	$headers = wc_pos_getallheaders();

	if ( isset( $headers['X-Pos-Id'] ) ) {
		return true;
	}

	if ( ! $referer ) {
		if ( isset( $_SERVER['HTTP_REFERER'] ) && strpos( wc_clean( wp_unslash( $_SERVER['HTTP_REFERER'] ) ), 'point-of-sale' ) !== false ) {
			return true;
		};

		// Very rare case: could not get referer info for some reason such as it's being
		// stripped out by a proxy, firewall, etc. Check wc-pos namespace in REQUEST_URI.
		if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( wc_clean( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'wp-json/wc-pos' ) ) {
			return true;
		}
	}

	$parsed = wp_parse_url( $referer );

	if ( isset( $parsed['port'] ) ) {
		$pos_url = $parsed['scheme'] . '://' . $parsed['host'] . ':' . $parsed['port'] . $parsed['path'];
	}

	if ( strpos( $referer, $pos_url ) !== false ) {
		return true;
	}

	return false;
}

function wc_pos_get_env() {
	$connection = @fsockopen( 'localhost', '4560', $errno, $errstr, 1 );

	if ( is_resource( $connection ) ) {
		fclose( $connection );
		return 'development';
	}

	return 'production';
}

/**
 * Returns all the sent HTTP hearders.
 *
 * @since 5.1.1
 * @return array Array of headers.
 */
function wc_pos_getallheaders() {
	$headers = [];

	foreach ( $_SERVER as $name => $value ) {
		if ( substr( $name, 0, 5 ) == 'HTTP_' ) {
			$headers[ str_replace( ' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $name, 5 ) ) ) ) ) ] = $value;
		}
	}

	return $headers;
}

/**
 * Use file_get_contents() with custom SSL context options to avoid potential read failure.
 *
 * @todo Pass the other params and merge context options.
 * @return string|false
 */
function wc_pos_file_get_contents( $filename ) {
	$context = stream_context_create(
		[
			'ssl' => [
				'verify_peer'      => false,
				'verify_peer_name' => false,
			],
		]
	);

	return file_get_contents( $filename, false, $context );
}

/**
 * Use get_headers() with custom SSL context options to avoid potential read failure.
 *
 * @return array|false
 */
function wc_pos_get_headers( $url, $format = 0 ) {
	$ssl_defaults = [
		'ssl' => [
			'verify_peer'      => true,
			'verify_peer_name' => true,
		],
	];
	$ssl_options  = [
		'ssl' => [
			'verify_peer'      => false,
			'verify_peer_name' => false,
		],
	];

	stream_context_set_default( $ssl_options );
	$headers = get_headers( $url, $format );
	stream_context_set_default( $ssl_defaults );

	return $headers;
}

/**
 * Sends success JSON response.
 *
 * @param string   $body              Response body.
 * @param int    [ $status_code=200] Status code.
 */
function wc_pos_send_json_success( $body, $status_code = 200 ) {
	wp_send_json( $body, $status_code );
}

/**
 * Sends error JSON response.
 *
 * @param string [ $message='']      Message to be associated with the response.
 * @param string [ $code='']         Code that represents the type of response.
 * @param int    [ $status_code=500] Status code.
 */
function wc_pos_send_json_error( $message = '', $code = '', $status_code = 500 ) {
	wp_send_json(
		[
			'message' => $message,
			'code'    => $code,
		],
		$status_code
	);
}

/**
 * Wrapper function for check_ajax_referer() that sends a meaningful error message on failure.
 *
 * @param string  $action    Action nonce.
 * @param string  $query_arg Key to check for the nonce in `$_REQUEST`.
 * @param boolean $die       Whether to die early if the nonce is invalid.
 */
function wc_pos_check_ajax_referer( $action, $query_arg = 'security', $die = false ) {
	if ( $die ) {
		check_ajax_referer( $action, $query_arg, $die );
	}

	if ( ! check_ajax_referer( $action, $query_arg, false ) ) {
		wc_pos_send_json_error( __( 'Invalid nonce.', 'woocommerce-point-of-sale' ), 'invalid_nonce' );
	}
}


function wc_pos_get_default_customer_statuses() {
	/**
	 * Default customer statuses.
	 *
	 * @since 6.0.4
	 */
	return apply_filters(
		'wc_pos_default_customer_statuses',
		[
			'lead'                      => __( 'Prospective/Lead', 'woocommerce-point-of-sale' ),
			'qualified_lead'            => __( 'Qualified Lead', 'woocommerce-point-of-sale' ),
			'customer'                  => __( 'Customer', 'woocommerce-point-of-sale' ),
			'active_customer'           => __( 'Active Customer', 'woocommerce-point-of-sale' ),
			'up_cross_sell_opportunity' => __( 'Upsell/Cross-sell opportunity', 'woocommerce-point-of-sale' ),
			'high_value_customer'       => __( 'High-Value Customer', 'woocommerce-point-of-sale' ),
			'open_issue'                => __( 'Open Issue', 'woocommerce-point-of-sale' ),
			'vip'                       => __( 'VIP Customer', 'woocommerce-point-of-sale' ),
			'lost_opportunity'          => __( 'Lost Opportunity', 'woocommerce-point-of-sale' ),
		]
	);
}

function wc_pos_custom_orders_table_usage_is_enabled() {
	if (
		class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' )
		&& Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
	) {
		return true;
	}

	return false;
}
