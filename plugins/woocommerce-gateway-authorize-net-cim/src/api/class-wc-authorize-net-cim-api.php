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

use SkyVerge\WooCommerce\Authorize_Net\API;
use SkyVerge\WooCommerce\PluginFramework\v5_12_0 as Framework;

/**
 * Authorize.Net API Class
 *
 * Handles sending/receiving/parsing of Authorize.Net XML, this is the main API
 * class responsible for communication with the Authorize.Net API
 *
 * @link http://developer.authorize.net/api/reference/
 * @link http://www.authorize.net/support/CIM_guide_XML.pdf
 *
 * @since 2.0.0
 */
class WC_Authorize_Net_CIM_API extends Framework\SV_WC_API_Base implements Framework\SV_WC_Payment_Gateway_API {


	/** the production endpoint */
	const PRODUCTION_ENDPOINT = 'https://api2.authorize.net/xml/v1/request.api';

	/** the test endpoint */
	const TEST_ENDPOINT = 'https://apitest.authorize.net/xml/v1/request.api';

	/** @var string request URI */
	protected $request_uri;

	/** @var \WC_Order|null order associated with the request, if any */
	protected $order;

	/** @var string gateway ID */
	private $gateway_id;

	/** @var string API login ID value */
	private $api_login_id;

	/** @var string API transaction key value */
	private $api_transaction_key;


	/**
	 * Constructor - setup request object and set endpoint
	 *
	 * @since 2.0.0
	 * @param string $gateway_id gateway id
	 * @param string $environment current API environment, either `production` or `test`
	 * @param string $api_login_id API login ID
	 * @param string $api_transaction_key API transaction key
	 * @return \WC_Authorize_Net_CIM_API
	 */
	public function __construct( $gateway_id, $environment, $api_login_id, $api_transaction_key ) {

		$this->gateway_id = $gateway_id;

		// request URI does not vary in between requests
		$this->request_uri = ( 'production' === $environment ) ? self::PRODUCTION_ENDPOINT : self::TEST_ENDPOINT;

		$this->set_request_content_type_header( 'application/xml' );
		$this->set_request_accept_header( 'application/xml' );

		// set auth creds
		$this->api_login_id        = $api_login_id;
		$this->api_transaction_key = $api_transaction_key;
	}


