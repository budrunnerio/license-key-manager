<?php
/**
 * Stripe credit card payment gateway
 *
 * @package WooCommerce_Point_Of_Sale/Gateways
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Gateway_Stripe_Credit_Card.
 */
class WC_POS_Gateway_Stripe_Credit_Card extends WC_Payment_Gateway {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id = 'pos_stripe_credit_card';
		/**
		 * The Stripe Credit Card icon.
		 *
		 * @since 5.0.0
		 */
		$this->icon         = apply_filters( 'wc_pos_stripe_credit_card_icon', '' );
		$this->method_title = __( 'Stripe Credit Card', 'woocommerce-point-of-sale' );
		/* translators: %s url */
		$this->method_description = sprintf( __( 'All other general Stripe settings can be adjusted <a href="%s">here</a>.', 'woocommerce-point-of-sale' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=stripe' ) );
		$this->has_fields         = true;

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->supports    = [ 'products', 'woocommerce-point-of-sale' ];

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
	}

	/**
	 * Initialize gateway settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = [
			'enabled'     => [
				'title'   => __( 'Enable/Disable', 'woocommerce-point-of-sale' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Stripe Cedit Card', 'woocommerce-point-of-sale' ),
				'default' => 'no',
			],
			'title'       => [
				'title'       => __( 'Title', 'woocommerce-point-of-sale' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the clerk sees during checkout.', 'woocommerce-point-of-sale' ),
				'default'     => __( 'Stripe Credit Card', 'woocommerce-point-of-sale' ),
				'desc_tip'    => true,
			],
			'description' => [
				'title'       => __( 'Description', 'woocommerce-point-of-sale' ),
				'description' => __( 'This controls the description which the clerk sees during checkout.', 'woocommerce-point-of-sale' ),
				'type'        => 'textarea',
				'default'     => __( 'Pay with Credit Card.', 'woocommerce-point-of-sale' ),
				'desc_tip'    => true,
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

		// if ( empty( $this->get_option( 'publishable_key' ) ) ) {
		// return false;
		// }

		return parent::is_available();
	}
}
