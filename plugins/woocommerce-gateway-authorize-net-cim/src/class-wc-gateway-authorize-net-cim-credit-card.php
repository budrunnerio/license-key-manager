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

use SkyVerge\WooCommerce\Authorize_Net\Blocks\Credit_Card_Checkout_Block_Integration;
use SkyVerge\WooCommerce\PluginFramework\v5_12_0 as Framework;

/**
 * Authorize.Net Payment Gateway
 *
 * Handles all credit card purchases
 *
 * This is a direct credit card gateway that supports card types, charge,
 * and authorization
 *
 * @since 2.0.0
 */
class WC_Gateway_Authorize_Net_CIM_Credit_Card extends WC_Gateway_Authorize_Net_CIM {


	/** @var Credit_Card_Checkout_Block_Integration|null $credit_card_checkout_block */
	protected ?Credit_Card_Checkout_Block_Integration $credit_card_checkout_block = null;


	/**
	 * Initialize the gateway
	 *
	 * @since 2.0.0
	 */
	public function __construct() {

		parent::__construct(
			WC_Authorize_Net_CIM::CREDIT_CARD_GATEWAY_ID,
			wc_authorize_net_cim(),
			array(
				'method_title'       => __( 'Authorize.Net Credit Card', 'woocommerce-gateway-authorize-net-cim' ),
				'method_description' => __( 'Allow customers to securely pay using their credit cards with Authorize.Net.', 'woocommerce-gateway-authorize-net-cim' ),
				'supports'           => array(
					self::FEATURE_PRODUCTS,
					self::FEATURE_CARD_TYPES,
					self::FEATURE_PAYMENT_FORM,
					self::FEATURE_PAYMENT_FORM_INLINE,
					self::FEATURE_PAYMENT_FORM_LIGHTBOX,
					self::FEATURE_TOKENIZATION,
					self::FEATURE_TOKEN_EDITOR,
					self::FEATURE_CREDIT_CARD_CHARGE,
					self::FEATURE_CREDIT_CARD_CHARGE_VIRTUAL,
					self::FEATURE_CREDIT_CARD_AUTHORIZATION,
					self::FEATURE_CREDIT_CARD_CAPTURE,
					self::FEATURE_DETAILED_CUSTOMER_DECLINE_MESSAGES,
					self::FEATURE_REFUNDS,
					self::FEATURE_VOIDS,
					self::FEATURE_CUSTOMER_ID,
					self::FEATURE_ADD_PAYMENT_METHOD,
					self::FEATURE_APPLE_PAY,
				 ),
				'payment_type'       => self::PAYMENT_TYPE_CREDIT_CARD,
				'environments'       => array( 'production' => __( 'Production', 'woocommerce-gateway-authorize-net-cim' ), 'test' => __( 'Test', 'woocommerce-gateway-authorize-net-cim' ) ),
				'shared_settings'    => $this->shared_settings_names,
			)
		);

		// add lightbox trigger markup to payment pages
		add_action( 'woocommerce_review_order_after_payment', [ $this, 'render_lightbox_markup' ] );
		add_action( 'woocommerce_pay_order_before_submit',    [ $this, 'render_lightbox_markup' ] );
	}


	/**
	 * Gets the checkout block integration instance.
	 *
	 * @since 3.10.0
	 *
	 * @return Credit_Card_Checkout_Block_Integration
	 */
	public function get_checkout_block_integration_instance(): ?Framework\Payment_Gateway\Blocks\Gateway_Checkout_Block_Integration {

		if ( null === $this->credit_card_checkout_block ) {

			require_once( $this->get_plugin()->get_plugin_path() . '/src/Blocks/Credit_Card_Checkout_Block_Integration.php' );

			$this->credit_card_checkout_block = new Credit_Card_Checkout_Block_Integration( $this->get_plugin(), $this );
		}

		return $this->credit_card_checkout_block;
	}


	/**
	 * Renders the Accept lightbox input for triggering the hosted form.
	 *
	 * @since 3.0.0
	 */
	public function render_lightbox_markup() {

		/**
		 * Filters the Accept lightbox form header text.
		 *
		 * @since 3.0.0
		 *
		 * @param string $text header text
		 * @param \WC_Gateway_Authorize_Net_CIM $gateway gateway object
		 */
		$header_text = apply_filters( 'wc_' . $this->get_id() . '_lightbox_header_text', get_bloginfo( 'name' ), $this );

		if ( is_add_payment_method_page() ) {
			$button_text = __( 'Add payment method', 'woocommerce-gateway-authorize-net-cim' );
		} else {
			$button_text = __( 'Place order', 'woocommerce-gateway-authorize-net-cim' );
		}

		/**
		 * Filters the Accept lightbox form button text.
		 *
		 * @since 3.0.0
		 *
		 * @param string $text button text
		 * @param \WC_Gateway_Authorize_Net_CIM $gateway gateway object
		 */
		$button_text = apply_filters( 'wc_' . $this->get_id() . '_lightbox_button_text', $button_text, $this );

		echo '<input
			type="hidden"
			class="AcceptUI"
			data-acceptUIFormHeaderTxt="' . esc_html( $header_text ) . '"
			data-acceptUIFormBtnTxt="' . esc_html( $button_text ) . '"
			data-billingAddressOptions=\'{"show":false, "required":false}\'
			data-apiLoginID="' . esc_attr( $this->get_api_login_id() ) . '"
			data-clientKey="' . esc_attr( $this->get_client_key() ) . '"
			data-responseHandler="wc_authorize_net_cim_credit_card_accept_hosted_handler" />';
	}


