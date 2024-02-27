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
 * The Authorize.Net customer webhook handler.
 *
 * @since 2.8.0
 */
abstract class WC_Authorize_Net_CIM_Customer_Webhook extends WC_Authorize_Net_CIM_Webhook {


	/**
	 * Marks all subscriptions associated with the given gateway and customer
	 * invalid.
	 *
	 * @since 2.8.0
	 *
	 * @param \WC_Gateway_Authorize_Net_CIM gateway object
	 * @param string $customer_id customer profile ID
	 * @param string $token payment profile ID.
	 */
	protected function mark_invalid_subscriptions( WC_Gateway_Authorize_Net_CIM $gateway, $customer_id, $token = '' ) {

		// sanity check for Subscriptions 2.0+
		if ( ! $this->get_plugin()->is_subscriptions_active() ) {
			return;
		}

		foreach ( $this->get_subscriptions_for_customer( $gateway, $customer_id, $token ) as $subscription ) {

			if ( $token ) {
				$message = __( 'The payment method has been deleted from the Authorize.Net merchant account.', 'woocommerce-gateway-authorize-net-cim' );
			} else {
				$message = __( 'The customer profile has been deleted from the Authorize.Net merchant account.', 'woocommerce-gateway-authorize-net-cim' );
			}

			$subscription->add_order_note( '<strong>' . $gateway->get_method_title() . '</strong>: ' . $message );
		}
	}


	/**
	 * Gets all subscriptions associated with the given gateway and customer.
	 *
	 * @since 2.8.0
	 *
	 * @param \WC_Gateway_Authorize_Net_CIM $gateway object
	 * @param string $customer_id customer profile ID
	 * @param string $token payment profile ID.
	 * @return \WC_Subscription[]
	 */
	protected function get_subscriptions_for_customer( WC_Gateway_Authorize_Net_CIM $gateway, $customer_id, $token = '' ) {

		$meta_query = [
			[
				'key'     => '_payment_method',
				'value'   => $gateway->get_id(),
			],
			[
				'key'   => $gateway->get_order_meta_prefix() . 'customer_id',
				'value' => $customer_id,
			],
		];

		if ( $token ) {

			$meta_query[] = [
				'key'   => $gateway->get_order_meta_prefix() . 'payment_token',
				'value' => $token,
			];
		}

		if ( Framework\SV_WC_Plugin_Compatibility::is_hpos_enabled() ) {

			return wcs_get_subscriptions( [
				'subscription_status'    => [ 'pending', 'active', 'on-hold' ],
				'meta_query'             => $meta_query,
				'subscriptions_per_page' => -1,
			] );

		} else {

			$subscriptions = get_posts( [
				'post_type'      => 'shop_subscription',
				'post_status'    => [ 'wc-pending', 'wc-active', 'wc-on-hold' ],
				'meta_query'     => $meta_query,
				'posts_per_page' => -1,
			] );

			// convert to subscription objects and filter out any non-subscriptions
			return array_filter( array_map( 'wcs_get_subscription', $subscriptions ) );
		}
	}


	/**
	 * Gets a WordPress user ID based on the passed customer profile ID.
	 *
	 * @since 2.8.0
	 *
	 * @param string $customer_id a customer profile ID
	 * @return int
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	protected function get_user_id_for_customer( $customer_id ) {
		global $wpdb;

		foreach ( $this->get_gateways() as $gateway ) {

			$user_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s", $gateway->get_customer_id_user_meta_name(), $customer_id ) );

			if ( ! $user_id ) {
				throw new Framework\SV_WC_API_Exception( "No user exists with the customer profile {$customer_id}" );
			}

			return $user_id;
		}
	}


}
