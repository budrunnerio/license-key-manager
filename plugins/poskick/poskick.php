<?php
/**
 * Plugin Name: Poskick Payment
 * Plugin URI: https://poskick.com
 * Description: Poskick Payment Plugin for Mobile App
 * Version: 1.0
 * Author: poskick.com
 * Author URI: https://poskick.com
 * License: GPLv2 or later
 * Text Domain: poskick-payment
 *
 * @package Poskick Payment
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

add_action( 'plugins_loaded', 'init_poskick_gateway_class' );

add_filter( 'woocommerce_payment_gateways', 'add_poskick_gateway_class' );

/**
 * Add the gateway to WooCommerce
 *
 * @param array $methods WooCommerce payment methods.
 */
function add_poskick_gateway_class( $methods ) {
	$methods[] = 'Poskick_Payment_Gateway';
	return $methods;
}

define( 'POSKICK_PAYMENT_VERSION', '1.0.1' );
define( 'POSKICK_URL', 'https://prod4.poskick.com' );
define( 'POSKICK_URL_SANBOX', 'https://development.poskick.com' );

/**
 * Initialize Gateway Class
 */
function init_poskick_gateway_class() {
	/**
	 * Add the gateway to WooCommerce
	 */
	class Poskick_Payment_Gateway extends WC_Payment_Gateway {

		/**
		 * Payment URL
		 *
		 * @var string
		 */
		public string $payment_url;

		/**
		 * API URL
		 *
		 * @var string
		 */
		public string $api_url;

		/**
		 * API URL Check Status
		 *
		 * @var string
		 */
		public string $api_url_check_status;

		/**
		 * Merchant ID
		 *
		 * @var string
		 */
		public string $merchant_number;

		/**
		 * API Key
		 *
		 * @var string
		 */
		public string $public_key;

		/**
		 * Secret Key
		 *
		 * @var string
		 */
		public string $secret_key;

		/**
		 * Order Status
		 *
		 * @var string
		 */
		public string $order_status;

		/**
		 * Sandbox
		 *
		 * @var bool
		 */
		public bool $sandbox;

		/**
		 * Payment fee
		 *
		 * @var string
		 */
		public string $fee;

		/**
		 * Class constructor
		 */
		public function __construct() {
			$this->id                 = 'poskick';
			$this->icon               = null;
			$this->method_title       = __( 'Poskick Payments', 'poskick-payment' );
			$this->method_description = __( 'Poskick Payment Plugin for Mobile App', 'poskick-payment' );
			$this->title              = __( 'Poskick Payments', 'poskick-payment' );
			$this->has_fields         = false;
			$this->enabled            = $this->get_option( 'enabled' );
			$this->title              = $this->get_option( 'title' );
			$this->description        = $this->get_option( 'description' );
			$this->merchant_number    = $this->get_option( 'merchant_number' );
			$this->public_key         = $this->get_option( 'public_key' );
			$this->secret_key         = $this->get_option( 'secret_key' );
			$this->order_status       = $this->get_option( 'order_status' );
			$this->fee                = $this->get_option( 'fee' );

			if ( 'yes' === $this->get_option( 'sanbox' ) ) {
				$this->payment_url          = POSKICK_URL_SANBOX;
				$this->api_url              = POSKICK_URL_SANBOX . '/api/v1/create-payment-token';
				$this->api_url_check_status = POSKICK_URL_SANBOX . '/api/v1/check-status';
				// $this->icon                 = POSKICK_URL_SANBOX . '/resources/images/payment_icon.png';
			} else {
				$this->payment_url          = POSKICK_URL;
				$this->api_url              = POSKICK_URL . '/api/v1/create-payment-token';
				$this->api_url_check_status = POSKICK_URL . '/api/v1/check-status';
				// $this->icon                 = POSKICK_URL . '/resources/images/payment_icon.png';
			}

			$this->init_form_fields();
			$this->init_settings();

			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			// Add action load javascript in checkout page.
			add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
			// Add action load style in checkout page.
			add_action( 'wp_enqueue_scripts', array( $this, 'load_styles' ) );
		}

		/**
		 * Load scripts
		 */
		public function load_scripts() {
			if ( is_checkout() ) {
				wp_enqueue_script( 'poskick-payment-js', $this->payment_url . '/resources/poskick-pay.js', array(), POSKICK_PAYMENT_VERSION, array( 'strategy' => 'defer' ) );
			}
		}

		/**
		 * Load styles
		 */
		public function load_styles() {
			if ( is_checkout() ) {
				wp_enqueue_style( 'poskick-payment-css', $this->payment_url . '/resources/poskick-pay.css', array(), POSKICK_PAYMENT_VERSION );
			}
		}

		/**
		 * Payment fields
		 */
		public function payment_fields() {
			?> <credit-card session="5660ba53-28f6-4467-90d0-bfb725ed67f4" />
			<?php
		}

		/**
		 * Initialize Gateway Settings Form Fields
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function init_form_fields() {
				$this->form_fields = array(
					'enabled'         => array(
						'title'   => __( 'Enable/Disable', 'poskick-payment' ),
						'type'    => 'checkbox',
						'label'   => __( 'Enable WeedMart Payments', 'poskick-payment' ),
						'default' => 'yes',
					),

					'sanbox'          => array(
						'title'   => __( 'Sanbox Mode Enable/Disable', 'poskick-payment' ),
						'type'    => 'checkbox',
						'label'   => __( 'Enable Sanbox Mode', 'poskick-payment' ),
						'default' => 'yes',
					),

					'title'           => array(
						'title'       => __( 'Method Title', 'poskick-payment' ),
						'type'        => 'text',
						'description' => __( 'This controls the title', 'poskick-payment' ),
						'default'     => __( 'Weedmart Payments', 'poskick-payment' ),
						'desc_tip'    => true,
					),
					'description'     => array(
						'title'       => __( 'Customer Message', 'poskick-payment' ),
						'type'        => 'textarea',
						'css'         => 'width:500px;',
						'default'     => 'Orders will be canceled if not paid within 15 minutes.',
						'description' => __( 'The message which you want it to appear to the customer in the checkout page.', 'poskick-payment' ),
					),
					'merchant_number' => array(
						'title'       => __( 'Merchant number', 'poskick-payment' ),
						'type'        => 'text',
						'description' => __( 'Enter Your Merchant Number Provided by Poskick', 'poskick-payment' ),
						'default'     => __( '989222333', 'poskick-payment' ),
						'desc_tip'    => true,
					),
					'public_key'      => array(
						'title'       => __( 'Poskick Public key Key', 'poskick-payment' ),
						'type'        => 'text',
						'description' => __( 'Enter Your Sandbox API Key', 'poskick-payment' ),
						'default'     => __( 'jhffPavffQKTbxAJ2MUecZNq6f74zy0a1z8P5sgopRzuhbQuBn', 'poskick-payment' ),
						'desc_tip'    => true,
					),
					'secret_key'      => array(
						'title'       => __( 'Poskick Secret Key', 'poskick-payment' ),
						'type'        => 'password',
						'description' => __( 'Enter Your Sandbox Secret Key', 'poskick-payment' ),
						'default'     => __( 'Z2itav1q7xsWrRseo6a1dZCf3t9btfgLGUanSxXZ3KyTJ4lCCe', 'poskick-payment' ),
						'desc_tip'    => true,
					),
					'fee'             => array(
						'title'       => __( 'Fee percentage', 'poskick-payment' ),
						'type'        => 'text',
						'description' => __( 'Enter Fee', 'poskick-payment' ),
						'default'     => __( '0', 'poskick-payment' ),
						'desc_tip'    => true,
					),
					'order_status'    => array(
						'title'       => __( 'Order Status After The Checkout', 'poskick-payment' ),
						'type'        => 'select',
						'options'     => wc_get_order_statuses(),
						'default'     => 'wc-pending',
						'description' => __( 'The default order status if this gateway used in payment.', 'poskick-payment' ),
					),
				);
		}

		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id The order ID.
		 */
		public function process_payment( $order_id ) {
			$order = new WC_Order( $order_id );

			// Get card token in order meta.
			$session = get_post_meta( $order->get_id(), '_poskick_session', true );

			if ( empty( $session ) ) {
				if ( ! isset( $_POST['card'] ) ) {
					return $this->get_failed_response();
				}

				$card       = $_POST['card'];
				$card_exp   = explode( '/', $card['expiry'] );
				$order_data = $order->get_data();

				$fee        = (float) ( empty( $this->fee ) ? 0 : $this->fee );
				$amount     = (int) ( $order->get_total() * 100 );
				$fee_amount = (int) ( ( $amount * $fee ) );

				$body = array(
					'amount'          => $amount,
					'currency'        => $order->get_currency(),
					'public_key'      => $this->public_key,
					'secret_key'      => $this->secret_key,
					'merchant_number' => $this->merchant_number,
					'card'            => array(
						'number'    => str_replace( ' ', '', $card['number'] ),
						'exp_month' => $card_exp[0],
						'exp_year'  => '20' . $card_exp[1],
						'cvc'       => $card['cvc'],
					),
					'return_url'      => get_rest_url( null, '/poskick-payment/v1/callback?orderId=' . $order->get_id() . '&web=1' ),
					'tip_amount'      => '0',
					'fee_amount'      => $fee_amount,
					'zipcode'         => $order_data['billing']['postcode'],
					'phone_number'    => $order_data['billing']['phone'],
					'first_name'      => $order_data['billing']['first_name'],
					'last_name'       => $order_data['billing']['last_name'],
					'ip_address'      => '192.168.1.1',
				);

				$response = wp_remote_post(
					$this->api_url,
					array(
						'method'  => 'POST',
						'headers' => array(
							'content-type' => 'application/json',
						),
						'body'    => wp_json_encode( $body ),
					)
				);

				// Check if response is valid.
				if ( is_wp_error( $response ) ) {
					return;
				}

				// Get response body.
				$response_body = wp_remote_retrieve_body( $response );
				$result        = json_decode( $response_body );

				if ( 'success' === $result->status ) {
					$order->update_meta_data( '_poskick_amount', ( $amount + $fee_amount ) );
					$order->update_meta_data( '_poskick_web_session', $result->session );
					$order->save();

					return array(
						'result'   => 'success',
						'redirect' => $result->callback,
					);
				}
			}

			// Redirect URL.
			$redirect = get_post_meta( $order->get_id(), '_poskick_callback', true );
			if ( $redirect ) {
				return array(
					'result'   => 'success',
					'redirect' => $redirect,
				);
			}

			return $this->get_failed_response();
		}

		/**
		 * Get failed response.
		 *
		 * @return array
		 */
		public function get_failed_response() {
			return array(
				'result'   => 'failed',
				'redirect' => '/checkout',
			);
		}
	}
}

