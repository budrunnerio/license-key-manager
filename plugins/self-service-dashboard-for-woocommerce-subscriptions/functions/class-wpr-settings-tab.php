<?php
/**
 * Settings Tab
 *
 * @package Self-service Dashboard for WooCommerce Subscriptions
 * @since 0.1
 * @extends \WC_Email
 */

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

if ( ! class_exists( 'WPR_Settings_Tab' ) ) {
	/**
	 * Class WPR_Settings_Tab
	 */
	class WPR_Settings_Tab {
		/**
		 * WPR_Settings_Tab constructor.
		 */
		public function __construct() {
			if ( defined( 'SSD_IS_PLUGIN' ) && SSD_IS_PLUGIN ) {
				add_action( 'ssd_admin_settings_tab', array( $this, 'ssd_settings_tab_content' ) );
				add_action( 'woocommerce_update_options_ssd_settings_tab', array( $this, 'ssd_update_settings' ) );
			} else {
				add_action( 'woocommerce_settings_subscription_force', array( $this, 'ssd_settings_tab_content' ) );
				add_action( 'woocommerce_update_options_subscription_force', array( $this, 'ssd_update_settings' ) );
			}
		}

		/**
		 * Add Self-Settings Woo Settings tab content
		 */
		public function ssd_settings_tab_content() {
			woocommerce_admin_fields( self::display_settings() );
		}

		/**
		 * Display the settings
		 *
		 * @return mixed|void
		 */
		public static function display_settings() {
			$settings = array(
				'section_title'        => array(
					'name' => __( 'Options for changing subscriptions', 'self-service-dashboard-for-woocommerce-subscriptions' ),
					'type' => 'title',
					'desc' => '',
					'id'   => 'settings_tabs_ssd_settings_tab_section_title',
				),
				'hide_pause_button'    => array(
					'name' => __( 'Allow subscription pause', 'self-service-dashboard-for-woocommerce-subscriptions' ),
					'type' => 'checkbox',
					'desc' => __( 'When checked, the [Pause] button will be displayed on the My Subscription page; otherwise, it will be hidden.', 'self-service-dashboard-for-woocommerce-subscriptions' ),
					'id'   => 'settings_tabs_hide_pause_button',
				),
				'hide_add_new_product' => array(
					'name' => __( 'Allow adding a new product', 'self-service-dashboard-for-woocommerce-subscriptions' ),
					'type' => 'checkbox',
					'desc' => __( 'When checked, the [Add product] button will be displayed on the My Subscription page; otherwise, it will be hidden.', 'self-service-dashboard-for-woocommerce-subscriptions' ),
					'id'   => 'settings_tabs_hide_add_new_product',
				),
			);

			// Add product types.
			foreach ( wc_get_product_types() as $type => $label ) {

				$checked = 'no';
				if ( 'subscription' == $type ) {
					$checked = 'yes';
				}

				$excluded_types_by_default = array( 'grouped', 'external' );

				if ( ! in_array( $type, $excluded_types_by_default ) ) {
					$settings[ 'product_type_' . $type ] = array(
						'name' => '',
						'type' => 'checkbox',
						'class' => 'conditional_addnewproduct',
						'desc' => $label,
						'default' => $checked,
						'id'   => 'settings_tabs_product_type_' . $type,
					);
				}
			}

			$settings['hide_quantity_change'] = array(
				'name' => __( 'Allow quantity change', 'self-service-dashboard-for-woocommerce-subscriptions' ),
				'type' => 'checkbox',
				'desc' => __( 'When checked, the [Change quantity] button will be displayed on the My Subscription page; otherwise, it will be hidden.', 'self-service-dashboard-for-woocommerce-subscriptions' ),
				'id'   => 'settings_tabs_hide_quantity_change',
			);

			$settings['hide_item_switching']  = array(
				'name' => __( 'Item switching', 'self-service-dashboard-for-woocommerce-subscriptions' ),
				'type' => 'checkbox',
				'desc' => __( 'When checked, the [Switch item] button will be displayed on the My Subscription page; otherwise, it will be hidden.', 'self-service-dashboard-for-woocommerce-subscriptions' ),
				'id'   => 'settings_tabs_hide_item_switching',
			);

			$settings['section_end']          = array(
				'type' => 'sectionend',
				'id'   => 'settings_tabs_ssd_settings_tab_section_end',
			);

			/**
			 * Settings filter
			 *
			 * @param array $settings Settings array.
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'settings_tabs_ssd_settings', $settings );
		}

		/**
		 * Update settings
		 */
		public function ssd_update_settings() {
			woocommerce_update_options( self::display_settings() );
		}
	}
}
new WPR_Settings_Tab();
