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
 * The Authorize.Net webhooks handler.
 *
 * @since 2.8.0
 */
class WC_Authorize_Net_CIM_Webhooks {


	/** the customer event type */
	const EVENT_TYPE_CUSTOMER_PROFILE = 'net.authorize.customer';

	/** the customer profile event type */
	const EVENT_TYPE_CUSTOMER_PAYMENT_PROFILE = 'net.authorize.customer.paymentProfile';


	/** @var \WC_Gateway_Authorize_Net_CIM[] gateways with unique API credentials */
	protected $gateways = array();

	/** @var WC_Authorize_Net_CIM Authorize.Net plugin instance */
	private $plugin;


	/**
	 * Constructs the class.
	 *
	 * @since 2.8.0
	 *
	 * @param WC_Authorize_Net_CIM $plugin Authorize.Net plugin instance
	 */
	public function __construct( WC_Authorize_Net_CIM $plugin ) {

		$this->plugin = $plugin;

		$this->add_hooks();
	}


	/**
	 * Adds the action and filter hooks.
	 *
	 * @since 2.8.0
	 */
	protected function add_hooks() {

		// handle webhook requests
		add_action( 'woocommerce_api_' . $this->get_endpoint(), array( $this, 'handle_request' ) );

		// add the setup admin notices
		add_action( 'admin_notices', array( $this, 'add_admin_notices' ) );

		// add the webhook reset debug tool
		add_action( 'woocommerce_debug_tools', array( $this, 'add_debug_tool' ) );

		// handle creating webhooks on user action
		add_action( 'admin_action_wc_' . $this->get_plugin()->get_id() . '_create_webhooks', array( $this, 'admin_create_webhooks' ) );
	}


	/** Processing Methods ****************************************************/


	/**
	 * Handles a webhook event request.
	 *
	 * All supported events are subscribed to the same URL and passed to their
	 * respective handling classes based on event type.
	 *
	 * @internal
	 *
	 * @since 2.8.0
	 */
	public function handle_request() {

		// bail if not enabled
		// note: we still want to hook into the API action above so Auth.Net
		// receives a 200 response and doesn't retry any webhooks
		if ( ! $this->is_enabled() ) {
			return;
		}

		try {

			if ( empty( $_SERVER['HTTP_X_ANET_SIGNATURE'] ) ) {
				throw new Framework\SV_WC_API_Exception( 'Signature missing' );
			}

			parse_str( $_SERVER['HTTP_X_ANET_SIGNATURE'], $signature );

			$body = file_get_contents( 'php://input' );

			if ( empty( $body ) ) {
				throw new Framework\SV_WC_API_Exception( 'The request body is empty.' );
			}

			$keys = $this->get_signature_keys();

			if ( empty( $keys ) ) {
				throw new Framework\SV_WC_API_Exception( 'No signature keys configured.' );
			}

			foreach ( $keys as $key ) {

				$hash = strtoupper( hash_hmac( 'sha512', $body, $key ) );

				if ( ! empty( $signature['sha512'] ) && hash_equals( $signature['sha512'], $hash ) ) {
					return $this->process_request( $body );
				}
			}

			// if no signature was matched above, bail
			throw new Framework\SV_WC_API_Exception( 'Signature invalid' );

		} catch ( Framework\SV_WC_Plugin_Exception $e ) {

			$this->log( 'Webhook Error: ' . $e->getMessage() );
		}
	}


	/**
	 * Processes the request data.
	 *
	 * @since 2.8.0
	 *
	 * @param string $body raw request body
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	protected function process_request( $body ) {

		$body = json_decode( $body );

		$this->log( "Webhook Request:\n" . print_r( $body, true ) );

		if ( empty( $body->eventType ) ) {
			throw new Framework\SV_WC_API_Exception( 'Event type is missing' );
		}

		// split the action from the rest of the event type
		$event_type = substr( $body->eventType, 0, strrpos( $body->eventType, '.' ) );
		$action     = substr( $body->eventType, strrpos( $body->eventType, '.' ) + 1 );

		$response_handler = $this->get_response_handler( $event_type );

		$response_handler->process( $action, $body->payload );
	}


	/**
	 * Gets the response handler based on event type.
	 *
	 * @since 2.8.0
	 *
	 * @param string $event_type event type
	 * @return \WC_Authorize_Net_CIM_Webhook
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	protected function get_response_handler( $event_type ) {

		$class = '';

		switch ( $event_type ) {

			case self::EVENT_TYPE_CUSTOMER_PROFILE:
				$class = 'WC_Authorize_Net_CIM_Customer_Profile_Webhook';
			break;

			case self::EVENT_TYPE_CUSTOMER_PAYMENT_PROFILE:
				$class = 'WC_Authorize_Net_CIM_Customer_Payment_Profile_Webhook';
			break;
		}

		if ( ! $class || ! class_exists( $class ) ) {
			throw new Framework\SV_WC_Plugin_Exception( 'Invalid event type' );
		}

		return new $class( $this->get_plugin() );
	}


	/**
	 * Gets the configured signature keys.
	 *
	 * Multiple can be returned if the gateways are configured differently.
	 *
	 * @since 2.8.0
	 *
	 * @return array
	 */
	protected function get_signature_keys() {

		$keys = array();

		foreach ( $this->get_unique_gateways() as $gateway ) {
			$keys[] = $gateway->get_api_signature_key();
		}

		return array_filter( array_unique( $keys ) );
	}


