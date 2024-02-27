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
 * Generates XML for CIM profile transaction requests, used when a logged-in (or new)
 * customer has opted to save their payment method
 *
 * @since 2.0.0
 */
class WC_Authorize_Net_CIM_API_Profile_Transaction_Request extends WC_Authorize_Net_CIM_API_Transaction_Request  {


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
	 * every request in the class, as all profile transactions use the same
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
	 * Create the transaction XML for profile auth-only/auth-capture transactions -- this
	 * handles both credit cards and eChecks
	 *
	 * @since 2.0.0
	 * @param string $type transaction type
	 */
	protected function create_transaction( $type ) {

		$transaction_type = ( $type === 'auth_only' ) ? self::AUTH_ONLY : self::AUTH_CAPTURE;

		$request_data = array(
			'refId'        			=> $this->order->get_id(),
			'transactionRequest'  	=> array(
				'transactionType'			=> $transaction_type,
				'amount'                    => $this->order->payment_total,
				'currencyCode'				=> $this->order->get_currency(),
				'profile'				=> array(
					'customerProfileId'		=> $this->order->customer_id,
					'paymentProfile'		=> array(
						'paymentProfileId'	=> $this->order->payment->token,
					),
					'shippingProfileId'		=> $this->order->payment->shipping_address_id,
				),
				'payment'	=> array(
					'creditCard' => array(
						'cardCode'	=> ! empty( $this->order->payment->csc ) ? $this->order->payment->csc : null,
					),
				),
				'order'                     => array(
					'invoiceNumber'       => ltrim( $this->order->get_order_number(), _x( '#', 'hash before the order number', 'woocommerce-gateway-authorize-net-cim' ) ),
					'description'         => Framework\SV_WC_Helper::str_truncate( $this->order->description, 255 ),
					'purchaseOrderNumber' => Framework\SV_WC_Helper::str_truncate( preg_replace( '/\W/', '', $this->order->payment->po_number ), 25 ),
				),
				'lineItems'                 => $this->get_line_items(),
				'tax'                       => $this->get_taxes(),
				'shipping'                  => $this->get_shipping(),
				'poNumber'            		=> Framework\SV_WC_Helper::str_truncate( preg_replace( '/\W/', '', $this->order->payment->po_number ), 25 ),
				'customerIP'                => $this->order->get_customer_ip_address(),
				'transactionSettings'		=> $this->get_transaction_settings(),
				'processingOptions'         => $this->get_processing_options()
			),
		);

		$this->request_data = $request_data;

	}



	/**
	 * Determines if the card is being used for the first time.
	 *
	 * @since 3.7.2
	 *
	 * @return bool
	 */
	protected function is_first_transaction() {

		$prefix = 'wc-' . wc_authorize_net_cim()->get_gateway( WC_Authorize_Net_CIM::CREDIT_CARD_GATEWAY_ID )->get_id_dasherized();

		return Framework\SV_WC_Helper::get_posted_value( $prefix . '-tokenize-payment-method' ) && ! Framework\SV_WC_Helper::get_posted_value( $prefix . '-payment-token' );
	}


