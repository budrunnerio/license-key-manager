<?php
/**
 * Database Update Script for 5.3.7
 *
 * @package WooCommerce_Point_Of_Sale/Updates
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;
$wpdb->hide_errors();

// Set order keys if not set.
$orders = $wpdb->get_col(
	"SELECT ID FROM {$wpdb->posts} p
	LEFT JOIN {$wpdb->postmeta} pm
	ON pm.post_id = p.ID AND pm.meta_key = '_order_key'
	WHERE p.post_type = 'shop_order' AND (pm.meta_value = '' OR pm.meta_value IS NULL)"
);

foreach ( $orders as $order_id ) {
	update_post_meta( intval( $order_id ), '_order_key', wc_generate_order_key() );
}

// Set order key for downloadable product permissions if not set.
$wpdb->query(
	"UPDATE {$wpdb->prefix}woocommerce_downloadable_product_permissions dpp
	SET dpp.order_key = (SELECT meta_value FROM {$wpdb->postmeta} pm WHERE pm.meta_key = '_order_key' AND pm.post_id = dpp.order_id)
	WHERE dpp.order_key = ''"
);
