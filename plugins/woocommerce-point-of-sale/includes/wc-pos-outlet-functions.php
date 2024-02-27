<?php
/**
 * Outlet Functions
 *
 * @since 5.0.0
 *
 * @package WooCommerce_Point_Of_Sale/Functions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get outlet.
 *
 * @since 5.0.0
 *
 * @param int|string|WC_POS_Outlet $outlet Outlet ID, slug or object.
 *
 * @throws Exception If outlet cannot be read/found and $data parameter of WC_POS_Outlet class constructor is set.
 * @return WC_POS_Outlet|null
 */
function wc_pos_get_outlet( $outlet ) {
	$outlet_object = new WC_POS_Outlet( $outlet );

	// If getting the default outlet and it does not exist, create a new one and return it.
	if ( wc_pos_is_default_outlet( $outlet ) && ! $outlet_object->get_id() ) {
		delete_option( 'wc_pos_default_outlet' );
		WC_POS_Install::create_default_posts();

		return wc_pos_get_outlet( (int) get_option( 'wc_pos_default_outlet' ) );
	}

	return 0 !== $outlet_object->get_id() ? $outlet_object : null;
}

/**
 * Returns all the available outlets.
 *
 * @since 6.0.0
 *
 * @param bool $exclude_default Whether to exclude the default outlet or not.
 * @return WC_POS_Outlet[]
 */
function wc_pos_get_outlets( $exclude_default = false ) {
	$query_args = [
		'numberposts' => -1,
		'post_type'   => 'pos_outlet',
		'orderby'     => 'post_name',
		'order'       => 'asc',
	];

	if ( $exclude_default ) {
		$query_args['exclude'] = (int) get_option( 'wc_pos_default_outlet', 0 );
	}

	$get_posts = get_posts( $query_args );

	return array_filter(
		array_map(
			function( $p ) {
				$outlet = wc_pos_get_outlet( $p->ID );

				if ( $outlet && is_a( $outlet, 'WC_POS_Outlet' ) ) {
					return $outlet;
				}

				return false;
			},
			$get_posts
		)
	);
}

/**
 * Check if a specific outlet is the default one.
 *
 * @since 5.0.0
 *
 * @param int $outlet_id Receipt ID.
 * @return bool
 */
function wc_pos_is_default_outlet( $outlet_id ) {
	return (int) get_option( 'wc_pos_default_outlet', 0 ) === $outlet_id;
}