	/**
	 * Create a new credit card charge transaction
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API::credit_card_charge()
	 * @param \WC_Order $order order
	 * @return \WC_Authorize_Net_CIM_API_Response Authorize.Net API response object
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function credit_card_charge( WC_Order $order ) {

		$request = $this->get_new_request( $this->get_transaction_request_type( $order ) );

		$request->create_credit_card_charge( $order );

		return $this->perform_transaction( $request, $order );
	}


	/**
	 * Create a new credit card auth transaction
	 *
	 * Note: The authorization is valid only for a fixed amount of time, which
	 * may vary by card issuer, but which is usually several days. Authorize.Net imposes
	 * its own maximum of 30 days after the date of the original authorization,
	 * but most issuers are expected to have a validity period significantly
	 * less than this.
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API::credit_card_authorization()
	 * @param \WC_Order $order order
	 * @return \WC_Authorize_Net_CIM_API_Response Authorize.Net API response object
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function credit_card_authorization( WC_Order $order ) {

		$request = $this->get_new_request( $this->get_transaction_request_type( $order ) );

		$request->create_credit_card_auth( $order );

		return $this->perform_transaction( $request, $order );
	}


	/**
	 * Capture funds for a credit card authorization
	 *
	 * This request can be made only after a previous and successful
	 * authorization request, where the card issuer has authorized a
	 * charge to be made against the specified credit card in the future. The
	 * transaction ID from that prior transaction must be used in this
	 * subsequent and related transaction. This request actually causes that
	 * authorized charge to be incurred against the customer's credit card.
	 *
	 * Notice that you cannot have multiple capture requests against a single
	 * authorization request. Each authorization request must
	 * have one and only one capture request.
	 *
	 * Note: The authorization to be captured is valid only for a fixed amount
	 * of time, which may vary by card issuer, but which is usually several
	 * days. Authorize.Net imposes its own maximum of 30 days after the date of the
	 * original authorization, but most issuers are expected to have a validity
	 * period significantly less than this.
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API::credit_card_capture()
	 * @param WC_Order $order order
	 * @return \WC_Authorize_Net_CIM_API_Response Authorize.Net API response object
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function credit_card_capture( WC_Order $order ) {

		$request = $this->get_new_request( $this->get_transaction_request_type( $order ) );

		$request->create_credit_card_capture( $order );

		return $this->perform_transaction( $request, $order );
	}


	/**
	 * Perform a customer check debit transaction
	 *
	 * @since 2.0.0
	 * @param WC_Order $order order
	 * @return \WC_Authorize_Net_CIM_API_Response Authorize.Net API response object
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function check_debit( WC_Order $order ) {

		$request = $this->get_new_request( $this->get_transaction_request_type( $order ) );

		$request->create_echeck_debit( $order );

		return $this->perform_transaction( $request, $order );
	}


	/**
	 * Wrapper around perform_request() to catch specific errors resulting from
	 * transaction requests that can be transparently handled:
	 *
	 * 1) If a local shipping address no longer exists in CIM, it will be removed
	 * after the failed transaction request. The user can retry the transaction
	 * and a new shipping address will be created.
	 *
	 * @since 2.0.0
	 * @param \WC_Authorize_Net_CIM_API_Request $request
	 * @param \WC_Order $order order
	 * @return \WC_Authorize_Net_CIM_API_Response Authorize.Net API response object
	 * @throws Framework\SV_WC_API_Exception
	 */
	protected function perform_transaction( $request, WC_Order $order ) {

		$this->order = $order;

		try {

			return $this->perform_request( $request );

		} catch ( Framework\SV_WC_API_Exception $e ) {

			// record not found
			if ( 40 == $e->getCode() ) {

				if ( Framework\SV_WC_Helper::str_exists( $e->getMessage(), 'Customer Shipping Address ID' ) ) {

					// shipping address not found, remove it locally
					$shipping = $this->get_gateway()->get_shipping_address( $order->get_user_id() );

					$shipping->delete();
				}
			}
		}

		throw $e;
	}


	/**
	 * Perform a refund for the order
	 *
	 * Note that only transactions settled in the past 120 days are eligible for
	 * refunds
	 *
	 * @since 2.0.0
	 * @param \WC_Order $order the order
	 * @return \WC_Authorize_Net_CIM_API_Response Authorize.Net API response object
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function refund( WC_Order $order ) {

		$this->order = $order;

		$request = $this->get_new_request( $this->get_transaction_request_type( $order ) );

		$request->create_refund( $order );

		return $this->perform_request( $request );
	}


	/**
	 * Perform a void for the order
	 *
	 * Note that a void is only performed when:
	 *
	 * + a transaction has a valid authorization that has not yet been captured
	 * + a previously attempt refund transaction fails with code 54, which indicates
	 *   a authorized/captured transaction that has not yet been settled and can
	 *   be voided
	 *
	 * @since 2.0.0
	 * @param \WC_Order $order the order
	 * @return \WC_Authorize_Net_CIM_API_Response Authorize.Net API response object
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function void( WC_Order $order ) {

		$this->order = $order;

		$request = $this->get_new_request( $this->get_transaction_request_type( $order ) );

		$request->create_void( $order );

		return $this->perform_request( $request );
	}


	/** Tokenization methods **************************************************/


