<?php
/**
 * Settings > Point of Sale > Tax.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/Admin/Settings
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Admin_Settings_Tax.
 */
class WC_POS_Admin_Settings_Tax {

	/**
	 * Returns section settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$class = 'wc-enhanced-select';

		if ( 'yes' !== get_option( 'woocommerce_calc_taxes', 'no' ) ) {
			update_option( 'wc_pos_tax_calculation', 'disabled' );
			$class = 'disabled_select';
		}

		$tax_calculation = [
			'name'     => __( 'Tax Calculation', 'woocommerce-point-of-sale' ),
			'id'       => 'wc_pos_tax_calculation',
			'css'      => '',
			'desc_tip' => __( 'Enables the calculation of tax using the WooCommerce configurations.', 'woocommerce-point-of-sale' ),
			'std'      => '',
			'type'     => 'select',
			'class'    => $class,
			'options'  => [
				'enabled'  => __( 'Enabled (using WooCommerce configurations)', 'woocommerce-point-of-sale' ),
				'disabled' => __( 'Disabled', 'woocommerce-point-of-sale' ),
			],
		];
		$tax_based_on    = [
			'name'     => __( 'Calculate Tax Based On', 'woocommerce-point-of-sale' ),
			'id'       => 'wc_pos_calculate_tax_based_on',
			'css'      => '',
			'std'      => '',
			'class'    => 'wc-enhanced-select',
			'desc_tip' => __( 'This option determines which address used to calculate tax.', 'woocommerce-point-of-sale' ),
			'type'     => 'select',
			'default'  => 'outlet',
			'options'  => [
				'default'  => __( 'Default WooCommerce', 'woocommerce-point-of-sale' ),
				'shipping' => __( 'Customer shipping address', 'woocommerce-point-of-sale' ),
				'billing'  => __( 'Customer billing address', 'woocommerce-point-of-sale' ),
				'base'     => __( 'Shop base address', 'woocommerce-point-of-sale' ),
				'outlet'   => __( 'Outlet address', 'woocommerce-point-of-sale' ),
			],
		];

		/**
		 * Tax settings.
		 *
		 * @since 6.0.0
		 */
		return apply_filters(
			'wc_pos_settings_tax_section',
			[

				[
					'title' => __( 'Tax Options', 'woocommerce-point-of-sale' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'tax_options',
				],
				$tax_calculation,
				$tax_based_on,
				[
					'name'     => __( 'Tax Number', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_tax_number',
					'desc_tip' => __( 'Enter the tax number which is applied to this particular register. This will be printed on receipts if tax number is enabled on receipt template.', 'woocommerce-point-of-sale' ),
					'type'     => 'text',
				],
				[
					'type' => 'sectionend',
					'id'   => 'tax_options',
				],

			]
		);
	}
}
