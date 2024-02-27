<?php
/**
 * WooCommerce Authorize.Net Gateway
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Authorize.Net Gateway to newer
 * versions in the future. If you wish to customize WooCommerce Authorize.Net Gateway for your
 * needs please refer to http://docs.woocommerce.com/document/authorize-net-cim/
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2023, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_12_0 as Framework;

/**
 * Authorize.Net Payment Gateway Parent Class
 *
 * Functionality which is shared between the credit card and echeck gateways
 *
 * @since 2.0.0
 */
class WC_Gateway_Authorize_Net_CIM extends Framework\SV_WC_Payment_Gateway_Direct {


	/** @var string the inline payment form */
	const FEATURE_PAYMENT_FORM_INLINE = 'inline';

	/** @var string the lightbox/popover payment form */
	const FEATURE_PAYMENT_FORM_LIGHTBOX = 'lightbox';

	/** @var string the hosted/iframe payment form */
	const FEATURE_PAYMENT_FORM_HOSTED = 'hosted';


	/** @var string authorize.net API login ID */
	public $api_login_id;

	/** @var string authorize.net API transaction key */
	public $api_transaction_key;

	/** @var string authorize.net API signature key */
	public $api_signature_key;

	/** @var string authorize.net test API login ID */
	public $test_api_login_id;

	/** @var string authorize.net test API transaction key */
	public $test_api_transaction_key;

	/** @var string authorize.net test API signature key */
	public $test_api_signature_key;

	/** @var string API client key */
	protected $client_key;

	/** @var WC_Authorize_Net_CIM_API instance */
	protected $api;

	/** @var array shared settings names */
	protected $shared_settings_names = array( 'api_login_id', 'api_transaction_key', 'api_signature_key', 'test_api_login_id', 'test_api_transaction_key', 'test_api_signature_key' );

	/** @var string the enabled Accept.js form type */
	protected $form_type;

	/** @var \SkyVerge\WooCommerce\Authorize_Net\Handlers\Hosted_Payment_Handler payment handler instance */
	protected $payment_handler;


	/**
	 * Constructs the gateway.
	 *
	 * @since 3.0.0
	 *
	 * @param string $id gateway ID
	 * @param WC_Authorize_Net_CIM $plugin plugin instance
	 * @param array $args gateway args
	 */
	public function __construct( $id, WC_Authorize_Net_CIM $plugin, array $args ) {

		parent::__construct( $id, $plugin, $args );

		// initialize the payment handler
		$this->init_payment_handler();

		// reset the button text so settings can be checked
		$this->order_button_text = $this->get_order_button_text();

		// log accept.js requests and responses
		add_action( 'wp_ajax_wc_' . $this->get_id() . '_log_js_data',        [ $this, 'log_accept_js_data' ] );
		add_action( 'wp_ajax_nopriv_wc_' . $this->get_id() . '_log_js_data', [ $this, 'log_accept_js_data' ] );
	}


	/**
	 * Initializes the capture handler.
	 *
	 * @since 3.3.2
	 */
	public function init_capture_handler() {

		$this->capture_handler = new SkyVerge\WooCommerce\Authorize_Net\Handlers\Capture( $this );
	}


	/**
	 * Initializes the payment handler.
	 *
	 * @since 3.0.0
	 */
	protected function init_payment_handler() {

		if ( $this->is_hosted_payment_form_enabled() ) {
			$this->payment_handler = new SkyVerge\WooCommerce\Authorize_Net\Handlers\Hosted_Payment_Handler( $this );
		}
	}


	/**
	 * Returns an array of form fields specific for this method
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway::get_method_form_fields()
	 * @return array of form fields
	 */
	protected function get_method_form_fields() {

		$fields = array(

			'api_login_id' => array(
				'title'    => __( 'API Login ID', 'woocommerce-gateway-authorize-net-cim' ),
				'type'     => 'text',
				'class'    => 'environment-field production-field',
				'desc_tip' => __( 'Your Authorize.Net API Login ID', 'woocommerce-gateway-authorize-net-cim' ),
			),

			'api_transaction_key' => array(
				'title'    => __( 'API Transaction Key', 'woocommerce-gateway-authorize-net-cim' ),
				'type'     => 'password',
				'class'    => 'environment-field production-field',
				'desc_tip' => __( 'Your Authorize.Net API Transaction Key', 'woocommerce-gateway-authorize-net-cim' ),
			),

			'api_signature_key' => array(
				'title'    => __( 'API Signature Key', 'woocommerce-gateway-authorize-net-cim' ),
				'type'     => 'password',
				'class'    => 'environment-field production-field',
				'desc_tip' => __( 'Your Authorize.Net API Signature Key, used for verifying webhooks', 'woocommerce-gateway-authorize-net-cim' ),
			),

			'test_api_login_id' => array(
				'title'    => __( 'Test API Login ID', 'woocommerce-gateway-authorize-net-cim' ),
				'type'     => 'text',
				'class'    => 'environment-field test-field',
				'desc_tip' => __( 'Your test Authorize.Net API Login ID', 'woocommerce-gateway-authorize-net-cim' ),
			),

			'test_api_transaction_key' => array(
				'title'    => __( 'Test API Transaction Key', 'woocommerce-gateway-authorize-net-cim' ),
				'type'     => 'password',
				'class'    => 'environment-field test-field',
				'desc_tip' => __( 'Your test Authorize.Net API Transaction Key', 'woocommerce-gateway-authorize-net-cim' ),
			),

			'test_api_signature_key' => array(
				'title'    => __( 'Test API Signature Key', 'woocommerce-gateway-authorize-net-cim' ),
				'type'     => 'password',
				'class'    => 'environment-field test-field',
				'desc_tip' => __( 'Your test Authorize.Net API Signature Key, used for processing webhooks', 'woocommerce-gateway-authorize-net-cim' ),
			),
		);

		return array_merge( $fields, $this->get_payment_form_type_fields() );
	}


