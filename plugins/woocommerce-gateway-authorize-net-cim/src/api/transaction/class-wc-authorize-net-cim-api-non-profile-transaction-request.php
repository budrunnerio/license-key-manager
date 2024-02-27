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
 * Authorize.Net API Request Class
 *
 * Generates XML for Non-Profile (AIM) transaction requests. This is primarily
 * used for guest customers, or logged in customers who opt not to save their paymen
 * method during checkout
 *
 * @since 2.0.0
 */
class WC_Authorize_Net_CIM_API_Non_Profile_Transaction_Request extends WC_Authorize_Net_CIM_API_Transaction_Request  {


	/** auth/capture transaction type */
	const AUTH_CAPTURE = 'authCaptureTransaction';

	/** authorize only transaction type */
	const AUTH_ONLY = 'authOnlyTransaction';

	/** prior auth-only capture transaction type */
	const PRIOR_AUTH_CAPTURE = 'priorAuthCaptureTransaction';

	/** refund transaction type */
	const REFUND = 'refundTransaction';

	/** void transaction type */
	const VOID = 'voidTransaction';


	/**
	 * Construct request object, overrides parent to set the request type for
	 * every request in the class, as all non-profile transactions use the same
	 * root element
	 *
	 * @since 2.0.0
	 * @see WC_Authorize_Net_CIM_API_Request::__construct()
	 * @param string $api_login_id API login ID
	 * @param string $api_transaction_key API transaction key
	 */
	public function __construct( $api_login_id, $api_transaction_key ) {

		parent::__construct( $api_login_id, $api_transaction_key );

		$this->request_type = 'createTransactionRequest';
	}


	/**
	 * Create the transaction XML for non-profile auth-only/auth-capture transactions -- this
	 * handles both credit cards and eChecks
	 *
	 * @since 2.0.0
	 * @param string $type transaction type
	 */
	protected function create_transaction( $type ) {

		$transaction_type = ( $type === 'auth_only' ) ? self::AUTH_ONLY : self::AUTH_CAPTURE;

		$this->request_data = array(
			'refId'              => $this->order->get_id(),
			'transactionRequest' => array(
				'transactionType'     => $transaction_type,
				'amount'              => $this->order->payment_total,
				'currencyCode'        => $this->order->get_currency(),
				'payment'             => $this->get_payment(),
				'solution'            => array( 'id' => 'A1000065' ),
				'order'               => array(
					'invoiceNumber' => ltrim( $this->order->get_order_number(), _x( '#', 'hash before the order number', 'woocommerce-gateway-authorize-net-cim' ) ),
					'description'   => Framework\SV_WC_Helper::str_truncate( $this->order->description, 255 ),
				),
				'lineItems'           => array( 'lineItem' => $this->get_line_items() ),
				'tax'                 => $this->get_taxes(),
				'shipping'            => $this->get_shipping(),
				'poNumber'            => Framework\SV_WC_Helper::str_truncate( preg_replace( '/\W/', '', $this->order->payment->po_number ), 25 ),
				'customer'            => array(
					'id' => $this->order->get_user_id(),
					'email' => is_email( $this->order->get_billing_email() ) ? $this->order->get_billing_email() : null,
				),
				'billTo'              => $this->get_address( 'billing' ),
				'shipTo'              => $this->get_address( 'shipping' ),
				'customerIP'          => $this->order->get_customer_ip_address(),
				'transactionSettings' => $this->get_transaction_settings(),
			),
		);
	}


	/**
	 * Add transactions settings, primarily used for setting the duplicate window check when the CSC is required
	 *
	 * This is important because of this use case:
	 *
	 * 1) Customer enters payment info and accidentally enters an incorrect CVV
	 * 2) Auth.net properly declines the transaction
	 * 3) Customer notices the CVV was incorrect, re-enters the correct CVV and tries to submit order
	 * 4) Auth.net rejects this second transaction attempt as a "duplicate transaction"
	 *
	 * For some reason, Auth.net doesn't consider the CVV changing evidence of a non-duplicate transaction and recommends
	 * changing the `duplicateWindow` transaction option between transactions
	 * (https://support.authorize.net/authkb/index?page=content&id=A425&actp=search&viewlocale=en_US&searchid=1375994496602)
	 * to avoid this error. However, simply changing the `duplicateWindow` between transactions *does not* prevent
	 * the "duplicate transaction" error.
	 *
	 * The `duplicateWindow` must actually be set to 0 to suppress this error. However, this has the side affect of
	 * potentially allowing duplicate transactions through.
	 *
	 * Since we now require Accept.js, the CVV will never be passed, so we should always set this to 0 for non-profile requests.
	 *
	 * @since 2.0.0
	 */
	protected function get_transaction_settings() {

		return array(
			'setting' => array(
				array(
					'settingName'  => 'duplicateWindow',
					'settingValue' => 0,
				),
			),
		);
	}


	/**
	 * Capture funds for a previous credit card authorization
	 *
	 * @since 2.0.0
	 * @param WC_Order $order the order object
	 */
	public function create_credit_card_capture( WC_Order $order ) {

		$this->order = $order;

		$this->request_data = array(
			'transactionRequest' => array(
				'transactionType' => self::PRIOR_AUTH_CAPTURE,
				'amount'          => $order->capture->amount,
				'refTransId'      => $order->capture->trans_id,
			),
		);
	}


	/** Create a refund for the given $order
	 *
	 * @since 2.0.0
	 * @param WC_Order $order order object
	 */
	public function create_refund( WC_Order $order ) {

		$this->order = $order;

		$this->request_data = array(
			'refId'              => $this->order->get_id(),
			'transactionRequest' => array(
				'transactionType' => self::REFUND,
				'amount'          => $order->refund->amount,
				'payment'         => array(
					'creditCard' => array(
						'cardNumber'     => $order->refund->last_four,
						'expirationDate' => $order->refund->expiry_date,
					),
				),
				'refTransId'      => $order->refund->trans_id,
				'order'           => array(
					'invoiceNumber' => ltrim( $this->order->get_order_number(), _x( '#', 'hash before the order number', 'woocommerce-gateway-authorize-net-cim' ) ),
					'description'   => Framework\SV_WC_Helper::str_truncate( $this->order->refund->reason, 255 ),
				),
				'billTo' => $this->get_address( 'billing' ),
				'shipTo' => $this->get_address( 'shipping' ),
			),
		);
	}


	/** Create a void for the given $order
	 *
	 * @since 2.0.0
	 * @param WC_Order $order order object
	 */
	public function create_void( WC_Order $order ) {

		$this->order = $order;

		$this->request_data = array(
			'refId'              => $this->order->get_id(),
			'transactionRequest' => array(
				'transactionType' => self::VOID,
				'refTransId'      => $order->refund->trans_id,
				'order'           => array(
					'invoiceNumber' => ltrim( $this->order->get_order_number(), _x( '#', 'hash before the order number', 'woocommerce-gateway-authorize-net-cim' ) ),
					'description'   => Framework\SV_WC_Helper::str_truncate( $this->order->refund->reason, 255 ),
				),
			),
		);
	}


}
