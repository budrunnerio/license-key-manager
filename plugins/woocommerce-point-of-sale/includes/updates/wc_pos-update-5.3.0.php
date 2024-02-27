<?php
/**
 * Database Update Script for 5.3.0
 *
 * @package WooCommerce_Point_Of_Sale/Updates
 */

// `wc_pos_grid_return` is renamed to `wc_pos_after_add_to_cart_behavior` and a third option is added.
// Option values renamed as follows:
// `stay`  => `category`
// `leave` => `home`
$grid_return = get_option( 'wc_pos_grid_return' );
$grid_return = 'leave' === $grid_return ? 'home' : 'category';
update_option( 'wc_pos_after_add_to_cart_behavior', $grid_return );
delete_option( 'wc_pos_grid_return' );
