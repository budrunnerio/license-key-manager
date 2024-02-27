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
 * Authorize.Net Transaction Response Class
 *
 * Parses transaction XML received from the Authorize.Net AIM API, the general response body looks like:
 *
 * <?xml version="1.0" encoding="utf-8"?>
 * <createTransactionResponse xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
 * 	<refId>123456</refId>
 * 	<messages>
 * 		<resultCode>Ok</resultCode>
 * 		<message>
 * 			<code>I00001</code>
 * 			<text>Successful.</text>
 * 		</message>
 * 	</messages>
 * 	<transactionResponse>
 * 		<responseCode>1</responseCode>
 * 		<authCode>UGELQC</authCode>
 * 		<avsResultCode>E</avsResultCode>
 * 		<cavvResultCode />
 * 		<transId>2148061808</transId>
 * 		<refTransID />
 *		<transHash>0B428D8A928AAC61121AF2F6EAC5FF3F</transHash>
 * 		<testRequest>0</testRequest>
 * 		<accountNumber>XXXX0015</accountNumber>
 * 		<accountType>MasterCard</accountType>
 * 		<message>
 * 			<code>1</code>
 * 			<description>This transaction has been approved.</description>
 * 		</message>
 * 		<userFields>
 * 			<userField>
 * 				<name>MerchantDefinedFieldName1</name>
 * 				<value>MerchantDefinedFieldValue1</value>
 * 			</userField>
 * 		</userFields>
 * 	</transactionResponse>
 * </createTransactionResponse>
 *
 * @link http://developer.authorize.net/api/reference/#payment-transactions-charge-a-credit-card
 * @link http://www.authorize.net/support/AIM_guide_XML.pdf
 *
 * @since 2.0.0
 * @see Framework\SV_WC_Payment_Gateway_API_Response
 */
class WC_Authorize_Net_CIM_API_Non_Profile_Transaction_Response extends WC_Authorize_Net_CIM_API_Transaction_Response {



	/**
	 * Checks if the response is from a test request which means all the response
	 * data is bogus.
	 *
	 * @since 2.0.0
	 * @return bool true if testRequest element is present, false otherwise
	 */
	public function is_test_request() {

		return isset( $this->response_xml->transactionResponse->testRequest ) && '1' === (string) $this->response_xml->transactionResponse->testRequest;
	}


	/**
	 * Gets the response transaction id, or null if there is no transaction id
	 * associated with this transaction
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API_Response::get_transaction_id()
	 * @return string transaction id
	 */
	public function get_transaction_id() {

		return isset( $this->response_xml->transactionResponse->transId ) ? (string) $this->response_xml->transactionResponse->transId : null;
	}


	/**
	 * Gets the transaction status code
	 *
	 * @since 2.0.0
	 * @return string transaction status code or null if none was found
	 */
	public function get_transaction_response_code() {

		return isset( $this->response_xml->transactionResponse->responseCode ) ? (string) $this->response_xml->transactionResponse->responseCode : null;
	}


	/**
	 * Gets the transaction status message
	 *
	 * @since 2.0.0
	 * @return string transaction status message
	 */
	public function get_transaction_status_message() {

		$messages = array();

		// messages
		if ( isset( $this->response_xml->transactionResponse->messages->message ) ) {

			foreach ( $this->response_xml->transactionResponse->messages->message as $message ) {

				$messages[] = sprintf( __( 'Message Code: %s - %s', 'woocommerce-gateway-authorize-net-cim' ), (string) $message->code, (string) $message->description );
			}
		}

		// errors
		if ( isset( $this->response_xml->transactionResponse->errors->error ) ) {

			foreach ( $this->response_xml->transactionResponse->errors->error as $error ) {

				$messages[] = sprintf( __( 'Error Code: %s - %s', 'woocommerce-gateway-authorize-net-cim' ), (string) $error->errorCode, (string) $error->errorText );
			}
		}

		return implode( ',', $messages );
	}


	/**
	 * Returns the response reason code
	 *
	 * @since 2.0.0
	 * @return string response reason code
	 */
	public function get_transaction_response_reason_code() {

		return isset( $this->response_xml->transactionResponse->errors->error->errorCode ) ? (string) $this->response_xml->transactionResponse->errors->error->errorCode : null;
	}


	/**
	 * Returns the response reason code
	 *
	 * @since 2.0.0
	 * @return string response reason code
	 */
	public function get_transaction_response_reason_text() {
		return isset( $this->response_xml->transactionResponse->errors->error->errorText ) ? $this->response_xml->transactionResponse->errors->error->errorText : null;
	}


	/**
	 * The authorization code is returned from the credit card processor to
	 * indicate that the charge will be paid by the card issuer
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API_Authorization_Response::get_authorization_code()
	 * @return string 6 character credit card authorization code
	 */
	public function get_authorization_code() {

		return isset( $this->response_xml->transactionResponse->authCode ) ? (string) $this->response_xml->transactionResponse->authCode : null;
	}


	/**
	 * Returns the result of the AVS check
	 *
	 * see page 49 of the CIM XML developer documentation for explanations
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API_Authorization_Response::get_avs_result()
	 * @return string result of the AVS check, if any
	 */
	public function get_avs_result() {

		return isset( $this->response_xml->transactionResponse->avsResultCode ) ? (string) $this->response_xml->transactionResponse->avsResultCode : null;
	}


	/**
	 * Returns the result of the CSC check
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API_Authorization_Response::get_csc_result()
	 * @return string result of CSC check
	 */
	public function get_csc_result() {

		return isset( $this->response_xml->transactionResponse->cvvResultCode ) ? (string) $this->response_xml->transactionResponse->cvvResultCode : null;
	}



	/**
	 * Returns the result of the CAVV (Cardholder authentication verification) check
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API_Authorization_Response::get_csc_result()
	 * @return string result of CAVV check
	 */
	public function get_cavv_result() {

		return isset( $this->response_xml->transactionResponse->cavvResultCode ) ? (string) $this->response_xml->transactionResponse->cavvResultCode : null;
	}


	/**
	 * Get the transaction payment type.
	 *
	 * @since 2.2.0
	 * @return string either `credit-card` or `echeck`
	 */
	public function get_payment_type() {

		return ( 'eCheck' === $this->response_xml->transactionResponse->accountType ) ? 'echeck' : 'credit-card';
	}


}
