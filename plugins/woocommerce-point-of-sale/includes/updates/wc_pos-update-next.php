<?php
/**
 * Database Update Script for 6.1.0
 *
 * @package WooCommerce_Point_Of_Sale/Updates
 */
defined( 'ABSPATH' ) || exit;

global $wpdb;
$wpdb->hide_errors();

// Set default value for the new option show_original_price to true.
$receipts = $wpdb->get_results( "SELECT p.ID as id FROM {$wpdb->posts} p WHERE p.post_type = 'pos_receipt'" );
foreach ( $receipts as $receipt ) {
	add_post_meta( (int) $receipt->id, 'show_original_price', 'yes', true );
}
