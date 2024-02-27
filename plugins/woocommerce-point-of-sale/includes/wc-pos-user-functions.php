<?php
/**
 * User Functions
 *
 * @package WooCommerce_Point_Of_Sale/Functions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns the order statuses with the wc- prefix stripped off.
 *
 * @since 6.0.4
 *
 * @param WP_User $user User object.
 * @return array
 */
function wc_pos_normalize_user_data( $user ) {
	$data = [
		'avatar_url'   => '',
		'capabilities' => [],
		'email'        => '',
		'first_name'   => '',
		'id'           => 0,
		'last_name'    => '',
		'meta'         => [],
		'name'         => '',
		'roles'        => [],
		'username'     => '',
	];

	if ( ! $user || ! is_a( $user, 'WP_User' ) ) {
		return $data;
	}

	$data['id']           = $user->ID;
	$data['avatar_url']   = get_avatar_url( $user->ID, [ 'size' => 100 ] );
	$data['capabilities'] = array_keys( (array) $user->allcaps );
	$data['email']        = $user->user_email;
	$data['first_name']   = $user->user_firstname;
	$data['last_name']    = $user->user_lastname;
	$data['name']         = $user->display_name;
	$data['username']     = $user->user_login;
	$data['roles']        = $user->roles;

	$meta = get_user_meta( $user->ID );
	$meta = $meta ? $meta : [];

	$meta = array_filter(
		$meta,
		function( $key ) {
			return 0 === strpos( $key, 'wc_pos_' );
		},
		ARRAY_FILTER_USE_KEY
	);

	array_walk(
		$meta,
		function( &$value, $key ) {
			$value = maybe_unserialize( $value[0] );

			switch ( $key ) {
				case 'wc_pos_assigned_outlets':
					$value = array_map( 'intval', $value );
					break;
			}
		}
	);

	$data['meta'] = $meta;

	return $data;
}
