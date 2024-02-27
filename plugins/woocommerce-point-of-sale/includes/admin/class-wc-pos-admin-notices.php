<?php
/**
 * Admin Notices
 *
 * Display notices in admin.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Admin_Notices.
 */
class WC_POS_Admin_Notices {

	/**
	 * Stores notices.
	 *
	 * @var array
	 */
	private static $notices = [];

	/**
	 * Array of notices - name => callback.
	 *
	 * @var array
	 */
	private static $core_notices = [
		'install'        => 'install_notice',
		'update'         => 'update_notice',
		'wc-rest-api'    => 'wc_rest_api_notice',
		'stripe-gateway' => 'stripe_gateway_notice',
	];

	/**
	 * Constructor.
	 */
	public static function init() {
		self::$notices = get_option( 'wc_pos_admin_notices', [] );

		add_action( 'wp_loaded', [ __CLASS__, 'hide_notices' ] );
		add_action( 'shutdown', [ __CLASS__, 'store_notices' ] );

		if ( current_user_can( 'manage_woocommerce' ) ) {
			add_action( 'admin_print_styles', [ __CLASS__, 'add_notices' ] );
		}
	}

	/**
	 * See if a notice is being shown.
	 *
	 * @param string $name Notice name.
	 *
	 * @return boolean
	 */
	public static function has_notice( $name ) {
		return in_array( $name, self::get_notices(), true );
	}

	/**
	 * Store notices to DB
	 */
	public static function store_notices() {
		update_option( 'wc_pos_admin_notices', self::get_notices() );
	}

	/**
	 * Get notices
	 *
	 * @return array
	 */
	public static function get_notices() {
		return self::$notices;
	}
	/**
	 * Remove all notices.
	 */
	public static function remove_all_notices() {
		self::$notices = [];
	}

	/**
	 * Show a notice.
	 *
	 * @param string $name Notice name.
	 */
	public static function add_notice( $name ) {
		self::$notices = array_unique( array_merge( self::get_notices(), [ $name ] ) );
	}

	/**
	 * Remove a notice from being displayed.
	 *
	 * @param string $name Notice name.
	 */
	public static function remove_notice( $name ) {
		self::$notices = array_diff( self::get_notices(), [ $name ] );
	}

	/**
	 * Hide a notice if the GET variable is set.
	 */
	public static function hide_notices() {
		if ( isset( $_GET['wc-pos-hide-notice'] ) && isset( $_GET['_wc_pos_notice_nonce'] ) ) { // WPCS: input var ok, CSRF ok.
			if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wc_pos_notice_nonce'] ) ), 'wc_pos_hide_notices_nonce' ) ) { // WPCS: input var ok, CSRF ok.
				wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'woocommerce-point-of-sale' ) );
			}

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'You don&#8217;t have permission to do this.', 'woocommerce-point-of-sale' ) );
			}

			$hide_notice = sanitize_text_field( wp_unslash( $_GET['wc-pos-hide-notice'] ) ); // WPCS: input var ok, CSRF ok.

			self::remove_notice( $hide_notice );

			update_user_meta( get_current_user_id(), 'dismissed_' . $hide_notice . '_notice', true );

			/**
			 * Hide notice.
			 *
			 * @since 5.0.0
			 */
			do_action( 'wc_pos_hide_' . $hide_notice . '_notice' );
		}
	}

	/**
	 * Add notices + styles if needed.
	 */
	public static function add_notices() {
		$notices = self::get_notices();

		if ( empty( $notices ) ) {
			return;
		}

		// Enqueue WC activation styles if not loaded.
		wp_enqueue_style( 'woocommerce-activation', plugins_url( '/assets/css/activation.css', WC_PLUGIN_FILE ), [], WC_VERSION );
		wp_style_add_data( 'woocommerce-activation', 'rtl', 'replace' );

		$screen          = get_current_screen();
		$screen_id       = $screen ? $screen->id : '';
		$show_on_screens = array_merge(
			wc_pos_get_screen_ids(),
			[
				'dashboard',
				'plugins',
			]
		);

		// Notices should only show on Point of Sale screens, the main dashboard, and on the plugins screen.
		if ( ! in_array( $screen_id, $show_on_screens, true ) ) {
			return;
		}

		foreach ( $notices as $notice ) {
			/**
			 * Show admin notice.
			 *
			 * @since 5.0.0
			 */
			if ( ! empty( self::$core_notices[ $notice ] ) && apply_filters( 'wc_pos_show_admin_notice', true, $notice ) ) {
				add_action( 'admin_notices', [ __CLASS__, self::$core_notices[ $notice ] ] );
			}
		}
	}

	/**
	 * If we need to update, include a message with the update button.
	 */
	public static function update_notice() {
		include dirname( __FILE__ ) . '/views/notices/html-notice-update.php';
	}

	/**
	 * If we have just installed, show a message with the install pages button.
	 */
	public static function install_notice() {
		include dirname( __FILE__ ) . '/views/notices/html-notice-install.php';
	}

	/**
	 * Show a notice if the WC REST API is blocked.
	 */
	public static function wc_rest_api_notice() {
		include dirname( __FILE__ ) . '/views/notices/html-notice-wc-rest-api.php';
	}

	/**
	 * Show a notice if the Stripe gateway is required.
	 */
	public static function stripe_gateway_notice() {
		include dirname( __FILE__ ) . '/views/notices/html-notice-stripe-gateway.php';
	}
}

WC_POS_Admin_Notices::init();
