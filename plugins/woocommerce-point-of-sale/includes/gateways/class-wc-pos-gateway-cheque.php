<?php
/**
 * In-store check payment gateway
 *
 * @package WooCommerce_Point_Of_Sale/Gateways
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Gateway_Cheque.
 */
#[AllowDynamicProperties]
class WC_POS_Gateway_Cheque extends WC_Payment_Gateway {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id = 'pos_cheque';
		/**
		 * This filter is documented in WC core.
		 *
		 * @since 5.0.0
		 */
		$this->icon               = apply_filters( 'woocommerce_cheque_icon', '' );
		$this->has_fields         = false;
		$this->method_title       = _x( 'In-store Check Payments', 'In-store check payment method', 'woocommerce-point-of-sale' );
		$this->method_description = __( 'Take payments in person via checks. This offline gateway can also be useful to test purchases.', 'woocommerce-point-of-sale' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions' );
		$this->supports     = [ 'products', 'woocommerce-point-of-sale' ];

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
		add_action( 'woocommerce_email_before_order_table', [ $this, 'email_instructions' ], 10, 3 );
		add_filter( 'wc_pos_app_data', [ $this, 'params' ] );
	}

	/**
	 * Check if the gateway is available.
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( ! function_exists( 'wc_pos_is_register_page' ) || ! wc_pos_is_register_page() || is_checkout() ) {
			return false;
		}

		return parent::is_available();
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {

		$this->form_fields = [
			'enabled'      => [
				'title'   => __( 'Enable/Disable', 'woocommerce-point-of-sale' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable In-store Check Payments', 'woocommerce-point-of-sale' ),
				'default' => 'no',
			],
			'title'        => [
				'title'       => __( 'Title', 'woocommerce-point-of-sale' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-point-of-sale' ),
				'default'     => _x( 'In-store Check Payments', 'Check payment method', 'woocommerce-point-of-sale' ),
				'desc_tip'    => true,
			],
			'description'  => [
				'title'       => __( 'Description', 'woocommerce-point-of-sale' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce-point-of-sale' ),
				'default'     => __( 'Please send a check to Store Name, Store Street, Store Town, Store State / County, Store Postcode.', 'woocommerce-point-of-sale' ),
				'desc_tip'    => true,
			],
			'instructions' => [
				'title'       => __( 'Instructions', 'woocommerce-point-of-sale' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce-point-of-sale' ),
				'default'     => '',
				'desc_tip'    => true,
			],
		];
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin Sent to admin.
	 * @param bool     $plain_text Email format: plain text or HTML.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && 'cheque' === $order->get_payment_method() && $order->has_status( 'on-hold' ) ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
		}
	}

	/**
	 * Add gateway params.
	 *
	 * @param array $params
	 * @return array
	 */
	public function params( $params ) {
		$settings                             = get_option( 'woocommerce_pos_cheque_settings', [] );
		$params['pos']['cheque_instructions'] = isset( $settings['instructions'] ) ? $settings['instructions'] : '';

		return $params;
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order->get_total() > 0 ) {
			// Mark as on-hold (we're awaiting for the cheque).
			$order->update_status(
				/**
				 * This filter is documented in WC core.
				 *
				 * @since 5.0.0
				 */
				apply_filters( 'woocommerce_cheque_process_payment_order_status', 'on-hold', $order ),
				_x( 'Awaiting check payment', 'Check payment method', 'woocommerce-point-of-sale' )
			);
		} else {
			$order->payment_complete();
		}
	}
}
