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
 * Authorize.Net API Payment Profile Request Class
 *
 * Generates XML required by API specs to perform a payment profile API request
 *
 * @link http://developer.authorize.net/api/reference/#manage-customer-profiles-create-customer-payment-profile
 *
 * @since 2.0.0
 */
class WC_Authorize_Net_CIM_API_Payment_Profile_Request extends WC_Authorize_Net_CIM_API_Profile_Request {


	/**
	 * Creates a payment profile for the given order/customer profile
	 *
	 * @since 2.0.0
	 * @param WC_Order $order the order object
	 */
	public function create_payment_profile( WC_Order $order ) {

		$this->order = $order;

		$this->request_type = 'createCustomerPaymentProfileRequest';

		$this->request_data = array(
			'refId'             => $this->order->get_id(),
			'customerProfileId' => $order->customer_id,
			'paymentProfile'    => array(
				'billTo'  => $this->get_address( 'billing' ),
				'payment' => $this->get_payment(),
			),
			'validationMode' => $this->get_validation_mode(),
		);
	}


	/**
	 * Retrieves the payment profile for a given payment profile ID
	 *
	 * @since 2.0.0
	 *
	 * @param string $customer_profile_id customer profile ID
	 * @param string $payment_profile_id customer payment profile ID
	 */
	public function get_payment_profile( $customer_profile_id, $payment_profile_id ) {

		$this->request_type = 'getCustomerPaymentProfileRequest';

		$this->request_data = array(
			'customerProfileId'        => $customer_profile_id,
			'customerPaymentProfileId' => $payment_profile_id,
		);
	}


	/**
	 * Validates the payment profile for a given customer profile/payment profile
	 *
	 * @since 2.0.0
	 *
	 * @param string $customer_profile_id customer profile ID
	 * @param string $payment_profile_id customer payment profile ID
	 */
	public function validate_payment_profile( $customer_profile_id, $payment_profile_id ) {

		$this->request_type = 'validateCustomerPaymentProfileRequest';

		$this->request_data = array(
			'customerProfileId'        => $customer_profile_id,
			'customerPaymentProfileId' => $payment_profile_id,
			'validationMode'           => $this->get_validation_mode(),
		);
	}


	/**
	 * Updates the payment profile for a given order/customer profile
	 *
	 * The billTo element can be omitted and it will not be updated or removed, but
	 * the payment element is required and must contain the original (masked)
	 * values ಠ_ಠ
	 *
	 * @link http://developer.authorize.net/api/reference/#manage-customer-profiles-update-customer-payment-profile
	 *
	 * @since 2.0.0
	 * @param WC_Order $order the order object
	 * @param WC_Authorize_Net_CIM_API_Payment_Profile_Response $profile_response
	 */
	public function update_payment_profile( WC_Order $order, $profile_response ) {

		$this->order = $order;

		$this->request_type = 'updateCustomerPaymentProfileRequest';

		$this->request_data = array(
			'refId'             => $this->order->get_id(),
			'customerProfileId' => $order->customer_id,
			'paymentProfile'    => array(
				'billTo'                   => $this->get_address( 'billing' ),
				'payment'                  => $this->get_payment_for_update( $profile_response ),
				'customerPaymentProfileId' => $order->payment->token,
			),
			'validationMode' => $this->get_validation_mode(),
		);
	}

	/**
	 * Remove the payment profile for a given customer profile
	 *
	 * @since 2.0.0
	 *
	 * @param string $customer_profile_id customer profile ID
	 * @param string $payment_profile_id customer payment profile ID
	 */
	public function delete_payment_profile( $payment_profile_id, $customer_profile_id ) {

		$this->request_type = 'deleteCustomerPaymentProfileRequest';

		$this->request_data = array(
			'customerProfileId'        => $customer_profile_id,
			'customerPaymentProfileId' => $payment_profile_id,
		);
	}


	/**
	 * Get the payment info data required for updating a profile. Authorize.Net
	 * "best practice" is to first retrieve the payment profile before updating
	 * it so as to include the masked values for things like cardNumber.
	 *
	 * @since 2.0.0
	 * @param WC_Authorize_Net_CIM_API_Payment_Profile_Response $profile
	 * @return array
	 */
	protected function get_payment_for_update( $profile ) {

		if ( 'credit_card' === $this->get_order()->payment->type ) {

			$payment = array(
				'creditCard' => array(
					'cardNumber'     => $profile->get_account_number(),
					'expirationDate' => $profile->get_expiration_date(),
				),
			);

		} else {

			$payment = array(
				'bankAccount' => array(
					'accountType'   => $profile->get_account_type(),
					'routingNumber' => $profile->get_routing_number(),
					'accountNumber' => $profile->get_account_number(),
					'nameOnAccount' => $profile->get_name_on_account(),
					'echeckType'    => $profile->get_echeck_type(),
				),
			);
		}

		return $payment;
	}


}
