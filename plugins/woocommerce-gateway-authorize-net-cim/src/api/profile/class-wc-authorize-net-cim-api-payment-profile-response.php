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
 * Authorize.Net Customer Payment Profile Response Class
 *
 * Parses XML received from payment profile requests
 *
 * @link http://developer.authorize.net/api/reference/#manage-customer-profiles
 * @link http://www.authorize.net/support/CIM_guide_XML.pdf
 *
 * @since 2.0.0
 * @see Framework\SV_WC_Payment_Gateway_API_Response
 */
class WC_Authorize_Net_CIM_API_Payment_Profile_Response extends WC_Authorize_Net_CIM_API_Profile_Response implements Framework\SV_WC_Payment_Gateway_API_Create_Payment_Token_Response {


	/**
	 * Returns the payment token for a given request, this is only available
	 * as a result of createCustomerPaymentProfileRequest or getCustomerPaymentProfileRequest
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API_Create_Payment_Token_Response::get_payment_token()
	 * @return WC_Authorize_Net_CIM_Payment_Profile
	 */
	public function get_payment_token() {

		$data = [];

		if ( 'getCustomerPaymentProfile' === $this->get_request()->get_request_type() ) {

			$data = [
				'type'                => isset( $this->response_xml->paymentProfile->payment->creditCard ) ? 'credit_card' : 'echeck',
				'last_four'           => ltrim( $this->get_account_number(), 'X' ),
				'customer_profile_id' => $this->get_request()->get_customer_profile_id(),
				'billing'             => [
					'first_name' => isset( $this->response_xml->paymentProfile->billTo->firstName )   ? (string) $this->response_xml->paymentProfile->billTo->firstName : '',
					'last_name'  => isset( $this->response_xml->paymentProfile->billTo->lastName )    ? (string) $this->response_xml->paymentProfile->billTo->lastName : '',
					'company'    => isset( $this->response_xml->paymentProfile->billTo->company )     ? (string) $this->response_xml->paymentProfile->billTo->company : '',
					'address'    => isset( $this->response_xml->paymentProfile->billTo->address )     ? (string) $this->response_xml->paymentProfile->billTo->address : '',
					'city'       => isset( $this->response_xml->paymentProfile->billTo->city )        ? (string) $this->response_xml->paymentProfile->billTo->city : '',
					'state'      => isset( $this->response_xml->paymentProfile->billTo->state )       ? (string) $this->response_xml->paymentProfile->billTo->state : '',
					'postcode'   => isset( $this->response_xml->paymentProfile->billTo->zip )         ? (string) $this->response_xml->paymentProfile->billTo->zip : '',
					'country'    => isset( $this->response_xml->paymentProfile->billTo->country )     ? (string) $this->response_xml->paymentProfile->billTo->country : '',
					'phone'      => isset( $this->response_xml->paymentProfile->billTo->phoneNumber ) ? (string) $this->response_xml->paymentProfile->billTo->phoneNumber : '',
				],
			];

			$expiration_date = $this->get_expiration_date();

			if ( $expiration_date && preg_match( '/^\d{4}-\d{2}$/', $expiration_date ) ) {
				$data['exp_year']  = substr( $expiration_date, 0, 4 );
				$data['exp_month'] = substr( $expiration_date, -2 );
			}

		} elseif ( 'createCustomerPaymentProfile' === $this->get_request()->get_request_type() ) {

			$billing = $this->get_request()->get_billing();

			// note that the token data is created from the request & order, since the response for creating a payment profile without validation
			// does not return any payment-method specific data (like last four, etc) or billing address details
			$data = [
				'order'               => $this->get_request()->get_order(),
				'customer_profile_id' => $this->get_request()->get_order()->customer_id,
				'billing'             => [
					'first_name' => isset( $billing['firstName'] )   ? (string) $billing['firstName'] : '',
					'last_name'  => isset( $billing['lastName'] )    ? (string) $billing['lastName'] : '',
					'company'    => isset( $billing['company'] )     ? (string) $billing['company'] : '',
					'address'    => isset( $billing['address'] )     ? (string) $billing['address'] : '',
					'city'       => isset( $billing['city'] )        ? (string) $billing['city'] : '',
					'state'      => isset( $billing['state'] )       ? (string) $billing['state'] : '',
					'postcode'   => isset( $billing['zip'] )         ? (string) $billing['zip'] : '',
					'country'    => isset( $billing['country'] )     ? (string) $billing['country'] : '',
					'phone'      => isset( $billing['phoneNumber'] ) ? (string) $billing['phoneNumber'] : '',
				],
			];
		}

		return new WC_Authorize_Net_CIM_Payment_Profile( $this->get_payment_profile_id(), $data );
	}

