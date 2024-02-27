<?php
/**
 * Chip & PIN payment gateway
 *
 * @since 5.0.0
 *
 * @package WooCommerce_Point_Of_Sale/Gateways
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Gateway_Chip_And_PIN.
 */
#[AllowDynamicProperties]
class WC_POS_Gateway_Chip_And_PIN extends WC_Payment_Gateway {

	/**
	 * Gateway number.
	 *
	 * This is to allow having multiple Chip & PIN gateways.
	 *
	 * @var int Number.
	 */
	public static $number = 0;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( intval( get_option( 'wc_pos_number_chip_and_pin_gateways', 1 ) ) === self::$number ) {
			self::$number = 0;
		}

		// Gateway ID.
		self::$number++;

		// Setup general properties.
		$this->setup_properties();

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Get settings.
		$this->title              = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
		$this->instructions       = $this->get_option( 'instructions' );
		$this->enable_for_methods = $this->get_option( 'enable_for_methods', [] );
		$this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes';
		$this->supports           = [ 'products', 'woocommerce-point-of-sale' ];

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );

		// Customer Emails.
		add_action( 'woocommerce_email_before_order_table', [ $this, 'email_instructions' ], 10, 3 );
	}

	/**
	 * Setup general properties for the gateway.
	 */
	protected function setup_properties() {
		$this->id = 1 === self::$number ? 'pos_chip_and_pin' : 'pos_chip_and_pin_' . self::$number;
		/**
		 * The Chip & PIN icon.
		 *
		 * @since 5.0.0
		 */
		$this->icon = apply_filters( 'wc_pos_chip_and_pin_icon', '' );
		/* translators: %s gateway number */
		$this->method_title       = sprintf( __( 'Chip &amp; PIN%s', 'woocommerce-point-of-sale' ), $this->process_gateway_number( self::$number ) );
		$this->method_description = __( 'Chip & PIN payment gateway.', 'woocommerce-point-of-sale' );
		$this->has_fields         = false;
	}

	/**
	 * Initialise gateway settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = [
			'enabled'                  => [
				'title'       => __( 'Enable/Disable', 'woocommerce-point-of-sale' ),
				/* translators: %s gateway number */
				'label'       => sprintf( __( 'Enable Chip & PIN%s', 'woocommerce-point-of-sale' ), $this->process_gateway_number( self::$number ) ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			],
			'title'                    => [
				'title'       => __( 'Title', 'woocommerce-point-of-sale' ),
				'type'        => 'text',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce-point-of-sale' ),
				/* translators: %s gateway number */
				'default'     => sprintf( __( 'Chip &amp; PIN%s', 'woocommerce-point-of-sale' ), $this->process_gateway_number( self::$number ) ),
				'desc_tip'    => true,
			],
			'description'              => [
				'title'       => __( 'Description', 'woocommerce-point-of-sale' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your website.', 'woocommerce-point-of-sale' ),
				'default'     => __( 'Pay via Chip & PIN.', 'woocommerce-point-of-sale' ),
				'desc_tip'    => true,
			],
			'require_reference_number' => [
				'title'       => __( 'Reference Number', 'woocommerce-point-of-sale' ),
				'type'        => 'checkbox',
				'label'       => __( 'Require reference number', 'woocommerce-point-of-sale' ),
				'description' => __( 'Check this box to make the reference number mandatory filed.', 'woocommerce-point-of-sale' ),
				'default'     => 'no',
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
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'pos_page' !== $screen->id ) {
			return false;
		}

		return parent::is_available();
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		/**
		 * This filter is documented in WC core.
		 *
		 * @since 5.0.0
		 */
		$order->update_status( apply_filters( "woocommerce_{$this->id}_process_payment_order_status", 'completed', $order ), __( 'Payment to be made upon delivery.', 'woocommerce-point-of-sale' ) );

		// Return thankyou redirect.
		return [
			'result'  => 'success',
			'message' => __( 'Success!', 'woocommerce-point-of-sale' ),
		];
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin  Sent to admin.
	 * @param bool     $plain_text Email format: plain text or HTML.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
		}
	}

	/**
	 * Returns the payment gateway number string.
	 *
	 * @param int  $number        Gateway number.
	 * @param bool $leading_space Whether to add a leading space.
	 *
	 * @since 5.2.7
	 *
	 * @return string
	 */
	private function process_gateway_number( $number, $leading_space = true ) {
		if ( 1 === $number ) {
			return '';
		}

		return $leading_space ? ' ' . $number : $number;
	}
}
