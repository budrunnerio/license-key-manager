<?php
/**
 * Receipt Functions
 *
 * @since 5.0.0
 *
 * @package WooCommerce_Point_Of_Sale/Functions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get receipt.
 *
 * @since 5.0.0
 *
 * @param int|WC_POS_Receipt $receipt Receipt ID or object.
 *
 * @throws Exception If receipt cannot be read/found and $data parameter of WC_POS_Receipt class constructor is set.
 * @return WC_POS_Receipt|null
 */
function wc_pos_get_receipt( $receipt ) {
	$receipt_object = new WC_POS_Receipt( (int) $receipt );

	// If getting the default receipt and it does not exist, create a new one and return it.
	if ( wc_pos_is_default_receipt( $receipt ) && ! $receipt_object->get_id() ) {
		delete_option( 'wc_pos_default_receipt' );
		WC_POS_Install::create_default_posts();

		return wc_pos_get_receipt( (int) get_option( 'wc_pos_default_receipt' ) );
	}

	return 0 !== $receipt_object->get_id() ? $receipt_object : null;
}

/**
 * Check if a specific receipt is the default one.
 *
 * @since 5.0.0
 *
 * @param int $receipt_id Receipt ID.
 * @return bool
 */
function wc_pos_is_default_receipt( $receipt_id ) {
	return (int) get_option( 'wc_pos_default_receipt', 0 ) === $receipt_id;
}
