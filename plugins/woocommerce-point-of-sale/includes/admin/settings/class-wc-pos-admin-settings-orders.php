<?php
/**
 * Settings > Point of Sale > Orders.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/Admin/Settings
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Admin_Settings_Orders.
 */
class WC_POS_Admin_Settings_Orders {

	/**
	 * Returns section settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$order_statuses = wc_pos_get_order_statuses_no_prefix();

		/**
		 * The orders settings section.
		 *
		 * @since 6.0.0
		 */
		return apply_filters(
			'wc_pos_settings_orders_section',
			[
				/*
				 * Order options.
				 */
				[
					'title' => __( 'Order Options', 'woocommerce-point-of-sale' ),
					'type'  => 'title',
					'desc'  => __( 'The following options affect how the orders are managed and displayed in POS.', 'woocommerce-point-of-sale' ),
					'id'    => 'order_options',
				],
				[
					'name'     => __( 'Display Orders', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_display_orders_for_logged_in_user',
					'std'      => '',
					'type'     => 'checkbox',
					'desc'     => __( 'Display orders for logged in user only', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Check this box to display orders for the logged in user only.', 'woocommerce-point-of-sale' ),
					'default'  => 'yes',
					'autoload' => true,
				],
				[
					'name'     => __( 'Fetch Orders ', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Select the order statuses of loaded orders when using the register.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_fetch_order_statuses',
					'class'    => 'wc-enhanced-select',
					'type'     => 'multiselect',
					/**
					 * Fetch orders statuses.
					 *
					 * @since 5.0.0
					 */
					'options'  => apply_filters( 'wc_pos_fetch_order_statuses', $order_statuses ),
					'default'  => [ 'on-hold', 'pending' ],
				],
				[
					'name'     => __( 'Website Orders ', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_load_website_orders',
					'std'      => '',
					'type'     => 'checkbox',
					'desc'     => __( 'Load website orders', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Loads orders placed through the web store.', 'woocommerce-point-of-sale' ),
					'default'  => 'no',
					'autoload' => true,
				],
				[
					'name'     => __( 'Order Filters', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Select which filters appear on the Orders page.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_order_filters',
					'class'    => 'wc-enhanced-select',
					'type'     => 'multiselect',
					'default'  => [ 'registers' ],
					'options'  => [
						'registers' => __( 'Registers', 'woocommerce-point-of-sale' ),
						'outlets'   => __( 'Outlets', 'woocommerce-point-of-sale' ),
					],
					'autoload' => true,
				],
				[
					'type' => 'sectionend',
					'id'   => 'order_options',
				],
			]
		);
	}
}
