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

namespace SkyVerge\WooCommerce\Authorize_Net\Handlers;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_12_0 as Framework;

/**
 * Hosted payment handler.
 *
 * @since 3.0.0
 *
 * @method \WC_Gateway_Authorize_Net_CIM get_gateway()
 */
class Hosted_Payment_Handler extends Framework\Payment_Gateway\Handlers\Abstract_Hosted_Payment_Handler {


	/** Authorize.Net Accept Hosted production form URL */
	const PRODUCTION_FORM_URL = 'https://accept.authorize.net/payment/payment';

	/** Authorize.Net Accept Hosted test form URL */
	const TEST_FORM_URL = 'https://test.authorize.net/payment/payment';


	/**
	 * Adds action & filter hooks.
	 *
	 * @since 3.0.0
	 */
	protected function add_hooks() {

		parent::add_hooks();

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}


	/**
	 * Enqueues the handler scripts.
	 *
	 * @since 3.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script(
			'wc' . $this->get_gateway()->get_id_dasherized() . '-accept-hosted',
			$this->get_gateway()->get_plugin()->get_plugin_url() . '/assets/js/frontend/wc-authorize-net-accept-hosted.min.js',
			[ 'jquery' ],
			$this->get_gateway()->get_plugin()->get_version(),
			true
		);
	}


	/**
	 * Renders the payment page.
	 *
	 * @since 3.0.0
	 *
	 * @param int $order_id order ID
	 */
	public function payment_page( $order_id ) {

		$order    = $this->get_gateway()->get_order( $order_id );
		$response = null;

		$order->payment->communicator_url = $this->get_iframe_communicator_url();

		try {

			$response = $this->get_gateway()->get_api()->get_hosted_form( $order, $this->get_gateway()->get_payment_type() );

			if ( ! $response->get_token() ) {
				throw new Framework\SV_WC_API_Exception( 'Payment form token missing' );
			}

			$this->render_payment_form( $response->get_token(), $order );

		} catch ( Framework\SV_WC_Plugin_Exception $exception ) {

			$this->get_gateway()->add_debug_message( $exception->getMessage(), 'error' );

			Framework\SV_WC_Helper::wc_add_notice( __( 'An error occurred, please try again or try an alternate form of payment.', 'woocommerce-gateway-authorize-net-cim' ), 'error' );

			wp_safe_redirect( $order->get_checkout_payment_url() );
			exit;
		}
	}


	/**
	 * Renders the payment form.
	 *
	 * 1. POST to the form URL with the provided token
	 * 2. Load an iframe with the resulting form html
	 *
	 * @since 3.0.0
	 *
	 * @param string $token payment form token
	 * @param \WC_Order $order order object
	 */
	protected function render_payment_form( $token, \WC_Order $order ) {

		$args = array(
			'id_dasherized'      => $this->get_gateway()->get_id_dasherized(),
			'form_url'           => $this->get_hosted_payment_url(),
			'response_url'       => $this->get_response_handler_url(),
			'response_nonce'     => wp_create_nonce( 'wc_' . $this->get_gateway()->get_id() . '_process_transaction_result' ),
			'order_id'           => $order->get_id(),
			'order_received_url' => $this->get_gateway()->get_return_url( $order ),
			'order_pay_url'      => $order->get_checkout_payment_url(),
		);

		wc_enqueue_js( sprintf( 'window.wc_authorize_net_accept_hosted_handler = new WC_Authorize_Net_Accept_Hosted_Handler( %s );', json_encode( $args ) ) );

		echo '<form id="wc-' . $this->get_gateway()->get_id_dasherized() . '-send-token" action="' . esc_url( $this->get_hosted_payment_url() ) . '" method="post" target="wc-' . $this->get_gateway()->get_id_dasherized() . '-iframe" >';
		echo '<input type="hidden" name="token" value="' . esc_attr( $token ) . '" />';
		echo '</form>';

		echo '<iframe id="wc-' . $this->get_gateway()->get_id_dasherized() . '-iframe" name="wc-' . $this->get_gateway()->get_id_dasherized() . '-iframe" width="100%" height="650px" frameborder="0" scrolling="no" hidden="true"></iframe>';
	}


	/**
	 * Handles the final payment request response.
	 *
	 * Note that wp_send_json_success() indicates the AJAX call was successful, but doesn't refer to the transaction's
	 * result.
	 *
	 * @since 3.0.0
	 *
	 * @param Framework\SV_WC_Payment_Gateway_API_Response|null $response
	 * @param string $url
	 */
	protected function do_transaction_request_response( Framework\SV_WC_Payment_Gateway_API_Response $response = null, $url = '' ) {

		wp_send_json_success( array(
			'redirect_url' => $url,
		) );
	}


	/**
	 * Gets the transaction response object.
	 *
	 * @since 3.0.0
	 *
	 * @param array $request_response_data raw response data
	 *
	 * @return \SkyVerge\WooCommerce\Authorize_Net\API\Hosted\eCheck_Payment_Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	protected function get_transaction_response( $request_response_data ) {

		if ( ! check_ajax_referer( 'wc_' . $this->get_gateway()->get_id() . '_process_transaction_result', 'nonce', false ) ) {
			throw new Framework\SV_WC_API_Exception( 'Invalid nonce' );
		}

		// only eChecks right now
		if ( ! $this->get_gateway()->is_echeck_gateway() ) {
			throw new Framework\SV_WC_API_Exception( 'Invalid payment type' );
		}

		return new \SkyVerge\WooCommerce\Authorize_Net\API\Hosted\eCheck_Payment_Response( $request_response_data );
	}


	/**
	 * Gets the hosted form URL.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	protected function get_hosted_payment_url() {

		return $this->get_gateway()->is_test_environment() ? self::TEST_FORM_URL : self::PRODUCTION_FORM_URL;
	}


	/**
	 * Gets the URL for the iframe communicator script.
	 *
	 * This communicates to the site the ideal iframe size as it changes.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	protected function get_iframe_communicator_url() {

		return $this->get_gateway()->get_plugin()->get_plugin_url() . '/assets/js/frontend/iframe-communicator.html';
	}


	/** Conditional methods *******************************************************************************************/


	/**
	 * Determines whether the payment response is IPN.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function is_ipn() {

		return true;
	}


}

