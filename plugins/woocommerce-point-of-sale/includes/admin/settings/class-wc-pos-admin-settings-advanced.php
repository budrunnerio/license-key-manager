<?php
/**
 * Settings > Point of Sale > Advanced.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/Admin/Settings
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Admin_Settings_Advanced.
 */
class WC_POS_Admin_Settings_Advanced {

	/**
	 * Returns section settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		/**
		 * The advanced settings section.
		 *
		 * @since 6.0.0
		 */
		return apply_filters(
			'wc_pos_settings_advanced_section',
			[
				/*
				 * Advanced options.
				 */
				[
					'title' => __( 'Loading Options', 'woocommerce-point-of-sale' ),
					'desc'  => __( 'The following options affect how data is fetched from the server when loading the POS applicaiton.', 'woocommerce-point-of-sale' ),
					'type'  => 'title',
					'id'    => 'loading_options',
				],
				[
					'title'             => __( 'Maximum Concurrent Requests', 'woocommerce-point-of-sale' ),
					'desc'              => __( 'Set the maximum number of API requests to the same endpoint', 'woocommerce-point-of-sale' ),
					'desc_tip'          => __( 'Use the maximum value for a faster loading experience.', 'woocommerce-point-of-sale' ),
					'id'                => 'wc_pos_max_concurrent_requests',
					'default'           => '30',
					'type'              => 'number',
					'custom_attributes' => [
						'min'  => '1',
						'max'  => '30',
						'step' => '1',
					],
				],
				[
					'title'             => __( 'Maximum Items in Request', 'woocommerce-point-of-sale' ),
					'desc'              => __( 'Specify the number of items to return in one request', 'woocommerce-point-of-sale' ),
					'desc_tip'          => __( 'Use the maximum value for a faster loading experience.', 'woocommerce-point-of-sale' ),
					'id'                => 'wc_pos_api_per_page',
					'default'           => '100',
					'type'              => 'number',
					'custom_attributes' => [
						'min'  => '1',
						'max'  => '100',
						'step' => '1',
					],
				],
				[
					'type' => 'sectionend',
					'id'   => 'loading_options',
				],

				/*
				 * Caching options.
				 */
				[
					'title' => __( 'Caching Options', 'woocommerce-point-of-sale' ),
					'desc'  => __( 'The following options affects what data is cached when POS is loading.', 'woocommerce-point-of-sale' ),
					'type'  => 'title',
					'id'    => 'caching_options',
				],
				[
					'title'             => __( 'Check Updates Interval', 'woocommerce-point-of-sale' ),
					'desc'              => __( 'Set the interval (in seconds) between requests to the server to check for updates.', 'woocommerce-point-of-sale' ),
					'id'                => 'wc_pos_check_updates_interval',
					'default'           => '15',
					'type'              => 'number',
					'custom_attributes' => [
						'min'  => '10',
						'max'  => '3600',
						'step' => '1',
					],
				],
				[
					'name'     => __( 'Coupons Cache', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_cache_coupons',
					'type'     => 'checkbox',
					'desc'     => __( 'Enable caching of coupons', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Check this box to load all coupons on register initialisation.', 'woocommerce-point-of-sale' ),
					'default'  => 'yes',
					'autoload' => true,
				],
				[
					'name'     => __( 'Customers Cache', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_cache_customers',
					'type'     => 'checkbox',
					'desc'     => __( 'Enable caching of customer data', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Check this box to load all customer data onto the register upon initialisation.', 'woocommerce-point-of-sale' ),
					'default'  => 'no',
					'autoload' => true,
				],
				[
					'type' => 'sectionend',
					'id'   => 'caching_options',
				],

				/*
				 * Database options.
				 */
				[
					'title' => __( 'Database', 'woocommerce-point-of-sale' ),
					'type'  => 'title',
					'id'    => 'database_options',
				],
				[ 'type' => 'database_options' ],
				[
					'type' => 'sectionend',
					'id'   => 'database_options',
				],
			]
		);
	}

	/**
	 * Renders the database options.
	 */
	public static function output_database_options() {
		include __DIR__ . '/views/html-admin-database-options.php';
	}
}
