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
 * Authorize.Net API Shipping Address Request Class
 *
 * Generates XML required by API specs to perform a shipping adddress API request
 *
 * @link http://developer.authorize.net/api/reference/#manage-customer-profiles-create-customer-shipping-address
 *
 * @since 2.0.0
 */
class WC_Authorize_Net_CIM_API_Shipping_Address_Request extends WC_Authorize_Net_CIM_API_Profile_Request {


	/**
	 * Creates a shipping address for the given order/customer profile
	 *
	 * @since 2.0.0
	 * @param WC_Order $order order object
	 */
	public function create_shipping_address( WC_Order $order ) {

		$this->order = $order;

		$this->request_type = 'createCustomerShippingAddressRequest';

		$this->request_data = array(
			'refId'             => $this->order->get_id(),
			'customerProfileId' => $order->customer_id,
			'address'           => $this->get_address( 'shipping' ),
		);
	}


	/**
	 * Retrieves the shipping address for a given shipping address ID
	 *
	 * @since 2.0.0
	 * @param string $customer_profile_id customer profile ID
	 * @param string $shipping_address_id shipping profile ID
	 */
	public function get_shipping_address( $customer_profile_id, $shipping_address_id ) {

		$this->request_type = 'getCustomerShippingAddressRequest';

		$this->request_data = array(
			'customerProfileId' => $customer_profile_id,
			'customerAddressId' => $shipping_address_id,
		);
	}


	/**
	 * Updates the shipping address for a given order/customer profile, and by
	 * update, I mean entirely replace the existing address xD
	 *
	 * @since 2.0.0
	 * @param WC_Order $order order object
	 */
	public function update_shipping_address( WC_Order $order ) {

		$this->order = $order;

		$this->request_type = 'updateCustomerShippingAddressRequest';

		$shipping = $this->get_address( 'shipping' );

		$shipping['customerAddressId'] = $order->payment->shipping_address_id;

		$this->request_data = array(
			'refId'                     => $this->order->get_id(),
			'customerProfileId'         => $order->customer_id,
			'address'                   => $shipping,
		);
	}


	/**
	 * Remove the shipping address for a given customer profile
	 *
	 * @since 2.0.0
	 * @param string $customer_profile_id customer profile ID
	 * @param string $shipping_address_id shipping address ID
	 */
	public function delete_shipping_address( $customer_profile_id, $shipping_address_id ) {

		$this->request_type = 'deleteCustomerShippingAddressRequest';

		$this->request_data = array(
			'customerProfileId' => $customer_profile_id,
			'customerAddressId' => $shipping_address_id,
		);
	}


}
