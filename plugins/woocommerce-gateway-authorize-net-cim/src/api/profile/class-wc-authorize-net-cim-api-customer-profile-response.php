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
 * Parses XML received from customer profile requests
 *
 * @link http://developer.authorize.net/api/reference/#manage-customer-profiles
 * @link http://www.authorize.net/support/CIM_guide_XML.pdf
 *
 * @since 2.0.0
 * @see Framework\SV_WC_Payment_Gateway_API_Response
 */
class WC_Authorize_Net_CIM_API_Customer_Profile_Response extends WC_Authorize_Net_CIM_API_Profile_Response implements Framework\SV_WC_Payment_Gateway_API_Create_Payment_Token_Response, Framework\SV_WC_Payment_Gateway_API_Get_Tokenized_Payment_Methods_Response, Framework\SV_WC_Payment_Gateway_API_Customer_Response {


	/**
	 * Returns the payment token for a given request, this is only available
	 * as a result of createCustomerProfileRequest
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API_Create_Payment_Token_Response::get_payment_token()
	 * @return WC_Authorize_Net_CIM_Payment_Profile
	 */
	public function get_payment_token() {

		$billing = $this->get_request()->get_billing();

		// note that the token data is created from the request order, since the response for creating a customer profile without validation
		// does not return any payment-method specific data (like last four, etc)
		$data = [
			'order'               => $this->get_request()->get_order(),
			'customer_profile_id' => $this->get_customer_id(),
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

		return new WC_Authorize_Net_CIM_Payment_Profile( (string) (string) $this->response_xml->customerPaymentProfileIdList->numericString, $data );
	}


	/**
	 * Returns the payment profiles for a given customer profile, this is only
	 * available as a result of getCustomerProfileRequest
	 *
	 * @link http://developer.authorize.net/api/reference/#manage-customer-profiles-get-customer-profile
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API_Get_Tokenized_Payment_Methods_Response::get_payment_tokens()
	 * @return array of WC_Authorize_Net_CIM_Payment_Profile objects
	 */
	public function get_payment_tokens() {

		$profiles = [];

		if ( isset( $this->response_xml->profile->paymentProfiles ) ) {

			foreach ( $this->response_xml->profile->paymentProfiles as $profile ) {

				// exp date is not included in the response
				$data = [
					'type'                => isset( $profile->payment->creditCard ) ? 'credit_card' : 'echeck',
					'last_four'           => ltrim( ( isset( $profile->payment->creditCard->cardNumber ) ? (string) $profile->payment->creditCard->cardNumber : (string) $profile->payment->bankAccount->accountNumber ), 'X' ),
					'customer_profile_id' => $this->get_customer_id(),
					'billing'             => [
						'first_name' => isset( $profile->billTo->firstName )   ? (string) $profile->billTo->firstName : '',
						'last_name'  => isset( $profile->billTo->lastName )    ? (string) $profile->billTo->lastName : '',
						'company'    => isset( $profile->billTo->company )     ? (string) $profile->billTo->company : '',
						'address'    => isset( $profile->billTo->address )     ? (string) $profile->billTo->address : '',
						'city'       => isset( $profile->billTo->city )        ? (string) $profile->billTo->city : '',
						'state'      => isset( $profile->billTo->state )       ? (string) $profile->billTo->state : '',
						'postcode'   => isset( $profile->billTo->zip )         ? (string) $profile->billTo->zip : '',
						'country'    => isset( $profile->billTo->country )     ? (string) $profile->billTo->country : '',
						'phone'      => isset( $profile->billTo->phoneNumber ) ? (string) $profile->billTo->phoneNumber : '',
					],
				];

				if ( isset( $profile->payment->creditCard->cardType ) ) {
					$data['card_type'] = Framework\SV_WC_Payment_Gateway_Helper::normalize_card_type( (string) $profile->payment->creditCard->cardType );
				}

				$expiration_date = isset( $profile->payment->creditCard->expirationDate ) ? $profile->payment->creditCard->expirationDate : null;

				if ( $expiration_date && preg_match( '/^\d{4}-\d{2}$/', $expiration_date ) ) {
					$data['exp_year']  = substr( $expiration_date, 0, 4 );
					$data['exp_month'] = substr( $expiration_date, -2 );
				}

				$profiles[ (string) $profile->customerPaymentProfileId ] = new WC_Authorize_Net_CIM_Payment_Profile( (string) $profile->customerPaymentProfileId, $data );
			}
		}

		return $profiles;
	}


	/**
	 * Returns the customer profile ID
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API_Customer_Response::get_customer_id()
	 * @return string customer ID
	 */
	public function get_customer_id() {

		$customer_profile_id = null;

		if ( ! empty( $this->response_xml->customerProfileId ) ) {

			// from createCustomerProfileRequest
			$customer_profile_id = (string) $this->response_xml->customerProfileId;

		} elseif ( ! empty( $this->response_xml->profile->customerProfileId ) ) {

			// from getCustomerProfileRequest
			$customer_profile_id = (string) $this->response_xml->profile->customerProfileId;
		}

		return $customer_profile_id;
	}


	/**
	 * Returns an array of customer shipping addresses, keyed by the shipping
	 * address ID
	 *
	 * @since 2.0.0
	 * @return array shipping addresses
	 */
	public function get_customer_shipping_addresses() {

		$addresses = array();

		if ( ! empty( $this->response_xml->profile->shipToList ) ) {

			foreach ( $this->response_xml->profile->shipToList as $ship ) {

				$addresses[ (string) $ship->customerAddressId ] = array(
					'first_name' => (string) $ship->firstName,
					'last_name'  => (string) $ship->lastName,
					'company'    => ! empty( $ship->company ) ? (string) $ship->company : '',
					'address'    => (string) $ship->address,
					'city'       => (string) $ship->city,
					'state'      => (string) $ship->state,
					'postcode'   => (string) $ship->zip,
					'country'    => (string) $ship->country,
				);
			}
		}

		return $addresses;
	}


}
