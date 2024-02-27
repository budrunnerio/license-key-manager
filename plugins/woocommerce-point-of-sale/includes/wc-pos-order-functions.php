<?php
/**
 * Order Functions
 *
 * @since 6.0.0
 *
 * @package WooCommerce_Point_Of_Sale/Functions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Extracts and returns the outlet ID associated with an order.
 *
 * @since 6.0.0
 *
 * @param WC_Order|int $order Order instance or ID.
 * @return int
 */
function wc_pos_get_order_outlet_id( $order ) {
	if ( ! is_a( $order, 'WC_Order' ) ) {
		$order = wc_get_order( $order );
	}

	if ( ! $order ) {
		return 0;
	}

	$outlet_id = (int) $order->get_meta( '_wc_pos_outlet_id', true );

	// Backward compatibility if outlet ID is not saved.
	if ( ! $outlet_id ) {
		$register_id = (int) $order->get_meta( '_wc_pos_register_id', true );
		$register    = wc_pos_get_register( $register_id );

		if ( $register && is_a( $register, 'WC_POS_Register' ) ) {
			$outlet = wc_pos_get_outlet( $register->get_outlet() );

			if ( $outlet && is_a( $outlet, 'WC_POS_Outlet' ) ) {
				$outlet_id = $outlet->get_id();
			}
		}
	}

	return $outlet_id ? $outlet_id : 0;
}