	/** API Methods ***********************************************************/


	/**
	 * Creates the supported webhooks.
	 *
	 * This will run for each gateway that has unique API credentials.
	 *
	 * @since 2.8.0
	 */
	public function create_webhooks() {

		$event_types = array(
			self::EVENT_TYPE_CUSTOMER_PROFILE . '.' . WC_Authorize_Net_CIM_Webhook::ACTION_DELETED,
			self::EVENT_TYPE_CUSTOMER_PAYMENT_PROFILE . '.' . WC_Authorize_Net_CIM_Webhook::ACTION_DELETED,
		);

		foreach ( $this->get_unique_gateways() as $gateway ) {

			$webhooks = $this->get_stored_webhooks( $gateway );

			try {

				$response = $gateway->get_api()->create_webhook( $this->get_endpoint_url(), $event_types );

				$webhooks[ $response->get_id() ] = $response->get_event_types();

				update_option( $this->get_webhook_option_name( $gateway ), $webhooks );

			} catch ( Framework\SV_WC_Plugin_Exception $e ) {

				$this->get_plugin()->log( 'Could not create webhook. ' . $e->getMessage(), $gateway->get_id() );

				return false;
			}
		}

		return true;
	}


	/**
	 * Removes any configured webhooks.
	 *
	 * This will always delete the local webhook ID storage, and attempt to
	 * delete them remotely from the merchant account.
	 *
	 * @since 2.8.0
	 */
	public function remove_webhooks() {

		foreach ( $this->get_unique_gateways() as $gateway ) {

			$webhooks = $this->get_stored_webhooks( $gateway );

			foreach ( array_keys( $webhooks ) as $webhook_id ) {

				try {

					$response = $gateway->get_api()->delete_webhook( $webhook_id );

				} catch ( Framework\SV_WC_Plugin_Exception $e ) {

					$this->get_plugin()->log( 'Could not delete webhook. ' . $e->getMessage(), $gateway->get_id() );
				}
			}

			// clear the local option regardless
			update_option( $this->get_webhook_option_name( $gateway ), array() );
		}
	}


	/**
	 * Resets the webhooks.
	 *
	 * This will clear any local webhook records and attempt to delete them
	 * remotely.
	 *
	 * @since 2.8.0
	 *
	 * @return bool
	 */
	public function reset_webhooks() {

		$this->remove_webhooks();

		return $this->create_webhooks();
	}


	/** Admin Methods *********************************************************/