	/**
	 * Adds order line items to the request.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	protected function get_line_items() {

		$line_items = array();

		// order line items
		foreach ( Framework\SV_WC_Helper::get_order_line_items( $this->order ) as $item ) {

			if ( $item->item_total >= 0 ) {

				$line_items['lineItem'][] = array(
					'itemId'      => Framework\SV_WC_Helper::str_truncate( $item->id, 31 ),
					'name'        => Framework\SV_WC_Helper::str_to_sane_utf8( Framework\SV_WC_Helper::str_truncate( htmlentities( $item->name, ENT_QUOTES, 'UTF-8', false ), 31 ) ),
					'description' => Framework\SV_WC_Helper::str_to_sane_utf8( Framework\SV_WC_Helper::str_truncate( htmlentities( $item->description, ENT_QUOTES, 'UTF-8', false ), 255 ) ),
					'quantity'    => $item->quantity,
					'unitPrice'   => Framework\SV_WC_Helper::number_format( $item->item_total ),
					'taxable'     => 'taxable' === $item->item->get_tax_status(),
				);
			}
		}

		// order fees
		foreach ( $this->order->get_fees() as $fee_id => $fee ) {

			/** @var \WC_Order_Item_Fee $fee object */
			if ( $this->order->get_item_total( $fee ) >= 0 ) {

				$line_items['lineItem'][] = array(
					'itemId'      => Framework\SV_WC_Helper::str_truncate( $fee_id, 31 ),
					'name'        => ! empty( $fee['name'] ) ? Framework\SV_WC_Helper::str_truncate( htmlentities( $fee['name'], ENT_QUOTES, 'UTF-8', false ), 31 ) : __( 'Fee', 'woocommerce-gateway-authorize-net-cim' ),
					'description' => __( 'Order Fee', 'woocommerce-gateway-authorize-net-cim' ),
					'quantity'    => 1,
					'unitPrice'   => Framework\SV_WC_Helper::number_format( $this->order->get_item_total( $fee ) ),
					'taxable'     => 'taxable' === $fee->get_tax_status(),
				);
			}
		}

		// maximum of 30 line items per order
		if ( isset( $line_items['lineItem'] ) && count( $line_items['lineItem'] ) > 30 ) {
			$line_items['lineItem'] = array_slice( $line_items['lineItem'], 0, 30 );
		}

		return $line_items;
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
			'refId'       			=> $this->order->get_id(),
			'transactionRequest' => array(
				'transactionType'		=> self::PRIOR_AUTH_CAPTURE,
				'amount'  				=> $order->capture->amount,
				'refTransId' 			=> $order->capture->trans_id,
				'order'					=> array(
					'invoiceNumber'     => ltrim( $this->order->get_order_number(), _x( '#', 'hash before the order number', 'woocommerce-gateway-authorize-net-cim' ) ),
					'description'       => Framework\SV_WC_Helper::str_truncate( $this->order->description, 255 ),
				)
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
			'refId'       			=> $this->order->get_id(),
			'transactionRequest' => array(
				'transactionType'		 => self::REFUND,
				'amount'  				 => $order->refund->amount,
				'refTransId' 			 => $order->refund->trans_id,
				'payment'        => array(
					'creditCard' => array(
						'cardNumber'     => $order->refund->last_four,
						'expirationDate' => $order->refund->expiry_date,
					),
				),
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
			'refId'       			=> $this->order->get_id(),
			'transactionRequest' 	=> array(
				'transactionType'		=> self::VOID,
				'refTransId' 			=> $order->refund->trans_id,
			),
		);
	}

	/**
	 * Sets processing options according to transaction type for COF compliance.
	 *
	 * @link https://developer.authorize.net/api/reference/features/card-on-file.html
	 *
	 * @since 3.7.2
	 *
	 * @return array $params processing options params
	 */
	protected function get_processing_options() {

		$params = [];

		if ( $this->is_current_user_customer() ) {

			if ( $this->cart_has_recurring_order() ) {
				// customer-initiated transaction: establish relationship for recurring payment (existing or new card on file)
				$params['isFirstRecurringPayment'] = 'true';
			} elseif ( $this->is_first_transaction() ) {
				// customer-initiated transaction: establish relationship for non-recurring payment (new card on file)
				$params['isFirstSubsequentAuth'] = 'true';
			}

			if ( ! $this->is_first_transaction() ) {
				// customer-initiated transaction: using card on file
				$params['isStoredCredentials'] = 'true';
			}

		} elseif ( ! $this->is_subscription_renewal_order() ) {

			// merchant-initiated transaction: unscheduled card on file
			$params['isSubsequentAuth'] = 'true';
		}

		return $params;
	}


	/**
	 * Adds transactions settings, primarily used for recurring payments.
	 *
	 * @since 3.7.2
	 */
	protected function get_transaction_settings() {

		return array(
			'setting' => array(
				array(
					'settingName'  => 'recurringBilling',
					// true for merchant-initiated transaction: recurring payment card on file
					'settingValue' => $this->is_subscription_renewal_order() ? 'true' : 'false',
				),
			),
		);
	}

	/**
	 * Determines whether the logged-in user is a customer.
	 *
	 * @since 3.7.2
	 *
	 * @return bool
	 */
	protected function is_current_user_customer() {
		return get_current_user_id() == $this->order->get_user_id();
	}

	/**
	 * Determines whether the order is for a subscription renewal.
	 *
	 * @since 3.7.2
	 *
	 * @return bool $is_renewal_order
	 */
	protected function is_subscription_renewal_order() {

		$is_renewal_order = false;

		if ( function_exists( 'wcs_order_contains_renewal' ) ) {
			$is_renewal_order = wcs_order_contains_renewal( $this->order->get_id() );
		}

		return $is_renewal_order;
	}

	/**
	 * Determines whether the cart contains a subscription renewal.
	 *
	 * @since 3.7.2
	 *
	 * @return bool $cart_has_renewal_order
	 */
	protected function cart_has_recurring_order() {

		$is_recurring_cart = false;

		if (
			class_exists( 'WC_Subscriptions_Cart' )
			&& WC_Subscriptions_Cart::cart_contains_subscription()
			&& isset ( WC()->cart->recurring_carts )
		) {
			$is_recurring_cart = true;
		}

		return $is_recurring_cart;
	}

}
