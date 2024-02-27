<?php
/**
 * Database Update Script for 5.2.2
 *
 * @package WooCommerce_Point_Of_Sale/Updates
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;
$wpdb->hide_errors();

$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_api_keys WHERE description LIKE 'POS API key for user ID%'" );
