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
 * Authorize.Net Response Class
 *
 * Provides helper methods for parsing responses
 *
 * Note: the (string) casts here are critical to convert the objects into valid
 * strings, without these you'll tend to get untraceable errors like
 * "Serialization of 'SimpleXMLElement' is not allowed"
 *
 * @since 2.0.0
 * @see Framework\SV_WC_Payment_Gateway_API_Response
 */
abstract class WC_Authorize_Net_CIM_API_Response extends Framework\SV_WC_API_XML_Response {


	/** @var WC_Authorize_Net_CIM_API_Request request that resulted in this response */
	protected $request;

	/** @var string the response root element name */
	protected $name;


	/**
	 * Build a response object from the raw response xml
	 *
	 * @since 2.0.0
	 * @param Framework\SV_WC_Payment_Gateway_API_Request $request the original request object
	 * @param string $raw_response_xml the raw response XML
	 */
	public function __construct( $request, $raw_response_xml ) {

		$this->request = $request;

		// Remove namespace as SimpleXML throws warnings with invalid namespace URI provided by Authorize.Net
		$raw_response_xml = preg_replace( '/[[:space:]]xmlns[^=]*="[^"]*"/i', '', $raw_response_xml );

		parent::__construct( $raw_response_xml );

		// root element name, useful for identifying exact type of response, e.g. `createTransactionResponse`
		// note that for hard errors, the response type will be `ErrorResponse`
		$this->name = $this->response_xml->getName();
	}


	/**
	 * Checks if response contains an API error code
	 *
	 * @since 2.0.0
	 * @return bool true if has API error, false otherwise
	 */
	public function has_api_error() {

		if ( ! isset( $this->response_xml->messages->resultCode ) ) {
			return true;
		}

		return 'error' == strtolower( (string) $this->response_xml->messages->resultCode );
	}


	/**
	 * Gets the API error code
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_api_error_code() {

		if ( ! isset( $this->response_xml->messages->message->code ) ) {
			return __( 'N/A', 'woocommerce-gateway-authorize-net-cim' );
		}

		return (string) $this->response_xml->messages->message->code;
	}


	/**
	 * Gets the API error message
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_api_error_message() {

		if ( ! isset( $this->response_xml->messages->message->text ) ) {
			return __( 'N/A', 'woocommerce-gateway-authorize-net-cim' );
		}

		$message = (string) $this->response_xml->messages->message->text;

		return $message;
	}


	/**
	 * Gets any user-defined fields associated with the transaction response
	 *
	 * @since 2.0.0
	 * @return array transaction user-defined fields
	 */
	public function get_user_defined_fields() {

		$fields = array();

		if ( isset( $this->response_xml->transactionResponse->userFields->userField ) ) {

			foreach ( $this->response_xml->transactionResponse->userFields->userField as $user_field ) {

				$fields[ (string) $user_field->name ] = (string) $user_field->value;
			}
		}

		return $fields;
	}


	/**
	 * Returns a message appropriate for a frontend user.  This should be used
	 * to provide enough information to a user to allow them to resolve an
	 * issue on their own, but not enough to help nefarious folks fishing for
	 * info.
	 *
	 * @since 2.0.0
	 * @return string user message, if there is one
	 */
	public function get_user_message() {

		$helper = new WC_Authorize_Net_CIM_API_Response_Message_Handler( $this );

		return $helper->get_message();
	}


	/**
	 * Returns the request object that resulted in this response
	 *
	 * @since 2.0.0
	 * @return WC_Authorize_Net_CIM_API_Request request object
	 */
	public function get_request() {

		return $this->request;
	}


}
