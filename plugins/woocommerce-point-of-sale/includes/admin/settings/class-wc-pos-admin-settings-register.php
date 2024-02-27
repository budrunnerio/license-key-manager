<?php
/**
 * Settings > Point of Sale > Register.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/Admin/Settings
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Admin_Settings_Register.
 */
class WC_POS_Admin_Settings_Register {

	/**
	 * Returns section settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$order_statuses = wc_pos_get_order_statuses_no_prefix();

		$discount_reasons          = [];
		$default_discount_reasons  = [
			__( 'Wastage', 'woocommerce-point-of-sale' ),
			__( 'Damaged', 'woocommerce-point-of-sale' ),
			__( 'Manager Approved', 'woocommerce-point-of-sale' ),
			__( 'General Discount', 'woocommerce-point-of-sale' ),
			__( 'Student Discount', 'woocommerce-point-of-sale' ),
			__( 'Member Discount', 'woocommerce-point-of-sale' ),
		];
		$selected_discount_reasons = get_option( 'wc_pos_discount_reasons', [] );
		$all_discount_reasons      = array_unique( array_merge( $default_discount_reasons, $selected_discount_reasons ) );

		foreach ( $all_discount_reasons as $reason ) {
			$discount_reasons[ $reason ] = $reason;
		}

		/**
		 * The register settings section.
		 *
		 * @since 6.0.0
		 */
		return apply_filters(
			'wc_pos_settings_register_section',
			[
				/*
				 * Register options.
				 */
				[
					'title' => __( 'Register Options', 'woocommerce-point-of-sale' ),
					'type'  => 'title',
					'desc'  => __( 'The following options affect the settings that are applied when loading all registers.', 'woocommerce-point-of-sale' ),
					'id'    => 'register_options',
				],
				[
					'name'              => __( 'Keypad Presets', 'woocommerce-point-of-sale' ),
					'desc_tip'          => __( 'Define the preset keys that appear when applying discounts in the register.', 'woocommerce-point-of-sale' ),
					'id'                => 'wc_pos_discount_presets',
					'class'             => 'wc-enhanced-select',
					'type'              => 'multiselect',
					/**
					 * Discount presets.
					 *
					 * @since 5.0.0
					 */
					'options'           => apply_filters(
						'wc_pos_discount_presets',
						[
							'5'   => __( '5%', 'woocommerce-point-of-sale' ),
							'10'  => __( '10%', 'woocommerce-point-of-sale' ),
							'15'  => __( '15%', 'woocommerce-point-of-sale' ),
							'20'  => __( '20%', 'woocommerce-point-of-sale' ),
							'25'  => __( '25%', 'woocommerce-point-of-sale' ),
							'30'  => __( '30%', 'woocommerce-point-of-sale' ),
							'35'  => __( '35%', 'woocommerce-point-of-sale' ),
							'40'  => __( '40%', 'woocommerce-point-of-sale' ),
							'45'  => __( '45%', 'woocommerce-point-of-sale' ),
							'50'  => __( '50%', 'woocommerce-point-of-sale' ),
							'55'  => __( '55%', 'woocommerce-point-of-sale' ),
							'60'  => __( '60%', 'woocommerce-point-of-sale' ),
							'65'  => __( '65%', 'woocommerce-point-of-sale' ),
							'70'  => __( '70%', 'woocommerce-point-of-sale' ),
							'75'  => __( '75%', 'woocommerce-point-of-sale' ),
							'80'  => __( '80%', 'woocommerce-point-of-sale' ),
							'85'  => __( '85%', 'woocommerce-point-of-sale' ),
							'90'  => __( '90%', 'woocommerce-point-of-sale' ),
							'95'  => __( '95%', 'woocommerce-point-of-sale' ),
							'100' => __( '100%', 'woocommerce-point-of-sale' ),
						]
					),
					'default'           => [ '5', '10', '15', '20' ],
					'custom_attributes' => [ 'data-maximum-selection-length' => 4 ],
				],
				[
					'name'     => __( 'Discount Reasons', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_discount_reasons',
					'type'     => 'multiselect',
					'class'    => 'wc-enhanced-select-with-tags',
					'desc_tip' => __( 'Select the reasons that will be available when applying discounts in the register.', 'woocommerce-point-of-sale' ),
					'options'  => $discount_reasons,
					'default'  => $default_discount_reasons,
				],
				[
					'title'    => __( 'Keyboard Shortcuts', 'woocommerce-point-of-sale' ),
					'desc'     => __( 'Enable keyboard shortcuts', 'woocommerce-point-of-sale' ),
					// translators: 1: opening anchor tag for the shortcuts link 2: closing anchor tag
					'desc_tip' => sprintf( __( 'Allows you to use keyboard shortcuts to execute popular and frequent actions. Click %1$shere%2$s for the list of keyboard shortcuts.', 'woocommerce-point-of-sale' ), '<a href="http://actualityextensions.com/woocommerce-point-of-sale/keyboard-shortcuts/" target="_blank">', '</a>' ),
					'id'       => 'wc_pos_keyboard_shortcuts',
					'default'  => 'no',
					'type'     => 'checkbox',
				],
				[
					'name'     => __( 'List Coupons', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Select the allowed user roles to list coupons.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_list_coupons_roles',
					'class'    => 'wc-enhanced-select',
					'type'     => 'multiselect',
					/**
					 * User roles that can list coupons.
					 *
					 * @since 6.0.0
					 */
					'options'  => apply_filters(
						'wc_pos_list_coupons_roles',
						[
							'register_clerk' => __( 'Register clerk', 'woocommerce-point-of-sale' ),
							'outlet_manager' => __( 'Outlet manager', 'woocommerce-point-of-sale' ),
							'shop_manager'   => __( 'Shop manager', 'woocommerce-point-of-sale' ),
						]
					),
					'default'  => [ 'outlet_manager', 'shop_manager' ],
				],
				[
					'title'   => __( 'Itemised Quantity', 'woocommerce-point-of-sale' ),
					'desc'    => __( 'Check this box to itemise products in the cart in separate rows.', 'woocommerce-point-of-sale' ),
					'id'      => 'wc_pos_itemised_quantity',
					'default' => 'no',
					'type'    => 'checkbox',
				],
				[
					'title'    => __( 'Manager Override', 'woocommerce-point-of-sale' ),
					'desc'     => __( 'Enable taking over of registers', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Allows shop managers to take over an already opened register.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_force_logout',
					'default'  => 'no',
					'type'     => 'checkbox',
				],
				[
					'title'    => __( 'Session Logout', 'woocommerce-point-of-sale' ),
					'desc'     => __( 'Force logout from WordPress', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Check this box to log out from the WordPress session after closing the register.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_force_end_wp_session',
					'default'  => 'no',
					'type'     => 'checkbox',
				],
				[
					'type' => 'sectionend',
					'id'   => 'register_options',
				],

				/*
				 * Cash management options.
				 */
				[
					'title' => __( 'Cash Management', 'woocommerce-point-of-sale' ),
					'type'  => 'title',
					'desc'  => __( 'The following options affect the settings that are applied when using the cash management function.', 'woocommerce-point-of-sale' ),
					'id'    => 'cash_management_options',
				],
				[
					'name'     => __( 'Order Status Criteria ', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Select the order statuses to be included to the cash management.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_cash_management_order_statuses',
					'class'    => 'wc-enhanced-select',
					'type'     => 'multiselect',
					/**
					 * Cash management order statuses.
					 *
					 * @since 5.0.0
					 */
					'options'  => apply_filters( 'wc_pos_cash_management_order_status', $order_statuses ),
					'default'  => [ 'processing' ],
				],
				[
					'title'    => __( 'Currency Rounding', 'woocommerce-point-of-sale' ),
					'desc'     => __( 'Enable currency rounding', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Rounds the total to the nearest value defined below. Used by some countries where not all denominations are available.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_enable_currency_rounding',
					'default'  => 'no',
					'type'     => 'checkbox',
				],
				[
					'title'    => __( 'Rounding Value', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Select the rounding value which you want the register to round nearest to.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_currency_rounding_value',
					'default'  => 'no',
					'type'     => 'select',
					'class'    => 'wc-enhanced-select',
					/**
					 * Currency rounding values.
					 *
					 * @since 5.0.0
					 */
					'options'  => apply_filters(
						'wc_pos_currency_rounding_values',
						[
							'0.01' => __( '0.01', 'woocommerce-point-of-sale' ),
							'0.05' => __( '0.05', 'woocommerce-point-of-sale' ),
							'0.10' => __( '0.10', 'woocommerce-point-of-sale' ),
							'0.50' => __( '0.50', 'woocommerce-point-of-sale' ),
							'1.00' => __( '1.00', 'woocommerce-point-of-sale' ),
							'5.00' => __( '5.00', 'woocommerce-point-of-sale' ),
						]
					),
				],
				[
					'type' => 'sectionend',
					'id'   => 'cash_management_options',
				],

				/*
				 * Cash denominations options.
				 */
				[
					'title' => __( 'Cash Denomination', 'woocommerce-point-of-sale' ),
					'type'  => 'title',
					'desc'  => __( 'The following table defines the denominations that your point of sale system use when handling cash.', 'woocommerce-point-of-sale' ),
					'id'    => 'cash_denomination_options',
				],
				[ 'type' => 'cash_denominations' ],
				[
					'type' => 'sectionend',
					'id'   => 'cash_denomination_options',
				],
			]
		);
	}

	/**
	 * Renders the cash denomination options table.
	 */
	public static function output_cash_denomination_options() {
		$denominations = get_option( 'wc_pos_cash_denominations', [] );
		include_once __DIR__ . '/views/html-admin-cash-denomination-options.php';
	}

	/**
	 * Saves cash denomination options.
	 */
	public static function save_cash_denomination_options() {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( wc_clean( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'woocommerce-settings' ) ) {
			return;
		}

		$denominations = ( isset( $_POST['wc_pos_cash_denominations'] ) ) ? array_map( 'wc_clean', (array) $_POST['wc_pos_cash_denominations'] ) : [];
		update_option( 'wc_pos_cash_denominations', array_values( $denominations ) );
	}
}