	/**
	 * Adds the webhook-related admin notices.
	 *
	 * @internal
	 *
	 * @since 2.8.0
	 */
	public function add_admin_notices() {

		// add the System Status indicator for webhook status
		foreach ( $this->get_gateways() as $gateway ) {
			add_action( 'wc_payment_gateway_' . $gateway->get_id() . '_system_status_end', array( $this, 'add_system_status_indicator' ) );
		}

		// only display notices on WooCommerce settings pages
		if ( 'wc-settings' !== Framework\SV_WC_Helper::get_requested_value( 'page' ) ) {
			return;
		}

		// display any one-off messages
		$this->get_plugin()->get_message_handler()->show_messages();

		$docs_url = trailingslashit( wc_authorize_net_cim()->get_documentation_url() ) . '#webhook-setup';

		foreach ( $this->get_unique_gateways() as $gateway ) {

			if ( ! $gateway->is_enabled() ) {
				continue;
			}

			if ( ! $this->has_webhooks( $gateway ) ) {

				if ( ! $gateway->supports_tokenization() || ! $gateway->tokenization_enabled() ) { // TODO: remove this bail if other webhooks are added, like transaction status {CW 2017-11-30}
					continue;
				}

				$message = sprintf(
					__( '%1$s supports webhooks to help keep your customer\'s payment methods in sync.', 'woocommerce-gateway-authorize-net-cim' ),
					count( $this->get_unique_gateways() ) > 1 ? '<strong>' . $gateway->get_method_title() . '</strong>' : '<strong>' . $this->get_plugin()->get_plugin_name() . '</strong>'
				);

				// see if the API signature key is set in the settings first
				$environment = Framework\SV_WC_Helper::get_posted_value( 'woocommerce_' . $gateway->get_id() . '_environment' ) ?: $gateway->get_environment();
				$setting_key = 'production' === $environment ? 'woocommerce_' . $gateway->get_id() . '_api_signature_key' : 'woocommerce_' . $gateway->get_id() . '_test_api_signature_key';
				$signature_key = isset( $_POST[ $setting_key ] ) ? $_POST[ $setting_key ] : $gateway->get_api_signature_key();

				if ( $signature_key ) {

					$action     = 'wc_' . $this->get_plugin()->get_id() . '_create_webhooks';
					$enable_url = add_query_arg( 'action', $action, 'admin.php' );

					$message .= ' <a href="' . esc_url( wp_nonce_url( $enable_url, $action ) ) . '">' . __( 'Click here to enable webhooks &raquo;', 'woocommerce-gateway-authorize-net-cim' ) . '</a>';

				} else {

					$message .= ' <a href="' . esc_url( $docs_url ) .'" target="_blank">' . __( 'Click here to learn more about setting up webhooks &raquo;', 'woocommerce-gateway-authorize-net-cim' ) . '</a>';
				}

				$this->get_plugin()->get_admin_notice_handler()->add_admin_notice( $message, $this->get_plugin()->get_id_dasherized() . '-webhook-setup-notice' );
				break;

			} elseif ( ! $gateway->get_api_signature_key() ) {

				$message = sprintf(
					__( '%1$s webhooks are currently disabled because your %2$sSignature Key%3$s is missing. %4$sPlease review the documentation%5$s for setting up webhook support.', 'woocommerce-gateway-authorize-net-cim' ),
					'<strong>' . $gateway->get_method_title() . '</strong>',
					'<strong>', '</strong>',
					'<a href="' . esc_url( $docs_url ) . '" target="_blank">', '</a>'
				);

				$this->get_plugin()->get_admin_notice_handler()->add_admin_notice( $message, $gateway->get_id_dasherized() . '-webhook-config-notice', array(
					'notice_class' => 'notice-warning',
				) );
			}
		}
	}


	/**
	 * Adds the System Status indicator for webhook status.
	 *
	 * @internal
	 *
	 * @since 2.8.0
	 *
	 * @param \WC_Gateway_Authorize_Net_CIM $gateway gateway instance
	 */
	public function add_system_status_indicator( $gateway ) {

		?>

		<tr>
			<td data-export-label="Webhooks Enabled"><?php esc_html_e( 'Webhooks Enabled', 'woocommerce-gateway-authorize-net-cim' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'Displays whether or not webhooks are enabled for this gateway.', 'woocommerce-gateway-authorize-net-cim' ) ); ?></td>
			<td>
				<?php if ( $gateway->inherit_settings() ) : ?>
					<?php esc_html_e( 'Inherited', 'woocommerce-gateway-authorize-net-cim' ); ?>
				<?php elseif ( $gateway->get_api_signature_key() && $this->has_webhooks( $gateway ) ) : ?>
					<mark class="yes">&#10004;</mark>
				<?php else : ?>
					<mark class="error">
						<span class="dashicons dashicons-no-alt"></span>
						<?php echo ! $gateway->get_api_signature_key() ? esc_html__( 'No signature key configured', 'woocommerce-gateway-authorize-net-cim' ) : esc_html__( 'Not generated', 'woocommerce-gateway-authorize-net-cim' ); ?>
					</mark>
				<?php endif; ?>
			</td>
		</tr>

