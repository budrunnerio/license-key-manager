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
 * Authorize.Net Accept Hosted response.
 *
 * @since 3.0.0
 */
abstract class Abstract_Payment_Response implements Framework\SV_WC_Payment_Gateway_API_Payment_Notification_Response {


	/** approved transaction response code */
	const TRANSACTION_APPROVED = '1';

	/** declined transaction response code */
	const TRANSACTION_DECLINED = '2';

	/** error with transaction response code */
	const TRANSACTION_ERROR = '3';

	/** held for review transaction response code */
	const TRANSACTION_HELD = '4';


	/** @var array response data */
	protected $data = [];


	/**
	 * Constructs the class.
	 *
	 * @since 3.0.0
	 *
	 * @param array $data response data
	 */
	public function __construct( array $data ) {

		$this->data = $data;
	}


	/**
	 * Determines if the transaction was successful.
	 *
	 * @see SV_WC_Payment_Gateway_API_Response::transaction_approved()
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function transaction_approved() {

		return self::TRANSACTION_APPROVED === $this->get_status_code();
	}


	/**
	 * Determines if the transaction was held.
	 *
	 * This indicates that the transaction was successful, but did not pass a
	 * fraud check and should be reviewed.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function transaction_held() {

		return self::TRANSACTION_HELD === $this->get_status_code();
	}


	/**
	 * Gets the order ID associated with this response.
	 *
	 * @since 3.0.0
	 *
	 * @return int
	 */
	public function get_order_id() {

		return $this->get_data( 'order_id' );
	}


	/**
	 * Determines if the transaction was cancelled.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function transaction_cancelled() {

		return false;
	}


	/**
	 * Gets the result status message.
	 *
	 * The Accept Hosted response does not contain any result message.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_status_message() {

		return '';
	}


	/**
	 * Gets the result status code.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_status_code() {

		return $this->get_data( 'responseCode' );
	}


	/**
	 * Gets the transaction ID.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_transaction_id() {

		return $this->get_data( 'transId' );
	}


	/**
	 * Gets the message to be displayed to the customer.
	 *
	 * The Accept Hosted form handles all error messages before we ever reach
	 * this point so this shouldn't ever be needed.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_user_message() {

		return '';
	}


	/**
	 * Gets the account number.
	 *
	 * The API returns this as XXXX1111 so we return only the last four.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_account_number() {

		return substr( $this->get_data( 'accountNumber' ), -4 );
	}


	/**
	 * Gets a value from the response data.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key data key
	 * @return string
	 */
	protected function get_data( $key ) {

		return ! empty( $this->data[ $key ] ) ? $this->data[ $key ] : '';
	}


	/**
	 * Gets the string representation of this request.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function to_string() {

		return print_r( $this->data, true );
	}


	/**
	 * Gets the string representation of this request with any and all sensitive
	 * elements masked or removed.
	 *
	 * This is a hosted response, so no sensitive data is included.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function to_string_safe() {

		return $this->to_string();
	}


	/**
	 * Determines if this is an IPN response.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function is_ipn() {

		return true;
	}


}