	/**
	 * Gets the payment form type fields.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	protected function get_payment_form_type_fields() {

		$fields  = [];
		$options = [];

		if ( $this->supports_inline_payment_form() ) {
			$options[ self::FEATURE_PAYMENT_FORM_INLINE ] = __( 'Inline', 'woocommerce-gateway-authorize-net-cim' );
		}

		if ( $this->supports_lightbox_payment_form() ) {
			$options[ self::FEATURE_PAYMENT_FORM_LIGHTBOX ] = __( 'Lightbox', 'woocommerce-gateway-authorize-net-cim' );
		}

		if ( $this->supports_hosted_payment_form() ) {
			$options[ self::FEATURE_PAYMENT_FORM_HOSTED ] = __( 'Hosted', 'woocommerce-gateway-authorize-net-cim' );
		}

		if ( ! empty( $options ) ) {

			$fields = [
				// TODO: determine the best messaging for this setting
				'form_type' => [
					'title'       => __( 'Payment form type', 'woocommerce-gateway-authorize-net-cim' ),
					'type'        => 'select',
					'class'       => 'form-type-select',
					'description' => __( 'Select the desired payment form behavior at checkout.', 'woocommerce-gateway-authorize-net-cim' ),
					'options'     => $options,
					'default'     => current( array_keys( $options ) ),
				],
			];
		}

		return $fields;
	}


	/**
	 * Display settings page with some additional JS for hiding conditional fields.
	 *
	 * @since 3.0.0
	 *
	 * @see Framework\SV_WC_Payment_Gateway::admin_options()
	 */
	public function admin_options() {

		parent::admin_options();

		// add inline javascript
		ob_start();

		?>
		( function( $ ) {

			$( '.form-type-select' ).change( function() {

				var value = $( this ).val();
				var tokenization_field = $( '#woocommerce_<?php echo esc_js( $this->get_id() ); ?>_tokenization' );

				if ( '<?php echo esc_js( self::FEATURE_PAYMENT_FORM_HOSTED ); ?>' === value ) {

					var description = '<?php _e( 'Display an embedded payment form on the pay page for PCI-DSS SAQ A compliance.', 'woocommerce-gateway-authorize-net-cim ' ); ?>';

					tokenization_field.parents( 'tr' ).hide();

				} else {

					if ( '<?php echo esc_js( self::FEATURE_PAYMENT_FORM_LIGHTBOX ); ?>' === value ) {
						var description = '<?php esc_html_e( 'Display the payment form as a lightbox at checkout for PCI-DSS SAQ A compliance.', 'woocommerce-gateway-authorize-net-cim ' ); ?>';
					} else {
						var description = '<?php esc_html_e( 'Display the payment form inline at checkout for PCI-DSS SAQ A-EP compliance.', 'woocommerce-gateway-authorize-net-cim ' ); ?>';
					}

					tokenization_field.parents( 'tr' ).show();
				}

				$( this ).next( '.description' ).html( description );

			} ).change();

		} ) ( jQuery );
		<?php

		wc_enqueue_js( ob_get_clean() );
	}


	/**
	 * Processes the admin options.
	 *
	 * @internal
	 *
	 * @since 3.0.1
	 *
	 * @return bool
	 */
	public function process_admin_options() {

		// clear the client key transients in case the API credentials have changed
		foreach ( $this->get_environments() as $id => $name ) {
			delete_transient( 'wc_' . $this->get_id() . '_' . $id . '_client_key' );
		}

		return parent::process_admin_options();
	}


