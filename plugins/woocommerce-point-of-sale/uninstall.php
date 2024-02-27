<?php
/**
 * Uninstall
 *
 * Uninstalling Point of Sale for WooCommerce deletes user roles, registers,
 * outlets, receipts, grids, tables and options.
 *
 * @package WooCommerce_Point_Of_Sale
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// Delete the POS custom product.
wp_delete_post( (int) get_option( 'wc_pos_custom_product_id' ), true );
delete_option( 'wc_pos_custom_product_id' );

/*
 * Only remove ALL plugin data if WC_POS_REMOVE_ALL_DATA constant is set to true in user's
 * wp-config.php. This is to prevent data loss when deleting the plugin from the backend
 * and to ensure only the site owner can perform this action.
 */
if ( defined( 'WC_POS_REMOVE_ALL_DATA' ) && true === WC_POS_REMOVE_ALL_DATA ) {
	// Drop custom tables.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wc_pos_grid_tiles" );

	// Delete options.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'wc\_pos\_%';" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'woocommerce\_pos\_%';" );

	// Delete usermeta.
	$wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE 'wc\_pos\_%';" );

	// Delete posts + data.
	$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type IN ( 'pos_register', 'pos_outlet', 'pos_receipt' );" );
	$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" );

	// Remove user roles.
	require_once 'includes/class-wc-pos-install.php';
	WC_POS_Install::remove_roles();

	// Clear any cached data that has been removed.
	wp_cache_flush();
}
