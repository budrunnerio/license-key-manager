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
 * The Authorize.Net customer profile webhook handler.
 *
 * @since 2.8.0
 */
class WC_Authorize_Net_CIM_Customer_Payment_Profile_Webhook extends WC_Authorize_Net_CIM_Customer_Webhook {


	/** @var string the customer payment profile entity slug */
	protected $entity_name = 'customerPaymentProfile';


	/**
	 * Triggers when a customer payment profile has been updated.
	 *
	 * @since 2.8.0
	 *
	 * @param object $data payload data
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	protected function update_entity( $data ) { }


	/**
	 * Triggers when a customer payment profile has been deleted.
	 *
	 * @since 2.8.0
	 *
	 * @param object $data payload data
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	protected function delete_entity( $data ) {

		$customer_id = $data->customerProfileId;
		$token       = $data->id;
		$user_id     = $this->get_user_id_for_customer( $customer_id );

		foreach ( $this->get_gateways() as $gateway ) {

			$token_meta = get_user_meta( $user_id, $gateway->get_payment_tokens_handler()->get_user_meta_name(), true );

			// if the token was from this gateway, add an entry to its log
			// CIM can get payment tokens via API, so no need to delete any user meta
			if ( ! empty( $token_meta[ $token ] ) ) {
				$this->get_plugin()->log( "Payment method {$token} deleted for user {$user_id} from the merchant account.", $gateway->get_id() );
			}

			// update any subscriptions that have the deleted token
			if ( $this->get_plugin()->is_subscriptions_active() ) {
				$this->mark_invalid_subscriptions( $gateway, $customer_id, $token );
			}
		}
	}


}
