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

use SkyVerge\WooCommerce\Authorize_Net\Blocks\Electronic_Check_Checkout_Block_Integration;
use SkyVerge\WooCommerce\PluginFramework\v5_12_0 as Framework;

/**
 * Authorize.Net eCheck Payment Gateway
 *
 * Handles all purchases with eChecks
 *
 * This is a direct check gateway
 *
 * @since 2.0.0
 */
class WC_Gateway_Authorize_Net_CIM_eCheck extends WC_Gateway_Authorize_Net_CIM {


	/** @var string the authorization message displayed at checkout */
	protected $authorization_message = '';

	/** @var string the authorization message displayed at checkout for subscriptions */
	protected $recurring_authorization_message = '';

	/** @var bool whether the authorization message should be displayed at checkout */
	protected $authorization_message_enabled;

	/** @var Electronic_Check_Checkout_Block_Integration|null */
	protected ?Electronic_Check_Checkout_Block_Integration $electronic_check_checkout_block_integration = null;


	/**
	 * Initialize the gateway
	 *
	 * @since 2.0.0
	 */
	public function __construct() {

		parent::__construct(
			WC_Authorize_Net_CIM::ECHECK_GATEWAY_ID,
			wc_authorize_net_cim(),
			array(
				'method_title'       => __( 'Authorize.Net eCheck', 'woocommerce-gateway-authorize-net-cim' ),
				'method_description' => __( 'Allow customers to securely pay using their checking/savings accounts with Authorize.Net.', 'woocommerce-gateway-authorize-net-cim' ),
				'supports'           => array(
					self::FEATURE_PRODUCTS,
					self::FEATURE_PAYMENT_FORM,
					self::FEATURE_PAYMENT_FORM_INLINE,
					self::FEATURE_PAYMENT_FORM_HOSTED,
					self::FEATURE_TOKENIZATION,
					self::FEATURE_TOKEN_EDITOR,
					self::FEATURE_DETAILED_CUSTOMER_DECLINE_MESSAGES,
					self::FEATURE_CUSTOMER_ID,
					self::FEATURE_ADD_PAYMENT_METHOD,
				 ),
				'payment_type'       => self::PAYMENT_TYPE_ECHECK,
				'environments'       => array( 'production' => __( 'Production', 'woocommerce-gateway-authorize-net-cim' ), 'test' => __( 'Test', 'woocommerce-gateway-authorize-net-cim' ) ),
				'shared_settings'    => $this->shared_settings_names,
			)
		);

		// display the authorization message at checkout
		if ( $this->is_authorization_message_enabled() ) {
			add_action( 'wc_' . $this->get_id() . '_payment_form_end',   array( $this, 'display_authorization_message' ) );
		}

		// adjust the recurring authorization message placeholders for subscriptions
		add_filter( 'wc_' . $this->get_id() . '_authorization_message_placeholders', array( $this, 'adjust_subscriptions_placeholders' ), 10, 2 );
	}


	/**
	 * Gets the checkout block integration instance.
	 *
	 * @since 3.1.0-dev.1
	 *
	 * @return Electronic_Check_Checkout_Block_Integration
	 */
	public function get_checkout_block_integration_instance(): ?Framework\Payment_Gateway\Blocks\Gateway_Checkout_Block_Integration {

		if ( null === $this->electronic_check_checkout_block_integration ) {

			require_once( $this->get_plugin()->get_plugin_path() . '/src/Blocks/Electronic_Check_Checkout_Block_Integration.php' );

			$this->electronic_check_checkout_block_integration = new Electronic_Check_Checkout_Block_Integration( $this->get_plugin(), $this );
		}

		return $this->electronic_check_checkout_block_integration;
	}


	/**
	 * Adds the authorization message fields to the gateway settings.
	 *
	 * @since 2.4.0
	 * @see Framework\SV_WC_Payment_Gateway::init_form_fields()
	 */
	public function init_form_fields() {

		$new_fields = array();

		parent::init_form_fields();

		foreach ( $this->form_fields as $id => $field ) {

			if ( 'enable_customer_decline_messages' === $id ) {

				$new_fields['authorization_message_enabled'] = array(
					'title'   => __( 'Authorization', 'woocommerce-gateway-authorize-net-cim' ),
					'label'   => esc_html__( 'Display an authorization confirmation message at checkout', 'woocommerce-gateway-authorize-net-cim' ),
					'type'    => 'checkbox',
					'default' => 'no',
				);

				$new_fields['authorization_message'] = array(
					'title'       => __( 'Authorization Message', 'woocommerce-gateway-authorize-net-cim' ),
					'description' => sprintf(
						/** translators: Placeholders: %1$s - <code> tag, %2$s - </code> tag */
						esc_html__( 'Use these tags to customize your message: %1$s{merchant_name}%2$s, %1$s{order_date}%2$s, and %1$s{order_total}%2$s', 'woocommerce-gateway-authorize-net-cim' ),
						'<code>',
						'</code>'
					),
					'type'    => 'textarea',
					'class'   => 'authorization-message-field',
					'default' => $this->get_default_authorization_message(),
				);

				if ( $this->get_plugin()->is_subscriptions_active() && $this->supports_tokenization() ) {

					$new_fields['recurring_authorization_message'] = array(
						'title'   => __( 'Recurring Authorization Message', 'woocommerce-gateway-authorize-net-cim' ),
						'description' => sprintf(
							/** translators: Placeholders: %1$s - <code> tag, %2$s - </code> tag */
							esc_html__( 'Use these tags to customize your message: %1$s{merchant_name}%2$s, %1$s{order_date}%2$s, and %1$s{order_total}%2$s', 'woocommerce-gateway-authorize-net-cim' ),
							'<code>',
							'</code>'
						),
						'type'    => 'textarea',
						'class'   => 'authorization-message-field',
						'default' => $this->get_default_recurring_authorization_message(),
					);
				}
			}

			$new_fields[ $id ] = $field;
		}

		$this->form_fields = $new_fields;
	}


