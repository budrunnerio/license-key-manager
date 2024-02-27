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
 * Authorize.Net Customer Shipping Address Response Class
 *
 * Parses XML received from shipping address requests
 *
 * @link http://developer.authorize.net/api/reference/#manage-customer-profiles
 * @link http://www.authorize.net/support/CIM_guide_XML.pdf
 *
 * @since 2.0.0
 * @see Framework\SV_WC_Payment_Gateway_API_Response
 */
class WC_Authorize_Net_CIM_API_Shipping_Address_Response extends WC_Authorize_Net_CIM_API_Profile_Response {


	/**
	 * Note: responses have not yet been implemented for getCustomerShippingAddressRequest or
	 * deleteCustomerShippingAddressRequest
	 */


	/**
	 * Return the shipping address ID for the response
	 *
	 * @since 2.0.0
	 * @return null|string shipping address ID
	 */
	public function get_shipping_address_id() {

		return ( ! empty( $this->response_xml->customerAddressId ) ) ? (string) $this->response_xml->customerAddressId : null;
	}


}
