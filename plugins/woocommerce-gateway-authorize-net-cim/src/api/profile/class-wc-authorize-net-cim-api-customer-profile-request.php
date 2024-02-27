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
 * Authorize.Net API Customer Profile Request Class
 *
 * Generates XML required by API specs to perform a customer profile API request
 *
 * @link http://developer.authorize.net/api/reference/#manage-customer-profiles
 *
 * @since 2.0.0
 */
class WC_Authorize_Net_CIM_API_Customer_Profile_Request extends WC_Authorize_Net_CIM_API_Profile_Request {


	/**
	 * Creates a customer profile and payment profile for the given order
	 *
	 * @since 2.0.0
	 * @param WC_Order $order the order object
	 */
	public function create_customer_and_payment_profile( WC_Order $order ) {

		$this->order = $order;

		$this->request_type = 'createCustomerProfileRequest';

		// payment profile data
		$payment_profile = array(
			'paymentProfiles' => array(
				'billTo'  => $this->get_address( 'billing' ),
				'payment' => $this->get_payment(),
			)
		);

		// customer profile data
		$this->request_data = array(
			'refId'          => $this->order->get_id(),
			'profile'        => $this->get_profile_data( $order, $payment_profile ),
			'validationMode' => $this->get_validation_mode(),
		);
	}


	/**
	 * Creates a customer profile (no payment profile) for the given order
	 *
	 * @since 2.0.0
	 * @param WC_Order $order the order object
	 */
	public function create_customer_profile( WC_Order $order ) {

		$this->order = $order;

		$this->request_type = 'createCustomerProfileRequest';

		$this->request_data = array(
			'refId'   => $this->order->get_id(),
			'profile' => $this->get_profile_data( $order ),
		);
	}


	/**
	 * Retrieves the customer profile (and associated payment profiles) for
	 * the given customer profile ID
	 *
	 * @since 2.0.0
	 * @param string $customer_profile_id customer profile ID
	 */
	public function get_customer_profile( $customer_profile_id ) {

		$this->request_type = 'getCustomerProfileRequest';

		$this->request_data = [
			'customerProfileId'    => $customer_profile_id,
			'unmaskExpirationDate' => true,
		];
	}


	/**
	 * Updates the customer profile for the given ID
	 *
	 * Note that phone number cannot be updated once set for a profile
	 *
	 * @since 2.0.0
	 * @param string $customer_profile_id customer profile ID
	 * @param WC_Order $order the order object
	 */
	public function update_customer_profile( $customer_profile_id, WC_Order $order ) {

		$this->order = $order;

		$this->request_type = 'updateCustomerProfileRequest';

		$this->request_data = array(
			'profile' => $this->get_profile_data( $order, array( 'customerProfileId' => $customer_profile_id ) ),
		);

		// remove phone number since apparently you can't update it for a profile once set (wtf auth.net?)
		unset( $this->request_data['phoneNumber'] );
	}


	/**
	 * Delete the customer profile for the given ID
	 *
	 * @since 2.0.0
	 * @param string $customer_profile_id customer profile ID
	 */
	public function delete_customer_profile( $customer_profile_id ) {

		$this->request_type = 'deleteCustomerProfileRequest';

		$this->request_data = array(
			'customerProfileId' => $customer_profile_id,
		);
	}


	/**
	 * Get *all* the customer profile IDs that exist in Authorize.Net for
	 * the given merchant account info
	 *
	 * @since 2.0.0
	 */
	public function get_customer_profile_ids() {

		$this->request_type = 'getCustomerProfileIdsRequest';
	}


	/**
	 * Helper method to return required profile data when working with customer profiles
	 *
	 * @since 2.0.0
	 * @param WC_Order $order the order object
	 * @param array $additional_data optional additional data to be set, like payment profile data
	 * @return array profile data
	 */
	protected function get_profile_data( WC_Order $order, $additional_data = array() ) {

		$data = array(
			'merchantCustomerId' => $order->get_user_id(),
			'email'              => is_email( $this->order->get_billing_email() ) ? $this->order->get_billing_email() : '',
		);

		if ( $additional_data ) {
			$data = array_merge( $data, $additional_data );
		}

		return $data;
	}


}
