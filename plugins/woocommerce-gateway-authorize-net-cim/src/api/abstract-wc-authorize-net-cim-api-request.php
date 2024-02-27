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
 * Authorize.Net Abstract API Request Class
 *
 * Provides helper methods for requests
 *
 * @since 2.0.0
 */
abstract class WC_Authorize_Net_CIM_API_Request extends Framework\SV_WC_API_XML_Request implements Framework\SV_WC_Payment_Gateway_API_Request {


	/** @var WC_Order optional order object if this request was associated with an order */
	protected $order;

	/** @var string root element name for request, e.g. `createTransactionRequest` */
	protected $request_type;

	/** @var string API login ID value */
	protected $api_login_id;

	/** @var string API transaction key value */
	protected $api_transaction_key;


	/**
	 * Construct request object
	 *
	 * @since 2.0.0
	 * @param string $api_login_id API login ID
	 * @param string $api_transaction_key API transaction key
	 */
	public function __construct( $api_login_id, $api_transaction_key ) {

		$this->api_login_id        = $api_login_id;
		$this->api_transaction_key = $api_transaction_key;
	}


	/** Request Helper Methods ************************************************/


	/**
	 * Adds payment information to the request
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	protected function get_payment() {

		return [
			'opaqueData' => [
				'dataDescriptor' => $this->order->payment->opaque_descriptor,
				'dataValue'      => $this->order->payment->opaque_value,
			],
		];
	}


	/**
	 * Get the billing or shipping address information for the request
	 *
	 * @since 2.0.0
	 * @param string $type address type, either `billing` or `shipping`
	 * @return array address data
	 */
	protected function get_address( $type ) {

		$billing_address  = trim( $this->order->get_billing_address_1() . ' ' . $this->order->get_billing_address_2() );
		$shipping_address = trim( $this->order->get_shipping_address_1() . ' ' . $this->order->get_shipping_address_2() );

		// address fields
		$fields = array(
			'billing'  => array(
				'firstName'   => array( 'value' => $this->order->get_billing_first_name(), 'limit' => 50 ),
				'lastName'    => array( 'value' => $this->order->get_billing_last_name(),  'limit' => 50 ),
				'company'     => array( 'value' => $this->order->get_billing_company(),    'limit' => 50 ),
				'address'     => array( 'value' => $billing_address,                       'limit' => 60 ),
				'city'        => array( 'value' => $this->order->get_billing_city(),       'limit' => 40 ),
				'state'       => array( 'value' => $this->order->get_billing_state(),      'limit' => 40 ),
				'zip'         => array( 'value' => $this->order->get_billing_postcode(),   'limit' => 20 ),
				'country'     => array( 'value' => Framework\Country_Helper::convert_alpha_country_code( $this->order->get_billing_country() ), 'limit' => 60 ),
				'phoneNumber' => array( 'value' => $this->order->get_billing_phone(),      'limit' => 25 ),
			),
			'shipping' => array(
				'firstName' => array( 'value' => $this->order->get_shipping_first_name(), 'limit' => 50 ),
				'lastName'  => array( 'value' => $this->order->get_shipping_last_name(),  'limit' => 50 ),
				'company'   => array( 'value' => $this->order->get_shipping_company(),    'limit' => 50 ),
				'address'   => array( 'value' => $shipping_address,                       'limit' => 60 ),
				'city'      => array( 'value' => $this->order->get_shipping_city(),       'limit' => 40 ),
				'state'     => array( 'value' => $this->order->get_shipping_state(),      'limit' => 40 ),
				'zip'       => array( 'value' => $this->order->get_shipping_postcode(),   'limit' => 20 ),
				'country'   => array( 'value' => Framework\Country_Helper::convert_alpha_country_code( $this->order->get_shipping_country() ), 'limit' => 60 ),
			),
		);

		$address = array();

		foreach ( $fields[ $type ] as $field_name => $field ) {

			if ( $value = $this->sanitize_address_field( $field_name, $field ) ) {
				$address[ $field_name ] = $value;
			}
		}

		// handle empty shipping addresses by simply setting the address to the billing first/last name
		// this helps ensure each customer profile has a valid shipping address, even if it's not specifically used
		if ( 'shipping' === $type && empty( $address ) ) {
			$address = array(
				'firstName' => $this->sanitize_address_field( 'firstName', $fields['billing']['firstName'] ),
				'lastName'  => $this->sanitize_address_field( 'lastName', $fields['billing']['lastName'] ),
			);
		}

		return $address;
	}


	/**
	 * Sanitize address fields by removing invalid UTF-8, direct response delimiter,
	 * and truncate to field length limits
	 *
	 * @since 2.0.5
	 * @param string $field_name address field name
	 * @param array $field field data
	 * @return string sanitized field
	 */
	protected function sanitize_address_field( $field_name, $field ) {

		if ( 'phone' === $field_name ) {

			$value = preg_replace( '/\D/', '', $field['value'] );

		} else {

			// authorize.net claims to support unicode, but not all code points yet.
			// Unrecognized code points will display in their control panel with question marks
			$value = Framework\SV_WC_Helper::str_to_sane_utf8( $field['value'] );
		}

		// remove any usages of our hybrid direct response delimiter so as to not break response parsing
		// see WC_Authorize_Net_CIM_API_Profile_Response::parse_direct_response()
		$value = str_replace( ':|:', '', $value );

		// truncate to field limits
		return $value ? Framework\SV_WC_Helper::str_truncate( $value, $field['limit'] ) : null;
	}


