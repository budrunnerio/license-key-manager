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

namespace SkyVerge\WooCommerce\Authorize_Net\API;

defined( 'ABSPATH' ) or exit;

/**
 * Authorize.Net API merchant details request class.
 *
 * @since 3.0.0
 */
class Merchant_Details_Request extends \WC_Authorize_Net_CIM_API_Request {


	/**
	 * Gets the details for the configured merchant account.
	 *
	 * @since 3.0.0
	 */
	public function get_merchant_details() {

		$this->request_type = 'getMerchantDetailsRequest';
		$this->request_data = [];
	}


}