	/**
	 * Tokenize the payment method associated with the order
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API::tokenize_payment_method()
	 * @param WC_Order $order the order with associated payment and customer info
	 * @return \WC_Authorize_Net_CIM_API_Payment_Profile_Response|\WC_Authorize_Net_CIM_API_Customer_Profile_Response
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function tokenize_payment_method( WC_Order $order ) {

		$this->order = $order;

		if ( $order->customer_id ) {

			// create the payment profile
			return $this->create_payment_profile( $order );

		} else {

			// new customer, create both customer profile and payment profile
			return $this->create_customer_and_payment_profile( $order );
		}
	}


	/**
	 * Create a new customer profile and payment profile
	 *
	 * @since 2.0.0
	 * @param \WC_Order $order the order with associated payment and customer info
	 * @return \WC_Authorize_Net_CIM_API_Customer_Profile_Response
	 * @throws Framework\SV_WC_API_Exception|Framework\SV_WC_Payment_Gateway_Exception
	 */
	protected function create_customer_and_payment_profile( WC_Order $order ) {

		try {

			$request = $this->get_new_request( 'customer-profile' );

			$request->create_customer_and_payment_profile( $order );

			return $this->perform_request( $request );

		} catch ( Framework\SV_WC_API_Exception $e ) {

			// E00039 - duplicate
			if ( 39 == $e->getCode() ) {

				// this can happen if the customer profile ID is removed locally

				// extract the duplicate profile ID from the error message
				if ( preg_match( '/ID\s[0-9]*/', $e->getMessage(), $id ) ) {

					$id = str_replace( 'ID ', '', $id[0] );

					if ( ! is_numeric( $id ) ) {
						throw new Framework\SV_WC_Payment_Gateway_Exception( sprintf( 'Invalid customer profile ID (%s) provided by Authorize.Net for duplicate customer profile.', $id ), 500, $e );
					}

					// update the customer ID for the user and the current order
					$this->get_gateway()->update_customer_id( $order->get_user_id(), $id );
					$order->customer_id = $id;

					// note we cannot attempt to create the payment profile now
					// since it is not stored locally. Instead, clear the token transient
					// so when the user reloads the page, the remote tokens will
					// be correctly loaded
					$this->get_gateway()->get_payment_tokens_handler()->clear_transient( $order->get_user_id() );
				}
			}

			throw $e;
		}
	}