add_action( 'woocommerce_store_api_checkout_update_order_from_request', 'poskick_woocommerce_store_api_checkout_update_order_from_request', 8, 2 );

/**
 * Fires when the Checkout Block/Store API updates an order's from the API request data.
 *
 * @param \WC_Order        $order Order object.
 * @param \WP_REST_Request $request Full details about the request.
 */
function poskick_woocommerce_store_api_checkout_update_order_from_request( \WC_Order $order, \WP_REST_Request $request ) {
	if ( class_exists( 'Poskick_Payment_Gateway' ) ) {
		$poskick_payment_gateway = new Poskick_Payment_Gateway();

		$card = $request->get_param( 'card' );
		$fee  = $request->get_param( 'fee' );
		$tip  = $request->get_param( 'tip' );

		$order_data = $order->get_data();

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$user_ip_address = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$user_ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$user_ip_address = $_SERVER['REMOTE_ADDR'];
		}

		$fee        = (float) ( empty( $poskick_payment_gateway->fee ) ? 0 : $poskick_payment_gateway->fee );
		$amount     = (int) ( $order->get_total() * 100 );
		$fee_amount = (int) ( ( $amount * $fee ) );
		$tip_amount = (int) ! empty( $tip ) ? $tip : '0';

		$body = array(
			'amount'          => $amount,
			'currency'        => $order->get_currency(),
			'public_key'      => $poskick_payment_gateway->public_key,
			'secret_key'      => $poskick_payment_gateway->secret_key,
			'merchant_number' => $poskick_payment_gateway->merchant_number,
			'card'            => array(
				'number'    => $card['number'],
				'exp_month' => $card['expiration_month'],
				'exp_year'  => $card['expiration_year'],
				'cvc'       => $card['cvc'],
			),
			'return_url'      => get_rest_url( null, '/poskick-payment/v1/callback?orderId=' . $order->get_id() ),
			'tip_amount'      => $tip_amount,
			'fee_amount'      => $fee_amount,
			'zipcode'         => $order_data['billing']['postcode'],
			'phone_number'    => $order_data['billing']['phone'],
			'first_name'      => $order_data['billing']['first_name'],
			'last_name'       => $order_data['billing']['last_name'],
			'ip_address'      => '192.168.1.1',
		);

		$response = wp_remote_post(
			$poskick_payment_gateway->api_url,
			array(
				'method'  => 'POST',
				'headers' => array(
					'content-type' => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		// Check if response is valid.
		if ( is_wp_error( $response ) ) {
			return;
		}

		// Get response body.
		$response_body = wp_remote_retrieve_body( $response );
		$result        = json_decode( $response_body );

		if ( 'success' === $result->status ) {
			$order->update_meta_data( '_poskick_session', $result->session );
			$order->update_meta_data( '_poskick_callback', $result->callback );
			$order->update_meta_data( '_poskick_amount', ( $amount + $fee_amount + $tip_amount ) );
			$order->save();
		}
	}
}


/**
 * Add REST API
 */
add_action( 'rest_api_init', 'register_poskick_payment_register_rest' );

/**
 * Register REST API
 */
function register_poskick_payment_register_rest() {
	register_rest_route(
		'poskick-payment/v1',
		'/callback',
		array(
			'methods'  => 'GET',
			'callback' => 'poskick_payment_callback',
		)
	);
}

/**
 * Callback function
 *
 * @param \WP_REST_Request $request Full details about the request.
 */
function poskick_payment_callback( \WP_REST_Request $request ) {
	$order_id = $request->get_param( 'orderId' );
	$is_web   = (int) $request->get_param( 'web' );
	$status   = 'failed';

	$poskick_payment_gateway = new Poskick_Payment_Gateway();
	$order                   = wc_get_order( $order_id );

	if ( 1 === $is_web ) {
		$session = get_post_meta( $order_id, '_poskick_web_session', true );
	} else {
		$session = get_post_meta( $order_id, '_poskick_session', true );
	}

	$body = array(
		'session' => $session,
	);

	$response = wp_remote_post(
		$poskick_payment_gateway->api_url_check_status,
		array(
			'method'  => 'POST',
			'headers' => array(
				'content-type' => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
		)
	);

	if ( ! is_wp_error( $response ) ) {
		$response_body = wp_remote_retrieve_body( $response );
		$result        = json_decode( $response_body );
		$status        = $result->status;
	}

	$amount = (int) get_post_meta( $order_id, '_poskick_amount', true );

	if ( 'succeeded' === $status && $amount === (int) $result->amount ) {
		update_post_meta( $order_id, 'session', $session );
		$order->payment_complete();
	}

	if ( 1 === $is_web ) {
		$order_url = $order->get_checkout_order_received_url();
		header( 'Location: ' . $order_url );
		exit;
	} else {
		header( 'Location: budplug://order?status=' . $status );
		exit;
	}
}

/**
 * Callback function for WooCommerce API.
 *
 * @param array $data
 */
function poskick_callback( $data ) {
	echo '1';
	die;
}

add_action( 'woocommerce_api_poskick_callback', 'poskick_callback' );
