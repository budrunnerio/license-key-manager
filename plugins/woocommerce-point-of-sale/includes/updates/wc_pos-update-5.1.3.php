<?php
/**
 * Database Update Script for 5.1.3
 *
 * @package WooCommerce_Point_Of_Sale/Updates
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;
$wpdb->hide_errors();

// Update user meta
$wpdb->query( "UPDATE {$wpdb->usermeta} SET meta_key = 'wc_pos_enable_discount' WHERE meta_key = 'discount'" );
$wpdb->query( "UPDATE {$wpdb->usermeta} SET meta_key = 'wc_pos_enable_tender_orders' WHERE meta_key = 'disable_pos_payment'" );
$wpdb->query( "UPDATE {$wpdb->usermeta} SET meta_value = 'yes' WHERE meta_key = 'wc_pos_enable_discount' AND meta_value = 'enable'" );
$wpdb->query( "UPDATE {$wpdb->usermeta} SET meta_value = 'no' WHERE meta_key = 'wc_pos_enable_discount' AND meta_value = 'disable'" );
