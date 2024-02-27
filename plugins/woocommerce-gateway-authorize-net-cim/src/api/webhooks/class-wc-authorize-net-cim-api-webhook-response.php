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
 * Authorize.Net Webhook API response class.
 *
 * @since 2.8.0
 */
class WC_Authorize_Net_CIM_API_Webhook_Response extends Framework\SV_WC_API_JSON_Response {


	/**
	 * Constructs the class.
	 *
	 * @since 2.8.0
	 *
	 * @param \WC_Authorize_Net_CIM_API_Webhook_Request $request request object
	 * @param string $raw_response_json raw JSON from the response
	 */
	public function __construct( $request, $raw_response_json ) {

		parent::__construct( $raw_response_json );
	}


	/**
	 * Checks if response contains an API error code.
	 *
	 * If there was an error, the API will return a numeric code. Otherwise,
	 * the webhook status (active or inactive) is returned.
	 *
	 * @since 2.8.0
	 *
	 * @return bool
	 */
	public function has_api_error() {

		return (int) $this->status;
	}


	/**
	 * Gets the API error code.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_api_error_code() {

		return (string) $this->reason;
	}


	/**
	 * Gets the API error message.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_api_error_message() {

		$messages = array( (string) $this->message );

		if ( isset( $this->details ) ) {

			foreach ( (array) $this->details as $detail ) {

				if ( isset( $detail->message ) ) {
					$message[] = $detail->message;
				}
			}
		}

		return implode( '. ', $messages );
	}


	/**
	 * Gets the webhook ID.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_id() {

		return (string) $this->webhookId;
	}


	/**
	 * Gets the webhook's enabled event types.
	 *
	 * @since 2.8.0
	 *
	 * @return array
	 */
	public function get_event_types() {

		return (array) $this->eventTypes;
	}


	/** no-op */
	public function is_test_request() {

		return false;
	}


}