	/**
	 * Enqueues the gateway-specific assets if present.
	 *
	 * @since 3.0.0
	 */
	protected function enqueue_gateway_assets() {

		// don't enqueue anything if the hosted form is enabled
		if ( $this->is_hosted_payment_form_enabled() || ( is_account_page() && ! is_add_payment_method_page() ) ) {
			return;
		}

		parent::enqueue_gateway_assets();

		$url    = '';
		$handle = '';

		if ( $this->is_lightbox_payment_form_enabled() ) {

			$url    = $this->is_production_environment() ? 'https://js.authorize.net/v3/AcceptUI.js' : 'https://jstest.authorize.net/v3/AcceptUI.js';
			$handle = $this->get_gateway_js_handle() . '-accept-lightbox';

		} elseif ( $this->is_inline_payment_form_enabled() ) {

			$url    = $this->is_production_environment() ? 'https://js.authorize.net/v1/Accept.js' : 'https://jstest.authorize.net/v1/Accept.js';
			$handle = $this->get_gateway_js_handle() . '-accept-inline';
		}

		if ( $url && $handle && $this->should_enqueue_accept_js() ) {
			wp_enqueue_script( $handle, $url, [], null, true );
		}
	}


	/**
	 * Determines if Accept JS should be enqueued.
	 *
	 * This is mainly necessary because WooCommerce loads the checkout block code on the order received page as well.
	 * However our checkout block code requires Accept.js and if not enqueued it will trigger an error.
	 *
	 * @since 3.10.-dev.1
	 *
	 * @return bool
	 */
	protected function should_enqueue_accept_js() : bool {

		if ( Framework\Blocks\Blocks_Handler::is_checkout_block_in_use() ) {
			$should_load_on_order_received_page = true;
		} else {
			$should_load_on_order_received_page = ! is_order_received_page();
		}

		return $should_load_on_order_received_page
			&& ( is_checkout() || is_checkout_pay_page() || is_add_payment_method_page() );
	}


	/**
	 * Initializes the payment form instance.
	 *
	 * @since 3.2.6
	 *
	 * @return \SkyVerge\WooCommerce\Authorize_Net\Payment_Form
	 */
	protected function init_payment_form_instance() {

		return new \SkyVerge\WooCommerce\Authorize_Net\Payment_Form( $this );
	}


	/**
	 * Validates the payment fields.
	 *
	 * Overridden to just validate the client-side tokens.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function validate_fields() {

		$is_valid = true;

		// don't validate if the hosted form is enabled
		if ( $this->is_hosted_payment_form_enabled() ) {
			return $is_valid;
		}

		// normal validation if a saved method is chosen
		if ( Framework\SV_WC_Helper::get_posted_value( 'wc-' . $this->get_id_dasherized() . '-payment-token' ) ) {

			$is_valid = parent::validate_fields();

		// otherwise, just validate the Accept.js nonces
		} else {

			// no direct payment data, so just check that nonces are present
			try {

				if ( ! Framework\SV_WC_Helper::get_posted_value( 'wc-' . $this->get_id_dasherized() . '-payment-nonce' ) ) {
					throw new Framework\SV_WC_Payment_Gateway_Exception( 'Accept.js Error: payment nonce is missing' );
				}

				if ( ! Framework\SV_WC_Helper::get_posted_value( 'wc-' . $this->get_id_dasherized() . '-payment-descriptor' ) ) {
					throw new Framework\SV_WC_Payment_Gateway_Exception( 'Accept.js Error: payment descriptor is missing' );
				}

			} catch ( Framework\SV_WC_Payment_Gateway_Exception $exception ) {

				Framework\SV_WC_Helper::wc_add_notice( __( 'An error occurred, please try again or try an alternate form of payment.', 'woocommerce-gateway-authorize-net-cim' ), 'error' );

				$this->add_debug_message( $exception->getMessage(), 'error' );

				$is_valid = false;
			}
		}

		return $is_valid;
	}


	/**
	 * Gets the payment handler.
	 *
	 * @since 3.0.0
	 *
	 * @return \SkyVerge\WooCommerce\Authorize_Net\Handlers\Hosted_Payment_Handler
	 */
	public function get_payment_handler() {

		return $this->payment_handler;
	}


	/**
	 * Logs data generated from requests and responses from Accept.js.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function log_accept_js_data() {

		check_ajax_referer( 'wc_' . $this->get_id() . '_log_js_data', 'security' );

		$message = sprintf( "Accept.js %1\$s:\n ", ! empty( $_REQUEST['type'] ) ? ucfirst( $_REQUEST['type'] ) : 'Request' );

		// add the data
		if ( ! empty( $_REQUEST['data'] ) ) {

			// mask the client key
			if ( ! empty( $_REQUEST['data']['authData']['clientKey'] ) ) {
				$_REQUEST['data']['authData']['clientKey'] = '****';
			}

			// mask the login ID
			if ( ! empty( $_REQUEST['data']['authData']['apiLoginID'] ) ) {
				$_REQUEST['data']['authData']['apiLoginID'] = '****';
			}

			$message .= print_r( $_REQUEST['data'], true );
		}

		$this->add_debug_message( $message );
	}


	/**
	 * Override the standard transaction processing to cover these situations:
	 *
	 * 1) For a tokenized transaction where the billing information entered does
	 * not match the billing information stored on the token -> update the token
	 * prior to processing the transaction
	 *
	 * 2) For a tokenized transaction when the order has no shipping address ID
	 * -> create one and use it for the transaction
	 *
	 * 3) For a tokenized transaction when the shipping address for the order does
	 * not match the shipping address stored for the customer in CIM -> update
	 * the shipping address stored in CIM to the address for the order
	 *
	 * @TODO: this method is not invoked for subscription renewal or pre-order
	 * release transactions and it probably should be @MR 2015-07-24
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_Direct::do_transaction()
	 * @param \WC_Order $order
	 * @return bool
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function do_transaction( $order ) {

		// bail if not profile transaction
		if ( empty( $order->payment->token ) ) {
			return parent::do_transaction( $order );
		}

		/** @var WC_Authorize_Net_CIM_Payment_Profile|null $token */
		$token = $this->get_payment_tokens_handler()->get_token( $order->get_user_id(), $order->payment->token );