	/**
	 * Create a payment profile for an existing customer profile
	 *
	 * @since 2.0.0
	 * @param \WC_Order $order the order with associated payment and customer info
	 * @return \WC_Authorize_Net_CIM_API_Payment_Profile_Response
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	protected function create_payment_profile( WC_Order $order ) {

		try {

			$request = $this->get_new_request( 'payment-profile' );
			$request->create_payment_profile( $order );

			return $this->perform_request( $request );

		} catch ( Framework\SV_WC_API_Exception $e ) {

			// some errors can be recovered from
			switch ( $e->getCode() ) {

				// a duplicate payment profile exists in CIM
				case 39:

					// safety check, we don't want to keep recursively adding & deleting payment profiles after the first one
					if ( ! empty( $order->auth_net_cim_removed_duplicate_payment_profile ) ) {
						throw new Framework\SV_WC_Payment_Gateway_Exception( 'Already removed previous duplicate payment profile, cannot continue.', 500, $e );
					}

					// remove the duplicate and re-add it
					if ( $response = $this->handle_duplicate_payment_profile( $order ) ) {
						return $response;
					}
				break;

				// customer profile not found in CIM
				case 40:

					// remove the customer ID
					$this->get_gateway()->remove_customer_id( $order->get_user_id() );

				break;
			}

			throw $e;
		}
	}


	/**
	 * Handle a duplicate payment profile error when trying to add a payment
	 * profile to an existing customer profile. Authorize.Net does not return
	 * which profile it considers a duplicate, so we use the payment hash
	 * saved on the local token and compare it against the generated hash for
	 * the payment method that is being added.
	 *
	 * The process is to remove the duplicate payment profile in CIM and re-add the
	 * current payment profile, rather than simply updating it. If we incorrectly
	 * identify the duplicate profile, it's better to delete it in error rather
	 * than partially update it with information from *different* profile.
	 *
	 * @since 2.0.0
	 * @param WC_Order $order the order with associated payment and customer info
	 * @return \WC_Authorize_Net_CIM_API_Payment_Profile_Response
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	protected function handle_duplicate_payment_profile( WC_Order $order ) {

		// get the current tokens
		$tokens = $this->get_gateway()->get_payment_tokens_handler()->get_tokens( $order->get_user_id() );

		foreach ( $tokens as $token ) {

			// if an existing payment profile is duplicate of the one we're trying to add...
			if ( $token->is_duplicate_of( $order ) ) {

				// remove the duplicate profile from CIM
				$this->get_gateway()->get_payment_tokens_handler()->remove_token( $order->get_user_id(), $token );

				// safety flag
				$order->auth_net_cim_removed_duplicate_payment_profile = true;

				// attempt to re-add the payment profile that was previously considered a duplicate
				return $this->tokenize_payment_method( $order );
			}
		}

		return false;
	}


	/**
	 * Get the tokenized payment methods for the customer
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API::get_tokenized_payment_methods()
	 * @param string $customer_id unique
	 * @return \WC_Authorize_Net_CIM_API_Payment_Profile_Response|\WC_Authorize_Net_CIM_API_Customer_Profile_Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function get_tokenized_payment_methods( $customer_id ) {

		$request = $this->get_new_request( 'customer-profile' );
		$request->get_customer_profile( $customer_id );

		try {

			return $this->perform_request( $request );

		} catch ( Framework\SV_WC_API_Exception $e ) {

			// record not found, meaning customer profile was removed
			if ( 40 == $e->getCode() ) {

				// remove the local tokens and customer ID after tokens are finished loading
				// this is to prevent stale local tokens from being temporary cached in the transient
				add_action( 'wc_payment_gateway_' . $this->get_gateway()->get_id() . '_payment_tokens_loaded', array( $this->get_gateway(), 'remove_local_profile' ) );

				// return an empty response - this causes the framework to delete any locally stored payment tokens
				return new WC_Authorize_Net_CIM_API_Customer_Profile_Response( $request, '<xml />' );
			}

			throw $e;
		}
	}


	/**
	 * Update the tokenized payment method for given customer
	 *
	 * @since 2.0.0
	 * @param WC_Order $order
	 * @return \WC_Authorize_Net_CIM_API_Payment_Profile_Response
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function update_tokenized_payment_method( WC_Order $order ) {

		$this->order = $order;

		// best practice is to first get the existing payment profile data
		$request = $this->get_new_request( 'payment-profile' );
		$request->get_payment_profile( $order->customer_id, $order->payment->token );
		$response = $this->perform_request( $request );

		// then overwrite only the fields that are being updated
		$request = $this->get_new_request( 'payment-profile' );
		$request->update_payment_profile( $order, $response );

		return $this->perform_request( $request );
	}


	/**
	 * Remove the given tokenized payment method for the customer
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API::remove_tokenized_payment_method()
	 * @param string $token the payment method token
	 * @param string $customer_id unique
	 * @return \WC_Authorize_Net_CIM_API_Payment_Profile_Response
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function remove_tokenized_payment_method( $token, $customer_id ) {

		$request = $this->get_new_request( 'payment-profile' );

		$request->delete_payment_profile( $token, $customer_id );

		return $this->perform_request( $request );
	}


	/**
	 * Authorize.Net supports retrieving tokenized payment methods
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API::supports_get_tokenized_payment_methods()
	 * @return boolean true
	 */
	public function supports_get_tokenized_payment_methods() {

		return true;
	}


	/**
	 * Authorize.Net supports removing tokenized payment methods
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API::supports_remove_tokenized_payment_method()
	 * @return boolean true
	 */
	public function supports_remove_tokenized_payment_method() {

		return true;
	}


	/**
	 * Determines if this API supports updating tokenized payment methods.
	 *
	 * @see SV_WC_Payment_Gateway_API::update_tokenized_payment_method()
	 *
	 * @since 2.10.2
	 *
	 * @return false
	 */
	public function supports_update_tokenized_payment_method() {

		return false;
	}


	/** Shipping Profile methods **********************************************/


