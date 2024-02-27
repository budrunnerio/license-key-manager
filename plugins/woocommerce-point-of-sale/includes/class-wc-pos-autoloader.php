<?php
/**
 * Autoloader
 *
 * @package WooCommerce_Point_Of_Sale/Classes
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_POS_Autoloader', false ) ) {
	return new WC_POS_Autoloader();
}

/**
 * WC_POS_Autoloader.
 */
class WC_POS_Autoloader {

	/**
	 * Path to the includes directory.
	 *
	 * @var string
	 */
	private $include_path = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( function_exists( '__autoload' ) ) {
			spl_autoload_register( '__autoload' );
		}

		spl_autoload_register( [ $this, 'autoload' ] );

		$this->include_path = untrailingslashit( plugin_dir_path( WC_POS_PLUGIN_FILE ) ) . '/includes/';
	}

	/**
	 * Take a class name and turn it into a file name.
	 *
	 * @param  string $class Class Name.
	 * @return string File name.
	 */
	private function get_file_name_from_class( $class ) {
		return 'class-' . str_replace( '_', '-', $class ) . '.php';
	}

	/**
	 * Include a class file.
	 *
	 * @param string $path Class file path.
	 * @return bool Whether successful or not.
	 */
	private function load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			include_once $path;
			return true;
		}
		return false;
	}

	/**
	 * Auto-load WC_POS classes on demand to reduce memory consumption.
	 *
	 * @param string $class Class name.
	 */
	public function autoload( $class ) {
		$class = strtolower( $class );
		$file  = $this->get_file_name_from_class( $class );
		$path  = '';

		if ( 0 === strpos( $class, 'wc_pos_screen' ) ) {
			$path = $this->include_path . 'screen/';
		} elseif ( 0 === strpos( $class, 'wc_pos_table' ) ) {
			$path = $this->include_path . 'tables/';
		} elseif ( 0 === strpos( $class, 'wc_pos_meta_box' ) ) {
			$path = $this->include_path . 'admin/meta-boxes/';
		} elseif ( 0 === strpos( $class, 'wc_pos_admin' ) ) {
			$path = $this->include_path . 'admin/';
		}

		if ( empty( $path ) || ( ! $this->load_file( $path . $file ) && 0 === strpos( $class, 'wc_pos_' ) ) ) {
			$this->load_file( $this->include_path . $file );
		}
	}
}

return new WC_POS_Autoloader();
