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

namespace SkyVerge\WooCommerce\Authorize_Net\Apple_Pay;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_12_0 as Framework;

/**
 * The Apple Pay frontend handler.
 *
 * @since 3.2.7
 */
class Frontend extends Framework\SV_WC_Payment_Gateway_Apple_Pay_Frontend {


	/**
	 * Enqueues the scripts.
	 *
	 * @since 3.2.7
	 */
	public function enqueue_scripts() {

		parent::enqueue_scripts();

		wp_enqueue_script( 'wc-authorize-net-apple-pay', $this->get_plugin()->get_plugin_url() . '/assets/js/frontend/wc-authorize-net-apple-pay.min.js', [ 'sv-wc-apple-pay-v5_12_0' ], $this->get_plugin()->get_version() );
	}


	/**
	 * Gets the JS handler class name.
	 *
	 * @since 3.2.7
	 *
	 * @return string
	 */
	protected function get_js_handler_class_name() {

		return 'WC_Authorize_Net_Apple_Pay_Handler';
	}


}
