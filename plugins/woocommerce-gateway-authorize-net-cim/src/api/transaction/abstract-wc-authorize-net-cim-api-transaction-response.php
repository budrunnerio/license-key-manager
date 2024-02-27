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
 * Authorize.Net Transaction Response Class
 *
 * Abstract class for parsing transaction responses
 *
 * @since 2.0.0
 * @see Framework\SV_WC_Payment_Gateway_API_Response
 */
abstract class WC_Authorize_Net_CIM_API_Transaction_Response extends WC_Authorize_Net_CIM_API_Response implements Framework\SV_WC_Payment_Gateway_API_Response, Framework\SV_WC_Payment_Gateway_API_Authorization_Response {


	/** approved transaction response code */
	const TRANSACTION_APPROVED = '1';

	/** declined transaction response code */
	const TRANSACTION_DECLINED = '2';

	/** error with transaction response code */
	const TRANSACTION_ERROR = '3';

	/** held for review transaction response code */
	const TRANSACTION_HELD = '4';

	/** CSC match code */
	const CSC_MATCH = 'M';


	/**
	 * Checks if the transaction was successful
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API_Response::transaction_approved()
	 * @return bool true if approved, false otherwise
	 */
	public function transaction_approved() {

		return ! $this->has_api_error() && self::TRANSACTION_APPROVED === $this->get_transaction_response_code();
	}


	/**
	 * Returns true if the transaction was held, for instance due to AVS/CSC
	 * Fraud Settings.  This indicates that the transaction was successful, but
	 * did not pass a fraud check and should be reviewed
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API_Response::transaction_held()
	 * @return bool true if approved, false otherwise
	 */
	public function transaction_held() {

		return ! $this->has_api_error() && self::TRANSACTION_HELD === $this->get_transaction_response_code();
	}


	/**
	 * Determines if the transaction was held for Fraud Filter reasons.
	 *
	 * This checks for both AVS & CSC result codes that indicate fraud, but only
	 * if the merchant's account is configured to hold them.
	 *
	 * @since 2.8.0
	 *
	 * @return bool
	 */
	public function transaction_held_for_fraud() {

		$avs_codes = array(
			'E',
			'R',
			'G',
			'U',
			'S',
			'N',
			'A',
			'Z',
			'W',
			'X',
		);

		$csc_codes = array(
			'N',
			'P',
			'S',
			'U',
		);

		return $this->transaction_held() && ( in_array( $this->get_avs_result(), $avs_codes, true ) || in_array( $this->get_csc_result(), $csc_codes, true ) );
	}


	/**
	 * Gets the transaction status message: API error message if there was an
	 * API error, otherwise the transaction status message
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API_Response::get_status_message()
	 * @return string status message
	 */
	public function get_status_message() {

		if ( $this->has_api_error() ) {

			if ( 'E00027' === $this->get_api_error_code() ) {
				return $this->get_api_error_message() . ' ' . $this->get_transaction_status_message();
			} else {
				return $this->get_api_error_message();
			}
		}

		return $this->get_transaction_status_message();
	}


	/**
	 * Gets the transaction status code: API error code if there was an
	 * API error, otherwise the transaction status code
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API_Response::get_status_code()
	 * @return string status code
	 */
	public function get_status_code() {

		if ( $this->has_api_error() ) {

			if ( 'E00027' === $this->get_api_error_code() ) {
				return sprintf( '%s (E00027)', $this->get_transaction_response_code() );
			} else {
				return $this->get_api_error_code();
			}
		}

		return $this->get_transaction_response_code();
	}


	/**
	 * Get the transaction status message
	 *
	 * @since 2.0.0
	 * @return mixed
	 */
	abstract public function get_transaction_status_message();


	/**
	 * Get the transaction response code
	 *
	 * @since 2.0.0
	 * @return mixed
	 */
	abstract public function get_transaction_response_code();


	/**
	 * Returns true if the CSC check was successful
	 *
	 * see page 50 of the CIM XML developer documentation for CSC response code explanations
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API_Authorization_Response::csc_match()
	 * @return boolean true if the CSC check was successful
	 */
	public function csc_match() {

		return self::CSC_MATCH == $this->get_csc_result();
	}


	/**
	 * Get the transaction payment type. This should be overridden by concrete classes.
	 *
	 * @since 2.2.0
	 * @return null
	 */
	public function get_payment_type() {
		return null;
	}


}
