<?php
/**
 * Installation-related functions and actions
 *
 * @package WooCommerce_Point_Of_Sale/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Install.
 */
class WC_POS_Install {
	/**
	 * DB updates that need to be run.
	 *
	 * @var array
	 */
	public static $db_updates = [
		'5.0.0' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.0.0.php',
		'5.1.3' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.1.3.php',
		'5.2.0' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.2.0.php',
		'5.2.2' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.2.2.php',
		'5.2.4' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.2.4.php',
		'5.2.5' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.2.5.php',
		'5.2.7' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.2.7.php',
		'5.2.8' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.2.8.php',
		'5.2.9' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.2.9.php',
		'5.3.0' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.3.0.php',
		'5.3.2' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.3.2.php',
		'5.3.3' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.3.3.php',
		'5.3.4' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.3.4.php',
		'5.3.5' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.3.5.php',
		'5.3.5' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.3.6.php',
		'5.3.6' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.3.7.php',
		'5.5.0' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.5.0.php',
		'5.5.2' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.5.2.php',
		'5.5.4' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.5.4.php',
		'6.0.0' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-6.0.0.php',
		'6.0.2' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-6.0.2.php',
		'6.1.0' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-6.1.0.php',
		'next'  => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-next.php',
	];

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'admin_init', [ __CLASS__, 'check_version' ], 5 );
		add_action( 'admin_init', [ __CLASS__, 'install_actions' ], 6 );

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			add_action( 'wpmu_new_blog', [ __CLASS__, 'new_blog' ], 10, 6 );
		}
	}

	public static function check_version() {
		$current_version = get_option( 'wc_pos_db_version', null );

		if ( ! is_null( $current_version ) && version_compare( $current_version, max( array_keys( self::$db_updates ) ), '<' ) ) {
			WC_POS_Admin_Notices::add_notice( 'update' );
		}
	}

	public static function new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
		global $wpdb;
		$pos_path = basename( dirname( WC_POS_PLUGIN_FILE ) );

		if ( is_plugin_active_for_network( $pos_path . '/woocommerce-point-of-sale.php' ) ) {
			$old_blog = $wpdb->blogid;
			switch_to_blog( $blog_id );
			self::install();
			switch_to_blog( $old_blog );
		}
	}

	/**
	 * Install actions such as installing pages when a button is clicked.
	 */
	public static function install_actions() {
		if ( ! empty( $_GET['do_update_wc_pos'] ) ) {
			self::update();

			// Update complete, remove the notice.
			WC_POS_Admin_Notices::remove_notice( 'update' );

			// Redirect to settings page.
			delete_transient( '_wc_pos_activation_redirect' );
			wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=point-of-sale' ) );
			exit;
		}
	}

	/**
	 * Installation.
	 */
	public static function install() {
		global $wpdb;
		if ( ! defined( 'WC_POS_INSTALLING' ) ) {
			define( 'WC_POS_INSTALLING', true );
		}

		self::create_default_posts();
		self::create_tables();
		self::create_custom_product();
		self::create_roles();
		self::update_user_meta();

		// Queue upgrades/setup wizard.
		$current_version = get_option( 'wc_pos_db_version', null );

		/**
		 * Enable setup wizard.
		 *
		 * @since 4.0.0
		 */
		$enable_wizard = apply_filters( 'wc_pos_enable_setup_wizard', true );

		// No versions? Then this is a new install.
		if ( is_null( $current_version ) && $enable_wizard ) {
			WC_POS_Admin_Notices::add_notice( 'install' );
			set_transient( '_wc_pos_activation_redirect', 1, 30 );
			delete_transient( '_wc_pos_activation_redirect' );

			// Hide products columns.
			$admins = get_users( [ 'role' => 'administrator' ] );
			foreach ( $admins as $admin ) {
				update_user_meta( $admin->ID, 'manageedit-productcolumnshidden', [ 'wc_pos_product_grid' ] );
			}
		}

		if ( ! is_null( $current_version ) && version_compare( $current_version, max( array_keys( self::$db_updates ) ), '<' ) ) {
			set_transient( '_wc_pos_activation_redirect', 1, 30 );
			WC_POS_Admin_Notices::add_notice( 'update' );
		} else {
			self::update_pos_version( get_option( 'wc_pos_db_version', WC_POS_VERSION ) );
		}

		/*
		 * Deletes all expired transients. The multi-table delete syntax is used
		 * to delete the transient record from table a, and the corresponding
		 * transient_timeout record from table b.
		 *
		 * Based on code inside core's upgrade_network() function.
		 */
		$wpdb->query(
			$wpdb->prepare(
				"
				DELETE a, b FROM $wpdb->options a, $wpdb->options b
				WHERE a.option_name LIKE %s
				AND a.option_name NOT LIKE %s
				AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
				AND b.option_value < %d
				",
				$wpdb->esc_like( '_transient_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_' ) . '%',
				time()
			)
		);

		/**
		 * Plugin installed.
		 *
		 * @since 4.0.0
		 */
		do_action( 'wc_pos_installed' );
	}

	/**
	 * Handle updates.
	 */
	public static function update() {
		$current_db_version = get_option( 'wc_pos_db_version' );
		foreach ( self::$db_updates as $version => $updater ) {
			if ( version_compare( $current_db_version, $version, '<' ) ) {
				include $updater;
				self::update_pos_version( $version );
			}
		}
		self::update_pos_version( get_option( 'wc_pos_db_version' ) );
	}

	/**
	 * Create the default posts for pos_register, pos_outlet and pos_receipt.
	 *
	 * @since 5.0.0
	 */
	public static function create_default_posts() {
		// Default receipt.
		if ( ! get_option( 'wc_pos_default_receipt', false ) ) {
			$post_id = wp_insert_post(
				[
					'post_type'   => 'pos_receipt',
					'post_status' => 'publish',
					'post_title'  => __( 'Default Receipt', 'woocommerce-point-of-sale' ),
					'meta_input'  => [
						'cashier_name_format'         => 'display_name',
						'num_copies'                  => 1,
						'order_date_format'           => 'm/d/Y',
						'order_time_format'           => 'g:i a',
						'print_copies'                => 'num_copies',
						'product_details_layout'      => 'single',
						'show_order_date'             => 'yes',
						'show_order_status'           => 'yes',
						'show_outlet_address'         => 'yes',
						'show_outlet_contact_details' => 'yes',
						'show_product_cost'           => 'yes',
						'show_product_sku'            => 'yes',
						'show_shop_name'              => 'yes',
						'width'                       => 70,
					],
				]
			);

			if ( $post_id ) {
				update_option( 'wc_pos_default_receipt', $post_id );
			}
		}

		// Default outlet.
		if ( ! get_option( 'wc_pos_default_outlet', false ) ) {
			$post_id = wp_insert_post(
				[
					'post_type'   => 'pos_outlet',
					'post_status' => 'publish',
					'post_title'  => __( 'Default Outlet', 'woocommerce-point-of-sale' ),
					'meta_input'  => [
						'address_1' => WC()->countries->get_base_address(),
						'address_2' => WC()->countries->get_base_address_2(),
						'city'      => WC()->countries->get_base_city(),
						'postcode'  => WC()->countries->get_base_postcode(),
						'country'   => WC()->countries->get_base_country(),
						'state'     => '*' === WC()->countries->get_base_state() ? '' : WC()->countries->get_base_state(),
					],
				]
			);

			if ( $post_id ) {
				update_option( 'wc_pos_default_outlet', $post_id );

				// Assign installing user to the default outlet if they are not assigned to any.
				if ( empty( get_user_meta( get_current_user_id(), 'wc_pos_assigned_outlets', true ) ) ) {
					update_user_meta( get_current_user_id(), 'wc_pos_assigned_outlets', [ (string) $post_id ] );
				}
			}
		}

		// Default register.
		if ( ! get_option( 'wc_pos_default_register', false ) ) {
			$post_id = wp_insert_post(
				[
					'post_type'   => 'pos_register',
					'post_status' => 'publish',
					'post_title'  => __( 'Default Register', 'woocommerce-point-of-sale' ),
					'meta_input'  => [
						'receipt' => (int) get_option( 'wc_pos_default_receipt' ),
						'outlet'  => (int) get_option( 'wc_pos_default_outlet' ),
					],
				]
			);

			if ( $post_id ) {
				update_option( 'wc_pos_default_register', $post_id );
			}
		}
	}

	private static function create_tables() {
		global $wpdb;
		$wpdb->hide_errors();

		$installed_version = get_option( 'wc_pos_db_version' );

		if ( WC_POS_VERSION !== $installed_version ) {

			$collate = '';
			if ( $wpdb->has_cap( 'collation' ) ) {
				if ( ! empty( $wpdb->charset ) ) {
					$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
				}
				if ( ! empty( $wpdb->collate ) ) {
					$collate .= " COLLATE $wpdb->collate";
				}
			}

			// Initial install.
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			maybe_create_table(
				"{$wpdb->prefix}wc_pos_grid_tiles",
				"CREATE TABLE {$wpdb->prefix}wc_pos_grid_tiles (
					id BIGINT UNSIGNED NOT NULL auto_increment,
					type varchar(200) NOT NULL DEFAULT '',
					item_id BIGINT UNSIGNED NOT NULL,
					display_order BIGINT UNSIGNED NOT NULL,
					grid_id BIGINT UNSIGNED NOT NULL,
					PRIMARY KEY (id),
					KEY grid_id (grid_id)
				) $collate;"
			);
		}
	}

	/**
	 * Create roles and capabilities.
	 */
	public static function create_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		// Dummy gettext calls to get strings in the catalog.
		/* translators: user role */
		_x( 'Register clerk', 'User role', 'woocommerce-point-of-sale' );
		/* translators: user role */
		_x( 'Outlet manager', 'User role', 'woocommerce-point-of-sale' );

		$register_clerk_caps = [
			'view_register'             => true,
			'manage_product_terms'      => true,
			'read_private_products'     => true,
			'read_private_shop_orders'  => true,
			'read_private_shop_coupons' => true,
			'publish_shop_orders'       => true,
			'edit_shop_order'           => true,
			'edit_others_shop_orders'   => true,
			'list_users'                => true,
			'edit_users'                => true,
			'promote_users'             => true,
		];

		$outlet_manager_caps = [
			'manage_woocommerce_point_of_sale' => true,
			'force_logout_register'            => true,
			'refund_orders'                    => true,
		];

		$shop_manager_caps = get_role( 'shop_manager' )->capabilities;

		$outlet_manager_caps = array_merge( $shop_manager_caps, $register_clerk_caps, $outlet_manager_caps );

		// Register clerk role.
		add_role( 'register_clerk', 'Register clerk', $register_clerk_caps );

		// Outlet manager role.
		add_role( 'outlet_manager', 'Outlet manager', $outlet_manager_caps );

		// Add outlet_manager caps to administrator and shop_manager.
		foreach ( $outlet_manager_caps as $cap => $status ) {
			if ( true === $status ) {
				$wp_roles->add_cap( 'administrator', $cap );
				$wp_roles->add_cap( 'shop_manager', $cap );
			}
		}
	}

	/**
	 * Update WC POS version to current.
	 */
	public static function update_pos_version( $version = null ) {
		update_option( 'wc_pos_db_version', is_null( $version ) ? WC_POS_VERSION : $version );
	}

	/**
	 * Set default user meta for the installing user.
	 */
	public static function update_user_meta() {
		if ( empty( get_user_meta( get_current_user_id(), 'wc_pos_enable_discount', true ) ) ) {
			update_user_meta( get_current_user_id(), 'wc_pos_enable_discount', 'yes' );
		}

		if ( empty( get_user_meta( get_current_user_id(), 'wc_pos_enable_tender_orders', true ) ) ) {
			update_user_meta( get_current_user_id(), 'wc_pos_enable_tender_orders', 'yes' );
		}
	}

	/**
	 * Create a hidden custom product for internal use.
	 *
	 * @return void
	 */
	public static function create_custom_product() {
		// Exit if already created.
		$custom_product_id = (int) get_option( 'wc_pos_custom_product_id', null );
		if ( $custom_product_id && 'product' === get_post_type( $custom_product_id ) ) {
			return;
		}

		$custom_product = new WC_Product();
		$custom_product->set_name( 'WooCommerce POS Custom Product' );
		$custom_product->set_status( ' publish' );
		$custom_product->set_catalog_visibility( 'hidden' );
		$custom_product->set_price( 10 );
		$custom_product->set_regular_price( 10 );

		$custom_product_id = $custom_product->save();

		update_option( 'wc_pos_custom_product_id', $custom_product_id );
	}

	public static function activate( $networkwide ) {
		self::flush_rewrite_rules();

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			// check if it is a network activation - if so, run the activation function for each blog id.
			if ( $networkwide ) {
				$old_blog = $wpdb->blogid;
				// Get all blog ids.
				$blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

				foreach ( $blogids as $blog_id ) {
					switch_to_blog( $blog_id );
					self::install();
				}

				switch_to_blog( $old_blog );

				return;
			}
		}
		self::install();
	}

	public static function flush_rewrite_rules() {
		flush_rewrite_rules();
	}

	/**
	 * Remove user roles.
	 */
	public static function remove_roles() {
		global $wp_roles;

		$capabilities = [
			'view_register',
			'manage_woocommerce_point_of_sale',
			'refund_orders',
		];

		foreach ( $capabilities as $cap ) {
			$wp_roles->remove_cap( 'shop_manager', $cap );
			$wp_roles->remove_cap( 'administrator', $cap );
		}

		remove_role( 'outlet_manager' );
		remove_role( 'register_clerk' );
	}
}

WC_POS_Install::init();