	/**
	 * Create a shipping address for the given order/customer profile
	 *
	 * @since 2.0.0
	 * @param \WC_Order $order order object
	 * @return \WC_Authorize_Net_CIM_API_Shipping_Address_Response response object
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function create_shipping_address( WC_Order $order ) {

		$this->order = $order;

		try {

			$request = $this->get_new_request( 'shipping-address' );

			$request->create_shipping_address( $order );

			return $this->perform_request( $request );

		} catch ( Framework\SV_WC_API_Exception $e ) {

			// duplicate address already in CIM, local data was probably corrupted or deleted
			if ( 39 == $e->getCode() ) {

				// get the customer profile
				$request = $this->get_new_request( 'customer-profile' );
				$request->get_customer_profile( $order->customer_id );
				$response = $this->perform_request( $request );

				// get the shipping addresses for the customer
				$addresses = $response->get_customer_shipping_addresses();

				// setup the local shipping address
				$shipping_address = $this->get_gateway()->get_shipping_address( $order->get_user_id() );

				// update hash to the shipping address on the order
				$shipping_address->update_hash( $order );

				// attempt to match the info provided to that provided by the order
				foreach ( $addresses as $address_id => $address ) {

					// found a match, persist to user meta and return it for the transaction
					if ( $shipping_address->matches_address( $address ) ) {

						$shipping_address->update_id( $address_id );

						return $shipping_address->get_id();
					}
				}
			}

			throw $e;
		}

	}


	/**
	 * Update a shipping address for the given order/customer profile
	 *
	 * @since 2.0.0
	 * @param \WC_Order $order order object
	 * @return \WC_Authorize_Net_CIM_API_Shipping_Address_Response response object
	 */
	public function update_shipping_address( WC_Order $order ) {

		$this->order = $order;

		$request = $this->get_new_request( 'shipping-address' );

		$request->update_shipping_address( $order );

		return $this->perform_request( $request );
	}


	/** Hosted form methods *******************************************************************************************/


	/**
	 * Gets a hosted payment form.
	 *
	 * @since 3.0.0
	 *
	 * @param \WC_Order $order
	 * @param string $payment_type gateway payment type
	 * @param bool $charge whether to charge the payment
	 * @return SkyVerge\WooCommerce\Authorize_Net\API\Hosted\Payment_Form_Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function get_hosted_form( \WC_Order $order, $payment_type, $charge = true ) {

		$request = $this->get_new_request( 'hosted-form' );

		$request->set_form_data( $order, $payment_type, $charge );

		return $this->perform_request( $request );
	}


	/** Misc. methods *********************************************************/


	/**
	 * Gets the details for a specific transaction.
	 *
	 * @since 2.10.0
	 *
	 * @param string $transaction_id Authorize.Net transaction ID
	 * @param int $order_id WooCommerce order ID
	 * @return \WC_Authorize_Net_CIM_API_Transaction_Details_Response response object
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function get_transaction_details( $transaction_id, $order_id = null ) {

		$request = $this->get_new_request( 'transaction-details' );

		$request->set_transaction_data( $transaction_id, $order_id );

		return $this->perform_request( $request );
	}


	/** Hosted Profile Page API methods ***************************************/

	/**
	 * Return the token for the hosted profile page, currently only used to
	 * determine if the CIM feature is enabled for the Authorize.Net account
	 *
	 * @since 2.0.0
	 * @return \WC_Authorize_Net_CIM_API_Hosted_Profile_Page_Response response object
	 */
	public function get_hosted_profile_page_token( $customer_id ) {

		$request = $this->get_new_request( 'hosted-profile-page' );

		$request->get_page_token( $customer_id );

		return $this->perform_request( $request );
	}


	/** Webhook API Methods ***************************************************/


	/**
	 * Creates a webhook.
	 *
	 * @since 2.8.0
	 *
	 * @param string $url URL for the webhook to post to
	 * @param array $event_types event types to subscribe to
	 * @return \WC_Authorize_Net_CIM_API_Webhook_Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function create_webhook( $url, array $event_types ) {

		$request = $this->get_new_request( 'webhook' );

		$request->create_webhook( $url, $event_types );

		return $this->perform_request( $request );
	}


	/**
	 * Deletes a webhook.
	 *
	 * @since 2.8.0
	 *
	 * @param string $webhook_id remote webhook ID
	 * @return \WC_Authorize_Net_CIM_API_Webhook_Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function delete_webhook( $webhook_id ) {

		$request = $this->get_new_request( 'webhook' );

		$request->delete_webhook( $webhook_id );

		return $this->perform_request( $request );
	}


	/**
	 * Gets the configured merchant account details.
	 *
	 * @since 3.0.0
	 *
	 * @return \SkyVerge\WooCommerce\Authorize_Net\API\Merchant_Details_Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function get_merchant_details() {

		$request = $this->get_new_request( 'merchant' );

		$request->get_merchant_details();

		return $this->perform_request( $request );
	}


	/** Validation methods ****************************************************/


