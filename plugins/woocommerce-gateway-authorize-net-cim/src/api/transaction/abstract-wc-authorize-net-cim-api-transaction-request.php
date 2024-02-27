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
 * Generates XML for transaction requests
 *
 * @since 2.0.0
 */
abstract class WC_Authorize_Net_CIM_API_Transaction_Request extends WC_Authorize_Net_CIM_API_Request  {


	/**
	 * Creates a credit card charge request for the given order.
	 *
	 * @since 2.0.0
	 * @param WC_Order $order the order object
	 */
	public function create_credit_card_charge( WC_Order $order ) {

		$this->order = $order;

		$this->create_transaction( 'auth_capture' );
	}


	/**
	 * Creates a credit card auth request for the given order.
	 *
	 * @since 2.0.0
	 * @param WC_Order $order the order object
	 */
	public function create_credit_card_auth( WC_Order $order ) {

		$this->order = $order;

		$this->create_transaction( 'auth_only' );
	}


	/**
	 * Creates a customer check debit request for the given order.
	 *
	 * @since 2.0.0
	 * @param WC_Order $order the order object
	 */
	public function create_echeck_debit( WC_Order $order ) {

		$this->order = $order;

		$this->create_transaction( 'auth_capture' );
	}


	/**
	 * Creates a payment transaction.
	 *
	 * The profile & non-profile child classes must implement this method.
	 *
	 * @since 2.0.0
	 * @param string $type the transaction type, either `auth_only` or `auth_capture`
	 */
	abstract protected function create_transaction( $type );


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

				$line_items[] = array(
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

				$line_items[] = array(
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
		if ( count( $line_items ) > 30 ) {
			$line_items = array_slice( $line_items, 0, 30 );
		}

		return $line_items;
	}


	/**
	 * Adds tax information to the request.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	protected function get_taxes() {

		if ( $this->order->get_total_tax() > 0 ) {

			$taxes = array();

			foreach ( $this->order->get_tax_totals() as $tax_code => $tax ) {

				$taxes[] = sprintf( '%s (%s) - %s', $tax->label, $tax_code, $tax->amount );
			}

			return array(
				'amount'      => Framework\SV_WC_Helper::number_format( $this->order->get_total_tax() ),
				'name'        => __( 'Order Taxes', 'woocommerce-gateway-authorize-net-cim' ),
				'description' => Framework\SV_WC_Helper::str_truncate( implode( ', ', $taxes ), 255 ),
			);

		} else {

			return array();
		}
	}


	/**
	 * Adds shipping information to the request.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	protected function get_shipping() {

		if ( $this->order->get_total_shipping() > 0 ) {

			return array(
				'amount'      => Framework\SV_WC_Helper::number_format( $this->order->get_total_shipping() ),
				'name'        => __( 'Order Shipping', 'woocommerce-gateway-authorize-net-cim' ),
				'description' => Framework\SV_WC_Helper::str_truncate( $this->order->get_shipping_method(), 255 ),
			);

		} else {

			return array();
		}
	}


}
