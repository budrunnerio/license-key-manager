<?php
/**
 * Stripe terminal payment gateway
 *
 * @package WooCommerce_Point_Of_Sale/Gateways
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Gateway_Stripe_Terminal.
 */
class WC_POS_Gateway_Stripe_Terminal extends WC_Payment_Gateway {

	private $terminals;
	private $publishable_key;
	private $secret_key;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id = 'pos_stripe_terminal';
		/**
		 * The Stripe Terminal icon.
		 *
		 * @since 5.0.0
		 */
		$this->icon         = apply_filters( 'wc_pos_stripe_terminal_icon', '' );
		$this->method_title = __( 'Stripe Terminal', 'woocommerce-point-of-sale' );
		/* translators: %s url */
		$this->method_description = sprintf( __( 'All other general Stripe settings can be adjusted <a href="%s">here</a>.', 'woocommerce-point-of-sale' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=stripe' ) );
		$this->publishable_key    = WC_POS_Stripe::get_publishable_key();
		$this->secret_key         = WC_POS_Stripe::get_secret_key();
		$this->has_fields         = true;

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->supports    = [ 'products', 'woocommerce-point-of-sale' ];
		$this->terminals   = [];

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );

		$this->load_terminals();
	}

	/**
	 * Initialize gateway settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = [
			'enabled'     => [
				'title'   => __( 'Enable/Disable', 'woocommerce-point-of-sale' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Stripe Terminal', 'woocommerce-point-of-sale' ),
				'default' => 'no',
			],
			'title'       => [
				'title'       => __( 'Title', 'woocommerce-point-of-sale' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-point-of-sale' ),
				'default'     => __( 'Stripe Terminal', 'woocommerce-point-of-sale' ),
				'desc_tip'    => true,
			],
			'description' => [
				'title'       => __( 'Description', 'woocommerce-point-of-sale' ),
				'description' => __( 'Payment method description that the customer will see on your website', 'woocommerce-point-of-sale' ),
				'type'        => 'textarea',
				'default'     => __( 'Pay with Stripe Terminal.', 'woocommerce-point-of-sale' ),
				'desc_tip'    => true,
			],
			'debug_mode'  => [
				'title'       => __( 'Simulated reader', 'woocommerce-point-of-sale' ),
				'description' => __( 'This will enable simulated reader used for testing', 'woocommerce-point-of-sale' ),
				'label'       => __( 'Enable testing reader', 'woocommerce-point-of-sale' ),
				'type'        => 'checkbox',
				'default'     => 'no',
			],
		];
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( ! function_exists( 'wc_pos_is_register_page' ) || ! wc_pos_is_register_page() ) {
			return false;
		}

		if ( is_checkout() ) {
			return false;
		}

		return parent::is_available();
	}

	public function admin_options() {
		parent::admin_options();
		$terminals = $this->terminals;
		include WC_POS()->plugin_path() . '/includes/gateways/stripe/views/html-admin-available-terminals.php';
	}

	public function load_terminals() {
		try {
			$stripe          = new \Stripe\StripeClient( $this->secret_key );
			$this->terminals = $stripe->terminal->readers->all( [ 'limit' => 100 ] )->data;
		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage() );
		}
	}
}
