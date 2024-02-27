<?php
/**
 * Load Assets
 *
 * @package WooCommerce_Point_Of_Sale/Classes
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_POS_Assets', false ) ) {
	return new WC_POS_Assets();
}

/**
 * WC_POS_Assets.
 *
 * Handles assets loading on the front-end.
 */
class WC_POS_Assets {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'styles' ] );
	}

	/**
	 * Enqueue styles.
	 */
	public function styles() {
		// Register styles.
		wp_register_style( 'wc-pos-fonts', WC_POS()->plugin_url() . '/assets/dist/css/fonts.min.css', [], WC_POS_VERSION );
		wp_register_style( 'wc-pos-frontend', WC_POS()->plugin_url() . '/assets/dist/css/frontend.min.css', [], WC_POS_VERSION );

		// Enqueue styles.
		wp_enqueue_style( 'wc-pos-fonts' );
		wp_enqueue_style( 'wc-pos-frontend' );
	}
}

return new WC_POS_Assets();
