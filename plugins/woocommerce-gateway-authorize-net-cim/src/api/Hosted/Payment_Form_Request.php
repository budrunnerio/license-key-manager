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

namespace SkyVerge\WooCommerce\Authorize_Net\API\Hosted;

use SkyVerge\WooCommerce\PluginFramework\v5_12_0 as Framework;

defined( 'ABSPATH' ) or exit;


/**
 * The Authorize.Net API payment form request.
 *
 * Generates XML required by API specs to perform a direct API request.
 *
 * @link https://developer.authorize.net/api/reference/#payment-transactions-get-an-accept-payment-page
 *
 * @since 3.0.0
 */
class Payment_Form_Request extends \WC_Authorize_Net_CIM_API_Request {


	/** auth/capture transaction type */
	const AUTH_CAPTURE = 'authCaptureTransaction';

	/** authorize only transaction type */
	const AUTH_ONLY = 'authOnlyTransaction';


	/** @var string payment type for this form request, either credit-card or echeck */
	protected $payment_type;


	/**
	 * Sets the data for requesting a transaction form.
	 *
	 * @since 3.0.0
	 *
	 * @param \WC_Order $order order object
	 * @param string $payment_type payment type
	 * @param bool $charge whether to charge the transaction or auth-only
	 */
	public function set_form_data( \WC_Order $order, $payment_type, $charge = true ) {

		$this->order        = $order;
		$this->payment_type = $payment_type;

		$this->request_data = [
			'refId'              => $this->get_order()->get_id(),
			'transactionRequest' => [
				'transactionType'     => $charge ? self::AUTH_CAPTURE : self::AUTH_ONLY,
				'amount'              => $this->get_order()->payment_total,
				'currencyCode'        => $this->get_order()->get_currency(),
				'solution'            => [
					'id' => 'A1000065',
				],
				'order' => [
					'invoiceNumber' => ltrim( $this->get_order()->get_order_number(), _x( '#', 'hash before the order number', 'woocommerce-gateway-authorize-net-cim' ) ),
					'description'   => Framework\SV_WC_Helper::str_truncate( $this->get_order()->description, 255 ),
				],
				'lineItems' => $this->get_line_items(),
				'tax'       => $this->get_taxes(),
				'shipping'  => $this->get_shipping(),
				'customer'  => $this->get_customer(),
				'billTo'    => $this->get_address( 'billing' ),
				'shipTo'    => $this->get_address( 'shipping' ),
			],
			'hostedPaymentSettings' => [ 'setting' => $this->get_form_settings() ],
		];

		// remove any empty fields
		foreach ( $this->request_data['transactionRequest'] as $key => $value ) {

			if ( empty( $value ) ) {
				unset( $this->request_data['transactionRequest'][ $key ] );
			}
		}
	}


	/** Request Helper Methods ******************************************************/


