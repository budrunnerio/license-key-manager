<?php
/**
 * Database Update Script for 5.3.5
 *
 * @package WooCommerce_Point_Of_Sale/Updates
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;
$wpdb->hide_errors();

// Some users could have multiple values for wc_pos_enable_tender_orders, find them.
$users = array_map(
	function( $user ) {
		return intval( $user['user_id'] ); },
	$wpdb->get_results(
		"SELECT (user_id)
		FROM {$wpdb->usermeta}
		WHERE meta_key = 'wc_pos_enable_tender_orders'
		GROUP BY user_id, meta_key
		HAVING COUNT(*) > 1
		",
		ARRAY_A
	)
);
// Now delete all meta fields and set a new one.
foreach ( $users as $user_id ) {
	$fields = get_user_meta( $user_id, 'wc_pos_enable_tender_orders', false );

	// Decide the value of the new field.
	$new_value = 'yes';
	if ( 1 === count( array_unique( $fields ) ) && 'no' === $fields[0] ) {
		$new_value = 'no';
	}

	delete_user_meta( $user_id, 'wc_pos_enable_tender_orders' );
	update_user_meta( $user_id, 'wc_pos_enable_tender_orders', $new_value );
}

// Some users could have multiple values for wc_pos_enable_discount, find them.
$users = array_map(
	function( $user ) {
		return intval( $user['user_id'] ); },
	$wpdb->get_results(
		"SELECT (user_id)
		FROM {$wpdb->usermeta}
		WHERE meta_key = 'wc_pos_enable_discount'
		GROUP BY user_id, meta_key
		HAVING COUNT(*) > 1
		",
		ARRAY_A
	)
);
// Now delete all meta fields and set a new one.
foreach ( $users as $user_id ) {
	$fields = get_user_meta( $user_id, 'wc_pos_enable_discount', false );

	// Decide the value of the new field.
	$new_value = 'yes';
	if ( 1 === count( array_unique( $fields ) ) && 'no' === $fields[0] ) {
		$new_value = 'no';
	}

	delete_user_meta( $user_id, 'wc_pos_enable_discount' );
	update_user_meta( $user_id, 'wc_pos_enable_discount', $new_value );
}

// Update user meta.
$wpdb->query( "UPDATE {$wpdb->usermeta} SET meta_key = 'wc_pos_assigned_outlets' WHERE meta_key = 'outlet'" );
$wpdb->query( "UPDATE {$wpdb->usermeta} SET meta_key = 'wc_pos_user_card_number' WHERE meta_key = 'user_card_number'" );

// Update post meta.
$wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_key = 'wc_pos_register_id' WHERE meta_key = 'wc_pos_id_register'" );

// Update options.
$wpdb->query( "UPDATE {$wpdb->options} SET option_name = 'wc_pos_tax_calculation' WHERE option_name = 'woocommerce_pos_tax_calculation'" );
$wpdb->query( "UPDATE {$wpdb->options} SET option_name = 'wc_pos_calculate_tax_based_on' WHERE option_name = 'woocommerce_pos_calculate_tax_based_on'" );
