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
 * Authorize.Net Customer Profile Response Class
 *
 * Parses XML received from the Authorize.Net API
 *
 * @link http://developer.authorize.net/api/reference/#manage-customer-profiles
 * @link http://www.authorize.net/support/CIM_guide_XML.pdf
 *
 * @since 2.0.0
 * @see Framework\SV_WC_Payment_Gateway_API_Response
 */
class WC_Authorize_Net_CIM_API_Profile_Response extends WC_Authorize_Net_CIM_API_Transaction_Response {


	/** @var stdClass parsed direct response */
	protected $direct_response;


	/**
	 * Override default constructor to additionally parse direct response from
	 * customer profile or payment profile creation
	 *
	 * @since 2.0.0
	 * @see WC_Authorize_Net_CIM_API_Response::__construct()
	 * @param Framework\SV_WC_Payment_Gateway_API_Request $request that resulted in this response
	 * @param string $raw_response_xml the raw response XML
	 */
	public function __construct( $request, $raw_response_xml ) {

		parent::__construct( $request, $raw_response_xml );

		// only createCustomerProfile, createCustomerPaymentProfile, and
		// validateCustomerPaymentProfile contain the direct response element
		if ( ! empty( $this->response_xml->validationDirectResponseList->string ) ||
			 ! empty( $this->response_xml->validationDirectResponse ) ||
			 ! empty( $this->response_xml->directResponse )
		) {

			$this->parse_direct_response();
		}
	}


	/**
	 * Indicate if the request is a test request and thus the returned data is bogus.
	 *
	 * A transaction ID = 0 indicates a test request with CIM, though some requests
	 * (getting profiles, for example) won't ever return a transaction ID and therefore
	 * cannot be test requests. This covers both cases.
	 *
	 * @since 2.0.0
	 * @return bool true if testRequest element is present, false otherwise
	 */
	public function is_test_request() {

		// 0 is also returned for E00027 duplicate transaction ಠ_ಠ
		return $this->get_transaction_id() === '0' && 'E00027' !== $this->get_api_error_code();
	}


	/**
	 * Parses delimited response string from profile creation, this provides a
	 * rough parallel to the same set of info received from a normal transaction.
	 *
	 * Note that for customer/payment profile creation requests, you cannot
	 * override the direct response delimiter via extraOptions, so we're forced to
	 * assume it's comma-separated and educate merchants that associated setting
	 * in their merchant account must be set to a comma
	 *
	 * @link page 47 of http://www.authorize.net/support/AIM_guide.pdf for format details
	 * @link http://www.authorize.net/support/merchant/Transaction_Response/Response_Reason_Codes_and_Response_Reason_Text.htm
	 *
	 * @since 2.0.0
	 */
	protected function parse_direct_response() {

		$direct_response = null;

		if ( ! empty( $this->response_xml->validationDirectResponseList->string ) ) {

			// from createCustomerProfileRequest
			$direct_response = $this->response_xml->validationDirectResponseList->string;

		} elseif ( ! empty( $this->response_xml->validationDirectResponse ) ) {

			// from createCustomerPaymentProfileRequest
			$direct_response = $this->response_xml->validationDirectResponse;

		} elseif ( ! empty( $this->response_xml->directResponse ) ) {

			// from validateCustomerPaymentProfileRequest
			$direct_response = $this->response_xml->directResponse;
		}

		// TODO: direct response from a customer/payment profile transaction
		// in liveMode validation can't use the extraOptions request param
		// to set the response delimiter or encapulsation character, so we
		// may need to provide a filter for the delim/encaps chars used here
		// in case someone uses the liveMode filter and cannot set their merchant
		// acount to the values we use @MR

		// adjust response based on our hybrid delimiter :|: (delimiter = | encapsulation = :)
		// remove the leading encap character and add a trailing delimiter/encap character
		// so explode works correctly (direct response string starts and ends with an encapsulation
		// character)
		$direct_response = ltrim( strval( $direct_response ), ':' ) . '|:';

		// parse response
		$response = explode( ':|:', $direct_response );

		if ( empty( $response ) ) {
			throw new Framework\SV_WC_Payment_Gateway_Exception( __( 'Could not parse direct response.', 'woocommerce-gateway-authorize-net-cim' ) );
		}

		// offset array by 1 to match Authorize.Net's order, mainly for readability
		array_unshift( $response, null );

		$this->direct_response = new stdClass();

		// direct response fields are URL encoded, but we currently do not use any fields
		// (e.g. billing/shipping details) that would be affected by that
		$response_fields = array(
			'response_code'        => 1,
			'response_subcode'     => 2,
			'response_reason_code' => 3,
			'response_reason_text' => 4,
			'authorization_code'   => 5,
			'avs_response'         => 6,
			'transaction_id'       => 7,
			'amount'               => 10,
			'account_type'         => 11, // CC or ECHECK
			'transaction_type'     => 12, // AUTH_ONLY or AUTH_CAPTUREVOID probably
			'csc_response'         => 39,
			'cavv_response'        => 40,
			'account_last_four'    => 51,
			'card_type'            => 52,
		);

		foreach ( $response_fields as $field => $order ) {

			$this->direct_response->$field = ( isset( $response[ $order ] ) ) ? $response[ $order ] : '';
		}
	}


