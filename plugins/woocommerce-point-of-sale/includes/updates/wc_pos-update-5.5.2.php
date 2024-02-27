<?php
/**
 * Database Update Script for 5.5.2
 *
 * @package WooCommerce_Point_Of_Sale/Updates
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;
$wpdb->hide_errors();

// Clean up non-used temp orders.
$results = $wpdb->get_results(
	"SELECT p.ID FROM {$wpdb->posts} p
	WHERE p.post_type = 'pos_temp_order'
	AND p.ID NOT IN(SELECT pm.meta_value FROM {$wpdb->postmeta} pm WHERE pm.meta_key = 'temp_order')"
);
foreach ( $results as $result ) {
	wp_delete_post( intval( $result->ID ), true );
}

// Force refresh the local database (IndexedDB).
update_option( 'wc_pos_force_refresh_db', 'yes' );
