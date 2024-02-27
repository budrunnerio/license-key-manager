<?php
/**
 * Stripe API Handler
 *
 * @package WooCommerce_Point_Of_Sale/Gateways
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Stripe_API.
 */
class WC_POS_Stripe_API {
	/**
	 * StripeClient instance.
	 *
	 * @var StripeClient
	 */
	private $stripe;

	/**
	 * The single instance of WC_POS.
	 *
	 * @var object
	 * @since 1.9.0
	 */
	private static $instance = null;

	public function __construct() {
		$secret_key = WC_POS_Stripe::get_secret_key();

		try {
			$this->stripe = new \Stripe\StripeClient( $secret_key );
		} catch ( Exception $e ) {
			return $e;
		}
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * Ensures only one instance of the class can be loaded.
	 *
	 * @since 6.0.0
	 *
	 * @return WC_POS_Stripe_API
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function create_connection_token() {
		if ( ! $this->stripe ) {
			return null;
		}

		return $this->stripe->terminal->connectionTokens->create( [] );
	}

	public function create_payment_intent( $amount, $currency, $payment_method_types, $capture_method, $metadata = [] ) {
		if ( ! $this->stripe ) {
			return null;
		}

		return $this->stripe->paymentIntents->create(
			[
				'amount'               => $amount,
				'currency'             => $currency,
				'payment_method_types' => $payment_method_types,
				'capture_method'       => $capture_method,
				'metadata'             => $metadata,
			]
		);
	}

	public function retrieve_payment_intent( $id ) {
		if ( ! $this->stripe ) {
			return null;
		}

		return $this->stripe->paymentIntents->retrieve( $id, [] );
	}

	public function get_terminals() {
		if ( ! $this->stripe ) {
			return [];
		}

		try {
			return $this->stripe->terminal->readers->all( [ 'limit' => 100 ] )->data;
		} catch ( Exception $e ) {
			return [];
		}
	}
}
