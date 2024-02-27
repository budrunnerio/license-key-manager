<?php
/**
 * Database Update Script for 5.3.6
 *
 * @package WooCommerce_Point_Of_Sale/Updates
 */

defined( 'ABSPATH' ) || exit;

// Force refresh the local database (IndexedDB).
update_option( 'wc_pos_force_refresh_db', 'yes' );
