<?php
/**
 * Stripe for POS.
 *
 * @package WooCommerce_Point_Of_Sale/Gateways
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Stripe.
 */
class WC_POS_Stripe {

	/**
	 * Init.
	 */
	public static function init() {
		self::includes();
		self::add_ajax_events();

		add_filter( 'wc_pos_app_data', [ __CLASS__, 'app_data' ] );
		add_filter( 'wc_pos_nonces', [ __CLASS__, 'nonces' ] );
		add_action( 'admin_notices', [ __CLASS__, 'show_stripe_notice' ] );
		add_filter( 'wc_pos_register_options_tabs', [ __CLASS__, 'register_options_tabs' ] );
		add_action( 'wc_pos_register_options_panels', [ __CLASS__, 'register_options_panels' ], 10, 2 );
		add_action( 'wc_pos_register_options_save', [ __CLASS__, 'save_register_data' ], 10, 2 );
		add_filter( 'wc_pos_register_data', [ __CLASS__, 'add_register_data' ] );
	}

	/**
	 * Includes.
	 */
	public static function includes() {
		if ( ! class_exists( '\Stripe\Stripe' ) ) {
			include WC_POS()->plugin_path() . '/vendor/stripe/stripe-php/init.php';
		}

		include dirname( __FILE__ ) . '/class-wc-pos-stripe-api.php';
		include dirname( __FILE__ ) . '/payment-methods/class-wc-pos-gateway-stripe-terminal.php';
		include dirname( __FILE__ ) . '/payment-methods/class-wc-pos-gateway-stripe-credit-card.php';
	}

	/**
	 * Returns the general Stripe gateway settings.
	 *
	 * @param $option null|string Whether to return the value of a specific option.
	 *
	 * @return array|string
	 */
	public static function get_stripe_settings( $option = null ) {
		$stripe_settings = maybe_unserialize( get_option( 'woocommerce_stripe_settings', [] ) );
		$stripe_settings = empty( $stripe_settings ) ? [] : $stripe_settings;

		// If no option specified, return all settings.
		if ( is_null( $option ) ) {
			return $stripe_settings;
		}

		// Return specific option value.
		return isset( $stripe_settings[ $option ] ) ? $stripe_settings[ $option ] : '';
	}

	/**
	 * Returns the publishable key based on Stripe mode.
	 *
	 * @return string
	 */
	public static function get_publishable_key() {
		if ( 'yes' === self::get_stripe_settings( 'testmode' ) ) {
			return self::get_stripe_settings( 'test_publishable_key' );
		}

		return self::get_stripe_settings( 'publishable_key' );
	}

	/**
	 * Returns the secret key based on Stripe mode.
	 *
	 * @return string
	 */
	public static function get_secret_key() {
		if ( 'yes' === self::get_stripe_settings( 'testmode' ) ) {
			return self::get_stripe_settings( 'test_secret_key' );
		}

		return self::get_stripe_settings( 'secret_key' );
	}

	/**
	 * Adds gateway params to the global object window.AppData.
	 *
	 * @param array $data
	 * @return array
	 */
	public static function app_data( $data ) {
		$stripe_data                               = get_option( 'woocommerce_pos_stripe_terminal_settings', [] );
		$data['pos']['stripe_terminal_debug_mode'] = ! empty( $stripe_data['debug_mode'] ) && 'yes' === $stripe_data['debug_mode'];

		return $data;
	}

	/**
	 * Adds gateway nonces to UserData.
	 *
	 * @param array $data
	 * @return array
	 */
	public static function nonces( $nonces ) {
		$nonces['stripe_create_connection_token'] = wp_create_nonce( 'stripe-create-connection-token' );
		$nonces['stripe_create_payment_intent']   = wp_create_nonce( 'stripe-create-payment-intent' );
		$nonces['stripe_retrieve_payment_intent'] = wp_create_nonce( 'stripe-retrieve-payment-intent' );

		return $nonces;
	}

