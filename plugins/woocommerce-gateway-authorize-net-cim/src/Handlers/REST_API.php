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

namespace SkyVerge\WooCommerce\Authorize_Net\Handlers;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_12_0 as Framework;

/**
 * REST API handler class.
 *
 * @since 3.0.0
 *
 * @method \WC_Authorize_Net_CIM get_plugin()
 */
class REST_API extends Framework\Payment_Gateway\REST_API {


	/**
	 * Gets the system status data.
	 *
	 * Adds some properties for the configured form type, and whether a client key is available.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function get_system_status_data() {

		$data = parent::get_system_status_data();

		foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

			if ( ! empty( $data['gateways'][ $gateway->get_id() ] ) ) {
				$data['gateways'][ $gateway->get_id() ]['form_type']      = $gateway->get_form_type();
				$data['gateways'][ $gateway->get_id() ]['has_client_key'] = (bool) $gateway->get_client_key();
			}
		}

		return $data;
	}


}