		<?php
	}


	/**
	 * Creates the supported webhooks.
	 *
	 * This will run for each gateway that has unique API credentials.
	 *
	 * @since 2.8.0
	 */
	public function admin_create_webhooks() {

		// nonce check
		check_admin_referer( 'wc_' . $this->get_plugin()->get_id() . '_create_webhooks' );

		// permissions check
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( __( 'You do not have permission to access this page.', 'woocommerce-gateway-authorize-net-cim' ) );
		}

		if ( $this->create_webhooks() ) {
			$this->get_plugin()->get_message_handler()->add_message( __( 'Success! Webhooks have been enabled.', 'woocommerce-gateway-authorize-net-cim' ) );
		} else {
			$this->get_plugin()->get_message_handler()->add_error( __( 'Error creating webhooks. Please check the debug logs.', 'woocommerce-gateway-authorize-net-cim' ) );
		}

		wp_safe_redirect( $this->get_plugin()->get_settings_url() );
		exit;
	}


	/**
	 * Adds a debug tool for manually resetting webhooks.
	 *
	 * @since 2.8.0
	 *
	 * @param array $tools existing debug tools
	 * @return array
	 */
	public function add_debug_tool( $tools ) {

		$signature_keys = $this->get_signature_keys();

		if ( empty( $signature_keys ) ) {
			return $tools;
		}

		$tools[ 'wc_' . $this->get_plugin()->get_id() . '_reset_webhooks'] = array(
			'name'     => __( 'Reset Authorize.Net Webhooks', 'woocommerce-gateway-authorize-net-cim' ),
			'button'   => __( 'Reset webhooks now', 'woocommerce-gateway-authorize-net-cim' ),
			'desc'     => __( 'This will reset your Authorize.Net webhooks - useful if your site URL has changed or you aren\'t receiving customer profile updates.', 'woocommerce-gateway-authorize-net-cim' ),
			'callback' => array( $this, 'reset_webhooks' )
		);

		return $tools;
	}


	/** Helper Methods ********************************************************/


	/**
	 * Determines if webhooks are enabled.
	 *
	 * @since 2.8.0
	 *
	 * @return bool
	 */
	protected function is_enabled() {

		/**
		 * Filters whether webhooks are enabled.
		 *
		 * TODO: determine if a setting is warranted {CW 2017-11-07}
		 *
		 * @since 2.8.0
		 *
		 * @param bool $enabled whether webhooks are enabled
		 */
		return apply_filters( 'wc_authorize_net_cim_enable_webhooks', true );
	}


	/**
	 * Determines whether webhooks have been generated remotely.
	 *
	 * @since 2.8.0
	 *
	 * @param \WC_Gateway_Authorize_Net_CIM $gateway gateway object
	 * @return bool
	 */
	protected function has_webhooks( $gateway ) {

		$registered_webhooks = $this->get_stored_webhooks( $gateway );

		return ! empty( $registered_webhooks );
	}


	/**
	 * Logs a message if at least one gateway has logging enabled.
	 *
	 * @since 2.8.0
	 *
	 * @param string $message message to log
	 */
	protected function log( $message ) {

		foreach ( $this->get_gateways() as $gateway ) {

			if ( $gateway->debug_log() ) {

				$this->get_plugin()->log( $message );
				break;
			}
		}
	}


	/** Getter Methods ********************************************************/


	/**
	 * Gets the stored webhook details.
	 *
	 * @since 2.8.0
	 *
	 * @param \WC_Gateway_Authorize_Net_CIM $gateway gateway object
	 * @return array
	 */
	protected function get_stored_webhooks( $gateway ) {

		return (array) get_option( $this->get_webhook_option_name( $gateway ), array() );
	}


	/**
	 * Gets the name of the option where webhook details are stored.
	 *
	 * @since 2.8.0
	 *
	 * @param \WC_Gateway_Authorize_Net_CIM $gateway gateway object
	 * @return string
	 */
	protected function get_webhook_option_name( $gateway ) {

		$option_name = 'wc_' . $gateway->get_id() . '_webhooks';

		return $gateway->is_test_environment() ? "{$option_name}_test" : $option_name;
	}


	/**
	 * Gets the full webhook endpoint URL for this site.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	protected function get_endpoint_url() {

		return home_url( '/' ) . 'wc-api/' . $this->get_endpoint();
	}


	/**
	 * Gets the webhook endpoint.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	protected function get_endpoint() {

		return $this->get_plugin()->get_id_dasherized() . '/webhooks';
	}


	/**
	 * Gets the gateways configured with unique API credentials.
	 *
	 * @since 3.0.6
	 *
	 * @return \WC_Gateway_Authorize_Net_CIM[]
	 */
	protected function get_unique_gateways() {

		$gateways = $this->get_gateways();

		foreach ( $gateways as $key => $gateway ) {

			// remove any gateways that inherit settings
			if ( $gateway->inherit_settings() ) {
				unset( $gateways[ $key ] );
			}
		}

		return $gateways;
	}


	/**
	 * Gets the gateways that support webhooks.
	 *
	 * @since 2.8.0
	 *
	 * @return \WC_Gateway_Authorize_Net_CIM[]
	 */
	protected function get_gateways() {

		if ( empty( $this->gateways ) ) {
			$this->gateways = $this->get_plugin()->get_gateways();
		}

		return $this->gateways;
	}


	/**
	 * Gets the Authorize.Net plugin instance.
	 *
	 * @since 2.8.0
	 *
	 * @return WC_Authorize_Net_CIM
	 */
	protected function get_plugin() {

		return $this->plugin;
	}


}
