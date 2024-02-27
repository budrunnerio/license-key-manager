<?php
/**
 * Database Update Script for 5.2.4
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
	'woocommerce_pos_end_of_sale_report_settings' => 'woocommerce_pos_end_of_day_report_settings',
	'wc_pos_end_of_sale_order_statuses'           => 'wc_pos_end_of_day_order_statuses',
	'wc_pos_display_end_of_sale_report'           => 'wc_pos_display_end_of_day_report',
	'woocommerce_pos_chip_and_pin_settings'       => 'woocommerce_pos_chip_and_pin_1_settings',
];

foreach ( $options_renamed as $old => $new ) {
	$old_option = get_option( $old );

	if ( ! empty( $old_option ) ) {
		update_option( $new, $old_option );
	}

	delete_option( $old );
}

/*
 * Payment gateway ID changed from pos_chip_and_pin to pos_chip_and_pin_1.
 */
$wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_value = 'pos_chip_and_pin_1' WHERE meta_key = '_payment_method' AND meta_value = 'pos_chip_and_pin'" );

$woocommerce_gateway_order = get_option( 'woocommerce_gateway_order', [] );
if ( isset( $woocommerce_gateway_order['pos_chip_and_pin'] ) ) {
	$woocommerce_gateway_order['pos_chip_and_pin_1'] = $woocommerce_gateway_order['pos_chip_and_pin'];
	unset( $woocommerce_gateway_order['pos_chip_and_pin'] );
	update_option( 'woocommerce_gateway_order', $woocommerce_gateway_order );
}
