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

namespace SkyVerge\WooCommerce\Authorize_Net;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_12_0 as Framework;

/**
 * Payment form handler.
 *
 * @since 3.0.0
 *
 * @method \WC_Gateway_Authorize_Net_CIM_Credit_Card|\WC_Gateway_Authorize_Net_CIM_eCheck get_gateway()
 */
class Payment_Form extends Framework\SV_WC_Payment_Gateway_Payment_Form {


	/** @var string JavaScript handler base class name */
	protected $js_handler_base_class_name = 'WC_Authorize_Net_Payment_Form_Handler';


	/**
	 * Gets the JS handler class name.
	 *
	 * @since 3.2.7
	 *
	 * @return string
	 */
	protected function get_js_handler_class_name() {

		return $this->js_handler_base_class_name;
	}


	/**
	 * Renders the payment form fields.
	 *
	 * @since 3.0.0
	 */
	public function render_payment_fields() {

		// display the regular payment fields if the inline form is enabled
		if ( $this->get_gateway()->is_inline_payment_form_enabled() ) {
			parent::render_payment_fields();
		}

		$fields = [
			'payment-nonce',
			'payment-descriptor',
			'last-four',
		];

		if ( $this->get_gateway()->is_credit_card_gateway() ) {

			$fields[] = 'card-type';

			if ( $this->get_gateway()->is_lightbox_payment_form_enabled() ) {
				$fields[] = 'expiry';
			}
		}

		foreach ( $fields as $field ) {

			$name = 'wc-' . $this->get_gateway()->get_id_dasherized() . '-' . $field;

			echo '<input type="hidden" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" />';
		}

		// add the lightbox fields on the add payment method page
		if ( is_add_payment_method_page() && $this->get_gateway()->is_credit_card_gateway() && $this->get_gateway()->is_lightbox_payment_form_enabled() ) {
			$this->get_gateway()->render_lightbox_markup();
		}
	}


	/**
	 * Gets the credit card payment fields.
	 *
	 * Overridden to remove name attributes, as handling is all client-side.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function get_credit_card_fields() {

		$fields = parent::get_credit_card_fields();

		$fields['card-number']['name'] = '';

		if ( isset( $fields['card-csc'] ) ) {
			$fields['card-csc']['name'] = '';
		}

		return $fields;
	}


	/**
	 * Gets the eCheck payment fields.
	 *
	 * Overridden to remove name attributes, as handling is all client-side.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	protected function get_echeck_fields() {

		$fields = parent::get_echeck_fields();

		$fields['routing-number']['name'] = $fields['account-number']['name'] = '';

		return $fields;
	}


	/**
	 * Gets the JS args for the payment form handler.
	 *
	 * @since 3.2.6
	 *
	 * @return array
	 */
	protected function get_js_handler_args() {

		$args = [
			'plugin_id'               => $this->get_gateway()->get_plugin()->get_id(),
			'id'                      => $this->get_gateway()->get_id(),
			'id_dasherized'           => $this->get_gateway()->get_id_dasherized(),
			'type'                    => $this->get_gateway()->get_payment_type(),
			'csc_required'            => $this->get_gateway()->csc_enabled(),
			'csc_required_for_tokens' => $this->get_gateway()->csc_enabled_for_tokens(),
			'logging_enabled'         => $this->get_gateway()->debug_log(),
			'lightbox_enabled'        => $this->get_gateway()->is_lightbox_payment_form_enabled(),
			'login_id'                => $this->get_gateway()->get_api_login_id(),
			'client_key'              => $this->get_gateway()->get_client_key(),
			'general_error'           => __( 'An error occurred, please try again or try an alternate form of payment.', 'woocommerce-gateway-authorize-net-cim' ),
			'ajax_url'                => admin_url( 'admin-ajax.php' ),
			'ajax_log_nonce'          => wp_create_nonce( 'wc_' . $this->get_gateway()->get_id() . '_log_js_data' ),
		];

		if ( $this->get_gateway()->supports_card_types() ) {
			$args['enabled_card_types'] = array_map( [ Framework\SV_WC_Payment_Gateway_Helper::class, 'normalize_card_type' ], $this->get_gateway()->get_card_types() );
		}

		/**
		 * Payment Gateway Payment Form JS Arguments Filter.
		 *
		 * Filter the arguments passed to the Payment Form handler JS class
		 *
		 * @since 3.0.0
		 *
		 * @param array $result {
		 *   @type string $plugin_id plugin ID
		 *   @type string $id gateway ID
		 *   @type string $id_dasherized gateway ID dasherized
		 *   @type string $type gateway payment type (e.g. 'credit-card')
		 *   @type bool $csc_required true if CSC field display is required
		 * }
		 * @param Payment_Form $this payment form instance
		 */
		$args = apply_filters( 'wc_' . $this->get_gateway()->get_id() . '_payment_form_js_args', $args, $this );

		return $args;
	}


}
