<?php
/**
 * Plugin Name: Point of Sale for WooCommerce
 * Plugin URI: https://www.woocommerce.com/products/woocommerce-point-of-sale/
 * Description: An advanced toolkit for placing in-store orders through a WooCommerce based Point of Sale (POS) interface. Requires <a href="http://wordpress.org/plugins/woocommerce/">WooCommerce</a>.
 * Author: Actuality Extensions
 * Author URI: https://woocommerce.com/vendor/actuality-extensions/
 * Version: 6.1.1
 * Requires at least: 6.0
 * Tested up to: 6.3.1
 * Requires PHP: 7.4
 * Text Domain: woocommerce-point-of-sale
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2013-2023 Actuality Extensions (info@actualityextensions.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package WooCommerce_Point_Of_Sale
 *
 * Woo: 5120689:8f6df80c02320298a50e6985162cc35f
 * WC requires at least: 6.0
 * WC tested up to: 8.0.3
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WC_POS_PLUGIN_FILE' ) ) {
	define( 'WC_POS_PLUGIN_FILE', __FILE__ );
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	include_once __DIR__ . '/vendor/autoload.php';
}

if ( ! class_exists( 'WC_POS', false ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-wc-pos.php';
}

function WC_POS() {
	return WC_POS::instance();
}

WC_POS();
