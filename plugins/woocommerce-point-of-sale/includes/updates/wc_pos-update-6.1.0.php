<?php
/**
 * Database Update Script for 6.1.0
 *
 * @package WooCommerce_Point_Of_Sale/Updates
 */
defined( 'ABSPATH' ) || exit;

global $wpdb;
$wpdb->hide_errors();

// Rename receipt meta key no_copies to num_copies.
$wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_key = 'num_copies' WHERE meta_key = 'no_copies'" );

// Rename receipt meta key no_items to num_items.
$wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_key = 'num_items' WHERE meta_key = 'no_items'" );

// Rename order item meta key `Item Note` to `note`.
$wpdb->query(
	$wpdb->prepare(
		"UPDATE {$wpdb->prefix}woocommerce_order_itemmeta SET meta_key = 'note' WHERE meta_key = %s",
		__( 'Item Note', 'woocommerce-point-of-sale' )
	)
);

// Options removed.
delete_option( 'wc_pos_refresh_on_load' );

// Force refresh the local database.
update_option( 'wc_pos_force_refresh_db', 'yes' );

// Remove deprecated posts and meta related to temp orders.
$temp_orders = $wpdb->get_results( "SELECT p.ID FROM {$wpdb->posts} p WHERE p.post_type = 'pos_temp_order'" );
foreach ( $temp_orders as $temp_order ) {
	wp_delete_post( intval( $temp_order->ID ), true );
}

$wpdb->query(
	"DELETE pm FROM {$wpdb->postmeta} pm
	LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
	WHERE p.post_type = 'pos_register'
	AND pm.meta_key = 'temp_order'"
);
