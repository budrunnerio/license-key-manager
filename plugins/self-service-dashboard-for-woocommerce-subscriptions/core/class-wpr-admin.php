<?php
/**
 * Admin Settings Tab
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

if ( ! class_exists( 'WPR_Admin' ) ) {
	/**
	 * Class WPR_Admin
	 */
	class WPR_Admin {
		/**
		 * WPR_Admin constructor.
		 */
		public function __construct() {
			add_filter( 'woocommerce_settings_tabs_array', array( $this, 'ssd_settings_tab' ), 50, 1 );
			add_action( 'woocommerce_settings_tabs_ssd_settings_tab', array( $this, 'ssd_settings_tab_content' ) );
			add_filter( 'wp_kses_allowed_html', array( $this, 'ssd_allow_target_blank_attribute' ), 10, 2 );
		}

		/**
		 * Add Self-Service Woo Settings tab
		 *
		 * @param array $sections Sections tabs.
		 *
		 * @return mixed
		 */
		public function ssd_settings_tab( $sections ) {
			$sections['ssd_settings_tab'] = __( 'Self-Service Dash', 'self-service-dashboard-for-woocommerce-subscriptions' );

			return $sections;
		}

		/**
		 * Add Self-Settings Woo Settings tab content
		 */
		public function ssd_settings_tab_content() { ?>
			<h1><?php echo esc_html__( 'Get started', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></h1>
			<iframe width="560" height="315" src="https://www.youtube.com/embed/-5eE4Y88DRk" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
			<?php
			/**
			 * Settings tab
			 *
			 * @since 1.0.0
			 */
			do_action( 'ssd_admin_settings_tab' );
			?>
			<?php
		}

		/**
		 * Allow open in new window
		 *
		 * @param   array  $allowed_tags An array of allowed tags.
		 * @param   string $context The context name.
		 *
		 * @return array
		 */
		public function ssd_allow_target_blank_attribute( $allowed_tags, $context ) {
			$allowed_tags['a']['target'] = true;

			return $allowed_tags;
		}
	}
}

new WPR_Admin();
