<?php

namespace SkyVerge\WooCommerce\Authorize_Net\Blocks\Traits;

use SkyVerge\WooCommerce\Authorize_Net\Blocks\Credit_Card_Checkout_Block_Integration;
use SkyVerge\WooCommerce\Authorize_Net\Blocks\Electronic_Check_Checkout_Block_Integration;

/**
 * Trait shared by {@see Credit_Card_Checkout_Block_Integration} and {@see Electronic_Check_Checkout_Block_Integration}.
 *
 * @since 3.10.0
 *
 * @property \WC_Gateway_Authorize_Net_CIM_Credit_Card|\WC_Gateway_Authorize_Net_CIM_eCheck $gateway the gateway instance
 */
trait Checkout_Block_Integration_Trait {


	/**
	 * Gets the payment method script handles.
	 *
	 * This will also ensure that the proper accept.js dependency will be registered before loading the block script.
	 *
	 * @since 3.10.0
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles() : array {

		$handles = parent::get_payment_method_script_handles();

		$url = 'https://js'
			. ( $this->gateway->is_production_environment() ? '.authorize.net/' : 'test.authorize.net/' )
			. ( $this->gateway->is_inline_payment_form_enabled() ? 'v1/Accept.js' : 'v3/AcceptUI.js' );

		// ONE OF: 'wc-authorize-net-cim-accept-inline' OR 'wc-authorize-net-cim-accept-lightbox'
		$handle = 'wc-' . $this->plugin->get_id_dasherized() . '-accept-' . $this->gateway->get_form_type();

		// null $ver prevents warnings from AcceptJS caused by WP versioning
		if ( wp_register_script( $handle, $url, [], null ) ) {
			$handles[] = $handle;
		}

		return $handles;
	}


	/**
	 * Gets configuration flags shared by gateways.
	 *
	 * @since 3.10.0
	 *
	 * @return array<string, bool>
	 */
	public function get_authorize_net_configuration_flags() : array {

		return [
			'lightbox_enabled'    => $this->gateway->is_lightbox_payment_form_enabled(),
			'hosted_form_enabled' => $this->gateway->is_hosted_payment_form_enabled(),
		];
	}


	/**
	 * Gets connection settings shared by gateways.
	 *
	 * @since 3.10.0
	 *
	 * @return array<string, string>
	 */
	public function get_authorize_net_connection_settings() : array {

		return [
			'api_login_id'   => $this->gateway->get_api_login_id(),
			'api_client_key' => $this->gateway->get_client_key(),
			'form_type'      => $this->gateway->get_form_type(),
		];
	}


}