	/**
	 * Adds the CSC settings form fields.
	 *
	 * Overridden to remove the "Saved Card Verification" checkbox, since the CSC field can't be used alone with Accept.js.
	 *
	 * @since 3.0.0
	 *
	 * @param array $form_fields
	 * @return array
	 */
	protected function add_csc_form_fields( $form_fields ) {

		$form_fields = parent::add_csc_form_fields( $form_fields );

		unset( $form_fields['enable_token_csc'] );

		return $form_fields;
	}


	/**
	 * Determines if the CSC field and validation is enabled for saved methods.
	 *
	 * Overridden to false, as the CSC isn't used for saved methods with Accept.js
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function csc_enabled_for_tokens() {

		return false;
	}


	/**
	 * Add payment data to the order.
	 *
	 * @since 2.4.0
	 *
	 * @param int $order_id the order ID
	 * @return \WC_Order
	 */
	public function get_order( $order_id ) {

		$order = parent::get_order( $order_id );

		if ( empty( $order->payment->token ) && ! Framework\SV_WC_Helper::get_posted_value( 'wc-' . $this->get_id_dasherized() . '-payment-token' ) ) {

			// expiry month/year
			if ( $expiry = Framework\SV_WC_Helper::get_posted_value( 'wc-' . $this->get_id_dasherized() . '-expiry' ) ) {
				[ $order->payment->exp_month, $order->payment->exp_year ] = array_map( 'trim', explode( '/', $expiry ) );
			}

			$order->payment->card_type = Framework\SV_WC_Helper::get_posted_value( 'wc-' . $this->get_id_dasherized() . '-card-type' );
		}

		return $order;
	}


	/**
	 * Gets the currencies supported by Apple Pay.
	 *
	 * @since 3.2.4-dev.1
	 *
	 * @return array
	 */
	public function get_apple_pay_currencies() {

		return [ 'USD' ];
	}


	/**
	 * Adds Apple Pay payment data to the order.
	 *
	 * @since 2.7.0
	 * @see Framework\SV_WC_Payment_Gateway::get_order_for_apple_pay()
	 *
	 * @param \WC_Order $order the order object
	 * @param Framework\SV_WC_Payment_Gateway_Apple_Pay_Payment_Response $response the authorized payment response
	 * @return \WC_Order
	 */
	public function get_order_for_apple_pay( WC_Order $order, Framework\SV_WC_Payment_Gateway_Apple_Pay_Payment_Response $response ) {

		$order = parent::get_order_for_apple_pay( $order, $response );

		// opaque data
		$order->payment->opaque_value      = base64_encode( json_encode( $response->get_payment_data() ) );
		$order->payment->opaque_descriptor = 'COMMON.APPLE.INAPP.PAYMENT';

		return $order;
	}


	/**
	 * Marks an order as held.
	 *
	 * @since 2.8.0
	 * @see Framework\SV_WC_Payment_Gateway::mark_order_as_held()
	 *
	 * @param WC_Order $order order object
	 * @param string $message the hold message
	 * @param WC_Authorize_Net_CIM_API_Transaction_Response|null $response
	 */
	public function mark_order_as_held( $order, $message, $response = null ) {

		parent::mark_order_as_held( $order, $message, $response );

		// bail if we don't have a full response object
		if ( ! $response instanceof WC_Authorize_Net_CIM_API_Transaction_Response ) {
			return;
		}

		// add an order note in case of fraud holds
		if ( $response->transaction_held_for_fraud() ) {
			$this->mark_order_as_held_for_fraud( $order, $response );
		}
	}