	/**
	 * Adds some inline JS to show/hide the authorization message settings fields.
	 *
	 * @since 2.4.0
	 * @see WC_Settings_API::admin_options()
	 */
	public function admin_options() {

		parent::admin_options();

		// add inline javascript to show/hide any shared settings fields as needed
		ob_start();

		?>
		( function( $ ) {

			$( '#woocommerce_<?php echo sanitize_html_class( $this->get_id() ); ?>_authorization_message_enabled' ).change( function() {

				var enabled = $( this ).is( ':checked' );

				if ( enabled ) {
					$( '.authorization-message-field' ).closest( 'tr' ).show();
				} else {
					$( '.authorization-message-field' ).closest( 'tr' ).hide();
				}

			} ).change();

		} ) ( jQuery );
		<?php

		wc_enqueue_js( ob_get_clean() );

	}


	/**
	 * Processes a payment.
	 *
	 * Overridden to use a dedicated hosted payment handler when needed.
	 *
	 * @since 3.0.0
	 *
	 * @param int|string $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {

		// normal processing if the hosted form isn't used
		if ( ! $this->is_hosted_payment_form_enabled() || ! $this->get_payment_handler() ) {
			return parent::process_payment( $order_id );
		}

		$order = $this->get_order( $order_id );

		try {

			return $this->get_payment_handler()->process_order_payment( $order );

		} catch ( Framework\SV_WC_Plugin_Exception $exception ) {

			$this->add_debug_message( $exception->getMessage(), 'error	' );

			$user_exception = $exception->getPrevious();

			if ( $user_exception && $user_exception->getMessage() && $this->is_detailed_customer_decline_messages_enabled() ) {
				$user_message = $user_exception->getMessage();
			} else {
				$user_message = __( 'An error occurred, please try again or try an alternate form of payment.', 'woocommerce-gateway-authorize-net-cim' );
			}

			Framework\SV_WC_Helper::wc_add_notice( $user_message, 'error' );

			return [
				'result'   => Framework\Payment_Gateway\Handlers\Abstract_Payment_Handler::RESULT_CODE_FAILURE,
				'redirect' => $order->get_checkout_payment_url(),
			];
		}
	}


	/**
	 * Add payment data to the order.
	 *
	 * @since 3.0.0
	 *
	 * @param int $order_id the order ID
	 * @return \WC_Order
	 */
	public function get_order( $order_id ) {

		$order = parent::get_order( $order_id );

		if ( empty( $order->payment->account_type ) ) {
			$order->payment->account_type = Framework\SV_WC_Helper::get_posted_value( 'wc-' . $this->get_id_dasherized() . '-account-type' );
		}

		return $order;
	}


	/**
	 * Displays the authorization message.
	 *
	 * @since 2.4.0
	 */
	public function display_authorization_message() {

		// do not show authorization message on My Payment Methods page
		if ( is_account_page() ) {
			return;
		}

		/**
		 * Filters the authorization message HTML displayed at checkout.
		 *
		 * @since 2.4.0
		 * @param string $html the message HTML
		 * @param \WC_Gateway_Authorize_Net_CIM_eCheck $gateway the gateway object
		 */
		$html = apply_filters( 'wc_' . $this->get_id() . '_authorization_message_html', '<p class="wc-' . $this->get_id_dasherized() . '-authorization-message">' . $this->get_authorization_message() . '</p>', $this );

		echo wp_kses_post( $html );
	}


	/**
	 * Return the default values for this payment method, used to pre-fill
	 * an authorize.net valid test account number when in testing mode
	 *
	 * @since 2.0.0
	 * @see Framework\SV_WC_Payment_Gateway::get_payment_method_defaults()
	 * @return array
	 */
	public function get_payment_method_defaults() {

		$defaults = parent::get_payment_method_defaults();

		if ( $this->is_test_environment() ) {

			$defaults['routing-number'] = '121000248';
			$defaults['account-number'] = '8675309';
		}

		return $defaults;
	}


