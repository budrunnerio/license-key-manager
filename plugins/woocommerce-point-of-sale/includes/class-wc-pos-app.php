<?php
/**
 * Responsible for the POS front-end
 *
 * @package WooCommerce_Point_Of_Sale/Classes
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_POS_App', false ) ) {
	return new WC_POS_App();
}

/**
 * WC_POS_App.
 */
class WC_POS_App {
	/**
	 * The single instance of the class.
	 *
	 * @var WC_POS_App
	 */
	protected static $instance = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'template_redirect', [ $this, 'template_redirect' ] );
		add_action( 'rest_api_init', [ $this, 'wc_api_init' ], 11 );
		add_action( 'rest_api_init', [ $this, 'wc_api_loaded' ], 12 );
		add_action( 'rest_api_init', [ $this, 'wc_api_classes' ], 15 );
		add_action( 'option_woocommerce_stripe_settings', [ $this, 'woocommerce_stripe_settings' ], 100, 1 );
		add_action( 'init', [ $this, 'wc_pos_checkout_gateways' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'assets' ], PHP_INT_MAX );
		add_filter( 'style_loader_tag', [ $this, 'filter_asset_tags' ], PHP_INT_MAX, 2 );
		add_filter( 'script_loader_tag', [ $this, 'filter_asset_tags' ], PHP_INT_MAX, 2 );
		add_action( 'plugins_loaded', [ $this, 'init_addons_hooks' ], 99999 );
		add_filter( 'woocommerce_available_payment_gateways', [ $this, 'available_payment_gateways' ], 100, 1 );
	}

	/**
	 * Load POS assets and dequeue everything else.
	 */
	public function assets() {
		if ( wc_pos_is_register_page() ) {
			global $wp, $wp_scripts, $wp_styles;

			// Filter enqueued scripts to include only the scripts that have 'wc-pos-app' in deps.
			$wp_scripts->queue = array_filter(
				$wp_scripts->queue,
				function( $handle ) use ( $wp_scripts ) {
					$script = $wp_scripts->registered[ $handle ];

					return $script->deps && count( array_intersect( $script->deps, [ 'wc-pos-app' ] ) );
				}
			);

			// Filter enqueued styles to include only the styles that have 'wc-pos-app' in deps.
			$wp_styles->queue = array_filter(
				$wp_styles->queue,
				function( $handle ) use ( $wp_styles ) {
					$style = $wp_styles->registered[ $handle ];

					return $style->deps && in_array( 'wc-pos-app', $style->deps );
				}
			);

			$register = wc_pos_get_register( $wp->query_vars['register'] );
			$session  = wc_pos_get_session( $register->get_current_session() );

			if ( 'development' === wc_pos_get_env() ) {
				wp_enqueue_script( 'wc-pos-app', 'http://localhost:4560/.quasar/client-entry.js', [], WC_POS_VERSION );
			} else {
				wp_enqueue_style( 'wc-pos-app', WC_POS()->plugin_url() . '/client/dist/assets/index.css', [], WC_POS_VERSION );

				// Unsetting the version for index.js as Vite reloads the file twice (in build) if '?ver=x.x.x' is appended.
				// @todo Report the issue on github.com/vitejs/vite
				// Defining the null value as a variable to overcome PHPCS validation that requires the version to be set.
				$version = null;
				wp_enqueue_script( 'wc-pos-app', WC_POS()->plugin_url() . '/client/dist/index.js', [], $version );
			}

			wp_add_inline_script( 'wc-pos-app', 'window.Nonces = ' . json_encode( $this->get_nonces() ) . ';', 'before' );
			wp_add_inline_script( 'wc-pos-app', 'window.UserData = ' . json_encode( $this->get_current_user() ) . ';', 'before' );
			wp_add_inline_script( 'wc-pos-app', 'window.SessionData = ' . json_encode( $this->get_session_data( $register->get_current_session() ) ) . ';', 'before' );
			wp_add_inline_script( 'wc-pos-app', 'window.AppData = ' . json_encode( $this->get_app_data() ) . ';', 'before' );

			// Hack.
			add_filter( 'script_loader_tag', [ $this, 'set_script_type' ], PHP_INT_MAX, 3 );
		}
	}

	public function set_script_type( $tag, $handle, $src ) {
		if ( 'wc-pos-app' === $handle ) {
			preg_match_all( '/<script[^>]*>(.*?)<\/script>/is', $tag, $matches );

			$id    = $handle . '-js';
			$parts = explode( '</script>', $tag );

			foreach ( $parts as $index => $part ) {
				if ( false !== strpos( $part, $src ) ) {
					$parts[ $index ]  = '<'; // To trick PHPCS rule: WordPress.WP.EnqueuedResources.NonEnqueuedScript.
					$parts[ $index ] .= 'script type="module" crossorigin src="' . esc_url( $src ) . '" id="' . esc_attr( $id ) . '">';
				}
			}

			return implode( '</script>', $parts );
		}

		return $tag;
	}

	public function get_current_user() {
		return wc_pos_normalize_user_data( wp_get_current_user() );
	}

	public function get_nonces() {
		/**
		 * Nonces array.
		 *
		 * @since 6.0.0
		 */
		return apply_filters(
			'wc_pos_nonces',
			[
				'auth_user'          => wp_create_nonce( 'auth-user' ),
				'check_db_changes'   => wp_create_nonce( 'check-db-changes' ),
				'check_updates'      => wp_create_nonce( 'check-updates' ),
				'close_register'     => wp_create_nonce( 'close-register' ),
				'delete_sync_cache'  => wp_create_nonce( 'delete-sync-cache' ),
				'open_register'      => wp_create_nonce( 'open-register' ),
				'print_receipt'      => wp_create_nonce( 'print-receipt' ), // @todo rename to generate-receipt
				'session_data'       => wp_create_nonce( 'session-data' ),
				'set_cash_data'      => wp_create_nonce( 'set-cash-data' ),
				'set_session_lock'   => wp_create_nonce( 'set-session-lock' ),
				'take_over_register' => wp_create_nonce( 'take-over-register' ),
				'update_option'      => wp_create_nonce( 'update-option' ),
				'wp_rest'            => wp_create_nonce( 'wp_rest' ),
			]
		);
	}

	public function get_session_data( $id ) {
		global $wp;

		$session = wc_pos_get_session( $id );

		if ( ! $id || ! $session || ! is_a( $session, 'WC_POS_Session' ) ) {
			$register = wc_pos_get_register( $wp->query_vars['register'] );
			$session  = new WC_POS_Session();
			$session->save();

			if ( $register && is_a( $register, 'WC_POS_Register' ) ) {
				$register->set_current_session( $session->get_id() );
				$register->save();
			}
		}

		$session_data = [];
		if ( $session ) {
			$session_data = [
				'id'                 => $session->get_id(),
				'date_opened'        => $session->get_date_opened() ? gmdate( 'Y-m-d H:i:s', $session->get_date_opened()->getTimestamp() ) : null,
				'date_closed'        => $session->get_date_closed() ? gmdate( 'Y-m-d H:i:s', $session->get_date_opened()->getTimestamp() ) : null,
				'open_first'         => $session->get_open_first(),
				'open_last'          => $session->get_open_last(),
				'locked'             => $session->get_locked(),
				'opening_cash_total' => $session->get_opening_cash_total(),
				'opening_note'       => $session->get_opening_note(),
				'closing_note'       => $session->get_closing_note(),
				'counted_totals'     => (object) $session->get_counted_totals(),
				'totals'             => wc_pos_get_session_totals( $session->get_id() ),
			];
		}

		/**
		 * Session data.
		 *
		 * @since 5.0.0
		 */
		return apply_filters( 'wc_pos_session_data', $session_data );
	}

	/**
	 * Returns back-end data for use in the front-end app.
	 *
	 * @return array
	 */
	public function get_app_data() {
		global $wp;
		global $wp_roles;

		$register_data = $this->get_register_data( $wp->query_vars['register'] );
		$outlet_data   = $this->get_outlet_data( $wp->query_vars['outlet'] );
		$receipt_data  = $this->get_receipt_data( $register_data['receipt'] );
		$grid_data     = $this->get_grid_data( $register_data['grid'] );

		$user_roles = [];
		foreach ( $wp_roles->roles as $role_name => $role_details ) {
			$user_roles[ $role_name ] = $role_details['name'];
		}

		$customer_required_fields = array_merge(
			get_option(
				'wc_pos_customer_create_required_fields',
				[
					'billing_address_1',
					'billing_city',
					'billing_state',
					'billing_postcode',
					'billing_country',
					'billing_phone',
				]
			),
			[
				'billing_first_name',
				'billing_last_name',
				'billing_email',
			]
		);

		/**
		 * Hidden order item meta.
		 *
		 * @since 4.0.0
		 */
		$hidden_order_itemmeta = apply_filters(
			'woocommerce_hidden_order_itemmeta',
			[
				'_qty',
				'_tax_class',
				'_product_id',
				'_variation_id',
				'_line_subtotal',
				'_line_subtotal_tax',
				'_line_total',
				'_line_tax',
				'method_id',
				'cost',
				'_reduced_stock',
				'_uom_unit',
				// WooCommerce Cost of Goods by SkyVerge.
				'_wc_cog_item_cost',
				'_wc_cog_item_total_cost',
				// Cost of Goods for WooCommerce by The Rite Sites.
				'_cog_wc_order_item_cost',
				'_cog_wc_order_item_cost_total',
			]
		);

		$fetch_order_statuses = get_option( 'wc_pos_fetch_order_statuses', [ 'pending' ] );
		$fetch_order_statuses = empty( $fetch_order_statuses ) ? [ 'pending' ] : $fetch_order_statuses;

		$logo_url = wp_get_attachment_image_src( get_option( 'wc_pos_theme_logo' ), 0 );
		$logo_url = $logo_url ? $logo_url[0] : WC_POS()->plugin_url() . '/assets/dist/images/pos-icon.svg';

		$tax_classes = array_map(
			function( $class ) {
				return [
					'slug' => sanitize_title( $class ),
					'name' => $class,
				];
			},
			WC_Tax::get_tax_classes()
		);

		$order_statuses = wc_pos_get_order_statuses_no_prefix();
		/**
		 * Fulfilled order statuses.
		 *
		 * @since 4.0.0
		 */
		$fulfilled_order_statuses = apply_filters( 'wc_pos_fulfilled_order_statuses', $order_statuses );

		/**
		 * Parked order statuses.
		 *
		 * @since 4.0.0
		 */
		$parked_order_statuses = apply_filters( 'wc_pos_parked_order_statuses', $order_statuses );

		$fulfilled_order_default_status     = get_option( 'wc_pos_fulfilled_order_default_status', 'processing' );
		$fulfilled_order_alternative_status = get_option( 'wc_pos_fulfilled_order_alternative_status', 'completed' );
		$parked_order_default_status        = get_option( 'wc_pos_parked_order_default_status', 'on-hold' );
		$parked_order_alternative_status    = get_option( 'wc_pos_parked_order_alternative_status', 'pending' );

		if ( ! isset( $fulfilled_order_statuses[ $fulfilled_order_default_status ] ) ) {
			$fulfilled_order_default_status = 'processing';
		}

		if ( ! isset( $fulfilled_order_statuses[ $fulfilled_order_alternative_status ] ) ) {
			$fulfilled_order_alternative_status = 'completed';
		}

		if ( ! isset( $parked_order_statuses[ $parked_order_default_status ] ) ) {
			$parked_order_default_status = 'on-hold';
		}

		if ( ! isset( $parked_order_statuses[ $parked_order_alternative_status ] ) ) {
			$parked_order_alternative_status = 'pending';
		}

		// @todo FIXME: DRY
		$default_discount_reasons = [
			__( 'Wastage', 'woocommerce-point-of-sale' ),
			__( 'Damaged', 'woocommerce-point-of-sale' ),
			__( 'Manager Approved', 'woocommerce-point-of-sale' ),
			__( 'General Discount', 'woocommerce-point-of-sale' ),
			__( 'Student Discount', 'woocommerce-point-of-sale' ),
			__( 'Member Discount', 'woocommerce-point-of-sale' ),
		];

		$checkout_order_fields    = $this->prepare_checkout_fields( WC()->checkout->get_checkout_fields( 'order' ) );
		$checkout_billing_fields  = $this->prepare_checkout_fields( WC()->checkout->get_checkout_fields( 'billing' ) );
		$checkout_shipping_fields = $this->prepare_checkout_fields( WC()->checkout->get_checkout_fields( 'shipping' ) );

		/**
		 * App data.
		 *
		 * @since 6.0.0
		 */
		return apply_filters(
			'wc_pos_app_data',
			[
				'php'  => [
					'localeconv' => localeconv(),
					'version'    => PHP_VERSION,
				],

				'wp'   => [
					'admin_url'   => admin_url(),
					'ajax_url'    => admin_url( 'admin-ajax.php' ),
					'avatar'      => function_exists( 'get_avatar_url' ) ? get_avatar_url( 0, [ 'size' => 64 ] ) : '',
					'date_format' => get_option( 'date_format' ),
					'gmt_offset'  => get_option( 'gmt_offset' ),
					'locale'      => get_locale(),
					'rest_url'    => get_rest_url(),
					'site_name'   => get_bloginfo( 'name' ),
					'time_format' => get_option( 'time_format' ),
				],

				'wc'   => [
					/**
					 * Adjust non-base location prices.
					 *
					 * @since 5.0.0
					 */
					'adjust_non_base_location_prices' => apply_filters( 'woocommerce_adjust_non_base_location_prices', true ),
					'address_formats'                 => WC()->countries->get_address_formats(),
					'allowed_countries'               => $this->normalize_countries( WC()->countries->get_allowed_countries() ),
					/**
					 * Apply base tax for local pickup.
					 *
					 * @since 5.0.0
					 */
					'apply_base_tax_for_local_pickup' => apply_filters( 'woocommerce_apply_base_tax_for_local_pickup', true ),
					'base_address_1'                  => WC()->countries->get_base_address(),
					'base_address_2'                  => WC()->countries->get_base_address_2(),
					'base_city'                       => WC()->countries->get_base_city(),
					'base_country'                    => WC()->countries->get_base_country(),
					'base_location'                   => wc_pos_get_shop_location(),
					'base_postcode'                   => WC()->countries->get_base_postcode(),
					'base_state'                      => '*' === WC()->countries->get_base_state() ? '' : WC()->countries->get_base_state(),
					'calc_discounts_sequentially'     => 'yes' === get_option( 'woocommerce_calc_discounts_sequentially', 'no' ),
					/**
					 * Hide zero taxes.
					 *
					 * @since 5.0.0
					 */
					'cart_hide_zero_taxes'            => apply_filters( 'woocommerce_cart_hide_zero_taxes', true ),
					/**
					 * Zero-taxes rate ID
					 *
					 * @since 5.0.0
					 */
					'cart_remove_taxes_zero_rate_id'  => apply_filters( 'woocommerce_cart_remove_taxes_zero_rate_id', 'zero-rated' ),
					'cash_denominations'              => (array) get_option( 'wc_pos_cash_denominations', [] ),
					'checkout_order_fields'           => $checkout_order_fields,
					'checkout_billing_fields'         => $checkout_billing_fields,
					'checkout_shipping_fields'        => $checkout_shipping_fields,
					'continents'                      => WC()->countries->get_continents(),
					'countries'                       => $this->normalize_countries( WC()->countries->get_countries() ),
					'coupons_enabled'                 => wc_coupons_enabled(),
					'currency'                        => get_option( 'woocommerce_currency' ),
					'currency_format'                 => esc_attr( str_replace( [ '%1$s', '%2$s' ], [ '%s', '%v' ], get_woocommerce_price_format() ) ), // For accounting JS
					'currency_format_symbol'          => html_entity_decode( get_woocommerce_currency_symbol() ),
					'currency_symbols'                => get_woocommerce_currency_symbols(),
					'default_country'                 => get_option( 'woocommerce_default_country' ),
					'default_customer_address'        => get_option( 'woocommerce_default_customer_address' ),
					'default_product_cat'             => (int) get_option( 'default_product_cat', 0 ),
					'discount_rounding_mode'          => self::get_discount_rounding_mode_string( WC_DISCOUNT_ROUNDING_MODE ),
					/**
					 * Local picup methods.
					 *
					 * @since 5.0.0
					 */
					'local_pickup_methods'            => apply_filters( 'woocommerce_local_pickup_methods', [ 'legacy_local_pickup', 'local_pickup' ] ),
					'manage_stock'                    => 'yes' === get_option( 'woocommerce_manage_stock' ),
					'placeholder_img_src'             => wc_placeholder_img_src(),
					'price_decimal_separator'         => wc_get_price_decimal_separator(),
					'price_decimals'                  => wc_get_price_decimals(),
					'price_display_suffix'            => get_option( 'woocommerce_price_display_suffix' ),
					'price_format'                    => get_woocommerce_price_format(),
					'price_thousand_separator'        => wc_get_price_thousand_separator(),
					/**
					 * Trim zeros in prices.
					 *
					 * @since 5.0.0
					 */
					'price_trim_zeros'                => apply_filters( 'woocommerce_price_trim_zeros', false ),
					'prices_include_tax'              => wc_prices_include_tax(),
					'registration_generate_password'  => 'yes' === get_option( 'woocommerce_registration_generate_password', 'yes' ),
					'registration_generate_username'  => 'yes' === get_option( 'woocommerce_registration_generate_username', 'yes' ),
					'rounding_precision'              => wc_get_rounding_precision(),
					'shipping_classes'                => $this->get_shipping_classes(),
					'shipping_countries'              => $this->normalize_countries( WC()->countries->get_shipping_countries() ),
					'shipping_enabled'                => wc_shipping_enabled(),
					'shipping_tax_class'              => get_option( 'woocommerce_shipping_tax_class' ),
					'shipping_zones'                  => $this->get_shipping_zones(),
					'tax_based_on'                    => get_option( 'woocommerce_tax_based_on' ),
					'tax_classes'                     => $tax_classes,
					'tax_display_cart'                => get_option( 'woocommerce_tax_display_cart' ),
					'tax_display_shop'                => get_option( 'woocommerce_tax_display_shop' ),
					'tax_enabled'                     => wc_tax_enabled(),
					'tax_rates'                       => $this->get_tax_rates(),
					'tax_round_at_subtotal'           => 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ),
					'tax_round_half_up'               => self::tax_round_half_up(),
					'tax_total_display'               => get_option( 'woocommerce_tax_total_display' ),
					'hpos_enabled'                    => wc_pos_custom_orders_table_usage_is_enabled(),
				],

				'pos'  => [
					'after_add_to_cart_behavior'           => get_option( 'wc_pos_after_add_to_cart_behavior', 'home' ),
					'allow_scanning_out_of_stock_products' => 'yes' === get_option( 'wc_pos_allow_scanning_out_of_stock_products', 'no' ),
					'auto_logout'                          => (int) get_option( 'wc_pos_auto_logout', 0 ),
					'beep_sound'                           => WC_POS()->plugin_url() . '/assets/dist/sounds/beep.mp3',
					'cache_coupons'                        => 'yes' === get_option( 'wc_pos_cache_coupons', 'yes' ),
					'cache_customers'                      => 'yes' === get_option( 'wc_pos_cache_customers', 'no' ),
					'camera_scanning'                      => 'yes' === get_option( 'wc_pos_camera_scanning', 'no' ),
					'currency_rounding'                    => 'yes' === get_option( 'wc_pos_enable_currency_rounding' ),
					'currency_rounding_value'              => (float) get_option( 'wc_pos_currency_rounding_value', 0.01 ),
					'custom_checkout_fields'               => 'yes' === get_option( 'wc_pos_custom_checkout_fields', 'no' ),
					'custom_product_id'                    => (int) get_option( 'wc_pos_custom_product_id', 0 ),
					'custom_product_required_fields'       => (array) get_option( 'wc_pos_custom_product_required_fields', [] ),
					'customer_required_fields'             => $customer_required_fields,
					'customer_status_field'                => get_option( 'wc_pos_customer_status_field' ),
					'customer_statuses'                    => wc_pos_get_default_customer_statuses(),
					'default_country'                      => get_option( 'wc_pos_default_country' ), // @todo rename option to wc_pos_default_customer_country
					'default_outlet_id'                    => (int) get_option( 'wc_pos_default_outlet', 0 ),
					'disable_status_selection'             => 'yes' === get_option( 'wc_pos_disable_status_selection', 'no' ),
					'disable_transitions_effects'          => 'yes' === get_option( 'wc_pos_disable_transitions_effects', 'no' ),
					'discount_presets'                     => (array) get_option( 'wc_pos_discount_presets', [ '5', '10', '15', '20' ] ),
					'discount_reasons'                     => (array) get_option( 'wc_pos_discount_reasons', $default_discount_reasons ),
					'display_product_attributes'           => get_option( 'wc_pos_display_product_attributes', [] ),
					'enable_dining'                        => 'yes' === get_option( 'wc_pos_enable_dining', 'no' ),
					'enable_pos_visibility'                => 'yes' === get_option( 'wc_pos_visibility', 'no' ),
					'enable_user_card'                     => 'yes' === get_option( 'wc_pos_enable_user_card', 'no' ),
					'enable_weight_embedded_barcodes'      => 'yes' === get_option( 'wc_pos_enable_weight_embedded_barcodes', 'no' ),
					'env'                                  => wc_pos_get_env(),
					'fetch_order_statuses'                 => $fetch_order_statuses,
					'force_end_wp_session'                 => 'yes' === get_option( 'wc_pos_force_end_wp_session', 'no' ),
					'force_logout'                         => 'yes' === get_option( 'wc_pos_force_logout', 'no' ),
					'force_refresh_db'                     => 'yes' === get_option( 'wc_pos_force_refresh_db', 'no' ),
					'fulfilled_order_alternative_status'   => $fulfilled_order_alternative_status,
					'fulfilled_order_default_status'       => $fulfilled_order_default_status,
					/* translators: 1: first name 2: last name */
					'full_name_format'                     => _x( '%1$s %2$s', 'full name', 'woocommerce-point-of-sale' ),
					'grid'                                 => $grid_data,
					'guest_checkout'                       => 'yes' === get_option( 'wc_pos_guest_checkout', 'yes' ),
					'hidden_order_itemmeta'                => $hidden_order_itemmeta,
					'hide_optional_fields'                 => 'yes' === get_option( 'wc_pos_hide_not_required_fields', 'no' ),
					'hide_tender_suggestions'              => 'yes' === get_option( 'wc_pos_hide_tender_suggestions', 'no' ),
					'hide_uncategorized'                   => 'yes' === get_option( 'wc_pos_hide_uncategorized', 'no' ),
					'image_resolution'                     => get_option( 'wc_pos_image_resolution', 'thumbnail' ),
					'itemised_quantity'                    => 'yes' === get_option( 'wc_pos_itemised_quantity', 'no' ),
					'keyboard_shortcuts'                   => 'yes' === get_option( 'wc_pos_keyboard_shortcuts', 'no' ),
					'list_coupons_roles'                   => (array) get_option( 'wc_pos_list_coupons_roles', [ 'shop_manager', 'outlet_manager' ] ),
					'load_website_orders'                  => 'yes' === get_option( 'wc_pos_load_website_orders' ),
					'logo_url'                             => $logo_url,
					'public_url'                           => WC_POS()->plugin_url() . '/client/public/', // @todo fix me
					'manage_outlet_stock'                  => 'yes' === get_option( 'wc_pos_manage_outlet_stock', 'no' ),
					'max_concurrent_requests'              => (int) get_option( 'wc_pos_max_concurrent_requests', 30 ),
					'api_per_page'                         => (int) get_option( 'wc_pos_api_per_page', 100 ),
					'order_statuses'                       => wc_pos_get_order_statuses_no_prefix(),
					'outlet'                               => $outlet_data,
					'parked_order_alternative_status'      => $parked_order_alternative_status,
					'parked_order_default_status'          => $parked_order_default_status,
					'payment_gateways'                     => wc_pos_get_available_payment_gateways( true ),
					'publish_product_default'              => 'yes' === get_option( 'wc_pos_publish_product_default', 'yes' ),
					'receipt'                              => $receipt_data,
					'register'                             => $register_data,
					'user_roles'                           => $user_roles,
					'save_customer_default'                => 'yes' === get_option( 'wc_pos_save_customer_default', 'no' ),
					'scanning_fields'                      => (array) get_option( 'wc_pos_scanning_fields', [ '_sku' ] ),
					'search_includes'                      => (array) get_option( 'wc_pos_search_includes', [ 'title', 'sku' ] ),
					'show_out_of_stock_products'           => 'yes' === get_option( 'wc_pos_show_out_of_stock_products', 'no' ),
					'show_product_preview'                 => 'yes' === get_option( 'wc_pos_show_product_preview', 'no' ),
					'signature_panel'                      => 'yes' === get_option( 'wc_pos_signature', 'no' ),
					'signature_required'                   => 'yes' === get_option( 'wc_pos_signature_required', 'no' ),
					'signature_required_on'                => get_option( 'wc_pos_signature_required_on', [ 'pay' ] ), // rename to wc_pos_capture_signature_modes
					'tax_based_on'                         => get_option( 'wc_pos_calculate_tax_based_on', 'outlet' ),
					'tax_number'                           => get_option( 'wc_pos_tax_number', '' ),
					'primary_color'                        => empty( get_option( 'wc_pos_theme_primary_color' ) ) ? '#7f54b3' : get_option( 'wc_pos_theme_primary_color', '#7f54b3' ),
					'upca_disable_middle_check_digit'      => 'yes' === get_option( 'wc_pos_upca_disable_middle_check_digit', 'no' ),
					'upca_multiplier'                      => (int) get_option( 'wc_pos_upca_multiplier', 100 ),
					'upca_type'                            => get_option( 'wc_pos_upca_type', 'price' ),
					'check_updates_interval'               => (int) get_option( 'wc_pos_check_updates_interval', 15 ),
					'version'                              => WC_POS_VERSION,
				],

				'i18n' => require WC_POS()->plugin_path() . '/i18n/app.php',
			]
		);
	}

	private function prepare_checkout_fields( $fields ) {
		$filtered = [];

		foreach ( $fields as $key => $field ) {
			$filtered[] = [
				'key'        => $key,
				'custom'     => $field['custom'] ?? false,
				'enabled'    => $field['enabled'] ?? false,
				'label'      => $field['label'] ?? $key,
				'options'    => ! empty( $field['options'] ) ? $field['options'] : (object) [],
				'plceholder' => $field['placeholder'] ?? '',
				'priority'   => $field['priority'] ?? 1,
				'type'       => $field['type'] ?? 'text',
				'validate'   => $field['validate'] ?? [],
				'required'   => $field['required'] ?? false,
			];
		}

		return $filtered;
	}

	private function get_shipping_zones() {
		$rest_of_the_world = WC_Shipping_Zones::get_zone_by( 'zone_id', 0 );
		$zones             = WC_Shipping_Zones::get_zones();
		array_unshift( $zones, $rest_of_the_world->get_data() );

		return array_map(
			function( $zone ) {
				$locations = array_map(
					function( $location ) {
						return [
							'code' => $location->code,
							'type' => $location->type,
						];
					},
					$zone['zone_locations']
				);

				if ( ! isset( $zone['shipping_methods'] ) ) {
					$zone['shipping_methods'] = [];
				}

				$shipping_methods = [];
				foreach ( $zone['shipping_methods'] as $method ) {
					$settings = [];
					foreach ( $method->get_instance_form_fields() as $id => $field ) {
						$settings[ $id ] = [
							'value'   => $method->get_instance_option( $id ),
							'default' => empty( $field['default'] ) ? '' : $field['default'],
						];
					}

					$shipping_methods[] = [
						'id'                 => $method->instance_id,
						'instance_id'        => $method->instance_id,
						'title'              => $method->title,
						'order'              => $method->method_order,
						'enabled'            => ( 'yes' === $method->enabled ),
						'method_id'          => $method->id,
						'method_title'       => $method->method_title,
						'method_description' => $method->method_description,
						'settings'           => $settings,
					];
				}

				return [
					'id'               => (int) $zone['id'],
					'name'             => $zone['zone_name'],
					'order'            => (int) $zone['zone_order'],
					'locations'        => $locations,
					'shipping_methods' => $shipping_methods,
				];
			},
			$zones
		);
	}

	private function get_shipping_classes() {
		return array_map(
			function( $class ) {
				return [
					'id'          => $class->term_id,
					'count'       => $class->count,
					'name'        => $class->name,
					'slug'        => $class->slug,
					'description' => $class->description,
				];
			},
			WC()->shipping()->get_shipping_classes()
		);
	}

	private function get_tax_rates() {
		global $wpdb;

		$results = $wpdb->get_results(
			"SELECT
			rates.tax_rate_id,
			rates.tax_rate_country,
			locations.tax_rate_cities,
			locations.tax_rate_postcodes,
			rates.tax_rate_state,
			rates.tax_rate,
			rates.tax_rate_name,
			rates.tax_rate_priority,
			rates.tax_rate_compound,
			rates.tax_rate_shipping,
			rates.tax_rate_order,
			rates.tax_rate_class
			FROM {$wpdb->prefix}woocommerce_tax_rates rates
			LEFT JOIN (SELECT DISTINCT
			loc_join.tax_rate_id,
			GROUP_CONCAT(DISTINCT IF(loc_join.location_type = 'city', loc_join.location_code, NULL) SEPARATOR ';') AS tax_rate_cities,
			GROUP_CONCAT(DISTINCT IF(loc_join.location_type = 'postcode', loc_join.location_code, NULL) SEPARATOR ';') AS tax_rate_postcodes
			FROM {$wpdb->prefix}woocommerce_tax_rate_locations loc, {$wpdb->prefix}woocommerce_tax_rate_locations loc_join
			WHERE loc.tax_rate_id = loc_join.tax_rate_id
			GROUP BY loc.tax_rate_id, loc.location_code) locations ON locations.tax_rate_id = rates.tax_rate_id
			",
			ARRAY_A
		);

		return array_map(
			function( $tax ) {
				return [
					'id'       => (int) $tax['tax_rate_id'],
					'country'  => $tax['tax_rate_country'],
					'state'    => $tax['tax_rate_state'],
					// @todo rename to postcodes and cities
					'postcode' => $tax['tax_rate_postcodes'] ? explode( ';', $tax['tax_rate_postcodes'] ) : [],
					'city'     => $tax['tax_rate_cities'] ? explode( ';', $tax['tax_rate_cities'] ) : [],
					'rate'     => $tax['tax_rate'],
					'name'     => $tax['tax_rate_name'],
					'priority' => (int) $tax['tax_rate_priority'],
					'compound' => (bool) $tax['tax_rate_compound'],
					'shipping' => (bool) $tax['tax_rate_shipping'],
					'order'    => (int) $tax['tax_rate_order'],
					'class'    => $tax['tax_rate_class'],
				];
			},
			$results
		);
	}

	private function normalize_countries( $countries ) {
		return array_map(
			function( $code, $name ) {
				$states = WC()->countries->get_states( $code ) ? WC()->countries->get_states( $code ) : [];
				return [
					'code'   => $code,
					'name'   => $name,
					'states' => array_map(
						function( $state_code, $state_name ) {
							return [
								'code' => $state_code,
								'name' => $state_name,
							];
						},
						array_keys( $states ),
						array_values( $states )
					),
				];
			},
			array_keys( $countries ),
			array_values( $countries )
		);
	}

	/**
	 * Check if WC will round the tax total half up/down.
	 *
	 * @return bool
	 */
	protected static function tax_round_half_up() {
		return 1.15 === wc_round_tax_total( 1.145, 2 ) ? true : false;
	}

	/**
	 * Converts and returns PHP rounding constant integers to their equivalnet constant name to be
	 * used with locutus/php/math/round.
	 *
	 * @param int constatn Constant integer value.
	 * @return string Constant name.
	 */
	protected static function get_discount_rounding_mode_string( $constant ) {
		if ( PHP_ROUND_HALF_UP === $constant ) {
			return 'PHP_ROUND_HALF_UP';
		}

		if ( PHP_ROUND_HALF_DOWN === $constant ) {
			return 'PHP_ROUND_HALF_DOWN';
		}

		if ( PHP_ROUND_HALF_EVEN === $constant ) {
			return 'PHP_ROUND_HALF_EVEN';
		}

		if ( PHP_ROUND_HALF_ODD === $constant ) {
			return 'PHP_ROUND_HALF_ODD';
		}
	}

	/**
	 * Additional filtering to omit unwanted third-party script/style tags that could not be excluded.
	 *
	 * @param $tag The HTML tag.
	 * @param $handle The tag's registered handle.
	 *
	 * @return string
	 */
	public function filter_asset_tags( $tag, $handle ) {
		if ( wc_pos_is_register_page() && 'wc-pos' !== substr( $handle, 0, 6 ) ) {
			return '';
		}

		return $tag;
	}

	public function init_addons_hooks() {
		if ( class_exists( 'WC_Subscriptions' ) ) {
			include_once 'class-wc-pos-subscriptions.php';
		}
		if ( class_exists( 'WC_Product_Addons' ) && 'yes' === get_option( 'wc_pos_force_enable_addons', 'wc_pos_force_enable_addons' ) ) {
			include_once 'class-wc-pos-product-addons.php';
		}
		include_once 'class-wc-pos-payment-gateways.php';
		add_filter( 'bwp_minify_is_loadable', [ $this, 'bwp_minify' ] );

	}

	public function bwp_minify( $is_loadable ) {
		if ( wc_pos_is_register_page() ) {
			$is_loadable = false;
		}
		return $is_loadable;
	}


	/**
	 * Display POS app.
	 */
	public function template_redirect() {
		global $wp;

		// Bail if not POS.
		if ( ! wc_pos_is_register_page() ) {
			return;
		}

		// User not logged in? Redirect to the login page.
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		// Not authorized?
		if ( ! current_user_can( 'view_register' ) ) {
			wp_die( esc_html__( 'You are not allowed to view this page.', 'woocommerce-point-of-sale' ) );
		}

		$register = wc_pos_get_register( $wp->query_vars['register'] );

		if ( ! $register ) {
			wp_die( esc_html__( 'Invalid register.', 'woocommerce-point-of-sale' ) );
		}

		// Update manifest.json.
		// $file                          = WC_POS()->plugin_path() . '/assets/dist/images/manifest.json';
		// $contents                      = wc_pos_file_get_contents( $file );
		// $contents_decoded              = json_decode( $contents, true );
		// $contents_decoded['start_url'] = esc_url( home_url( $wp->request ) );
		// $json                          = json_encode( $contents_decoded );

		// if ( is_writable( $file ) ) {
		// file_put_contents( $file, $json );
		// }

		include_once WC_POS()->plugin_path() . '/includes/views/html-app.php';

		exit;
	}

	/**
	 * Init POS REST API.
	 *
	 * @param object $api_server WC_API_Server Object
	 */
	public function wc_api_init( $api_server ) {
		if ( wc_pos_is_pos_referer() || wc_pos_is_register_page() ) {
			include_once 'api/class-wc-pos-api.php';
			new WC_POS_API();
		}
	}

	/**
	 * Include required files for REST API request
	 *
	 * @since 3.0.0
	 */
	public function wc_api_loaded() {
		include_once 'api/class-wc-pos-rest-coupons-controller.php';
		include_once 'api/class-wc-pos-rest-customers-controller.php';
		include_once 'api/class-wc-pos-rest-orders-refunds-controller.php';
		include_once 'api/class-wc-pos-rest-orders-controller.php';
		include_once 'api/class-wc-pos-rest-product-categories-controller.php';
		include_once 'api/class-wc-pos-rest-product-variations-controller.php';
		include_once 'api/class-wc-pos-rest-products-controller.php';
		include_once 'api/class-wc-pos-rest-users-controller.php';
	}

	/**
	 * Register available API resources
	 *
	 * @since 3.0.0
	 * @param WC_API_Server $server the REST server
	 */
	public function wc_api_classes() {
		$api_classes = [
			'WC_POS_REST_Coupons_Controller',
			'WC_POS_REST_Customers_Controller',
			'WC_POS_REST_Orders_Controller',
			'WC_POS_REST_Orders_Refunds_Controller',
			'WC_POS_REST_Product_Categories_Controller',
			'WC_POS_REST_Product_Variations_Controller',
			'WC_POS_REST_Products_Controller',
			'WC_POS_REST_Users_Controller',
		];

		foreach ( $api_classes as $api_class ) {
			$api_class = new $api_class();
			$api_class->register_routes();
		}

	}

	public function available_payment_gateways( $available_gateways ) {
		if ( wc_pos_is_register_page() ) {
			$available_gateways = [];
			$payment_gateways   = WC()->payment_gateways->payment_gateways;
			$enabled_gateways   = wc_pos_get_payment_gateways_ids( true );

			foreach ( $payment_gateways as $gateway ) {
				if ( in_array( $gateway->id, $enabled_gateways, true ) ) {
					$available_gateways[ $gateway->id ] = $gateway;
				}
			}
		}

		return $available_gateways;
	}

	public function woocommerce_stripe_settings( $value ) {
		if ( wc_pos_is_register_page() ) {
			$value['saved_cards']     = 'no';
			$value['stripe_checkout'] = 'no';
		}
		return $value;
	}

	public function wc_pos_checkout_gateways() {
		if ( wc_pos_is_register_page() ) {
			$enabled_gateways   = wc_pos_get_payment_gateways_ids( true );
			$pos_exist_gateways = wc_pos_get_payment_gateways_ids( false );

			foreach ( $pos_exist_gateways as $gateway_id ) {
				if ( ! in_array( $gateway_id, $enabled_gateways, true ) ) {
					add_filter( 'option_woocommerce_' . $gateway_id . '_settings', [ $this, 'disable_gateway' ] );
				} else {
					if ( 'pos_cash' === $gateway_id ) {
						add_filter( 'pre_option_woocommerce_' . $gateway_id . '_settings', [ $this, 'enable_gateway_cod' ] );
					} else {
						add_filter( 'option_woocommerce_' . $gateway_id . '_settings', [ $this, 'enable_gateway' ] );
					}
				}
			}
		}
	}

	public function disable_gateway( $val ) {
		$val['enabled'] = 'no';
		return $val;
	}

	public function enable_gateway( $val ) {

		$val['enabled'] = 'yes';
		if ( isset( $val['enable_for_virtual'] ) ) {
			$val['enable_for_virtual'] = 'yes';
		}

		if ( isset( $val['enable_for_methods'] ) ) {
			$val['enable_for_methods'] = [];
		}

		return $val;
	}

	public function enable_gateway_cod() {
		$val                       = [];
		$val['enabled']            = 'yes';
		$val['enable_for_virtual'] = 'yes';
		$val['enable_for_methods'] = [];

		return $val;
	}

	/**
	 * Returns register data.
	 *
	 * @param int|string $register Register ID or slug.
	 * @return array
	 */
	public function get_register_data( $register ) {
		global $wpdb;

		$register_object = wc_pos_get_register( $register );
		$register_data   = [];

		if ( $register_object ) {
			$register_data = [
				'id'              => $register_object->get_id(),
				'name'            => $register_object->get_name(),
				'slug'            => $register_object->get_slug(),
				'date_opened'     => $register_object->get_date_opened() ? gmdate( 'Y-m-d H:i:s', $register_object->get_date_opened()->getTimestamp() ) : null,
				'date_closed'     => $register_object->get_date_closed() ? gmdate( 'Y-m-d H:i:s', $register_object->get_date_closed()->getTimestamp() ) : null,
				'open_first'      => $register_object->get_open_first(),
				'open_last'       => $register_object->get_open_last(),
				'current_session' => $register_object->get_current_session(),
				'grid'            => $register_object->get_grid(),
				'receipt'         => $register_object->get_receipt(),
				'grid_layout'     => $register_object->get_grid_layout(),
				'prefix'          => $register_object->get_prefix(),
				'suffix'          => $register_object->get_suffix(),
				'outlet'          => $register_object->get_outlet(),
				'customer'        => $register_object->get_customer(),
				'cash_management' => $register_object->get_cash_management(),
				'dining_option'   => $register_object->get_dining_option(),
				'default_mode'    => $register_object->get_default_mode(),
				'change_user'     => $register_object->get_change_user(),
				'email_receipt'   => $register_object->get_email_receipt(),
				'print_receipt'   => $register_object->get_print_receipt(),
				'gift_receipt'    => $register_object->get_gift_receipt(),
				'note_request'    => $register_object->get_note_request(),
			];
		}

		/**
		 * Register data.
		 *
		 * @since 5.0.0
		 */
		return apply_filters( 'wc_pos_register_data', $register_data );
	}

	/**
	 * Returns outlet data.
	 *
	 * @param int|string $outlet Outlet ID or slug.
	 * @return array
	 */
	public function get_outlet_data( $outlet ) {
		$outlet_object = wc_pos_get_outlet( $outlet );
		$outlet_data   = [];

		if ( $outlet_object ) {
			$outlet_data = [
				'id'                => $outlet_object->get_id(),
				'name'              => $outlet_object->get_name(),
				'address_1'         => $outlet_object->get_address_1(),
				'address_2'         => $outlet_object->get_address_2(),
				'city'              => $outlet_object->get_city(),
				'postcode'          => $outlet_object->get_postcode(),
				'country'           => $outlet_object->get_country(),
				'state'             => $outlet_object->get_state(),
				'email'             => $outlet_object->get_email(),
				'phone'             => $outlet_object->get_phone(),
				'fax'               => $outlet_object->get_fax(),
				'website'           => $outlet_object->get_website(),
				'wifi_network'      => $outlet_object->get_wifi_network(),
				'wifi_password'     => $outlet_object->get_wifi_password(),
				'social_accounts'   => $outlet_object->get_social_accounts(),
				'formatted_address' => explode(
					'<br/>',
					WC()->countries->get_formatted_address(
						[
							'address_1' => $outlet_object->get_address_1(),
							'address_2' => $outlet_object->get_address_2(),
							'city'      => $outlet_object->get_city(),
							'state'     => empty( $outlet_object->get_state() ) ? $outlet_object->get_state() : '',
							'postcode'  => $outlet_object->get_postcode(),
							'country'   => $outlet_object->get_country(),
						]
					)
				),
			];
		}

		/**
		 * Outlet data.
		 *
		 * @since 4.0.0
		 */
		return apply_filters( 'wc_pos_outlet_data', $outlet_data );
	}

	public function get_receipt_data( $id ) {
		$receipt_object = wc_pos_get_receipt( $id );
		$receipt_data   = [];

		if ( $receipt_object ) {
			$receipt_data = $receipt_object->get_data();
		}

		unset( $receipt_data['meta_data'] );
		unset( $receipt_data['date_created'] );
		unset( $receipt_data['date_modified'] );

		$receipt_data['logo'] = $receipt_data['logo'] ? wp_get_attachment_url( $receipt_data['logo'] ) : null;

		/**
		* Receipt data.
		*
		* @since 6.0.0
		*/
		return apply_filters( 'wc_pos_receipt_data', $receipt_data );
	}

	public function get_custom_product_params() {
		return json_encode( [] );
	}

	public static function get_coupons_labels() {
		$c = [ 'WC_POINTS_REDEMPTION' ];
		$l = [];
		foreach ( $c as $code ) {
			$l[ $code ] = $code;
		}

		return $l;
	}

	/**
	 * Returns grid data.
	 *
	 * @return array|null
	 */
	public function get_grid_data( $grid_id ) {
		$grid = wc_pos_get_grid( $grid_id );

		/*
		 * Get product categories.
		 */
		$categories = [];
		$terms      = get_terms(
			'product_cat',
			[
				'orderby' => 'name',
				'order'   => 'ASC',
				'fields'  => 'all',
			]
		);
		if ( $terms ) {
			foreach ( $terms as $term ) {
				$thumbnail_id = absint( get_term_meta( $term->term_id, 'thumbnail_id', true ) );

				if ( $thumbnail_id ) {
					$image = wc_pos_grid_thumbnail( $thumbnail_id, [ 250, 250 ] );
				} else {
					$image = wc_placeholder_img_src();
				}

				if ( ! $image || null === $image ) {
					$image = wc_placeholder_img_src();
				}

				$term->image        = $image;
				$term->display_type = get_term_meta( $term->term_id, 'display_type', true );
				$term->description  = wp_slash( $term->description );

				$categories[ '_' . $term->term_id ] = $term;
			}
		}

		$tiles             = $grid_id && $grid ? $grid->get_tiles() : [];
		$product_tiles     = [];
		$product_cat_tiles = [];

		foreach ( $tiles as $tile_id => $tile ) {
			if ( 'product' === $tile['type'] ) {
				$product_tiles[] = (int) $tile['item_id'];
			} elseif ( 'product_cat' === $tile['type'] ) {
				$product_cat_tiles[] = (int) $tile['item_id'];
			}
		}

		$grid_data = [
			'product_tiles'     => $product_tiles,
			'product_cat_tiles' => $product_cat_tiles,
			'grid_name'         => $grid_id && $grid ? $grid->get_name() : '',
			'grid_id'           => $grid_id,
			'categories'        => $categories, // @todo what the heck is this?
			'tile_sorting'      => get_option( 'wc_pos_default_tile_orderby', 'menu_order' ),
		];

		/**
		 * Grid data.
		 *
		 * @since 5.0.0
		 */
		return apply_filters( 'wc_pos_grid_data', $grid_data );
	}

	/**
	 * Main WC_POS_App Instance.
	 *
	 * Ensures only one instance of WC_POS_App is loaded or can be loaded.
	 *
	 * @return WC_POS_App Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}

		return new WC_POS_App();
