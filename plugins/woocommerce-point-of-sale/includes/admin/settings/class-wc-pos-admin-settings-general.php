<?php
/**
 * Settings > Point of Sale > General.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/Admin/Settings
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Admin_Settings_General.
 */
class WC_POS_Admin_Settings_General {

	/**
	 * Returns section settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		/**
		 * The general settings section.
		 *
		 * @since 6.0.0
		 */
		return apply_filters(
			'wc_pos_settings_general_section',
			[
				/*
				 * General options.
				 */
				[
					'title' => __( 'General Options', 'woocommerce-point-of-sale' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'general_options',
				],
				[
					'title'    => __( 'Logo', 'woocommerce-point-of-sale' ),
					'desc'     => __( 'Register logo image.', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Upload an image to replace the default WooCommerce logo.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_theme_logo',
					'type'     => 'media_upload',
				],
				[
					'name'              => __( 'Primary Color', 'woocommerce-point-of-sale' ),
					'desc_tip'          => __( 'The primary color of the theme.', 'woocommerce-point-of-sale' ),
					'id'                => 'wc_pos_theme_primary_color',
					'class'             => 'color-pick',
					'custom_attributes' => [ 'data-default-color' => '#7f54b3' ],
					'type'              => 'text',
					'default'           => '#7f54b3',
				],
				[
					'name'     => __( 'Transitions', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_disable_transitions_effects',
					'type'     => 'checkbox',
					'desc'     => __( 'Disable transitions and effects', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Check this box to disable transitions and effects when using the register.', 'woocommerce-point-of-sale' ),
					'default'  => 'no',
					'autoload' => true,
				],
				[
					'title'    => __( 'Dashboard Access', 'woocommerce-point-of-sale' ),
					'desc'     => __( 'Allow access to registers from front-end dashbaord', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Allows cashiers to access their assigned registers from their My Account page.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_enable_frontend_access',
					'default'  => 'no',
					'type'     => 'checkbox',
				],
				[
					'title'    => __( 'Auto Logout', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Choose whether to automatically log out of the register after inactivity. Note: this will not close the register session.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_auto_logout',
					'default'  => '0',
					'class'    => 'wc-enhanced-select',
					'type'     => 'select',
					'options'  => [
						0   => __( 'Disable', 'woocommerce-point-of-sale' ),
						1   => __( '1 min', 'woocommerce-point-of-sale' ),
						5   => __( '5 min', 'woocommerce-point-of-sale' ),
						15  => __( '15 min', 'woocommerce-point-of-sale' ),
						30  => __( '30 mins', 'woocommerce-point-of-sale' ),
						60  => __( '1 hour', 'woocommerce-point-of-sale' ),
						120 => __( '2 hours', 'woocommerce-point-of-sale' ),
					],
				],
				[
					'title'    => __( 'Chip & PIN', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Select the number of Chip & PIN Gateways to show in WooCommerce > Payments.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_number_chip_and_pin_gateways',
					'default'  => 'no',
					'type'     => 'select',
					/**
					 * Number of the Chip And PIN gateways.
					 *
					 * @since 5.0.0
					 */
					'options'  => apply_filters(
						'wc_pos_number_chip_and_pin_gateways',
						[
							1  => '1',
							2  => '2',
							3  => '3',
							4  => '4',
							5  => '5',
							6  => '6',
							7  => '7',
							8  => '8',
							9  => '9',
							10 => '10',
							11 => '11',
							12 => '12',
							13 => '13',
							14 => '14',
							15 => '15',
							16 => '16',
							17 => '17',
							18 => '18',
							19 => '19',
							20 => '20',
						]
					),
				],
				[
					'name'     => __( 'Dining', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_enable_dining',
					'type'     => 'checkbox',
					'desc'     => __( 'Enable dining option', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Check this box to enable dining when using the register. (Note: this will disable shipping)', 'woocommerce-point-of-sale' ),
					'default'  => 'no',
					'autoload' => true,
				],
				[
					'title'    => __( 'Units of Measurement', 'woocommerce-point-of-sale' ),
					'desc'     => __( 'Enable decimal stock quantities and set a unit of measurement.', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Allows you to sell your stock in decimal quantities and set the a unit of measurement for the stock value. Useful for those who want to sell weight or linear based products.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_decimal_quantities',
					'default'  => 'no',
					'type'     => 'checkbox',
				],
				[
					'name'     => __( 'Outlet Stock', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_manage_outlet_stock',
					'type'     => 'checkbox',
					'desc'     => __( 'Manage product stock per outlet', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Check this box to manage the product stock quantity per outlet.', 'woocommerce-point-of-sale' ),
					'default'  => 'no',
					'autoload' => true,
				],
				[
					'type' => 'sectionend',
					'id'   => 'general_options',
				],
			]
		);
	}
}