	/**
	 * Show a notice if any of the POS Stripe payment methods is enabled and the main
	 * Stripe gateway is not installed or inactive.
	 */
	public static function show_stripe_notice() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		global $wpdb;
		$pos_stripe_methods = $wpdb->get_results(
			"SELECT option_value
			FROM {$wpdb->options}
			WHERE option_name = 'woocommerce_pos_stripe_terminal_settings'
			OR option_name = 'woocommerce_pos_stripe_credit_card_settings'"
		);

		// Check if any of the Stripe methods is enabled.
		$enabled = false;
		if ( $pos_stripe_methods ) {
			foreach ( $pos_stripe_methods as $method ) {
				$settings = maybe_unserialize( $method->option_value );
				if ( 'yes' === $settings['enabled'] ) {
					$enabled = true;
					break;
				}
			}
		}

		// If any of the POS Stripe payment methods is enabled, we require the main Stripe plugin to be installed and active.
		if ( $enabled && ! is_plugin_active( 'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php' ) ) {
			?>
			<div id="message" class="error">
				<p><?php esc_html_e( 'Point of Sale Stripe payment methods require the WooCommerce Stripe Payment Gateway to be installed and active.', 'woocommerce-point-of-sale' ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Hook in methods.
	 */
	public static function add_ajax_events() {
		$ajax_events_nopriv = [
			'create_connection_token',
			'create_payment_intent',
			'retrieve_payment_intent',
		];

		foreach ( $ajax_events_nopriv as $ajax_event ) {
			add_action( 'wp_ajax_wc_pos_stripe_' . $ajax_event, [ __CLASS__, 'ajax_' . $ajax_event ] );
			add_action( 'wp_ajax_nopriv_wc_pos_stripe_' . $ajax_event, [ __CLASS__, 'ajax_' . $ajax_event ] );
		}
	}

	/**
	 * Ajax: create token.
	 */
	public static function ajax_create_connection_token() {
		if ( ! check_ajax_referer( 'stripe-create-connection-token', 'security', false ) ) {
			wc_pos_send_json_error( __( 'Invalid nonce.', 'woocommerce-point-of-sale' ), 'invalid_nonce' );
		}

		try {
			$api    = WC_POS_Stripe_API::instance();
			$result = $api->create_connection_token();

			if ( ! $result || ! isset( $result['object'] ) || 'terminal.connection_token' !== $result['object'] ) {
				$error = $result ? $result : __( 'Stripe API: failed to create connection token.', 'woocommerce-point-of-sale' );
				throw new Exception( $error );
			}

			wc_pos_send_json_success( [ 'token' => $result['secret'] ], 201 );
		} catch ( Exception $e ) {
			wc_pos_send_json_error( $e->getMessage(), 'create_connection_token_failed' );
		}
	}

	/**
	 * Ajax: payment intent.
	 */
	public static function ajax_create_payment_intent() {
		if ( ! check_ajax_referer( 'stripe-create-payment-intent', 'security', false ) ) {
			wc_pos_send_json_error( __( 'Invalid nonce.', 'woocommerce-point-of-sale' ), 'invalid_nonce' );
		}

		try {
			$amount               = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0;
			$currency             = isset( $_POST['currency'] ) ? wc_clean( wp_unslash( $_POST['currency'] ) ) : get_option( 'woocommerce_currency' );
			$payment_method_types = isset( $_POST['payment_method_types'] ) ? json_decode( wc_clean( wp_unslash( $_POST['payment_method_types'] ) ) ) : [ 'card' ];
			$capture_method       = isset( $_POST['capture_method'] ) ? wc_clean( wp_unslash( $_POST['capture_method'] ) ) : 'automatic';
			$order_number         = isset( $_POST['order_number'] ) ? intval( $_POST['order_number'] ) : '';
			$metadata             = [];

			if ( $amount <= 0 ) {
				throw new Exception( __( 'Order total cannot be zero or less.', 'woocommerce-point-of-sale' ) );
			}

			if ( $order_number ) {
				$metadata['order_number'] = $order_number;
			}

			$api    = WC_POS_Stripe_API::instance();
			$result = $api->create_payment_intent( $amount, $currency, $payment_method_types, $capture_method, $metadata );

			if ( ! $result || ! isset( $result['object'] ) || 'payment_intent' !== $result['object'] ) {
				$error = $result ? $result : __( 'Stripe API: failed to created payment intent.', 'woocommerce-point-of-sale' );
				throw new Exception( $error );
			}

			wc_pos_send_json_success( $result, 201 );
		} catch ( Exception $e ) {
			wc_pos_send_json_error( $e->getMessage(), 'create_payment_intent_failed' );
		}
	}

	public static function ajax_retrieve_payment_intent() {
		if ( ! check_ajax_referer( 'stripe-retrieve-payment-intent', 'security', false ) ) {
			wc_pos_send_json_error( __( 'Invalid nonce.', 'woocommerce-point-of-sale' ), 'invalid_nonce' );
		}

		try {
			$id = isset( $_POST['id'] ) ? sanitize_title( $_POST['id'] ) : '';

			$api    = WC_POS_Stripe_API::instance();
			$result = $api->retrieve_payment_intent( $id );

			if ( ! $result || ! isset( $result['object'] ) || 'payment_intent' !== $result['object'] ) {
				$error = $result ? $result : __( 'Stripe API: failed to retrieve payment intent.', 'woocommerce-point-of-sale' );
				throw new Exception( $error );
			}

			wc_pos_send_json_success( $result, 200 );
		} catch ( Exception $e ) {
			wc_pos_send_json_error( $e->getMessage, 'retrieve_payment_intent_failed' );
		}
	}

	/**
	 * Add Stripe Terminal tab to the register data meta box.
	 *
	 * @param array $tabs
	 * @return array
	 */
	public static function register_options_tabs( $tabs ) {
		$stripe_terminal_data = get_option( 'woocommerce_pos_stripe_terminal_settings', [] );
		$enabled              = ! empty( $stripe_terminal_data['enabled'] ) && 'yes' === $stripe_terminal_data['enabled'];

		if ( $enabled ) {
			$tabs['stripe_terminal'] = [
				'label'  => __( 'Stripe Terminal', 'woocommerce-point-of-sale' ),
				'target' => 'stripe_terminal_register_options',
				'class'  => '',
			];
		}

		return $tabs;
	}

	/**
	 * Display the Stripe Terminal tab content.
	 *
	 * @param int             $thepostid
	 * @param WC_POS_Register $register
	 */
	public static function register_options_panels( $thepostid, $register_object ) {
		include_once 'views/html-admin-register-options-stripe-terminal.php';
	}

	/**
	 * On save register data.
	 *
	 * @param int             $post_id
	 * @param WC_POS_Register $register
	 */
	public static function save_register_data( $post_id, $register ) {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'update-post_' . $post_id ) ) {
			wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'woocommerce-point-of-sale' ) );
		}

		$terminal    = ! empty( $_POST['stripe_terminal'] ) ? wc_clean( wp_unslash( $_POST['stripe_terminal'] ) ) : 'none';
		$skip_review = ! empty( $_POST['stripe_terminal_skip_review'] ) ? wc_clean( wp_unslash( $_POST['stripe_terminal_skip_review'] ) ) : 'no';

		update_post_meta( $post_id, 'stripe_terminal', $terminal );
		update_post_meta( $post_id, 'stripe_terminal_skip_review', $skip_review );
	}

	/**
	 * Add Stripe Terminal data to register data.
	 *
	 * @param array $register_data
	 * @return array
	 */
	public static function add_register_data( $register_data ) {
		$register_data['stripe_terminal']             = get_post_meta( $register_data['id'], 'stripe_terminal', true );
		$register_data['stripe_terminal_skip_review'] = 'yes' === get_post_meta( $register_data['id'], 'stripe_terminal_skip_review', true );

		return $register_data;
	}
}

WC_POS_Stripe::init();
