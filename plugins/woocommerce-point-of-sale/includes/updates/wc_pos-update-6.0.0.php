<?php
/**
 * Database Update Script for 6.0.0
 *
 * @package WooCommerce_Point_Of_Sale/Updates
 */
defined( 'ABSPATH' ) || exit;

global $wpdb;
$wpdb->hide_errors();

// Flush rewrite rules.
flush_rewrite_rules();

// Force refresh the local database (IndexedDB).
update_option( 'wc_pos_force_refresh_db', 'yes' );

// Update order meta.
$wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_key = '_wc_pos_notification_email_address' WHERE meta_key = 'pos_payment_email_receipt'" );

// Replace all _created_via = 'POS' values with lowercase 'pos'.
$wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_value = 'pos' WHERE meta_key = '_created_via' AND meta_value = 'POS'" );

// Deprecate order meta `wc_pos_order_type` in favor of `_created_via`.
$wpdb->query(
	"UPDATE {$wpdb->postmeta} pm1
INNER JOIN {$wpdb->postmeta} pm2 ON pm2.post_id = pm1.post_id
SET pm1.meta_value = 'pos'
WHERE pm1.meta_key = '_created_via'
AND pm2.meta_key = 'wc_pos_order_type' AND pm2.meta_value = 'POS'"
);
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = 'wc_pos_order_type'" );
