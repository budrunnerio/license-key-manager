<?php
/**
 * Database Update Script for 5.5.4
 *
 * @package WooCommerce_Point_Of_Sale/Updates
 */

defined( 'ABSPATH' ) || exit;

/*
 * Options renamed.
 */
$options_renamed = [
	'wc_pos_fulfilled_order_status' => 'wc_pos_fulfilled_order_default_status',
	'wc_pos_parked_order_status'    => 'wc_pos_parked_order_default_status',
];

foreach ( $options_renamed as $old => $new ) {
	$old_option = get_option( $old );

	if ( ! empty( $old_option ) ) {
		update_option( $new, $old_option );
	}

	delete_option( $old );
}

// Force refresh the local database.
update_option( 'wc_pos_force_refresh_db', 'yes' );