	/**
	 * Check if the response has any status code errors
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_API_Base::do_pre_parse_response_validation()
	 * @throws Framework\SV_WC_API_Exception non HTTP 200 status
	 */
	protected function do_pre_parse_response_validation() {

		// authorize.net should rarely return a non-200 status
		if ( 200 != $this->get_response_code() ) {

			throw new Framework\SV_WC_API_Exception( sprintf( __( 'HTTP %s: %s', 'woocommerce-gateway-authorize-net-cim' ), $this->get_response_code(), $this->get_response_message() ) );
		}
	}


	/**
	 * Check if the response has any errors
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_API_Base::do_post_parse_response_validation()
	 * @throws Framework\SV_WC_API_Exception if response has API error
	 */
	protected function do_post_parse_response_validation() {

		// E00027 is a processing error that almost always includes additional transaction info, like status codes and a transaction ID so it's treated like a general transaction decline than API error
		if ( $this->get_response()->has_api_error() && 'E00027' !== $this->get_response()->get_api_error_code() ) {

			$exception_code = intval( str_ireplace( array( 'E', 'I' ), '', $this->get_response()->get_api_error_code() ) );

			throw new Framework\SV_WC_API_Exception( sprintf( __( 'Code: %s, Message: %s', 'woocommerce-gateway-authorize-net-cim' ), $this->get_response()->get_api_error_code(), $this->get_response()->get_api_error_message() ), $exception_code );

		} elseif ( $this->get_response()->is_test_request() ) {

			throw new Framework\SV_WC_API_Exception( __( 'Test request detected -- please disable test mode in your Authorize.Net control panel and use a separate Authorize.Net test account for testing.' ) );
		}
	}


	/** Helper methods ********************************************************/


