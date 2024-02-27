<?php
/**
 * Database Update Script for 5.2.5
 *
 * @package WooCommerce_Point_Of_Sale/Updates
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;
$wpdb->hide_errors();

// Update user roles.
$users = get_users();
foreach ( $users as $user ) {
	$u = new WP_User( $user );
	if ( in_array( 'pos_manager', (array) $u->roles, true ) ) {
		$u->remove_role( 'pos_manager' );
		$u->add_role( 'outlet_manager' );
	}

	if ( in_array( 'cashier', (array) $u->roles, true ) ) {
		$u->remove_role( 'cashier' );
		$u->add_role( 'register_clerk' );
	}
}

// Role pos_manager changed to outlet_manager.
// Role cashier changed to register_clerk.
remove_role( 'pos_manager' );
remove_role( 'cashier' );
WC_POS_Install::remove_roles();
WC_POS_Install::create_roles();