		if ( ! $token ) {
			throw new Framework\SV_WC_Plugin_Exception( 'Invalid payment token', 'woocommerce-gateway-authorize-net-cim' );
		}

		// compare the billing information saved on the token with the billing information for the order
		if ( ! $token->billing_matches_order( $order ) ) {

			// does not match, update the existing payment profile
			$this->get_api()->update_tokenized_payment_method( $order );

			// update the token billing hash with the entered info
			$token->update_billing_hash( $order );

			// persist the token to user meta
			$this->get_payment_tokens_handler()->update_token( $order->get_user_id(), $token );
		}

		$shipping_address = $this->get_shipping_address( $order->get_user_id() );

		// no shipping profile, create one
		if ( empty( $order->payment->shipping_address_id ) ) {

			$response = $this->get_api()->create_shipping_address( $order );

			// create_shipping_address() can return an ID if a duplicate exists in CIM, as it
			// will simply return the ID instead of creating the address
			$order->payment->shipping_address_id = is_numeric( $response ) ? $response : $response->get_shipping_address_id();

			// persist profile ID to user meta
			$shipping_address->update_id( $order->payment->shipping_address_id );

			// update the hash and persist to user meta
			$shipping_address->update_hash( $order );

		} elseif ( ! $shipping_address->matches_order( $order ) ) {

			// saved shipping profile does not match shipping info used for order,
			// update the existing shipping profile in CIM
			$this->get_api()->update_shipping_address( $order );

			// update the hash and persist to user meta
			$shipping_address->update_hash( $order );
		}

