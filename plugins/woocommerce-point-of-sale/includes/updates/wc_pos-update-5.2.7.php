<?php
/**
 * Database Update Script for 5.2.7
 *
 * @package WooCommerce_Point_Of_Sale/Updates
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;
$wpdb->hide_errors();

/*
 * Options renamed.
 */
$options_renamed = [
	'woocommerce_pos_chip_and_pin_1_settings' => 'woocommerce_pos_chip_and_pin_settings',
];

foreach ( $options_renamed as $old => $new ) {
	$old_option = get_option( $old );

	if ( ! empty( $old_option ) ) {
		update_option( $new, $old_option );
	}

	delete_option( $old );
}

/*
 * Payment gateway ID changed from pos_chip_and_pin_1 to pos_chip_and_pin.
 */
$wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_value = 'pos_chip_and_pin' WHERE meta_key = '_payment_method' AND meta_value = 'pos_chip_and_pin_1'" );

$woocommerce_gateway_order = get_option( 'woocommerce_gateway_order', [] );
if ( isset( $woocommerce_gateway_order['pos_chip_and_pin_1'] ) ) {
	$woocommerce_gateway_order['pos_chip_and_pin'] = $woocommerce_gateway_order['pos_chip_and_pin_1'];
	unset( $woocommerce_gateway_order['pos_chip_and_pin_1'] );
	update_option( 'woocommerce_gateway_order', $woocommerce_gateway_order );
}