	/**
	 * Checks if the transaction was successful. For customer/payment profile
	 * creation/validation, we consider both TRANSACTION_APPROVED and TRANSACTION_HELD
	 * to be the same, since either results in the profile being created within
	 * Authorize.Net -- note there are two distinct possible situations:
	 *
	 * 1) profile creation results in a "held" response due to CVV/AVS rules -
	 * profile is created, even if admin later voids the associated profile transaction
	 *
	 * 2) profile creation results in a "declined" response due to CVV/AVS rules -
	 * profile is NOT created
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API_Response::transaction_approved()
	 * @return bool true if approved, false otherwise
	 */
	public function transaction_approved() {

		// only some profile requests (detailed in the constructor) return a direct response element
		if ( empty( $this->direct_response ) ) {

			// for all others, there's just a simple API error check
			return ! $this->has_api_error();

		} else {

			// see note above
			return ! $this->has_api_error() && ( self::TRANSACTION_APPROVED === $this->get_transaction_response_code() || self::TRANSACTION_HELD === $this->get_transaction_response_code() );
		}

	}


	/** Direct Response Methods ***********************************************/


	/**
	 * Gets the response transaction id, or null if there is no transaction id
	 * associated with this transaction
	 *
	 * @since 2.0.0
	 * @see WC_Authorize_Net_CIM_API_Transaction_Response::get_transaction_id()
	 * @return string transaction id
	 */
	public function get_transaction_id() {

		return isset( $this->direct_response->transaction_id ) ? $this->direct_response->transaction_id : null;
	}


	/**
	 * Gets the transaction status code
	 *
	 * @since 2.0.0
	 * @see WC_Authorize_Net_CIM_API_Transaction_Response::get_transaction_response_code()
	 * @return string transaction status code or null if none was found
	 */
	public function get_transaction_response_code() {

		return $this->direct_response->response_code;
	}


	/**
	 * Gets the transaction status message
	 *
	 * @since 2.0.0
	 * @see WC_Authorize_Net_CIM_API_Transaction_Response::get_transaction_status_message()
	 * @return string transaction status message
	 */
	public function get_transaction_status_message() {

		return sprintf( __( 'Code: %s - %s', 'woocommerce-gateway-authorize-net-cim' ), $this->get_transaction_response_reason_code(), $this->get_transaction_response_reason_text() );
	}


	/**
	 * Returns the response reason code
	 *
	 * @since 2.0.0
	 * @return string response reason code
	 */
	public function get_transaction_response_reason_code() {
		return isset( $this->direct_response->response_reason_code ) ? $this->direct_response->response_reason_code : null;
	}


	/**
	 * Returns the response reason code
	 *
	 * @since 2.0.0
	 * @return string response reason code
	 */
	public function get_transaction_response_reason_text() {
		return isset( $this->direct_response->response_reason_text ) ? $this->direct_response->response_reason_text : null;
	}


	/**
	 * The authorization code is returned from the credit card processor to
	 * indicate that the charge will be paid by the card issuer
	 *
	 * @since 2.0.0
	 * @see WC_Authorize_Net_CIM_API_Transaction_Response::get_authorization_code()
	 * @return string 6 character credit card authorization code
	 */
	public function get_authorization_code() {

		return isset( $this->direct_response->authorization_code ) ? $this->direct_response->authorization_code : null;
	}


	/**
	 * Returns the result of the AVS check
	 *
	 * @since 2.0.0
	 * @see WC_Authorize_Net_CIM_API_Transaction_Response::get_avs_result()
	 * @return string result of the AVS check, if any
	 */
	public function get_avs_result() {

		return $this->direct_response->avs_response;
	}


	/**
	 * Returns the result of the CSC check
	 *
	 * @since 2.0.0
	 * @see WC_Authorize_Net_CIM_API_Transaction_Response::get_csc_result()
	 * @return string result of CSC check
	 */
	public function get_csc_result() {

		return $this->direct_response->csc_response;
	}


	/**
	 * Returns the result of the CAVV (Cardholder authentication verification) check
	 *
	 * @since 2.0.0
	 * @see WC_Authorize_Net_CIM_API_Transaction_Response::get_cavv_result()
	 * @return string result of CAVV check
	 */
	public function get_cavv_result() {

		return $this->direct_response->cavv_response;
	}


	/**
	 * Returns the account type saved, either `cc` or `echeck`
	 *
	 * @since 2.0.0
	 * @return string account type
	 */
	public function get_account_type() {

		return strtolower( $this->direct_response->account_type );
	}


	/**
	 * Returns the last four digits of the account saved
	 *
	 * @since 2.0.0
	 * @return string account last four
	 */
	public function get_account_last_four() {

		return ltrim( $this->direct_response->account_last_four, 'X' );
	}


	/**
	 * Returns the card type saved, e.g. `visa`
	 *
	 * @since 2.0.0
	 * @return string card type
	 */
	public function get_card_type() {

		return strtolower( $this->direct_response->card_type );
	}


	/**
	 * Get the transaction payment type.
	 *
	 * @since 2.2.0
	 * @return string either `credit-card` or `echeck`
	 */
	public function get_payment_type() {

		return ( 'CC' === $this->get_account_type() ) ? 'credit-card' : 'echeck';
	}


}
