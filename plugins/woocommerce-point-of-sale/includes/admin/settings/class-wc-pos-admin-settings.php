<?php
/**
 * Plugin settings.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/Admin
 */

defined( 'ABSPATH' ) || exit;

// Load WC_Settings_Page.
require_once WC()->plugin_path() . '/includes/admin/settings/class-wc-settings-page.php';

if ( class_exists( 'WC_POS_Admin_Settings', false ) ) {
	return new WC_POS_Admin_Settings();
}

/**
 * WC_POS_Admin_Settings.
 */
class WC_POS_Admin_Settings extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'point-of-sale';
		$this->label = __( 'Point of Sale', 'woocommerce-point-of-sale' );

		parent::__construct();

		$this->include_sections();
	}

	public static function include_sections() {
		include_once __DIR__ . '/class-wc-pos-admin-settings-general.php';
		include_once __DIR__ . '/class-wc-pos-admin-settings-register.php';
		include_once __DIR__ . '/class-wc-pos-admin-settings-products.php';
		include_once __DIR__ . '/class-wc-pos-admin-settings-scanning.php';
		include_once __DIR__ . '/class-wc-pos-admin-settings-orders.php';
		include_once __DIR__ . '/class-wc-pos-admin-settings-customers.php';
		include_once __DIR__ . '/class-wc-pos-admin-settings-end-of-sale.php';
		include_once __DIR__ . '/class-wc-pos-admin-settings-tax.php';
		include_once __DIR__ . '/class-wc-pos-admin-settings-reports.php';
		include_once __DIR__ . '/class-wc-pos-admin-settings-advanced.php';
	}

	public static function render() {
		woocommerce_admin_fields( self::get_settings() );
	}

	/**
	 * Get own sections.
	 *
	 * @return array
	 */
	protected function get_own_sections() {
		$sections = [
			''            => __( 'General', 'woocommerce-point-of-sale' ),
			'register'    => __( 'Register', 'woocommerce-point-of-sale' ),
			'products'    => __( 'Products', 'woocommerce-point-of-sale' ),
			'scanning'    => __( 'Scanning', 'woocommerce-point-of-sale' ),
			'orders'      => __( 'Orders', 'woocommerce-point-of-sale' ),
			'customers'   => __( 'Customers', 'woocommerce-point-of-sale' ),
			'end_of_sale' => __( 'End of Sale', 'woocommerce-point-of-sale' ),
			'tax'         => __( 'Tax', 'woocommerce-point-of-sale' ),
			'reports'     => __( 'Reports', 'woocommerce-point-of-sale' ),
			'advanced'    => __( 'Advanced', 'woocommerce-point-of-sale' ),
		];

		if ( 'yes' !== get_option( 'woocommerce_calc_taxes' ) ) {
			unset( $sections['tax'] );
		}

		return $sections;
	}

	public function get_settings_for_default_section() {
		return WC_POS_Admin_Settings_General::get_settings();
	}

	public function get_settings_for_register_section() {
		add_action( 'woocommerce_admin_field_cash_denominations', [ 'WC_POS_Admin_Settings_Register', 'output_cash_denomination_options' ] );
		add_action( 'woocommerce_update_options_point-of-sale_register', [ 'WC_POS_Admin_Settings_Register', 'save_cash_denomination_options' ] );

		return WC_POS_Admin_Settings_Register::get_settings();
	}

	public function get_settings_for_products_section() {
		return WC_POS_Admin_Settings_Products::get_settings();
	}

	public function get_settings_for_scanning_section() {
		add_filter( 'wc_pos_scanning_fields', [ 'WC_POS_Admin_Settings_Scanning', 'filter_scanning_fields' ] );

		return WC_POS_Admin_Settings_Scanning::get_settings();
	}

	public function get_settings_for_orders_section() {
		return WC_POS_Admin_Settings_Orders::get_settings();
	}

	public function get_settings_for_customers_section() {
		return WC_POS_Admin_Settings_Customers::get_settings();
	}

	public function get_settings_for_end_of_sale_section() {
		add_filter( 'wc_pos_fulfilled_order_statuses', [ 'WC_POS_Admin_Settings_End_Of_Sale', 'fulfilled_order_statuses' ] );
		add_filter( 'wc_pos_parked_order_statuses', [ 'WC_POS_Admin_Settings_End_Of_Sale', 'parked_order_statuses' ] );

		return WC_POS_Admin_Settings_End_Of_Sale::get_settings();
	}

	public function get_settings_for_tax_section() {
		return WC_POS_Admin_Settings_Tax::get_settings();
	}

	public function get_settings_for_reports_section() {
		return WC_POS_Admin_Settings_Reports::get_settings();
	}

	public function get_settings_for_advanced_section() {
		add_action( 'woocommerce_admin_field_database_options', [ 'WC_POS_Admin_Settings_Advanced', 'output_database_options' ] );

		return WC_POS_Admin_Settings_advanced::get_settings();
	}
}

return new WC_POS_Admin_Settings();
