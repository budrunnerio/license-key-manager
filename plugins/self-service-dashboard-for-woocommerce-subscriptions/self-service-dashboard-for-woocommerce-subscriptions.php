<?php
/**
 * Plugin Name: Self-service Dashboard for WooCommerce Subscriptions
 * Plugin URI:  https://woocommerce.com/products/self-service-dashboard-for-woocommerce-subscriptions/
 * Description: A simple self-service interface on top of WooCommerce Subscriptions that allows your customers to manage their subscriptions by themselves.
 * Version:     3.0.1
 * Author:      eCommerce Tools
 * Author URI:  https://ecommercetools.io/
 *
 * Text Domain: self-service-dashboard-for-woocommerce-subscriptions
 * Domain Path: /languages
 *
 * Requires PHP: 7.2
 *
 * Requires at least: 5.7.0
 * Tested up to: 6.4.2
 *
 * Woo: 7691728:03cfc27108a332ad1eb1455d1894bb7a
 * WC requires at least: 5.9.0
 * WC tested up to: 8.3.1
 *
 * Copyright: © 2021 eCommerce Tools
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Self-service Dashboard for WooCommerce Subscriptions
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SFORCE_PLUGIN_FILE' ) ) {
	define( 'SFORCE_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'SFORCE_PLUGIN_URL' ) ) {
	define( 'SFORCE_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
}
if ( ! defined( 'SFORCE_PLUGIN_PATH' ) ) {
	define( 'SFORCE_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
}

if ( ! defined( 'SSD_IS_PLUGIN' ) ) {
	define( 'SSD_IS_PLUGIN', true );
	define( 'SSD_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
	define( 'SSD_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
}

/**
 * Load text domain
 */
add_action(
	'init',
	function() {
		load_plugin_textdomain( 'self-service-dashboard-for-woocommerce-subscriptions', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
);

require SSD_PLUGIN_PATH . '/functions/class-load-packages.php';
require SSD_PLUGIN_PATH . '/core/class-wpr-admin.php';

if ( ! \SSD\Load_Packages::init() ) {
	return;
}
\SSD\Load_Packages::init();

/**
 * Register data
 */
register_activation_hook(
	__FILE__,
	function() {
		update_option( 'ssd_activation_date', time() );
	}
);

/**
 * Unregister data
 */
register_deactivation_hook(
	__FILE__,
	function () {
		delete_option( 'ssd_activation_date' );
	}
);