	/**
	 * Helper to return the customer profile ID associated with a profile request,
	 * as some responses don't contain the customer profile ID but require them
	 * to build a proper response object (like getCustomerPaymentProfileRequest)
	 *
	 * @since 2.0.0
	 * @return string|null
	 */
	protected function get_customer_profile_id() {

		$data = array_shift( $this->get_request()->get_data() );

		return isset( $data['customerProfileId'] ) ? $data['customerProfileId'] : null;
	}


	/**
	 * Return the payment profile ID for the response
	 *
	 * @since 2.0.0
	 * @return null|string payment profile ID
	 */
	public function get_payment_profile_id() {

		$payment_profile_id = null;

		if ( isset( $this->response_xml->paymentProfile->customerPaymentProfileId ) ) {

			// getCustomerPaymentProfileResponse
			$payment_profile_id = (string) $this->response_xml->paymentProfile->customerPaymentProfileId;
		} else {

			// createCustomerPaymentProfileResponse
			$payment_profile_id = (string) $this->response_xml->customerPaymentProfileId;
		}

		return $payment_profile_id;
	}


	/* The following methods are currently only used for retrieving the masked
	 * values for a payment profile so the billing info can be updated without
	 * affecting the saved payment info ¯\_(ツ)_/¯
	 */


	/**
	 * Return the masked account number (either credit card or bank account)
	 *
	 * @since 2.0.0
	 * @return null|string account number (usually like XXXX1234)
	 */
	public function get_account_number() {

		$account_number = null;

		if ( isset( $this->response_xml->paymentProfile->payment->creditCard->cardNumber ) ) {

			$account_number = (string) $this->response_xml->paymentProfile->payment->creditCard->cardNumber;

		} elseif ( isset( $this->response_xml->paymentProfile->payment->bankAccount->accountNumber ) ) {

			$account_number = (string) $this->response_xml->paymentProfile->payment->bankAccount->accountNumber;
		}

		return $account_number;
	}


	/**
	 * Return the masked expiration date
	 *
	 * @since 2.0.0
	 * @return null|string expiration date (usually like XXXX)
	 */
	public function get_expiration_date() {

		return isset( $this->response_xml->paymentProfile->payment->creditCard->expirationDate ) ? $this->response_xml->paymentProfile->payment->creditCard->expirationDate : null;
	}


	/**
	 * Return the bank account type
	 *
	 * @since 2.0.0
	 * @return null|string bank account type, e.g. `checking` or `savings`
	 */
	public function get_account_type() {

		return isset( $this->response_xml->paymentProfile->payment->bankAccount->accountType ) ? $this->response_xml->paymentProfile->payment->bankAccount->accountType : null;
	}


	/**
	 * Return the masked routing number
	 *
	 * @since 2.0.0
	 * @return null|string routing number (XXXX1234)
	 */
	public function get_routing_number() {

		return isset( $this->response_xml->paymentProfile->payment->bankAccount->routingNumber ) ? $this->response_xml->paymentProfile->payment->bankAccount->routingNumber : null;
	}


	/**
	 * Return the name on the bank account
	 *
	 * @since 2.0.0
	 * @return null|string name on bank account
	 */
	public function get_name_on_account() {

		$xml_str = <<<XML
<?xml version='1.0' standalone='yes'?>
<nameOnAccount>
    <echeckType>WEB</echeckType>
</nameOnAccount>
XML;
		$name_on_account =  new \SimpleXMLElement( $xml_str, LIBXML_NOCDATA );
		return isset( $this->response_xml->paymentProfile->payment->bankAccount->nameOnAccount) ? $this->response_xml->paymentProfile->payment->bankAccount->nameOnAccount : $name_on_account;
	}


	/**
	 * Return the eCheck type
	 *
	 * @since 2.0.0
	 * @return null|string eCheck type, should always be `WEB`
	 */
	public function get_echeck_type() {

		return isset( $this->response_xml->paymentProfile->payment->bankAccount->echeckType ) ? $this->response_xml->paymentProfile->payment->bankAccount->echeckType : null;
	}

}
