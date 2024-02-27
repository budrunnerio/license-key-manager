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
 * Authorize.Net API Profile Request Class
 *
 * Generates XML required by API specs to perform a profile API request
 *
 * @link http://developer.authorize.net/api/reference/#manage-customer-profiles
 *
 * @since 2.0.0
 */
class WC_Authorize_Net_CIM_API_Profile_Request extends WC_Authorize_Net_CIM_API_Request {


	/**
	 * Helper to get the validation mode used when creating a payment profile
	 *
	 * @since 2.0.0
	 */
	protected function get_validation_mode() {

		$validation_mode = 'none';

		/**
		 * CIM Profile Validation Mode
		 *
		 * Allow actors to change the validation mode for CIM profiles to liveMode.
		 * This is normally set to `none`, but there are some instances in which
		 * `liveMode` is preferred, (like adding a new payment method) as there's
		 * no follow-up transaction to ensure the payment method added passes
		 * fraud checks.
		 *
		 * @since 2.0.0
		 * @param bool $live_mode false by default, set to true to enable liveMode
		 * @param WC_Order $order order instance
		 * @param WC_Authorize_Net_CIM_API_Customer_Profile_Request $this, API request class instance
		 */
		if ( apply_filters( 'wc_authorize_net_cim_enable_live_mode_profile_validation', false, $this->order, $this ) ) {
			$validation_mode = 'liveMode';
		}

		return $validation_mode;
	}


	/**
	 * Helper to return the billing info exactly as it was sent to Authorize.Net
	 * in the request. It's important that it matches what was sent, as this is
	 * used to calculate the billing hash saved to the payment profile.
	 *
	 * Authorize.Net does not return the billing info for some profile requests
	 * (like createCustomerProfileRequest or createCustomerPaymentProfileRequest)
	 * and this avoids an extra API call to retrieve it.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_billing() {

		$data = $this->get_data();

		$data = array_shift( $data );

		$billing = array();

		if ( isset( $data['profile']['paymentProfiles']['billTo'] ) ) {

			// from createCustomerProfileRequest
			$billing = $data['profile']['paymentProfiles']['billTo'];

		} elseif ( isset( $data['paymentProfile']['billTo'] ) ) {

			// from createCustomerPaymentProfileRequest
			$billing = $data['paymentProfile']['billTo'];
		}

		return $billing;
	}


}
