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

 namespace SkyVerge\WooCommerce\Authorize_Net\Blocks;

 use SkyVerge\WooCommerce\Authorize_Net\Blocks\Traits\Checkout_Block_Integration_Trait;
 use SkyVerge\WooCommerce\PluginFramework\v5_12_0\SV_WC_Payment_Gateway;
 use SkyVerge\WooCommerce\PluginFramework\v5_12_0\SV_WC_Payment_Gateway_Plugin;
 use SkyVerge\WooCommerce\PluginFramework\v5_12_0\Payment_Gateway\Blocks\Gateway_Checkout_Block_Integration;

 /**
 * Checkout block integration for the {@see \WC_Gateway_Authorize_Net_CIM_eCheck} gateway.
 *
 * @since 3.10.0
 *
 * @property \WC_Gateway_Authorize_Net_CIM_eCheck $gateway
 */
class Electronic_Check_Checkout_Block_Integration extends Gateway_Checkout_Block_Integration {


	use Checkout_Block_Integration_Trait;

	/**
	 * Constructor.
	 *
	 * @since 3.10.0
	 *
	 * @param SV_WC_Payment_Gateway_Plugin $plugin
	 * @param \WC_Gateway_Authorize_Net_CIM_eCheck $gateway
	 */
	public function __construct( SV_WC_Payment_Gateway_Plugin $plugin, SV_WC_Payment_Gateway $gateway ) {

		parent::__construct( $plugin, $gateway );

		// allow {order_total} to be updated on the frontend
		add_filter('wc_' . $gateway->get_id() . '_authorization_message_placeholders', [ $this, 'keep_order_total_placeholder' ] );

		// accept.js script: either 'inline' or 'lightbox'
		$this->add_main_script_dependency( 'wc-authorize-net-cim-accept-' . $gateway->get_form_type() );
	}


	/**
	 * Removes the {order_total} placeholder from echeck authorization message, so it can be managed by our block script.
	 *
	 * @internal
	 *
	 * @since 3.10.0
	 *
	 * @param array<string, string>|mixed $placeholders the authorization message placeholders
	 */
	public function keep_order_total_placeholder( $placeholders ) : array {

		if ( is_array( $placeholders ) && isset( $placeholders['{order_total}'] ) ) {
			unset( $placeholders['{order_total}'] );
		}

		return $placeholders;
	}


	/**
	 * Adds payment method data.
	 *
	 * @internal
	 *
	 * @see Gateway_Checkout_Block_Integration::get_payment_method_data()
	 *
	 * @since 3.10.0
	 *
	 * @param array<string, mixed> $payment_method_data
	 * @param \WC_Gateway_Authorize_Net_CIM_eCheck $gateway
	 * @return array<string, mixed>
	 */
	public function add_payment_method_data( array $payment_method_data, SV_WC_Payment_Gateway $gateway ) : array {

		$payment_method_data['flags'] = array_merge(
			$payment_method_data['flags'] ?: [],
			[
				'authorization_message_enabled' => $gateway->get_option( 'authorization_message_enabled' ),
			],
			$this->get_authorize_net_configuration_flags()
		);

		$payment_method_data['gateway'] = array_merge(
			$payment_method_data['gateway'] ?: [],
			[
				'authorization_message' => wp_kses_post( $gateway->get_authorization_message() ),
				'merchant_name'         => get_bloginfo( 'name' ),
			],
			$this->get_authorize_net_connection_settings()
		);

		return $payment_method_data;
	}


}