	/**
	 * Gets the line items for a payment form request.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	private function get_line_items() {

		$line_items = [];

		// order line items
		foreach ( Framework\SV_WC_Helper::get_order_line_items( $this->get_order() ) as $item ) {

			if ( $item->item_total >= 0 ) {

				$line_items[] = [
					'itemId'      => $item->id,
					'name'        => Framework\SV_WC_Helper::str_to_sane_utf8( Framework\SV_WC_Helper::str_truncate( $item->name, 31 ) ),
					'description' => Framework\SV_WC_Helper::str_to_sane_utf8( Framework\SV_WC_Helper::str_truncate( $item->description, 255 ) ),
					'quantity'    => $item->quantity,
					'unitPrice'   => Framework\SV_WC_Helper::number_format( $item->item_total ),
					'taxable'     => 'taxable' === $item->item->get_tax_status(),
				];
			}
		}

		// order fees
		foreach ( $this->get_order()->get_fees() as $fee_id => $fee ) {

			/** @var \WC_Order_Item_Fee $fee object */
			if ( $this->get_order()->get_item_total( $fee ) >= 0 ) {

				$line_items[] = [
					'itemId'      => $fee_id,
					'name'        => ! empty( $fee['name'] ) ? Framework\SV_WC_Helper::str_to_sane_utf8( Framework\SV_WC_Helper::str_truncate( $fee['name'], 31 ) ) : __( 'Fee', 'woocommerce-gateway-authorize-net-cim' ),
					'description' => __( 'Order Fee', 'woocommerce-gateway-authorize-net-cim' ),
					'quantity'    => 1,
					'unitPrice'   => Framework\SV_WC_Helper::number_format( $this->get_order()->get_item_total( $fee ) ),
					'taxable'     => 'taxable' === $fee->get_tax_status(),
				];
			}
		}

		// authorize.net only allows 30 line items per order
		if ( count( $line_items ) > 30 ) {
			$line_items = array_slice( $line_items, 0, 30 );
		}

		return [ 'lineItem' => $line_items ];
	}


	/**
	 * Gets tax information for a payment form request.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	private function get_taxes() {

		$taxes = [];

		if ( $this->get_order()->get_total_tax() > 0 ) {

			$tax_totals = [];

			foreach ( $this->get_order()->get_tax_totals() as $tax_code => $tax ) {
				$tax_totals[] = sprintf( '%s (%s) - %s', $tax->label, $tax_code, $tax->amount );
			}

			$taxes = [
				'amount'      => Framework\SV_WC_Helper::number_format( $this->get_order()->get_total_tax() ),
				'name'        => __( 'Order Taxes', 'woocommerce-gateway-authorize-net-cim' ),
				'description' => Framework\SV_WC_Helper::str_truncate( implode( ', ', $tax_totals ), 255 ),
			];
		}

		return $taxes;
	}


	/**
	 * Gets shipping information for a payment form request.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	private function get_shipping() {

		$shipping = [];

		if ( $this->get_order()->get_shipping_total() > 0 ) {

			$shipping = [
				'amount'      => Framework\SV_WC_Helper::number_format( $this->get_order()->get_shipping_total() ),
				'name'        => __( 'Order Shipping', 'woocommerce-gateway-authorize-net-cim' ),
				'description' => Framework\SV_WC_Helper::str_truncate( $this->get_order()->get_shipping_method(), 255 ),
			];
		}

		return $shipping;
	}


	/**
	 * Get the customer data for a payment form request.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	private function get_customer() {

		$customer = [
			'id' => $this->get_order()->get_user_id(),
		];

		$email = $this->get_order()->get_billing_email( 'edit' );

		if ( is_email( $email ) ) {
			$customer['email'] = $email;
		}

		return $customer;
	}


	/**
	 * Gets the payment form settings.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	private function get_form_settings() {

		/**
		 * Filters the payment form submit button text.
		 *
		 * @since 3.0.0
		 *
		 * @param string $text button text, like "Pay"
		 * @param \WC_Order $order order object
		 */
		$submit_text = apply_filters( 'wc_authorize_net_sim_form_submit_button_text', __( 'Pay', 'woocommerce-gateway-authorize-net-cim' ), $this->get_order() );

		/**
		 * Filters the payment form cancel button text.
		 *
		 * @since 3.0.0
		 *
		 * @param string $text button text, like "Cancel"
		 * @param \WC_Order $order order object
		 */
		$cancel_text = apply_filters( 'wc_authorize_net_sim_form_cancel_button_text', __( 'Cancel', 'woocommerce-gateway-authorize-net-cim' ), $this->get_order() );

		// any non-URL strings need to be URL-encoded so json_encode() doesn't mess with the values
		$settings = [
			'hostedPaymentReturnOptions' => [
				'showReceipt'   => false,
				'cancelUrl'     => home_url( '/' ),
				'cancelUrlText' => urlencode( $cancel_text ),
			],
			'hostedPaymentButtonOptions' => [
				'text' => urlencode( $submit_text ),
			],
			'hostedPaymentPaymentOptions' => [
				'cardCodeRequired' => isset( $this->get_order()->payment->csc_required ) ? $this->get_order()->payment->csc_required : false,
				'showCreditCard'   => $this->payment_type === Framework\SV_WC_Payment_Gateway::PAYMENT_TYPE_CREDIT_CARD,
				'showBankAccount'  => $this->payment_type === Framework\SV_WC_Payment_Gateway::PAYMENT_TYPE_ECHECK,
			],
			'hostedPaymentBillingAddressOptions' => [
				'show' => false,
			],
			'hostedPaymentOrderOptions' => [
				// Authorize.Net doesn't like `+` characters in the merchant name, so replace them with spaces (these are typically added with spaces by `urlencode()`)
				'merchantName' => str_replace( '+', ' ', urlencode( wp_specialchars_decode( Framework\SV_WC_Helper::get_site_name(), ENT_QUOTES ) ) ),
			],
			'hostedPaymentIFrameCommunicatorUrl' => [
				'url' => $this->get_order()->payment->communicator_url,
			],
		];

		$data = [];

		foreach ( $settings as $name => $value ) {

			$data[] = [
				'settingName'  => $name,
				'settingValue' => json_encode( $value ),
			];
		}

		return $data;
	}


	/**
	 * Get the root element for the XML document.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	protected function get_root_element() {

		return 'getHostedPaymentPageRequest';
	}


}
