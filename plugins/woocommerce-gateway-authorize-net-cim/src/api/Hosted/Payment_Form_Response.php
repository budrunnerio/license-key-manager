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

namespace SkyVerge\WooCommerce\Authorize_Net\API\Hosted;

use SkyVerge\WooCommerce\PluginFramework\v5_12_0 as Framework;

defined( 'ABSPATH' ) or exit;


/**
 * Authorize.Net API payment form response class.
 *
 * Note: the (string) casts here are critical, without these you'll tend to get untraceable
 * errors like "Serialization of 'SimpleXMLElement' is not allowed"
 *
 * @link https://developer.authorize.net/api/reference/#payment-transactions-get-an-accept-payment-page
 *
 * @since 3.0.0
 */
class Payment_Form_Response extends \WC_Authorize_Net_CIM_API_Response {


	/**
	 * Gets the generated form token.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_token() {

		return (string) $this->token;
	}


	/**
	 * Gets the safe string representation of the response.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function to_string_safe() {

		// mask the generated token as it's kind of useless and clutters the logs
		$string = str_replace( $this->get_token(), '**********', parent::to_string_safe() );

		return $string;
	}


	/**
	 * Determines if this is a test request.
	 *
	 * @since 3.0.0
	 *
	 * @return false
	 */
	public function is_test_request() {

		return false;
	}


}