		// continue processing the transaction
		return parent::do_transaction( $order );
	}


	/**
	 * Add any Authorize.Net specific transaction information as
	 * class members of WC_Order instance.  Added members can include:
	 *
	 * + po_number - PO Number to be included with the transaction via the legacy filter below
	 *
	 * @see WC_Gateway_Authorize_Net_CIM::get_order()
	 *
	 * @since 2.0.0
	 *
	 * @param int $order_id order ID being processed
	 * @return \WC_Order object with payment and transaction information attached
	 */
	public function get_order( $order_id ) {

		// add common order members
		$order = parent::get_order( $order_id );

		// backwards compat for transaction/PO number filters introduced in v1.x
		// @deprecated in 2.0.0
		$order->description = apply_filters( 'wc_authorize_net_cim_transaction_description', $order->description, $order_id, $this );

		// remove any weirdness in the description
		$order->description = Framework\SV_WC_Helper::str_to_sane_utf8( $order->description );

		// @deprecated in 2.0.0
		$order->payment->po_number = apply_filters( 'wc_authorize_net_cim_transaction_po_number', '', $order_id, $this );

		// add shipping address ID for profile transactions (using existing payment method or adding a new one)
		if ( $order->get_user_id() && ( ! empty( $order->payment->token ) || $this->get_payment_tokens_handler()->should_tokenize() ) ) {

			$shipping_address = $this->get_shipping_address( $order->get_user_id() );

			$order->payment->shipping_address_id = $shipping_address->get_id();
		}

		if ( $last_four = Framework\SV_WC_Helper::get_posted_value( 'wc-' . $this->get_id_dasherized() . '-last-four' ) ) {
			$order->payment->account_number = $order->payment->last_four = substr( $last_four, -4 );
		}

		// nonce data
		if ( empty( $order->payment->opaque_value ) ) {
			$order->payment->opaque_descriptor = Framework\SV_WC_Helper::get_posted_value( 'wc-' . $this->get_id_dasherized() . '-payment-descriptor' );
			$order->payment->opaque_value      = Framework\SV_WC_Helper::get_posted_value( 'wc-' . $this->get_id_dasherized() . '-payment-nonce' );
		}

		return $order;
	}


	/**
	 * Override the default get_order_meta() to provide backwards compatibility
	 * for Subscription/Pre-Order tokens added in v1.x, as the meta keys were
	 * not scoped per gateway. A bulk upgrade of all meta keys while trying to
	 * account for the gateway used for the original order (only defined by the
	 * payment type saved) is too risky given the potential for timeouts.
	 *
	 * Eventually this method can be removed and an upgrade routine can be added
	 * to catch any straggling meta keys that haven't been updated.
	 *
	 * @TODO: Remove me in July 2016 @MR
	 *
	 * @since 2.0.0
	 *
	 * @see Framework\SV_WC_Payment_Gateway::get_order_meta()
	 * @param int|\WC_Order $order ID for order to get meta for
	 * @param string $key meta key
	 * @return mixed
	 */
	public function get_order_meta( $order, $key ) {

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order ) {
			return false;
		}

		$order_id = $order->get_id();

		// Add token meta to renewal orders from subscriptions that were created before CIM v2.x
		if ( 'payment_token' === $key && ! parent::get_order_meta( $order_id, $key ) && $this->get_plugin()->is_subscriptions_active() && wcs_order_contains_renewal( $order ) ) {

			$subscriptions = wcs_get_subscriptions_for_renewal_order( $order );
			$subscription  = array_pop( $subscriptions );

			$parent_order = is_callable( array( $subscription, 'get_parent' ) ) ? $subscription->get_parent() : $subscription->order;

			// if the parent order has a payment token stored, store it for the renewal order too
			if ( $parent_order instanceof \WC_Order && $parent_token = $parent_order->get_meta( '_wc_authorize_net_cim_payment_profile_id' ) ) {

				$this->update_order_meta( $order_id, 'payment_token', $parent_token );

				return $parent_token;
			}
		}

		// v2.0.0-v2.0.3 fix due to copypasta
		if ( 'payment_token' === $key && $order->meta_exists($this->get_order_meta_prefix() . $key ) && $order->meta_exists( '_wc_authorize_net_cim_payment_profile_id' ) ) {

			$legacy_payment_token = $order->get_meta( '_wc_authorize_net_cim_payment_profile_id' );
			$payment_token        = $order->get_meta( $this->get_order_meta_prefix() . 'payment_token' );
			$customer_id          = $order->get_meta( '_wc_authorize_net_cim_customer_profile_id' );

			// oops, payment token was previously overwritten due to copypasta
			// fix by correctly setting the payment token to the value provided in the old meta value ('_wc_authorize_net_cim_payment_profile_id')
			if ( $payment_token === $customer_id ) {

				$this->update_order_meta( $order_id, 'payment_token', $legacy_payment_token );

				return $legacy_payment_token;
			}
		}

		// v1.x token order meta handling
		if ( 'payment_token' === $key && ! $order->meta_exists( $this->get_order_meta_prefix() . $key ) && $order->meta_exists( '_wc_authorize_net_cim_payment_profile_id' ) ) {

			$token = $order->get_meta( '_wc_authorize_net_cim_payment_profile_id' );

			$this->update_order_meta( $order_id, 'payment_token', $token );

			return $token;
		}

		// v1.x customer ID order meta handling
		if ( 'customer_id' === $key && ! $order->meta_exists( $this->get_order_meta_prefix() . $key ) && $order->meta_exists( '_wc_authorize_net_cim_customer_profile_id' ) ) {

			$customer_id = $order->get_meta( '_wc_authorize_net_cim_customer_profile_id' );

			$this->update_order_meta( $order_id, 'customer_id', $customer_id );

			return $customer_id;
		}

		// migrate any missing trans IDs
		if ( 'trans_id' === $key && ! $order->meta_exists( $this->get_order_meta_prefix() . $key ) && $order->get_transaction_id() ) {
			$this->update_order_meta( $order, 'trans_id', $order->get_transaction_id() );
		}

		return $order->get_meta( $this->get_order_meta_prefix() . $key );
	}


	/**
	 * Get the payment tokens handler class instance.
	 *
	 * @since 2.2.0
	 * @return \WC_Authorize_Net_CIM_Payment_Profile_Handler
	 */
	protected function build_payment_tokens_handler() {

		return new WC_Authorize_Net_CIM_Payment_Profile_Handler( $this );
	}


	/**
	 * Auth.net tokenizes payment methods prior to sale
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_Direct::tokenize_before_sale()
	 * @return bool
	 */
	public function tokenize_before_sale() {
		return true;
	}


	/** Customer ID methods ***************************************************/


	/**
	 * CIM generates it's own customer IDs (customer profile IDs) after creating
	 * the customer profile via the API
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway::get_customer_id()
	 * @param int $user_id WP user ID
	 * @param array $args optional additional arguments which can include: environment_id, autocreate (true/false), and order
	 * @return string payment gateway customer id
	 */
	public function get_customer_id( $user_id, $args = array() ) {

		$defaults = array(
			'environment_id' => $this->get_environment(),
			'autocreate'     => true,
			'order'          => null,
		);

		$args = array_merge( $defaults, $args );

		return get_user_meta( $user_id, $this->get_customer_id_user_meta_name( $args['environment_id'] ), true );
	}


	/**
	 * Change the meta name for the customer ID to:
	 *
	 * `wc_authorize_net_cim_customer_profile_id{_non-production environment ID}`
	 *
	 * @since 2.0.0
	 * @param string|null $environment_id
	 * @return string
	 */
	public function get_customer_id_user_meta_name( $environment_id = null ) {

		if ( null === $environment_id ) {
			$environment_id = $this->get_environment();
		}

		// no leading underscore since this is meant to be visible to the admin
		return 'wc_' . $this->get_plugin()->get_id() . '_customer_profile_id' . ( ! $this->is_production_environment( $environment_id ) ? '_' . $environment_id : '' );
	}


	/**
	 * Return an instance of the shipping address class for the given user
	 * @since 2.0.0
	 * @param string|int $user_id WP user ID
	 * @return \WC_Authorize_Net_CIM_Shipping_Address
	 */
	public function get_shipping_address( $user_id ) {

		return new WC_Authorize_Net_CIM_Shipping_Address( $user_id, $this );
	}


	/**
	 * Helper method to remove all local customer/payment profile data
	 *
	 * 1) removes local tokens
	 * 2) removes local customer ID
	 *
	 * @since 2.0.0
	 */
	public function remove_local_profile() {

		$user_id = get_current_user_id();

		$env = $this->is_test_environment() ? '_test' : '';

		// remove local tokens, hardcoded key names because this method could be invoked
		// from either the credit card or eCheck gateway, and we want to remove *both*
		// sets of tokens, not simply the tokens from the gateway that invoked the method.
		delete_user_meta( $user_id, '_wc_authorize_net_cim_credit_card_payment_tokens' . $env );
		delete_user_meta( $user_id, '_wc_authorize_net_cim_echeck_payment_tokens' . $env );

		// remove shipping address ID
		delete_user_meta( $user_id, 'wc_authorize_net_cim_shipping_address_id' . $env );

		// remove customer ID
		$this->remove_customer_id( $user_id );
	}


	/** Subscriptions *********************************************************/


	/**
	 * Tweak the labels shown when editing the payment method for a Subscription,
	 * hooked from Framework\SV_WC_Payment_Gateway_Integration_Subscriptions
	 *
	 *
	 * @since 2.0.3
	 * @see Framework\SV_WC_Payment_Gateway_Integration_Subscriptions::admin_add_payment_meta()
	 * @param array $meta payment meta
	 * @param \WC_Subscription $subscription subscription being edited
	 * @return array
	 */
	public function subscriptions_admin_add_payment_meta( $meta, $subscription ) {

		if ( isset( $meta[ $this->get_id() ] ) ) {
			$meta[ $this->get_id() ]['post_meta'][ $this->get_order_meta_prefix() . 'payment_token' ]['label'] = __( 'Payment Profile ID', 'woocommerce-gateway-authorize-net-cim' );
			$meta[ $this->get_id() ]['post_meta'][ $this->get_order_meta_prefix() . 'customer_id' ]['label']   = __( 'Customer Profile ID', 'woocommerce-gateway-authorize-net-cim' );
		}

		return $meta;
	}


	/**
	 * Validate the CIM payment meta for a Subscription by ensuring the payment
	 * profile ID and customer profile ID are both numeric
	 *
	 *
	 * @since 2.0.3
	 * @see Framework\SV_WC_Payment_Gateway_Integration_Subscriptions::admin_validate_payment_meta()
	 * @param array $meta payment meta
	 * @throws \Exception if payment profile/customer profile IDs are not numeric
	 */
	public function subscriptions_admin_validate_payment_meta( $meta ) {

		// payment profile ID (payment_token) must be numeric
		if ( ! ctype_digit( (string) $meta['post_meta'][ $this->get_order_meta_prefix() . 'payment_token' ]['value'] ) ) {
			throw new \Exception( __( 'Payment Profile ID must be numeric.', 'woocommerce-gateway-authorize-net-cim' ) );
		}

		// customer profile ID (customer_id) must be numeric
		if ( ! ctype_digit( (string) $meta['post_meta'][ $this->get_order_meta_prefix() . 'customer_id' ]['value'] ) ) {
			throw new \Exception( __( 'Customer Profile ID must be numeric.', 'woocommerce-gateway-authorize-net-cim' ) );
		}
	}


	/** Conditional methods ********************************************************/


	/**
	 * Determines if the gateway is properly configured to perform transactions.
	 *
	 * Authorize.Net requires: API Login ID & API Transaction Key.
	 *
	 * @see Framework\SV_WC_Payment_Gateway::is_configured()
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function is_configured() {

		$is_configured = parent::is_configured();

		// missing configuration
		if ( ! $this->get_api_login_id() || ! $this->get_api_transaction_key() || ! $this->get_form_type() ) {
			$is_configured = false;
		}

		return $is_configured;
	}


	/**
	 * Checks if the CIM add-on is enabled for the provided authorize.net account
	 * by requesting a token for the hosted profile page using dummy data. The
	 * 'getHostedProfilePageRequest' method was chosen as it's lightweight
	 * and multiple calls to it have no effect on the provided authorize.net account
	 *
	 * @since 1.0.4
	 * @return bool true if CIM feature is enabled on provided authorize.net account, false otherwise
	 */
	public function is_cim_feature_enabled() {

		try {

			$customer_id =  $this->get_customer_id( get_current_user_id() );

			$this->get_api()->get_hosted_profile_page_token( $customer_id ? $customer_id : 0 );

		} catch ( Framework\SV_WC_Plugin_Exception $e ) {

			// E00044 is 'Customer Information Manager is not enabled.' error
			if ( 44 === (int) $e->getCode() ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Determines if this is a hosted gateway.
	 *
	 * Overridden to take the form setting into account.
	 *
	 * @return bool
	 */
	public function is_hosted_gateway() {

		return $this->is_lightbox_payment_form_enabled() || $this->is_hosted_payment_form_enabled();
	}


	/**
	 * Determines if tokenization is supported for the gateway.
	 *
	 * Tokenization is not supported if the hosted/iframe form is used.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function supports_tokenization() {

		return parent::supports_tokenization() && ! $this->is_hosted_payment_form_enabled();
	}


	/**
	 * Determines if the enhanced payment form is supported.
	 *
	 * Not supported if using the hosted payment form.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function supports_payment_form() {

		return parent::supports_payment_form() && ! $this->is_hosted_payment_form_enabled();
	}


	/**
	 * Determines whether the inline payment form is supported.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function supports_inline_payment_form() {

		return $this->supports( self::FEATURE_PAYMENT_FORM_INLINE );
	}


	/**
	 * Determines whether the lightbox/popover payment form is supported.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function supports_lightbox_payment_form() {

		return $this->supports( self::FEATURE_PAYMENT_FORM_LIGHTBOX );
	}


	/**
	 * Determines whether the hosted/iframe payment form is supported.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function supports_hosted_payment_form() {

		return $this->supports( self::FEATURE_PAYMENT_FORM_HOSTED );
	}


	/**
	 * Determines if the inline payment form is enabled.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function is_inline_payment_form_enabled() {

		return $this->supports_inline_payment_form() && self::FEATURE_PAYMENT_FORM_INLINE === $this->get_form_type();
	}


	/**
	 * Determines if the lightbox payment form is enabled.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function is_lightbox_payment_form_enabled() {

		return $this->supports_lightbox_payment_form() && self::FEATURE_PAYMENT_FORM_LIGHTBOX === $this->get_form_type();
	}


	/**
	 * Determines if the hosted/iframe payment form is enabled.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function is_hosted_payment_form_enabled() {

		return $this->supports_hosted_payment_form() && self::FEATURE_PAYMENT_FORM_HOSTED === $this->get_form_type();
	}


	/** Utility methods ********************************************************/


	/**
	 * Get the API object
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway::get_api()
	 * @return \WC_Authorize_Net_CIM_API API instance
	 */
	public function get_api() {

		if ( is_object( $this->api ) ) {
			return $this->api;
		}

		$includes_path = $this->get_plugin()->get_plugin_path() . '/src';

		// main API class
		require_once( $includes_path . '/api/class-wc-authorize-net-cim-api.php' );

		// abstracts
		require_once( $includes_path . '/api/abstract-wc-authorize-net-cim-api-request.php' );
		require_once( $includes_path . '/api/abstract-wc-authorize-net-cim-api-response.php' );
		require_once( $includes_path . '/api/transaction/abstract-wc-authorize-net-cim-api-transaction-request.php' );
		require_once( $includes_path . '/api/transaction/abstract-wc-authorize-net-cim-api-transaction-response.php' );

		// profiles
		require_once( $includes_path . '/api/profile/class-wc-authorize-net-cim-api-profile-request.php' );
		require_once( $includes_path . '/api/profile/class-wc-authorize-net-cim-api-profile-response.php' );
		require_once( $includes_path . '/api/profile/class-wc-authorize-net-cim-api-customer-profile-request.php' );
		require_once( $includes_path . '/api/profile/class-wc-authorize-net-cim-api-customer-profile-response.php' );
		require_once( $includes_path . '/api/profile/class-wc-authorize-net-cim-api-payment-profile-request.php' );
		require_once( $includes_path . '/api/profile/class-wc-authorize-net-cim-api-payment-profile-response.php' );
		require_once( $includes_path . '/api/profile/class-wc-authorize-net-cim-api-shipping-address-request.php' );
		require_once( $includes_path . '/api/profile/class-wc-authorize-net-cim-api-shipping-address-response.php' );

		// hosted profile page
		require_once( $includes_path . '/api/profile-page/class-wc-authorize-net-cim-api-hosted-profile-page-request.php' );
		require_once( $includes_path . '/api/profile-page/class-wc-authorize-net-cim-api-hosted-profile-page-response.php' );

		// webhooks
		require_once( $includes_path . '/api/webhooks/class-wc-authorize-net-cim-api-webhook-request.php' );
		require_once( $includes_path . '/api/webhooks/class-wc-authorize-net-cim-api-webhook-response.php' );

		// transactions
		require_once( $includes_path . '/api/transaction/class-wc-authorize-net-cim-api-non-profile-transaction-request.php' );
		require_once( $includes_path . '/api/transaction/class-wc-authorize-net-cim-api-non-profile-transaction-response.php' );
		require_once( $includes_path . '/api/transaction/class-wc-authorize-net-cim-api-profile-transaction-request.php' );
		require_once( $includes_path . '/api/transaction/class-wc-authorize-net-cim-api-profile-transaction-response.php' );
		require_once( $includes_path . '/api/transaction/class-wc-authorize-net-cim-api-transaction-details-request.php' );
		require_once( $includes_path . '/api/transaction/class-wc-authorize-net-cim-api-transaction-details-response.php' );

		// merchant details
		require_once( $includes_path . '/api/Merchant_Details_Request.php' );
		require_once( $includes_path . '/api/Merchant_Details_Response.php' );

		// payment form
		require_once( $includes_path . '/api/Hosted/Payment_Form_Request.php' );
		require_once( $includes_path . '/api/Hosted/Payment_Form_Response.php' );

		// response message helper
		require_once( $includes_path . '/api/class-wc-authorize-net-cim-api-response-message-helper.php' );

		return $this->api = new WC_Authorize_Net_CIM_API( $this->get_id(), $this->get_environment(), $this->get_api_login_id(), $this->get_api_transaction_key() );
	}


	/**
	 * Returns the API Login ID based on the current environment
	 *
	 * @since 2.0.0
	 * @param string $environment_id optional one of 'test' or 'production', defaults to current configured environment
	 * @return string the API login ID to use
	 */
	public function get_api_login_id( $environment_id = null ) {

		if ( null === $environment_id ) {
			$environment_id = $this->get_environment();
		}

		return 'production' === $environment_id ? $this->api_login_id : $this->test_api_login_id;
	}


	/**
	 * Returns the API Transaction Key based on the current environment
	 *
	 * @since 2.0.0
	 * @param string $environment_id optional one of 'test' or 'production', defaults to current configured environment
	 * @return string the API transaction key to use
	 */
	public function get_api_transaction_key( $environment_id = null ) {

		if ( null === $environment_id ) {
			$environment_id = $this->get_environment();
		}

		return 'production' === $environment_id ? $this->api_transaction_key : $this->test_api_transaction_key;
	}


	/**
	 * Gets the API Signature Key based on the current environment.
	 *
	 * @since 2.8.0
	 *
	 * @param string $environment_id one of 'test' or 'production', defaults to current configured environment
	 * @return string
	 */
	public function get_api_signature_key( $environment_id = null ) {

		if ( null === $environment_id ) {
			$environment_id = $this->get_environment();
		}

		return 'production' === $environment_id ? $this->api_signature_key : $this->test_api_signature_key;
	}


	/**
	 * Get the API client key.
	 *
	 * @since 2.4.0
	 *
	 * @return string
	 */
	public function get_client_key() {

		$client_key = get_transient( 'wc_' . $this->get_id() . '_' . $this->get_environment() . '_client_key' );
		$api        = $this->get_api();

		if ( ! $client_key && $this->is_configured() ) {

			try {

				$response = $api->get_merchant_details();

				if ( ! $response->get_client_key() ) {
					throw new Framework\SV_WC_Plugin_Exception( 'Unable to retrieve client key' );
				}

				$client_key = $response->get_client_key();

				set_transient( 'wc_' . $this->get_id() . '_' . $this->get_environment() . '_client_key', $client_key, HOUR_IN_SECONDS );

			} catch ( Framework\SV_WC_Plugin_Exception $exception ) {

				$this->get_plugin()->log( 'Error getting merchant account details. ' . $exception->getMessage(), $this->get_id() );
			}
		}

		$this->client_key = (string) $client_key;

		return $this->client_key;
	}


	/**
	 * Ensure a customer ID is created in CIM for guest customers
	 *
	 * A customer ID must exist in CIM before it can be used so a guest
	 * customer ID cannot be generated on the fly. This ensures a customer is
	 * created when a payment method is tokenized for transactions such as a
	 * pre-order guest purchase.
	 *
	 * @since 2.2.1
	 * @see Framework\SV_WC_Payment_Gateway::get_guest_customer_id()
	 * @param WC_Order $order
	 * @return bool false
	 */
	public function get_guest_customer_id( WC_Order $order ) {

		// is there a customer id already tied to this order?
		if ( $customer_id = $this->get_order_meta( $order, 'customer_id' ) ) {
			return $customer_id;
		}

		// default to false as a customer must be created first
		return false;
	}


	/**
	 * Gets the configured form type.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_form_type() {

		return $this->form_type ?: self::FEATURE_PAYMENT_FORM_INLINE;
	}


}
