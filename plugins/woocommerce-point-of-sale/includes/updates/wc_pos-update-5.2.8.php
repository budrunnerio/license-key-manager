<?php
/**
 * Database Update Script for 5.2.8
 *
 * @package WooCommerce_Point_Of_Sale/Updates
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;
$wpdb->hide_errors();

/*
 * Payment gateway ID changed from pos_stripe to pos_stripe_terminal.
 */
$wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_value = 'pos_stripe_terminal' WHERE meta_key = '_payment_method' AND meta_value = 'pos_stripe'" );

$woocommerce_gateway_order = get_option( 'woocommerce_gateway_order', [] );
if ( isset( $woocommerce_gateway_order['pos_stripe'] ) ) {
	$woocommerce_gateway_order['pos_stripe_terminal'] = $woocommerce_gateway_order['pos_stripe'];
	unset( $woocommerce_gateway_order['pos_stripe'] );
	update_option( 'woocommerce_gateway_order', $woocommerce_gateway_order );
}

/*
 * Set orders _payment_method_title meta if not set.
 */
$results = $wpdb->get_results(
	"SELECT p.ID AS id, pmm.meta_value AS payment_method from {$wpdb->posts} p
	LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_payment_method_title'
	LEFT JOIN {$wpdb->postmeta} pmm ON pmm.post_id = p.ID AND pmm.meta_key = '_payment_method'
	WHERE p.post_type = 'shop_order' AND (pm.meta_value IS NULL OR pm.meta_value = '')"
);

if ( ! empty( $orders ) ) {
	$gateways = WC()->payment_gateways()->payment_gateways();

	foreach ( $results as $result ) {
		$payment_method_title = isset( $gateways[ $result->payment_method ] ) ? $gateways[ $result->payment_method ]->get_title() : false;
		if ( $payment_method_title ) {
			update_post_meta( $result->id, '_payment_method_title', $payment_method_title );
		}
	}
}
