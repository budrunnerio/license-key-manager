<?php
/**
 * Database Update Script for 6.0.2
 *
 * @package WooCommerce_Point_Of_Sale/Updates
 */
defined( 'ABSPATH' ) || exit;

global $wpdb;
$wpdb->hide_errors();

$wpdb->query(
	"UPDATE {$wpdb->postmeta} pm
INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID AND p.post_type = 'pos_receipt'
SET pm.meta_value = REPLACE(pm.meta_value, '#receipt-', '.') WHERE pm.meta_key = 'custom_css'
"
);

// Update CSS selectors in receipt templates.
// Set the default value of show_order_status for the existing receipt templates.
$receipts = $wpdb->get_results(
	"SELECT p.ID as id, pm_css.meta_value as custom_css FROM {$wpdb->posts} p
INNER JOIN {$wpdb->postmeta} pm_css ON pm_css.post_id = p.ID AND pm_css.meta_key = 'custom_css'
WHERE p.post_type = 'pos_receipt'"
);

foreach ( $receipts as $receipt ) {
	$custom_css = str_replace( '#receipt-', '.', $receipt->custom_css );

	update_post_meta( (int) $receipt->id, 'custom_css', $custom_css );
	update_post_meta( (int) $receipt->id, 'show_order_status', 'yes' );
}
