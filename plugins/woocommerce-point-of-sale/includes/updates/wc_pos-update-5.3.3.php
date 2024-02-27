<?php
/**
 * Database Update Script for 5.3.3
 *
 * @package WooCommerce_Point_Of_Sale/Updates
 */

global $wpdb;
$wpdb->hide_errors();

// Add new field to existing registers.
$registers = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'pos_register'" );
foreach ( $registers as $register_id ) {
	update_post_meta( absint( $register_id ), 'grid_layout', 'rectangular' );
}
