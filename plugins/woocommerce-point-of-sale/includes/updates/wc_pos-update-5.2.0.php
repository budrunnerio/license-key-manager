<?php
/**
 * Database Update Script for 5.2.0
 *
 * @package WooCommerce_Point_Of_Sale/Updates
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;
$wpdb->hide_errors();

/*
 * Options removed.
 */
$options_removed = [
	'wc_pos_day_end_report',
	'wc_pos_day_end_emails',
	'wc_pos_report_title',
];

foreach ( $options_removed as $option ) {
	delete_option( $option );
}

/*
 * Options renamed.
 */
$options_renamed = [
	// 'old_option_name'                => 'new_option_name'.
	'wc_pos_display_reports'            => 'wc_pos_display_end_of_sale_report',
	'wc_pos_report_order_status'        => 'wc_pos_end_of_sale_order_statuses',
	'woocommerce_chip_and_pin_settings' => 'woocommerce_pos_chip_and_pin_settings',
];

foreach ( $options_renamed as $old => $new ) {
	$old_option = get_option( $old );

	if ( ! empty( $old_option ) ) {
		update_option( $new, $old_option );
	}

	delete_option( $old );
}

/*
 * Payment gateway ID changed from chip_and_pin to pos_chip_and_pin.
 */
$wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_value = 'pos_chip_and_pin' WHERE meta_key = '_payment_method' AND meta_value = 'chip_and_pin'" );

$woocommerce_gateway_order = get_option( 'woocommerce_gateway_order', [] );
if ( isset( $woocommerce_gateway_order['pos_chip_pin'] ) ) {
	$woocommerce_gateway_order['pos_chip_and_pin'] = $woocommerce_gateway_order['pos_chip_pin'];
	unset( $woocommerce_gateway_order['pos_chip_pin'] );
	update_option( 'woocommerce_gateway_order', $woocommerce_gateway_order );
}