	/**
	 * Marks an order as being held for Fraud Filter reasons.
	 *
	 * @since 2.8.0
	 *
	 * @param WC_Order $order order object
	 * @param WC_Authorize_Net_CIM_API_Transaction_Response $response
	 */
	protected function mark_order_as_held_for_fraud( WC_Order $order, WC_Authorize_Net_CIM_API_Transaction_Response $response ) {

		$message = sprintf(
			/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag */
			__( '%1$sPossible fraud detected based on your Authorize.Net Fraud Filter configuration.%2$s Please review the transaction from your merchant account before processing.', 'woocommerce-gateway-authorize-net-cim' ),
			'<strong>', '</strong>'
		);

		if ( $response->get_avs_result() ) {
			/* translators: Placeholders: %s - an AVS result code, such as "A" */
			$message .= '<br />' . sprintf( __( 'AVS Result: %s', 'woocommerce-gateway-authorize-net-cim' ), $response->get_avs_result() );
		}

		if ( $response->get_csc_result() ) {
			/* translators: Placeholders: %s - a CSC result code, such as "N" */
			$message .= '<br />' . sprintf( __( 'CSC Result: %s', 'woocommerce-gateway-authorize-net-cim' ), $response->get_csc_result() );
		}

		$order->add_order_note( $message );
	}


	/**
	 * Add Authorize.Net specific data to the order for performing a refund/void,
	 * all transactions require transaction ID and amount.
	 *
	 *
	 * Refund transactions require the last 4 digits and expiration date of
	 * the card used for the original transaction
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway::get_order_for_refund()
	 * @param int $order_id order ID
	 * @param float $amount refund amount
	 * @param string $reason refund reason text
	 * @return WC_Order|WP_Error order object on success, or WP_Error if missing required data
	 */
	protected function get_order_for_refund( $order_id, $amount, $reason ) {

		// set defaults
		$order = parent::get_order_for_refund( $order_id, $amount, $reason );

		try {

			// try and get the transaction's details
			$response = $this->get_api()->get_transaction_details( $order->refund->trans_id, $order->get_id() );

			$order->refund->last_four = $response->get_last_four();

			// if this is a partial refund, find out if the transaction has settled before continuing
			if ( $order->refund->amount != $order->get_total() ) {

				if ( $response->is_captured() && ! $response->is_settled() ) {
					$error_message = __( 'The transaction cannot be partially refunded until it has settled. You can either wait for settlement to process or use the full order total to void the transaction.', 'woocommerce-gateway-authorize-net-cim' );
				} elseif ( $response->is_authorized() ) {
					$error_message = __( 'The transaction cannot be partially refunded until it has been captured and settled. Please capture the order or use the full order total to void the transaction.', 'woocommerce-gateway-authorize-net-cim' );
				}
			}

		} catch ( Framework\SV_WC_Plugin_Exception $exception ) {

			$this->add_debug_message( $exception->getMessage() );
		}

		// if the transaction details API request above fails, fall back to the order meta if it exists
		if ( empty( $order->refund->last_four ) ) {
			$order->refund->last_four = $this->get_order_meta( $order, 'account_four' );
		}

		if ( $expiry_date = $this->get_order_meta( $order, 'card_expiry_date' ) ) {
			$order->refund->expiry_date = date( 'm-Y', strtotime( '20' . $expiry_date ) );
		} else {
			$order->refund->expiry_date = 'XXXX';
		}

		// we must error out if all above attempts at retrieving the card last four fail
		if ( empty( $order->refund->last_four ) || empty( $order->refund->expiry_date ) ) {
			$error_message = __( 'Order is missing the last four digits or expiration date of the credit card used.', 'woocommerce-gateway-authorize-net-cim' );
		}

		if ( ! empty( $error_message ) ) {
			return new \WP_Error( 'wc_' . $this->get_id() . '_refund_error', sprintf( __( '%s Refund error - %s', 'woocommerce-gateway-authorize-net-cim' ), $this->get_method_title(), $error_message ) );
		}

		return $order;
	}


	/**
	 * Authorize.Net allows for an authorized & captured transaction that has not
	 * yet settled to be voided. This overrides the refund method when a refund
	 * request encounters the "Code 54 - The referenced transaction does not meet
	 * the criteria for issuing a credit." error and attempts a void instead.
	 *
	 * @see Framework\SV_WC_Payment_Gateway::maybe_void_instead_of_refund()
	 *
	 * @since 2.0.0
	 * @param \WC_Order $order order
	 * @param \WC_Authorize_Net_CIM_API_Profile_Transaction_Response|\WC_Authorize_Net_CIM_API_Non_Profile_Transaction_Response $response refund response
	 * @return boolean true if
	 */
	protected function maybe_void_instead_of_refund( $order, $response ) {

		return ! $response->transaction_approved() && 3 === (int) $response->get_transaction_response_code() && 54 === (int) $response->get_transaction_response_reason_code();
	}


	/** Getter methods ************************************************************************************************/


	/**
	 * Return the default values for this payment method, used to pre-fill
	 * an authorize.net valid test account number when in testing mode
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway::get_payment_method_defaults()
	 * @return array
	 */
	public function get_payment_method_defaults() {

		$defaults = parent::get_payment_method_defaults();

		if ( $this->is_test_environment() ) {
			$defaults['account-number'] = '4007000000027';
		}

		return $defaults;
	}


}
