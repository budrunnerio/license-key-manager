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
 * Authorize.Net Shipping Address
 *
 * Represents a single CIM shipping address -- each registered customer can
 * have a single shipping address which is used for every profile transaction
 * and updated as needed on a per-transaction basis
 *
 * @since 2.0.0
 */
class WC_Authorize_Net_CIM_Shipping_Address {


	/** @var int|string WP user ID */
	protected $user_id;

	/** @var \WC_Gateway_Authorize_Net_CIM gateway instance */
	protected $gateway;

	/** @var string|int CIM shipping address ID */
	protected $id;

	/** @var string MD5 hash of the shipping address data */
	protected $hash;


	/**
	 * Construct the shipping address
	 *
	 * @since 2.0.0
	 * @param int|string $user_id WP user ID
	 * @param \WC_Gateway_Authorize_Net_CIM $gateway instance
	 */
	public function __construct( $user_id, $gateway ) {

		$this->user_id = $user_id;
		$this->gateway = $gateway;

		// load from user meta
		$this->id   = get_user_meta( $this->get_user_id(), $this->get_user_meta_key( 'id' ), true );
		$this->hash = get_user_meta( $this->get_user_id(), $this->get_user_meta_key( 'hash' ), true );

		// TODO: maybe set hash from user on the fly, since migrated users won't have hash set during the upgrade routine (maybe) @MR
	}


	/**
	 * Get the WP user ID
	 *
	 * @since 2.0.0
	 * @return int|string
	 */
	public function get_user_id() {

		return $this->user_id;
	}


	/**
	 * Get the gateway instance
	 *
	 * @since 2.0.0
	 * @return \WC_Gateway_Authorize_Net_CIM
	 */
	protected function get_gateway() {
		return $this->gateway;
	}


	/**
	 * Get the shipping address ID, this is provided by Authorize.Net
	 * @since 2.0.0
	 * @return string
	 */
	public function get_id() {

		return $this->id;
	}


	/**
	 * Get the shipping address data hash
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_hash() {

		return $this->hash;
	}


	/**
	 * Update the shipping address ID in user meta
	 *
	 * @since 2.0.0
	 * @param string $id shipping address ID provided by Authorize.Net
	 */
	public function update_id( $id ) {

		$this->id = $id;

		update_user_meta( $this->get_user_id(), $this->get_user_meta_key( 'id' ), $this->id );
	}


	/**
	 * Update the shipping data hash
	 *
	 * @since 2.0.0
	 * @param \WC_Order $order order object
	 */
	public function update_hash( WC_Order $order ) {

		$this->hash = $this->generate_hash( $order );

		update_user_meta( $this->get_user_id(), $this->get_user_meta_key( 'hash' ), $this->hash );
	}


	/**
	 * Calculate the shipping data hash from the provided order object, or if null,
	 * the current user object. The exact hashed value is the md5 hash of a JSON-encoded
	 * array in format:
	 *
	 * {
	 *   'first_name' => 'First',
	 *   'last_name'  => 'Last',
	 *   'address'    => '123 Example St',
	 *   'city'       => 'Anywhere',
	 *   'state'      => 'HI',
	 *   'postcode'   => '90210',
	 *   'country'    => 'US',
	 * }
	 *
	 * @since 2.0.0
	 * @param \WC_Order|null $order order object, optional
	 * @return string hash
	 */
	public function generate_hash( $order = null ) {

		if ( $order && $order instanceof WC_Order ) {

			// generate from WC
			$shipping = $this->get_shipping_from_object( $order );

		} else {

			// generate from WC shipping user meta
			$user = get_user_by( 'id', $this->get_user_id() );

			$shipping = $this->get_shipping_from_object( $user );
		}

		return md5( json_encode( $shipping ) );
	}


	/**
	 * Return the shipping address ID user meta key
	 *
	 * `wc_authorize_net_cim_shipping_address_id{_non-production environment ID}`
	 *
	 * @since 2.0.0
	 * @param string $type, key type - either `id` or `hash`
	 * @return string
	 */
	protected function get_user_meta_key( $type ) {

		// no leading underscore since this is meant to be visible to admin
		// e.g. wc_authorize_net_cim_shipping_address_id_test
		return sprintf( 'wc_%s_shipping_address_%s%s', $this->get_gateway()->get_plugin()->get_id(), strtolower( $type ), $this->get_gateway()->is_production_environment() ? '' : '_' . $this->get_gateway()->get_environment() );
	}


	/**
	 * Returns true if the shipping info on the provided order matches that saved
	 * for the customer. Used to determine if the the shipping address needs to be
	 * updated in Authorize.Net prior to processing a transaction.
	 *
	 * @since 2.0.0
	 * @param \WC_Order $order order object
	 * @return bool
	 */
	public function matches_order( WC_Order $order ) {

		$shipping = $this->get_shipping_from_object( $order );

		return md5( json_encode( $shipping ) ) === $this->get_hash();
	}


	/**
	 * Returns true if the shipping info provided by Authorize.Net for the customer
	 * profile matches the shipping info on the order. Used when the local
	 * shipping profile has been corrupted or deleted, but it exists in Authorize.Net.
	 * Because the API does not return which profile is considered duplicate, we
	 * must iterate through each one and match it to the shipping address on the order
	 * to find which one to use ಠ_ಠ
	 *
	 * @since 2.0.0
	 * @param array $address address data provided by WC_Authorize_Net_CIM_API_Customer_Profile_Response::get_customer_shipping_addresses()
	 * @return bool
	 */
	public function matches_address( $address ) {

		// convert the country code to 2-character, as stored by WC
		if ( ! empty( $address['country'] ) ) {
			$address['country'] = 3 === strlen( $address['country'] ) ? Framework\Country_Helper::convert_alpha_country_code( $address['country'] ) : $address['country'];
		} else {
			$address['country'] = '';
		}

		return md5( json_encode( $address ) ) === $this->get_hash();
	}


	/**
	 * Helper to get the correct array format for hashing the shipping info from
	 * a given object, either order or user
	 *
	 * @since 2.0.0
	 * @param \WC_Order|\WP_User $object
	 * @return array
	 */
	protected function get_shipping_from_object( $object ) {

		if ( $object instanceof \WC_Order ) {

			$shipping = array(
				'first_name' => $object->get_shipping_first_name(),
				'last_name'  => $object->get_shipping_last_name(),
				'company'    => $object->get_shipping_company(),
				'address'    => trim( $object->get_shipping_address_1() . ' ' . $object->get_shipping_address_2() ),
				'city'       => $object->get_shipping_city(),
				'state'      => $object->get_shipping_state(),
				'postcode'   => $object->get_shipping_postcode(),
				'country'    => $object->get_shipping_country(),
			);

		} else {

			$shipping = array(
				'first_name' => $object->shipping_first_name,
				'last_name'  => $object->shipping_last_name,
				'company'    => $object->shipping_company,
				'address'    => trim( $object->shipping_address_1 . ' ' . $object->shipping_address_2 ),
				'city'       => $object->shipping_city,
				'state'      => $object->shipping_state,
				'postcode'   => $object->shipping_postcode,
				'country'    => $object->shipping_country,
			);
		}

		return $shipping;
	}


	/**
	 * Delete the shipping address ID and hash from user meta
	 *
	 * @since 2.0.0
	 */
	public function delete() {

		delete_user_meta( $this->get_user_id(), $this->get_user_meta_key( 'id' ) );
		delete_user_meta( $this->get_user_id(), $this->get_user_meta_key( 'hash' ) );
	}


}
