<?php
/**
 * Load Packages
 *
 * @package Self-service Dashboard for WooCommerce Subscriptions
 * @since 1.0.13
 */

namespace SSD;

defined( 'ABSPATH' ) || exit;

if ( defined( 'SSD_IS_PLUGIN' ) && SSD_IS_PLUGIN ) {
	define( 'SSD_FUNC_PATH', SSD_PLUGIN_PATH . '/functions/' );
	define( 'SSD_FUNC_URL', SSD_PLUGIN_URL . '/functions/' );
} else {
	define( 'SSD_FUNC_PATH', SFORCE_PLUGIN_PATH . '/functionalities/SSD/' );
	define( 'SSD_FUNC_URL', SFORCE_PLUGIN_URL . '/functionalities/SSD/' );
}

if ( ! class_exists( 'Load_Packages' ) ) {
	/**
	 * Class Load_Packages
	 */
	class Load_Packages {
		/**
		 * Packages
		 *
		 * @var string[]
		 */
		protected static $packages = array(
			'update-subscription'     => 'class-wpr-update-subscription.php',
			'pause-subscription'      => 'class-wpr-pause-subscription.php',
			'settings-tab'            => 'class-wpr-settings-tab.php',
			'helpers'                 => 'helpers.php',
		);

		/**
		 * Include path.
		 *
		 * @var string
		 */
		private static $include_path = '';

		/**
		 * Load_Packages constructor.
		 */
		private function __construct() {
		}

		/**
		 * The include path.
		 */
		public static function set_include_path() {
			self::$include_path = SSD_FUNC_PATH;
		}

		/**
		 * Load the file
		 *
		 * @param string $path Path string.
		 *
		 * @return bool
		 */
		private static function load_the_file( $path ) {
			if ( $path && is_readable( $path ) ) {
				require_once $path;

				return true;
			}

			return false;
		}

		/**
		 * Init.
		 */
		public static function init() {
			self::set_include_path();

			add_action( 'plugins_loaded', array( __CLASS__, 'on_init' ) );
		}

		/**
		 * Callback for WordPress init hook.
		 */
		public static function on_init() {
			if ( defined( 'SSD_IS_PLUGIN' ) ) {
				if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
					require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
				}

				if ( is_multisite() ) {
					if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
						$woo_need = is_plugin_active_for_network( 'woocommerce/woocommerce.php' );
						$woos_need = is_plugin_active_for_network( 'woocommerce-subscriptions/woocommerce-subscriptions.php' );

						if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
							$woo_need = true;
						}

						if ( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
							$woo_need = true;
							$woos_need = true;
						}
					} else {
						$woo_need = is_plugin_active( 'woocommerce/woocommerce.php' );
						$woos_need = is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' );
					}
					// this plugin runs on a single site.
				} else {
					$woo_need = is_plugin_active( 'woocommerce/woocommerce.php' );
					$woos_need = is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' );
				}

				if ( ! $woo_need || ! $woos_need ) {

					if ( ! $woo_need ) {
						add_action(
							'admin_notices',
							function () {
								$install_url = wp_nonce_url(
									add_query_arg(
										array(
											'action' => 'install-plugin',
											'plugin' => 'woocommerce',
										),
										admin_url( 'update.php' )
									),
									'install-plugin_woocommerce'
								);
								/* translators: Notice message */
								$admin_notice_content = sprintf( esc_html__( '%1$sSelf Service Dashboard is inactive.%2$s The %3$sWooCommerce plugin%4$s must be active for Self Service Dashboard to work. Please %5$sinstall & activate WooCommerce &raquo;%6$s', 'bos4w' ), '<strong>', '</strong>', '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>', '<a href="' . esc_url( $install_url ) . '">', '</a>' );
								/* translators: Notice HTML */
								printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', wp_kses_post( $admin_notice_content ) );
							}
						);
					}

					if ( ! $woos_need ) {
						add_action(
							'admin_notices',
							function () {
								/* translators: Notice message */
								$admin_notice_content = sprintf( esc_html__( '%1$sSelf Service Dashboard is inactive.%2$s The %3$sWooCommerce Subscriptions plugin%4$s must be active for Self Service Dashboard to work.', 'bos4w' ), '<strong>', '</strong>', '<a href="https://woocommerce.com/products/woocommerce-subscriptions">', '</a>' );
								/* translators: Notice HTML */
								printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', wp_kses_post( $admin_notice_content ) );
							}
						);
					}

					return;
				}
			}

			self::load_packages();
		}

		/**
		 * Loads packages after plugins_loaded hook.
		 *
		 * Each package should include an init file which loads the package so it can be used by core.
		 */
		protected static function load_packages() {
			if ( ! function_exists( 'fs_subscription_force' ) ) {
				self::$packages = array_merge(
					self::$packages,
					array(
						'change-payment-date'     => 'change-payment-date/class-change-payment-date.php',
						'change-frequency'        => 'change-frequency/class-change-frequency.php',
						'add-to-next-shipment'    => 'add-to-next-shipment/class-add-to-next-shipment.php',
					)
				);
			}

			foreach ( self::$packages as $package_name => $package_file ) {
				if ( ! self::check_if_package_exists( $package_file ) ) {
					self::if_is_missing_package( $package_name );
					continue;
				}

				self::load_the_file( self::$include_path . $package_file );
			}
		}

		/**
		 * Check for package.
		 *
		 * @param string $package The package.
		 *
		 * @return bool
		 */
		public static function check_if_package_exists( $package ) {
			return file_exists( self::$include_path . $package );
		}

		/**
		 * Check if missing.
		 *
		 * @param string $package The package.
		 */
		protected static function if_is_missing_package( $package ) {
			add_action(
				'admin_notices',
				function () use ( $package ) {
					?>
					<div class="notice notice-error">
						<p>
							<strong>
								<?php
								/* translators: Notice message */
								echo sprintf( esc_html__( 'Missing the SSD %s package', 'bos4w' ), '<code>' . esc_html( $package ) . '</code>' );
								?>
							</strong>
							<br>
							<?php
							echo esc_html__( 'Your installation of SSD is incomplete.', 'bos4w' );
							?>
						</p>
					</div>
					<?php
				}
			);
		}
	}
}
