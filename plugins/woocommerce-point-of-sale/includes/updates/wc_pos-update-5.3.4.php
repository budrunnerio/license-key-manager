<?php
/**
 * Database Update Script for 5.3.4
 *
 * @package WooCommerce_Point_Of_Sale/Updates
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;
$wpdb->hide_errors();

// Update products meta
$wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_key = 'unit_of_measurement' WHERE meta_key = 'weight_based_decimal_quantity'" );
$wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_key = 'uom_unit' WHERE meta_key = 'decimal_quantity_unit'" );
$wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_key = 'uom_starting_value' WHERE meta_key = 'decimal_quantity_value'" );

// Force refresh the local database.
update_option( 'wc_pos_force_refresh_db', 'yes' );