	/**
	 * Gets the raw authorization message.
	 *
	 * This may be used by {@see WC_Gateway_Authorize_Net_CIM_eCheck::get_authorization_message()} or by the block component.
	 *
	 * @since 3.10.0
	 *
	 * @return string
	 */
	public function get_raw_authorization_message() : string {

		if ( $this->supports_subscriptions() && ( WC_Subscriptions_Cart::cart_contains_subscription() || WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment ) ) {

			if ( $this->recurring_authorization_message ) {
				$raw_message = $this->recurring_authorization_message;
			} else {
				$raw_message = $this->get_default_recurring_authorization_message();
			}

		} else {

			if ( $this->authorization_message ) {
				$raw_message = $this->authorization_message;
			} else {
				$raw_message = $this->get_default_authorization_message();
			}
		}

		/**
		 * Filters the authorization message displayed at checkout, before replacing the placeholders.
		 *
		 * @since 2.4.0
		 *
		 * @param string $message the raw authorization message text
		 * @param \WC_Gateway_Authorize_Net_CIM_eCheck $gateway the gateway object
		 */
		return (string) apply_filters( 'wc_' . $this->get_id() . '_raw_authorization_message', $raw_message, $this );
	}


	/**
	 * Gets the authorization message displayed at checkout.
	 *
	 * @since 2.4.0
	 *
	 * @return string
	 */
	public function get_authorization_message() : string {

		$raw_message = $this->get_raw_authorization_message();
		$order_total = ( $order = wc_get_order( $this->get_checkout_pay_page_order_id() ) ) ? $order->get_total() : WC()->cart->total;

		/**
		 * Filters the authorization message placeholders.
		 *
		 * @since 2.4.0
		 *
		 * @param array $placeholders the authorization message placeholders
		 * @param \WC_Gateway_Authorize_Net_CIM_eCheck $gateway the gateway object
		 */
		$placeholders = apply_filters( 'wc_' . $this->get_id() . '_authorization_message_placeholders', array(
			'{merchant_name}' => get_bloginfo( 'name' ),
			'{order_total}'   => wc_price( $order_total ),
			'{order_date}'    => date_i18n( wc_date_format() ),
		), $this );

		$message = str_replace( array_keys( $placeholders ), $placeholders, $raw_message );

		/**
		 * Filters the authorization message displayed at checkout.
		 *
		 * @since 2.4.0
		 *
		 * @param string $message the authorization message text
		 * @param \WC_Gateway_Authorize_Net_CIM_eCheck $gateway the gateway object
		 */
		return (string) apply_filters( 'wc_' . $this->get_id() . '_authorization_message', $message, $this );
	}


	/**
	 * Adjust the recurring authorization message placeholders for subscriptions.
	 *
	 * Mainly changing the authorization date to match if on the Change Payment screen.
	 *
	 * @since 2.4.0
	 * @param array $placeholders the authorization message placeholders
	 * @param \WC_Gateway_Authorize_Net_CIM_eCheck $gateway the gateway object
	 * @return array
	 */
	public function adjust_subscriptions_placeholders( $placeholders, $gateway ) {
		global $wp;

		if ( ! $gateway->supports_subscriptions() || ! \WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment ) {
			return $placeholders;
		}

		$subscription = wcs_get_subscription( absint( $wp->query_vars['order-pay'] ) );

		$placeholders['{order_date}'] = $subscription->get_date_to_display( 'next_payment' );

		return $placeholders;
	}


	/**
	 * Gets the default authorization message.
	 *
	 * @since 2.4.0
	 * @return string
	 */
	protected function get_default_authorization_message() {

		return sprintf(
			/** translators: Placeholders: %1$s - the {merchant_name} placeholder, %2$s - the {order_date} placeholder, %3$s - the {order_total} placeholder */
			__( 'By clicking the button below, I authorize %1$s to charge my bank account on %2$s for the amount of %3$s.', 'woocommerce-gateway-authorize-net-cim' ),
			'{merchant_name}',
			'{order_date}',
			'{order_total}'
		);
	}


	/**
	 * Gets the default recurring authorization message.
	 *
	 * @since 2.4.0
	 * @return string
	 */
	protected function get_default_recurring_authorization_message() {

		return sprintf(
			/** translators: Placeholders: %1$s - the {merchant_name} placeholder, %2$s - the {order_total} placeholder, %3$s - the {order_date} placeholder */
			__( 'By clicking the button below, I authorize %1$s to charge my bank account for the amount of %2$s on %3$s, then according to the above recurring totals thereafter.', 'woocommerce-gateway-authorize-net-cim' ),
			'{merchant_name}',
			'{order_total}',
			'{order_date}'
		);
	}


	/**
	 * Determines if the authorization message should be displayed at checkout.
	 *
	 * @since 2.4.0
	 * @return bool
	 */
	public function is_authorization_message_enabled() {

		/**
		 * Filters whether the authorization message should be displayed at checkout.
		 *
		 * @since 2.4.0
		 * @param bool $enabled
		 */
		return (bool) apply_filters( 'wc_' . $this->get_id() . '_authorization_message_enabled', 'yes' === $this->authorization_message_enabled );
	}


}