	/**
	 * Builds and returns a new API request object
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_API_Base::get_new_request()
	 * @param string $type
	 * @throws Framework\SV_WC_API_Exception for invalid request types
	 * @return \WC_Authorize_Net_CIM_API_Non_Profile_Transaction_Request|\WC_Authorize_Net_CIM_API_Profile_Transaction_Request|\WC_Authorize_Net_CIM_API_Customer_Profile_Request|\WC_Authorize_Net_CIM_API_Payment_Profile_Request|\WC_Authorize_Net_CIM_API_Shipping_Address_Request|\WC_Authorize_Net_CIM_API_Hosted_Profile_Page_Request|API\Hosted\Payment_Form_Request| object
	 */
	protected function get_new_request( $type = null ) {

		switch ( $type ) {

			case 'non-profile-transaction':
				$this->set_response_handler( 'WC_Authorize_Net_CIM_API_Non_Profile_Transaction_Response' );
				return new WC_Authorize_Net_CIM_API_Non_Profile_Transaction_Request( $this->api_login_id, $this->api_transaction_key );

			case 'profile-transaction':
				$this->set_response_handler( 'WC_Authorize_Net_CIM_API_Profile_Transaction_Response' );
				return new WC_Authorize_Net_CIM_API_Profile_Transaction_Request( $this->api_login_id, $this->api_transaction_key );

			case 'customer-profile':
				$this->set_response_handler( 'WC_Authorize_Net_CIM_API_Customer_Profile_Response' );
				return new WC_Authorize_Net_CIM_API_Customer_Profile_Request( $this->api_login_id, $this->api_transaction_key );

			case 'payment-profile':
				$this->set_response_handler( 'WC_Authorize_Net_CIM_API_Payment_Profile_Response' );
				return new WC_Authorize_Net_CIM_API_Payment_Profile_Request( $this->api_login_id, $this->api_transaction_key );

			case 'shipping-address':
				$this->set_response_handler( 'WC_Authorize_Net_CIM_API_Shipping_Address_Response' );
				return new WC_Authorize_Net_CIM_API_Shipping_Address_Request( $this->api_login_id, $this->api_transaction_key );

			case 'hosted-profile-page':
				$this->set_response_handler( 'WC_Authorize_Net_CIM_API_Hosted_Profile_Page_Response' );
				return new WC_Authorize_Net_CIM_API_Hosted_Profile_Page_Request( $this->api_login_id, $this->api_transaction_key );

			case 'hosted-form':
				$this->set_response_handler( API\Hosted\Payment_Form_Response::class );
				return new API\Hosted\Payment_Form_Request( $this->api_login_id, $this->api_transaction_key );

			case 'transaction-details':
				$this->set_response_handler( 'WC_Authorize_Net_CIM_API_Transaction_Details_Response' );
				return new WC_Authorize_Net_CIM_API_Transaction_Details_Request( $this->api_login_id, $this->api_transaction_key );

			case 'webhook':

				// for webhooks, we have to use the REST API
				$this->request_uri = ( 'production' === $this->get_gateway()->get_environment() ) ? 'https://api2.authorize.net/rest/v1' : 'https://apitest.authorize.net/rest/v1';

				$this->set_request_content_type_header( 'application/json' );
				$this->set_request_accept_header( 'application/json' );

				$this->set_http_basic_auth( $this->api_login_id, $this->api_transaction_key );

				$this->set_response_handler( 'WC_Authorize_Net_CIM_API_Webhook_Response' );

				return new WC_Authorize_Net_CIM_API_Webhook_Request();

			case 'merchant':
				$this->set_response_handler( API\Merchant_Details_Response::class );
				return new API\Merchant_Details_Request( $this->api_login_id, $this->api_transaction_key );

			default:
				throw new Framework\SV_WC_API_Exception( 'Invalid request type' );
		}
	}


	/**
	 * Get the request type for a given transaction, if an order has a token
	 * attached a CIM profile transaction will be performed, otherwise a simple AIM
	 * transaction will be performed
	 *
	 * @since 2.0.0
	 * @param WC_Order $order
	 * @return string
	 */
	protected function get_transaction_request_type( WC_Order $order ) {

		// refunds/voids
		if ( isset( $order->refund ) ) {

			return empty( $order->refund->customer_payment_profile_id ) ? 'non-profile-transaction' : 'profile-transaction';

		} else {

			// all other transactions
			return empty( $order->payment->token ) ? 'non-profile-transaction' : 'profile-transaction';
		}
	}


	/**
	 * Return the parsed response object for the request, overridden primarily
	 * to provide the request object to the response classes, as Auth.net does not
	 * return some useful data (like a credit card's expiration date) in the response
	 * so it must be retrieved from the request
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_API_Base::get_parsed_response()
	 * @param string $raw_response_body
	 * @return object response class instance which implements Framework\SV_WC_API_Request
	 */
	protected function get_parsed_response( $raw_response_body ) {

		$handler_class = $this->get_response_handler();

		return new $handler_class( $this->get_request(), $raw_response_body );
	}


	/**
	 * Return the order associated with the request, if any
	 *
	 * @since 2.0.3
	 * @return \WC_Order|null
	 */
	public function get_order() {

		return $this->order;
	}


	/**
	 * Get the ID for the API, used primarily to namespace the action name
	 * for broadcasting requests
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_API_Base::get_api_id()
	 * @return string
	 */
	protected function get_api_id() {

		return $this->gateway_id;
	}


	/**
	 * Returns the main plugin class
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_API_Base::get_plugin()
	 * @return WC_Authorize_Net_CIM
	 */
	protected function get_plugin() {
		return wc_authorize_net_cim();
	}


	/**
	 * Returns the gateway class associated with the request
	 *
	 * @since 2.0.0
	 * @return WC_Gateway_Authorize_Net_CIM
	 */
	protected function get_gateway() {

		return $this->get_plugin()->get_gateway( $this->gateway_id );
	}


}
