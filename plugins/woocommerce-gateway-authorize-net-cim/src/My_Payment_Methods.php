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

namespace SkyVerge\WooCommerce\Authorize_Net;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_12_0 as Framework;

/**
 * The My Payment Methods handler.
 *
 * @since 3.2.7
 */
class My_Payment_Methods extends Framework\SV_WC_Payment_Gateway_My_Payment_Methods {


	/**
	 * Maybe enqueue the styles and scripts.
	 *
	 * Only enqueue if there are payment methods to manage.
	 *
	 * @since 3.2.7
	 */
	public function maybe_enqueue_styles_scripts() {

		parent::maybe_enqueue_styles_scripts();

		if ( $this->has_tokens ) {

			wp_enqueue_script( 'wc-authorize-net-my-payment-methods', $this->get_plugin()->get_plugin_url() . '/assets/js/frontend/wc-authorize-net-my-payment-methods.min.js', [ 'sv-wc-payment-gateway-my-payment-methods-v5_12_0' ], $this->get_plugin()->get_version() );
		}
	}


	/**
	 * Gets the JS handler class name.
	 *
	 * @since 3.2.7
	 *
	 * @return string
	 */
	public function get_js_handler_class_name() {

		return 'WC_Authorize_Net_My_Payment_Methods_Handler';
	}


}