	/**
	 * Helper to return completed XML document
	 *
	 * @since 2.0.0
	 * @return string XML
	 */
	protected function to_xml() {

		// required for every request
		$authentication_data = array(
			'@attributes'            => array( 'xmlns' => 'AnetApi/xml/v1/schema/AnetApiSchema.xsd' ),
			'merchantAuthentication' => array(
				'name'           => $this->api_login_id,
				'transactionKey' => $this->api_transaction_key,
			),
		);

		// add specific request data
		$this->request_data = array( $this->get_root_element() => array_merge( $authentication_data, $this->request_data ) );

		/**
		 * API Request Data
		 *
		 * Allow actors to modify the request data before it's sent to Authorize.Net
		 *
		 * @since 2.0.0
		 * @param array $data request data to be filtered
		 * @param WC_Order $order order instance
		 * @param WC_Authorize_Net_CIM_API_Request $this, API request class instance
		 */
		$this->request_data = apply_filters( 'wc_authorize_net_cim_api_request_data', $this->request_data, $this->order, $this );

		// remove any empty elements
		$this->request_data = $this->remove_empty_array_elements( $this->request_data );

		return parent::to_xml();
	}


	/**
	 * Helper to recursively remove empty array elements
	 *
	 * @since 2.0.0
	 *
	 * @param array $haystack request elements
	 * @return array
	 */
	private function remove_empty_array_elements( $haystack ) {

		foreach ( $haystack as $key => $value) {
			if ( is_array( $value ) ) {
				$haystack[ $key ] = $this->remove_empty_array_elements( $haystack[ $key ] );
			}

			// remove empty elements
			if ( is_array( $haystack[ $key ] ) ) {

				if ( empty( $haystack[ $key ] ) ) {
					unset( $haystack[ $key ] );
				}

			} else {

				// 0 is a valid value that would otherwise be considered empty
				if ( '0' !== strval( $haystack[ $key ] ) && empty( $haystack[ $key ] ) ) {
					unset( $haystack[ $key ] );
				}
			}
		}

		return $haystack;
	}


	/**
	 * Returns the string representation of this request with any and all
	 * sensitive elements masked or removed
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway_API_Request::to_string_safe()
	 * @return string the request XML, safe for logging/displaying
	 */
	public function to_string_safe() {

		$string = $this->to_string();

		// API login ID
		if ( preg_match( '/<merchantAuthentication>(\s*)<name>(\w+)<\/name>/', $string, $matches ) ) {
			$string = preg_replace( '/<merchantAuthentication>\s*<name>\w+<\/name>/', "<merchantAuthentication>{$matches[1]}<name>" . str_repeat( '*', strlen( $matches[2] ) ) . '</name>', $string );
		}

		// API transaction key
		if ( preg_match( '/<transactionKey>(\w+)<\/transactionKey>/', $string, $matches ) ) {
			$string = preg_replace( '/<transactionKey>\w+<\/transactionKey>/', '<transactionKey>' . str_repeat( '*', strlen( $matches[1] ) ) . '</transactionKey>', $string );
		}

		// card number
		if ( preg_match( '/<cardNumber>(\d+)<\/cardNumber>/', $string, $matches ) && strlen( $matches[1] ) > 4 ) {
			$string = preg_replace( '/<cardNumber>\d+<\/cardNumber>/', '<cardNumber>' . substr( $matches[1], 0, 1 ) . str_repeat( '*', strlen( $matches[1] ) - 5 ) . substr( $matches[1], -4 ) . '</cardNumber>', $string );
		}

		// real CSC code
		$string = preg_replace( '/<cardCode>\d+<\/cardCode>/', '<cardCode>***</cardCode>', $string );

		// bank account number
		if ( preg_match( '/<accountNumber>(\d+)<\/accountNumber>/', $string, $matches ) ) {
			$string = preg_replace( '/<accountNumber>\d+<\/accountNumber>/', '<accountNumber>' . str_repeat( '*', strlen( $matches[1] ) ) . '</accountNumber>', $string );
		}

		// routing number
		if ( preg_match( '/<routingNumber>(\d+)<\/routingNumber>/', $string, $matches ) ) {
			$string = preg_replace( '/<routingNumber>\d+<\/routingNumber>/', '<routingNumber>' . str_repeat( '*', strlen( $matches[1] ) ) . '</routingNumber>', $string );
		}

		if ( preg_match( '/<dataValue>(\w+)<\/dataValue>/', $string, $matches ) ) {
			$string = preg_replace( '/<dataValue>\w+<\/dataValue>/', '<dataValue>' . str_repeat( '*', 10 ) . '</dataValue>', $string );
		}

		return $this->prettify_xml( $string );
	}


	/**
	 * Returns the order associated with this request, if there was one
	 *
	 * @since 2.0.0
	 * @return WC_Order the order object
	 */
	public function get_order() {

		return $this->order;
	}


	/**
	 * Return the request type, mainly for use in response classes where the
	 * responses vary according to the type of request
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_request_type() {

		return str_replace( 'Request', '', $this->request_type );
	}


	/**
	 * Get the root element for the XML document.
	 *
	 * @since 2.2.0
	 * @return string
	 */
	protected function get_root_element() {

		return $this->request_type;
	}


}
