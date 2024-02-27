<?php
/**
 * The Paymentsense Gateway
 *
 * @package WooCommerce_Point_Of_Sale/Gateways
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Gateway_Paymentsense.
 */
class WC_POS_Gateway_Paymentsense extends WC_Payment_Gateway {

	private $api;
	private $terminals = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id = 'pos_paymentsense';
		/**
		 * The Paymentsense icon.
		 *
		 * @since 4.0.0
		 */
		$this->icon               = apply_filters( 'wc_pos_paymentsense_icon', '' );
		$this->method_title       = __( 'Paymentsense', 'woocommerce-point-of-sale' );
		$this->method_description = __( 'Take payments in person via EMV. More commonly known as Chip & PIN.', 'woocommerce-point-of-sale' );
		$this->has_fields         = true;

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
		$this->api                = new WC_POS_Gateway_Paymentsense_API();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
	}

	/**
	 * Initialize gateway settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = [
			'enabled'           => [
				'title'       => __( 'Enable/Disable', 'woocommerce-point-of-sale' ),
				'label'       => __( 'Enable Paymentsense', 'woocommerce-point-of-sale' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			],
			'title'             => [
				'title'       => __( 'Title', 'woocommerce-point-of-sale' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-point-of-sale' ),
				'default'     => __( 'Paymentsense', 'woocommerce-point-of-sale' ),
				'desc_tip'    => true,
			],
			'description'       => [
				'title'       => __( 'Description', 'woocommerce-point-of-sale' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your website.', 'woocommerce-point-of-sale' ),
				'default'     => __( 'Pay with EMV terminal.', 'woocommerce-point-of-sale' ),
				'desc_tip'    => true,
			],
			'credentials'       => [
				'title'       => __( 'Credentials', 'woocommerce-point-of-sale' ),
				'type'        => 'title',
				'description' => __( 'Enter the settings given to you by Paymentsense when setting up your account. This includes the Host Address and an API Key.', 'woocommerce-point-of-sale' ),
			],
			'host_address'      => [
				'title'       => __( 'Host Address', 'woocommerce-point-of-sale' ),
				'type'        => 'text',
				'description' => __( 'Enter the Paymentsense Host Address.', 'woocommerce-point-of-sale' ),
				'desc_tip'    => true,
			],
			'api_key'           => [
				'title'       => __( 'API Key', 'woocommerce-point-of-sale' ),
				'type'        => 'text',
				'description' => __( 'Enter the Paymentsense API key.', 'woocommerce-point-of-sale' ),
				'desc_tip'    => true,
			],
			'installer_id'      => [
				'title'       => __( 'Installer ID', 'woocommerce-point-of-sale' ),
				'type'        => 'text',
				'description' => __( 'Enter the Paymentsense Installer ID.', 'woocommerce-point-of-sale' ),
				'desc_tip'    => true,
			],
			'software_house_id' => [
				'title'       => __( 'Software House ID', 'woocommerce-point-of-sale' ),
				'type'        => 'text',
				'description' => __( 'Enter the Paymentsense Software House ID.', 'woocommerce-point-of-sale' ),
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

		return parent::is_available();
	}

	/**
	 * Displays admin optoins.
	 *
	 * @return string Admin options.
	 */
	public function admin_options() {
		$this->test_connection();
		$this->display_errors();

		parent::admin_options();

		if ( count( $this->terminals ) ) :
			?>
		<h3 class="wc-settings-sub-title"><?php esc_html_e( 'Available Terminals', 'woocommerce-point-of-sale' ); ?></h3>
		<ol>
			<?php
			foreach ( $this->terminals as $terminal ) {
				echo '<li>' . esc_html( $terminal['tid'] ) . '</li>';
			}
			?>
		</ol>
			<?php
		endif;
	}

	/**
	 * Test connection.
	 *
	 * @return bool
	 */
	public function test_connection() {
		$response = $this->api->pac_terminals( 0 );

		if ( is_wp_error( $response ) ) {
			$this->add_error( __( 'An error occurred while checking the connection: ', 'woocommerce-point-of-sale' ) . '<strong>' . $response->get_error_message() . '</strong>' );

			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( wp_remote_retrieve_response_code( $response ) != 200 ) {
			$message = isset( $body['userMessage'] ) ? $body['userMessage'] : wp_remote_retrieve_response_message( $response );

			$this->add_error( $message );
			return false;
		}

		if ( isset( $body['terminals'] ) && count( $body['terminals'] ) ) {
			$this->terminals = $body['terminals'];
		}

		return true;
	}

	public function get_polling_transaction( $tid, $transaction, $data ) {
		$response = $this->api->pac_transactions( $tid, $transaction, $data );

		return $response;
	}

	/**
	 * Display payment fields.
	 */
	public function payment_fields() {
		if ( function_exists( 'wc_pos_is_register_page' ) && wc_pos_is_register_page() ) {
			include_once 'views/html-paymentsense-payment-panel.php';

			return;
		}

		parent::payment_fields();
	}
}
