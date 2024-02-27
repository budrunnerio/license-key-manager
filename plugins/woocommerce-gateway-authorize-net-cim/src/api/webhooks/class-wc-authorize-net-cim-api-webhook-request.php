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
 * Authorize.Net Webhook API request class.
 *
 * @since 2.8.0
 */
class WC_Authorize_Net_CIM_API_Webhook_Request extends Framework\SV_WC_API_JSON_Request {


	/**
	 * Constructs the class.
	 *
	 * @since 2.8.0
	 */
	public function __construct() {

		$this->path = '/webhooks';
	}


	/**
	 * Creates a new webhook.
	 *
	 * @since 2.8.0
	 *
	 * @param string $url URL for the webhook to post to
	 * @param array $event_types event types to subscribe to
	 */
	public function create_webhook( $url, array $event_types ) {

		$this->data = array(
			'name'       => str_replace( array( ' ', '.' ), '_', wc_authorize_net_cim()->get_plugin_name() ) . '_Webhook',
			'url'        => $url,
			'eventTypes' => $event_types,
		);
	}


	/**
	 * Deletes a webhook.
	 *
	 * @since 2.8.0
	 *
	 * @param string $webhook_id remote webhook ID
	 */
	public function delete_webhook( $webhook_id ) {

		$this->path .= '/' . $webhook_id;

		$this->method = 'DELETE';
	}


}
