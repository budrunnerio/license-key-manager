<?php
/**
 * The main class.
 *
 * @package WooCommerce_Point_Of_Sale/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS class.
 */
class WC_POS {

	/**
	 * The plugin version.
	 *
	 * @var string
	 * @since 3.0.5
	 */
	public $version = '6.1.1';

	/**
	 * The single instance of WC_POS.
	 *
	 * @var object
	 * @since 1.9.0
	 */
	private static $_instance = null;

	/**
	 * Point of Sale menu sulg.
	 *
	 * @var string
	 */
	public $menu_slug = 'point-of-sale';

	/**
	 * Barcodes page sulg.
	 *
	 * @var string
	 */
	public $barcodes_page_slug = 'wc-pos-barcodes';

	/**
	 * Stock Controller page sulg.
	 *
	 * @var string
	 */
	public $stock_controller_page_slug = 'wc-pos-stock-controller';

	/**
	 * The main WC_POS instance.
	 *
	 * Ensures only one instance of WC_POS is/can loaded be loaded.
	 *
	 * @since 1.9.0
	 * @see WC_POS
	 *
	 * @return WC_POS
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * The constructor.
	 */
	public function __construct() {
		if ( ! $this->run_checks() ) {
			return;
		}

		$this->define_constants();
		$this->includes();
		$this->init_hooks();

		/**
		 * Hook: woocommerce_point_of_sale_loaded.
		 *
		 * @since 5.0.0
		 */
		do_action( 'woocommerce_point_of_sale_loaded' );
	}

	public function run_checks() {
		if ( ! in_array( 'woocommerce/woocommerce.php', get_option( 'active_plugins' ), true ) ) {
			add_action(
				'admin_notices',
				function() {
					/* translators: 1. URL link. */
					echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Point of Sale for WooCommerce requires WooCommerce to be installed and active. You can download %s here.', 'woocommerce-point-of-sale' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
				}
			);

			return false;
		}

		if ( in_array( 'woocommerce-pos/woocommerce-pos.php', get_option( 'active_plugins' ), true ) ) {
			add_action(
				'admin_notices',
				function() {
					echo '<div class="error"><p><strong>' . esc_html__( 'Point of Sale for WooCommerce requires the “WooCommerce POS” plugin to be deactivated to avoid conflicts.', 'woocommerce-point-of-sale' ) . '</strong></p></div>';
				}
			);

			return false;
		}

		return true;
	}

	/**
	 * On plugin activation.
	 *
	 * @param bool $network_wide Whether the plugin is enabled for all sites in the network or just the current site.
	 */
	public function activate( $network_wide ) {
		include_once 'class-wc-pos-install.php';
		include_once 'admin/class-wc-pos-admin-notices.php';

		global $wpdb;

		// If the plugin is being activated network wide, then run the activation code for each site.
		if ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide ) {
			$current_blog = $wpdb->blogid;

			// Loop over blogs.
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );
				WC_POS_Install::install();
			}

			switch_to_blog( $current_blog );
			return;
		}

		WC_POS_Install::install();
	}

	/**
	 * On plugin deactivation.
	 */
	public function deactivate() {
		// Delete the hidden custom product as it will no longer be hidden after deactivation.
		// On re-activation a new custom product will be created.
		wp_delete_post( (int) get_option( 'wc_pos_custom_product_id' ), true );
		delete_option( 'wc_pos_custom_product_id' );
	}


	/**
	 * On plugin update.
	 *
	 * @param WC_Upgrade $wc_upgrade
	 * @param array      $hook_extra Array of bulk item update data.
	 */
	public function update( $wc_upgrade, $hook_extra ) {
		if (
			'update' === $hook_extra['action'] &&
			'plugin' === $hook_extra['type'] &&
			isset( $hook_extra['plugins'] ) &&
			in_array( plugin_basename( WC_POS_PLUGIN_FILE ), $hook_extra['plugins'], true )
		) {
			WC_POS_Install::install();
		}
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', WC_POS_PLUGIN_FILE ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( WC_POS_PLUGIN_FILE ) );
	}

	/**
	 * Returns the plugin barcode URL.
	 *
	 * @return string
	 */
	public function barcode_url() {
		return untrailingslashit( plugins_url( 'includes/vendor/barcode/image.php', WC_POS_PLUGIN_FILE ) . '?filetype=PNG&dpi=72&scale=1&rotation=0&font_family=0&thickness=60&start=NULL&code=BCGcode128' );
	}

	/**
	 * Returns plugin menu screen ID.
	 *
	 * @return string
	 */
	public function plugin_screen_id() {
		return sanitize_title( __( 'Point of Sale', 'woocommerce-point-of-sale' ) );
	}

	/**
	 * Returns WooCommerce menu screen ID.
	 *
	 * @return string
	 */
	public function wc_screen_id() {
		/*
		 * We cannot just use __( 'WooCommerce', 'woocommerce' ) to get the WC screen ID
		 * to avoid a PHPCS violation WordPress.WP.I18n.TextDomainMismatch.
		 */
		$wc_screen_ids = array_values(
			array_filter(
				wc_get_screen_ids(),
				function( $id ) {
					return false !== strpos( $id, '_page_wc-settings' );
				}
			)
		);

		$wc_screen_id = str_replace( '_page_wc-settings', '', $wc_screen_ids[0] );

		return $wc_screen_id;
	}

	/**
	 * Receives Heartbeat data and respond.
	 *
	 * @param array $response Heartbeat response data to pass back to front-end.
	 * @param array $data Data received from the front-end.
	 *
	 * @return array
	 */
	public function pos_register_status( $response, $data ) {
		if ( empty( $data['pos_register_id'] ) ) {
			return $response;
		}

		$is_lock = wc_pos_is_register_locked( (int) $data['pos_register_id'] );
		if ( ! $is_lock ) {
			return $response;
		}

		$user_data = get_userdata( $is_lock )->to_array();

		$response['register_status_data'] = [
			'ID'            => $user_data['ID'],
			'display_name'  => $user_data['display_name'],
			'user_nicename' => $user_data['user_nicename'],
		];

		return $response;
	}

	/**
	 * Defines WC_POS Constants.
	 */
	private function define_constants() {
		$this->define( 'WC_POS_ABSPATH', dirname( WC_POS_PLUGIN_FILE ) );
		$this->define( 'WC_POS_PLUGIN_BASENAME', plugin_basename( WC_POS_PLUGIN_FILE ) );
		$this->define( 'WC_POS_VERSION', $this->version );
		$this->define( 'WC_POS_TOKEN', 'wc_pos' );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Checks the request type.
	 *
	 * @param string $type Request name.
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	/**
	 * Includes the required core files used in admin and on the front-end.
	 */
	public function includes() {
		/*
		 * Global includes.
		 */
		include_once 'wc-pos-core-functions.php';
		include_once 'wc-pos-register-functions.php';
		include_once 'wc-pos-outlet-functions.php';
		include_once 'wc-pos-grid-functions.php';
		include_once 'wc-pos-receipt-functions.php';
		include_once 'wc-pos-session-functions.php';
		include_once 'wc-pos-stock-functions.php';
		include_once 'wc-pos-order-functions.php';
		include_once 'wc-pos-user-functions.php';
		include_once 'class-wc-pos-autoloader.php';
		include_once 'class-wc-pos-install.php';
		include_once 'class-wc-pos-post-types.php';
		include_once 'class-wc-pos-emails.php';
		include_once 'admin/class-wc-pos-admin-post-types.php';
		include_once 'admin/class-wc-pos-admin.php';
		include_once 'admin/class-wc-pos-admin-analytics.php';
		include_once 'admin/class-wc-pos-admin-assets.php';

		// On the front-end.
		if ( ! is_admin() ) {
			include_once 'class-wc-pos-app.php';
			include_once 'class-wc-pos-assets.php';

			if ( 'yes' === get_option( 'wc_pos_enable_frontend_access', 'no' ) ) {
				include_once 'class-wc-pos-my-account.php';
			}
		}

		// On Ajax requests.
		if ( defined( 'DOING_AJAX' ) ) {
			include_once 'class-wc-pos-ajax.php';
		}
	}

	/**
	 * Hooks.
	 */
	public function init_hooks() {
		// Activation/deactivation.
		register_activation_hook( WC_POS_PLUGIN_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( WC_POS_PLUGIN_FILE, [ $this, 'deactivate' ] );

		add_action( 'before_woocommerce_init', [ $this, 'declare_hpos_compatibility' ] );
		add_action( 'init', [ $this, 'visibility' ] );
		add_action( 'init', [ $this, 'register_meta' ] );
		add_action( 'admin_init', [ $this, 'force_country_display' ] );
		add_action( 'admin_init', [ $this, 'print_report' ], 100 );
		add_action( 'admin_notices', [ $this, 'check_wc_rest_api' ] );
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );
		add_action( 'upgrader_process_complete', [ $this, 'update' ], 10, 2 );
		add_action( 'woocommerce_hidden_order_itemmeta', [ $this, 'hidden_order_itemmeta' ], 150, 1 );
		add_filter( 'woocommerce_get_checkout_order_received_url', [ $this, 'order_received_url' ] );
		add_filter( 'woocommerce_admin_order_actions', [ $this, 'order_actions_reprint_receipts' ], 2, 20 );
		add_filter( 'woocommerce_order_number', [ $this, 'format_order_number' ], 99, 2 );
		add_filter( 'woocommerce_order_item_display_meta_key', [ $this, 'filter_order_item_display_meta_key' ], 10, 3 );
		add_filter( 'woocommerce_screen_ids', [ $this, 'screen_ids' ], 10, 1 );
		add_filter( 'woocommerce_payment_gateways', [ $this, 'add_payment_gateways' ], 100 );
		add_filter( 'woocommerce_valid_order_statuses_for_payment', [ $this, 'add_valid_order_statuses_for_payment' ], 10, 1 );
		add_action( 'plugins_loaded', [ $this, 'init_payment_gateways' ] );
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ], 0 );
		add_action( 'pre_get_posts', [ $this, 'hide_pos_custom_product' ], 99, 1 );
		add_filter( 'heartbeat_received', [ $this, 'pos_register_status' ], 10, 2 );

		add_filter( 'request', [ $this, 'orders_by_order_type' ] );
		add_filter( 'woocommerce_order_list_table_prepare_items_query_args', [ $this, 'order_list_table_prepare_items_query_args' ], 10, 1 );

		add_filter( 'woocommerce_data_stores', [ $this, 'register_data_stores' ], 10, 1 );
		add_action( 'woocommerce_loaded', [ $this, 'manage_floatval_quantity' ] );

		// Sync hooks.
		add_action( 'created_term', [ $this, 'update_category_last_update_timestamp' ], 10, 3 );
		add_action( 'edit_term', [ $this, 'update_category_last_update_timestamp' ], 10, 3 );
		add_action( 'delete_term', [ $this, 'cache_deleted_product_category' ], 10, 3 );
		add_action( 'delete_post', [ $this, 'cache_deleted_product' ], 10, 2 );
		add_action( 'delete_post', [ $this, 'cache_deleted_coupon' ], 10, 2 );
		add_action( 'delete_user', [ $this, 'cache_deleted_user' ], 10, 1 );

		// For compatibility with WooCommerce Subscriptions.
		if ( in_array( 'woocommerce-subscriptions/woocommerce-subscriptions.php', get_option( 'active_plugins' ), true ) ) {
			add_filter( 'woocommerce_subscription_payment_method_to_display', [ $this, 'get_subscription_payment_method' ], 10, 2 );
		}

		// If product visiblity is enabled.
		if ( 'yes' === get_option( 'wc_pos_visibility', 'no' ) ) {
			add_action( 'add_inline_data', [ $this, 'quick_edit_inline_data' ], 10, 2 );
			add_action( 'quick_edit_custom_box', [ $this, 'quick_edit' ], 10, 2 );
			add_action( 'save_post', [ $this, 'save_quick_edit' ], 10, 2 );
			add_action( 'woocommerce_product_bulk_edit_end', [ $this, 'bulk_edit' ], 10, 0 );
			add_action( 'woocommerce_product_bulk_edit_save', [ $this, 'save_bulk_visibility' ], 15, 1 );
			add_action( 'woocommerce_process_product_meta', [ $this, 'save_visibility' ], 10, 2 );
			add_action( 'woocommerce_save_product_variation', [ $this, 'save_variation_visibility' ], 10, 2 );
			add_filter( 'woocommerce_product_is_visible', [ $this, 'product_visibility' ], 99, 2 );
		}
	}

	/**
	 * Declare compatibility with High Performance Order Storage (HPOS).
	 */
	public function declare_hpos_compatibility(): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WC_POS_PLUGIN_FILE, true );
		}
	}

	/**
	 * Filters the order item display meta key.
	 *
	 * @param string        $display_key       The display key.
	 * @param array         $meta              Array of WC_Meta_Data.
	 * @param WC_Order_Item $order_item The order item.
	 *
	 * @return string
	 */
	public function filter_order_item_display_meta_key( $display_key, $meta, $order_item ) {
		$meta_data = $meta->get_data();

		if ( 'note' === $meta_data['key'] ) {
			return __( 'Item Note', 'woocommerce-point-of-sale' );
		}

		return $display_key;
	}

	/**
	 * Sanitize the per_page param.
	 *
	 * @since 5.2.9
	 */
	public function sanitize_per_page( $value, $request, $param ) {
		return intval( $value, 10 );
	}

	/**
	 * Register screen ID.
	 *
	 * @param array $ids IDs.
	 * @return array
	 */
	public function screen_ids( $ids ) {
		$ids[] = 'point-of-sales';
		return $ids;
	}

	/**
	 * Manage product visibility.
	 */
	public function visibility() {
		if ( 'yes' === get_option( 'wc_pos_visibility', 'no' ) ) {
			add_action( 'pre_get_posts', [ $this, 'query_visibility_filter' ], 15, 1 );
			add_filter( 'views_edit-product', [ $this, 'add_visibility_views' ] );
		}
	}

	/**
	 * Check if the WC REST API is blocked (i.e. status code != 200).
	 */
	public function check_wc_rest_api() {
		try {
			$request     = new WP_REST_Request( 'GET', '/wc/v3' );
			$response    = rest_do_request( $request );
			$status_code = $response->get_status();
		} catch ( Exception $e ) {
			// Cannot get the status code (e.g. cURL error). Bypass the check.
			$status_code = 200;
		}

		if ( 200 !== $status_code ) {
			WC_POS_Admin_Notices::add_notice( 'wc-rest-api' );
			return;
		}

		// Remove the notice if added.
		WC_POS_Admin_Notices::remove_notice( 'wc-rest-api' );
	}

	/**
	 * Filter the WP_Query based on the value of wc_pos_visibility.
	 *
	 * @todo Explain the different cases.
	 *
	 * @param WP_Query $query The query object.
	 * @return void
	 */
	public function query_visibility_filter( $query ) {
		// Original meta query.
		$meta_query = (array) $query->get( 'meta_query' );

		// Case 1.
		if (
			! isset( $_GET['filter']['updated_at_min'] ) &&
			! is_admin() &&
			isset( $_SERVER['REQUEST_URI'] ) && false === strpos( wc_clean( $_SERVER['REQUEST_URI'] ), 'wp-json/wc' ) &&
			( isset( $query->query_vars['post_type'] ) && 'product' === $query->query_vars['post_type'] ) ||
			( is_product_category() && ! isset( $query->query_vars['post_type'] ) ) ||
			( is_product_tag() && ! isset( $query->query_vars['post_type'] ) )
		) {
			$meta_query[] = [
				'relation' => 'OR',
				[
					'key'     => '_pos_visibility',
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => '_pos_visibility',
					'value'   => 'pos',
					'compare' => '!=',
				],
			];
		}

		// Case 2.
		if (
			isset( $query->query_vars['post_type'] ) &&
			'product' === $query->query_vars['post_type'] &&
			isset( $_GET['pos_only'] )
		) {
			$meta_query[] = [
				'relation' => 'OR',
				[
					'key'     => '_pos_visibility',
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => '_pos_visibility',
					'value'   => 'pos',
					'compare' => '=',
				],
			];
		}

		// Case 3.
		if (
			isset( $query->query_vars['post_type'] ) &&
			'product' === $query->query_vars['post_type'] &&
			isset( $_GET['online_only'] )
		) {
			$meta_query[] = [
				'relation' => 'OR',
				[
					'key'     => '_pos_visibility',
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => '_pos_visibility',
					'value'   => 'online',
					'compare' => '=',
				],
			];
		}

		$query->set( 'meta_query', $meta_query );
	}

	/**
	 * Add visibility views on the edit product screen.
	 *
	 * @todo To be moved out of this class.
	 *
	 * @param  array $views Array of views.
	 * @return array
	 */
	public function add_visibility_views( $views ) {
		global $post_type_object;
		global $wpdb;

		$post_type = $post_type_object->name;

		// POS Only count.
		$count = $wpdb->get_var( "SELECT COUNT(post_id) FROM $wpdb->postmeta WHERE meta_key = '_pos_visibility' AND meta_value = 'pos'" );
		$count = $count ? $count : 0;

		if ( $count ) {
			$class             = ( isset( $_GET['pos_only'] ) ) ? 'current' : '';
			$views['pos_only'] = "<a href='edit.php?post_type={$post_type}&pos_only=1' class='{$class}'>" . __( 'POS Only', 'woocommerce-point-of-sale' ) . " ({$count}) " . '</a>';
		}

		// Online Only count.
		$count = $wpdb->get_var( "SELECT COUNT(post_id) FROM $wpdb->postmeta WHERE meta_key = '_pos_visibility' AND meta_value = 'online'" );
		$count = $count ? $count : 0;
		if ( $count ) {
			$class                = ( isset( $_GET['online_only'] ) ) ? 'current' : '';
			$views['online_only'] = "<a href='edit.php?post_type={$post_type}&online_only=1' class='{$class}'>" . __( 'Online Only', 'woocommerce-point-of-sale' ) . " ({$count}) " . '</a>';
		}

		return $views;
	}

	public function quick_edit_inline_data( $post, $post_type_object ) {
		if ( 'product' === $post->post_type ) {
			echo '<div class="_pos_visibility">' . esc_html( get_post_meta( $post->ID, '_pos_visibility', true ) ) . '</div>';
		}
	}

	/**
	 * Add a quick edit column to the edit product screen.
	 *
	 * @todo To be moved out of this class.
	 *
	 * @param string $column_name Column being shown.
	 * @param string $post_type Post type being shown.
	 */
	public function quick_edit( $column_name, $post_type ) {
		global $post;

		if ( 'thumb' === $column_name && 'product' === $post_type ) {
			include_once $this->plugin_path() . '/includes/admin/views/html-quick-edit-product.php';
		}
	}

	/**
	 * Save quick edit product.
	 */
	public function save_quick_edit( $post_id, $post ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( empty( $_POST ) ) {
			return;
		}

		if ( ! isset( $_POST['_inline_edit'] ) || ! wp_verify_nonce( wc_clean( wp_unslash( $_POST['_inline_edit'] ) ), 'inlineeditnonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $post->post_type ) && 'revision' === $post->post_type ) {
			return;
		}

		if ( 'product' !== $post->post_type ) {
			return;
		}

		if ( ! empty( $_REQUEST['woocommerce_quick_edit'] ) && isset( $_POST['_pos_visibility'] ) ) {
			update_post_meta( $post_id, '_pos_visibility', wc_clean( wp_unslash( $_POST['_pos_visibility'] ) ) );
		}
	}

	/**
	 * Bulk edit.
	 *
	 * @todo Move the presentation logic to a view file.
	 */
	public function bulk_edit() {
		global $post;
		?>
		<div class="inline-edit-group">
			<label class="alignleft">
				<span class="title"><?php esc_html_e( 'POS Status', 'woocommerce-point-of-sale' ); ?></span>
				<span class="input-text-wrap">
					<select class="pos_visibility" name="_pos_bulk_visibility">
					<?php
					/**
					 * Filter: wc_pos_visibility_options.
					 *
					 * @since 5.0.0
					 */
					$visibility_options = apply_filters(
						'wc_pos_visibility_options',
						[
							''           => __( '— No Change —', 'woocommerce-point-of-sale' ),
							'pos_online' => __( 'POS & Online', 'woocommerce-point-of-sale' ),
							'pos'        => __( 'POS Only', 'woocommerce-point-of-sale' ),
							'online'     => __( 'Online Only', 'woocommerce-point-of-sale' ),
						]
					);
					foreach ( $visibility_options as $key => $value ) {
						echo "<option value='" . esc_attr( $key ) . "'>" . esc_html( $value ) . '</option>';
					}
					?>
					</select>
				</span>
			</label>
		</div>
		<?php
	}

	/**
	 * Save visibility on bulk edit.
	 *
	 * @todo To be moved out of this class.
	 * @param object $product
	 */
	public function save_bulk_visibility( $product ) {
		$product_id = $product->get_id();

		if ( ! current_user_can( 'edit_post', $product_id ) || ! isset( $_REQUEST['_pos_bulk_visibility'] ) ) {
			return;
		}

		update_post_meta( $product_id, '_pos_visibility', wc_clean( $_REQUEST['_pos_bulk_visibility'] ) );
	}

	/**
	 * Save product visibility.
	 */
	public function save_visibility( $post_id, $post ) {
		if ( 'product' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'update-post_' . $post_id ) ) {
			wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'woocommerce-point-of-sale' ) );
		}

		$visibility = isset( $_POST['_pos_visibility'] ) ? wc_clean( wp_unslash( $_POST['_pos_visibility'] ) ) : 'pos_online';
		$product    = wc_get_product();

		if ( 'variable' === $product->get_type() ) {
			$variations = $product->get_available_variations();

			foreach ( $variations as $variation ) {
				update_post_meta( $variation['variation_id'], '_pos_visibility', $visibility );
			}
		}

		update_post_meta( $post_id, '_pos_visibility', $visibility );
	}

	/**
	 * Update product visibility when variations are saved.
	 *
	 * @param int $variation_id
	 * @param int $i
	 */
	public function save_variation_visibility( $variation_id, $i ) {
		$variation  = new WC_Product_Variation( $variation_id );
		$parent_id  = $variation->get_parent_id();
		$visibility = get_post_meta( $parent_id, '_pos_visibility', true );

		update_post_meta( $variation_id, '_pos_visibility', $visibility );
	}

	// @todo consider using WC_Product::is_visibile() elsewhere to determine POS visibility.
	public function product_visibility( $visbile, $id ) {
		if ( ! wc_pos_is_register_page() && ! wc_pos_is_pos_referer() ) {
			$pos_visbility = get_post_meta( $id, '_pos_visibility', true );

			if ( 'pos' === $pos_visbility ) {
				return false;
			}
		}

		return $visbile;
	}

	/**
	 * Hide our custom product created for internal use.
	 *
	 * @param WP_Query $query
	 * @return WP_Query
	 */
	public function hide_pos_custom_product( $query ) {
		// Bail if not querying products.
		if ( 'product' !== $query->get( 'post_type' ) ) {
			return;
		}

		$post__not_in = $query->get( 'post__not_in', [] );

		if ( ! is_array( $post__not_in ) ) {
			$post__not_in = [ $post__not_in ];
		}

		$post__not_in[] = (int) get_option( 'wc_pos_custom_product_id' );
		$query->set( 'post__not_in', $post__not_in );
	}

	/**
	 * Load localisation
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'woocommerce-point-of-sale', false, dirname( plugin_basename( WC_POS_PLUGIN_FILE ) ) . '/i18n/languages/' );
	}

	/**
	 * Display admin notices.
	 *
	 * @todo To be moved to WC_POST_Admin_Notices. See WC_Admin_Notices.
	 */
	public function admin_notices() {
		if ( empty( get_option( 'permalink_structure' ) ) ) {
			?>
			<div class="error">
				<p><?php esc_html_e( 'Incorrect Permalinks Structure.', 'woocommerce-point-of-sale' ); ?> <a href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>"><?php esc_html_e( 'Change Permalinks', 'woocommerce-point-of-sale' ); ?></a>
				</p>
			</div>
			<?php
		}
	}

	public function order_received_url( $order_received_url ) {
		if ( isset( $_GET['page'] ) && 'wc-pos-register' === $_GET['page'] && isset( $_GET['register'] ) && ! empty( $_GET['register'] ) && isset( $_GET['outlet'] ) && ! empty( $_GET['outlet'] ) ) {
			$register = wc_clean( $_GET['register'] );
			$outlet   = wc_clean( $_GET['outlet'] );

			$register_url = get_home_url() . "/point-of-sale/$outlet/$register";

			return $register_url;
		} else {
			return $order_received_url;
		}
	}

	public function order_list_table_prepare_items_query_args( $order_query_args ) {
		$created_via = sanitize_text_field( wp_unslash( $_REQUEST['_created_via'] ?? '' ) );
		$register_id = sanitize_text_field( wp_unslash( $_REQUEST['_register_id'] ?? '' ) );
		$outlet_id   = sanitize_text_field( wp_unslash( $_REQUEST['_outlet_id'] ?? '' ) );

		$field_query = $order_query_args['field_query'] ?? [];
		$meta_query  = $order_query_args['meta_query'] ?? [];

		if ( 'pos' === $created_via ) {
			$field_query[] = [
				'field'   => 'created_via',
				'value'   => 'pos',
				'compare' => '=',
			];
		} elseif ( 'online' === $created_via ) {
			$field_query[] = [
				'field'   => 'created_via',
				'value'   => 'pos',
				'compare' => '!=',
			];
		}

		if ( $register_id ) {
			$meta_query[] = [
				'key'     => 'wc_pos_register_id',
				'value'   => $register_id,
				'compare' => '=',
			];
		}

		if ( $outlet_id ) {
			$meta_query[] = [
				'key'     => '_wc_pos_outlet_id',
				'value'   => $outlet_id,
				'compare' => '=',
			];
		}

		if ( ! empty( $field_query ) ) {
			$order_query_args['field_query'] = $field_query;
		}

		if ( ! empty( $meta_query ) ) {
			$order_query_args['meta_query'] = $meta_query;
		}

		return $order_query_args;
	}

	public function orders_by_order_type( $vars ) {
		global $typenow, $wp_query;

		if ( 'shop_order' === $typenow ) {
			if ( isset( $_GET['_created_via'] ) && '' !== $_GET['_created_via'] ) {
				if ( 'pos' === $_GET['_created_via'] ) {
					$vars['meta_query'][] = [
						'key'     => '_created_via',
						'value'   => 'pos',
						'compare' => '=',
					];
				} elseif ( 'online' === $_GET['_created_via'] ) {
					$vars['meta_query'][] = [
						'key'     => '_created_via',
						'value'   => 'pos',
						'compare' => '!=',
					];
				}
			}

			if ( isset( $_GET['_register_id'] ) && '' !== $_GET['_register_id'] ) {
				$vars['meta_query'][] = [
					'key'     => 'wc_pos_register_id',
					'value'   => wc_clean( wp_unslash( $_GET['_register_id'] ) ),
					'compare' => '=',
				];
			}

			if ( isset( $_GET['_outlet_id'] ) && '' !== $_GET['_outlet_id'] ) {
				$vars['meta_query'][] = [
					'key'     => '_wc_pos_outlet_id',
					'value'   => wc_clean( wp_unslash( $_GET['_outlet_id'] ) ),
					'compare' => '=',
				];
			}
		}

		return $vars;
	}

	public function order_actions_reprint_receipts( $actions, $the_order ) {
		$amount_change = $the_order->get_meta( 'wc_pos_amount_change' );
		$register_id   = $the_order->get_meta( 'wc_pos_register_id' );
		$register      = wc_pos_get_register( absint( $register_id ) );

		if ( $amount_change && $register ) {
			$actions['reprint_receipts'] = [
				'url'    => wp_nonce_url( admin_url( 'admin.php?pos_print_receipt=true&print_from_wc=true&order_id=' . $the_order->get_id() ), 'print-receipt' ),
				'name'   => __( 'Reprints receipts', 'woocommerce-point-of-sale' ),
				'action' => 'reprint_receipts',
				'target' => '_parent',
			];
		}

		return $actions;
	}

	/**
	 * Add prefix and/or suffix to order numbers based on register settings.
	 *
	 * @param int      $order_id Order ID.
	 * @param WC_Order $order    Order object.
	 *
	 * @return int|string Order number.
	 */
	public function format_order_number( $order_id, $order ) {
		if ( ! $order instanceof WC_Order ) {
			return $order_id;
		}

		// Is POS order?
		$register_id = absint( get_post_meta( $order->get_id(), 'wc_pos_register_id', true ) );
		$register    = wc_pos_get_register( $register_id );
		if ( $register ) {
			return $register->get_prefix() . $order_id . $register->get_suffix();
		}

		return $order_id;
	}

	/**
	 * Force WC()->countries->get_formatted_address() to always display country regardless if it's
	 * the same as base.
	 */
	public function force_country_display() {
		add_filter( 'woocommerce_formatted_address_force_country_display', '__return_true' );
	}

	public function print_report() {
		if ( ! isset( $_GET['pos_print_receipt'] ) || empty( $_GET['pos_print_receipt'] ) ) {
			return;
		}

		if ( ! isset( $_GET['order_id'] ) && ! isset( $_GET['refund_id'] ) ) {
			return;
		}

		if ( empty( $_GET['order_id'] ) && empty( $_GET['refund_id'] ) ) {
			return;
		}

		$order_id  = isset( $_GET['order_id'] ) ? intval( $_GET['order_id'] ) : 0;
		$refund_id = isset( $_GET['refund_id'] ) ? intval( $_GET['refund_id'] ) : 0;

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$refund  = null;
		$refunds = $order->get_refunds();

		if ( $refund_id ) {
			foreach ( $refunds as $_refund ) {
				if ( $refund_id === $_refund->get_id() ) {
					$refund = $_refund;
					break;
				}
			}
		}

		$nonce = isset( $_REQUEST['_wpnonce'] ) ? wc_clean( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'print-receipt' ) || ! is_user_logged_in() ) {
			wp_die( esc_html__( 'You are not allowed to view this page.', 'woocommerce-point-of-sale' ) );
		}

		$register_id = get_post_meta( $order_id, 'wc_pos_register_id', true );

		$register = wc_pos_get_register( absint( $register_id ) );
		if ( ! $register ) {
			$register = wc_pos_get_register( absint( get_option( 'wc_pos_default_register' ) ) );
		}

		$receipt = wc_pos_get_receipt( $register->get_receipt() );
		$outlet  = wc_pos_get_outlet( $register->get_outlet() );

		remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );

		include_once WC_POS()->plugin_path() . '/includes/views/html-print-receipt.php';
	}

	public function hidden_order_itemmeta( $meta_keys = [] ) {
		$meta_keys[] = '_original_price';
		return $meta_keys;
	}

	public function get_subscription_payment_method( $payment_method, $subscription ) {
		$order = wc_get_order( $subscription->get_order_number() );
		if ( $order && 'pos' === $order->get_created_via() ) {
			$payment_method = $order->get_payment_method_title();
		}

		return $payment_method;
	}

	/**
	 * Init payment gateways.
	 *
	 * @since 5.0.0
	 */
	public function init_payment_gateways() {
		include_once 'gateways/class-wc-pos-gateway-bacs.php';
		include_once 'gateways/class-wc-pos-gateway-cheque.php';
		include_once 'gateways/class-wc-pos-gateway-cash.php';
		include_once 'gateways/class-wc-pos-gateway-chip-and-pin.php';
		include_once 'gateways/stripe/class-wc-pos-stripe.php';
		include_once 'gateways/paymentsense/class-wc-pos-paymentsense.php';
	}

	/**
	 * Add payment gateways.
	 *
	 * @since 5.0.0
	 *
	 * @param array $methods Payment methods.
	 * @return array
	 */
	public function add_payment_gateways( $methods ) {
		$methods[] = 'WC_POS_Gateway_Cash';
		$methods[] = 'WC_POS_Gateway_BACS';
		$methods[] = 'WC_POS_Gateway_Cheque';
		$methods[] = 'WC_POS_Gateway_Stripe_Terminal';

		$chip_and_pin = empty( get_option( 'wc_pos_number_chip_and_pin_gateways', 1 ) ) ? 1 : get_option( 'wc_pos_number_chip_and_pin_gateways', 1 );

		for ( $n = 1; $n <= (int) $chip_and_pin; $n++ ) {
			$methods[] = 'WC_POS_Gateway_Chip_And_PIN';
		}

		return $methods;
	}

	public function add_valid_order_statuses_for_payment( $statuses ) {
		array_push(
			$statuses,
			get_option( 'wc_pos_parked_order_default_status', 'on-hold' ),
			get_option( 'wc_pos_parked_order_alternative_status', 'pending' )
		);

		return array_unique( $statuses );
	}

	/**
	 * Returns an instance of WC_POS_Barcodes.
	 *
	 * @since 1.9.0
	 * @return WC_POS_Barcodes
	 */
	public function barcode() {
		return WC_POS_Barcodes::instance();
	}

	/**
	 * Returns an instance of WC_POS_Stock.
	 *
	 * @since 3.0.0
	 * @return WC_POS_Stock
	 */
	public function stock() {
		return WC_POS_Stocks::instance();
	}

	public function manage_floatval_quantity() {
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval', 1 );
	}

	/**
	 * Save the last updated timestamp for product categories.
	 *
	 * @param mixed  $term_id Term ID being saved.
	 * @param mixed  $tt_id Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function update_category_last_update_timestamp( $term_id, $tt_id, $taxonomy ) {
		if ( 'product_cat' === $taxonomy ) {
			update_term_meta( $term_id, 'last_update', time() );
		}
	}

	/**
	 * Adds a recently deleted item ID to the cached list.
	 *
	 * @param integer $item The item ID.
	 * @param string  $type The type of the item.
	 *
	 * @return void
	 */
	private function add_recently_deleted_item( $item, $type ) {
		global $wpdb;

		$registers = $wpdb->get_col(
			// @todo only get open registers.
			$wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = 'pos_register'" )
		);

		$registers = array_map( 'intval', $registers );

		foreach ( $registers as $register_id ) {
			add_post_meta( $register_id, "_wc_pos_recently_deleted_{$type}", $item, false );
		}
	}

	/**
	 * Caches the ID of a recently (permenantly) deleted product.
	 *
	 * @param integer $post_id The product ID.
	 * @param WP_Post $post    The post object.
	 */
	public function cache_deleted_product( $post_id, $post ) {
		if ( 'product' === $post->post_type && 'publish' === $post->post_status ) {
			$this->add_recently_deleted_item( $post_id, 'product' );
		}
	}

	/**
	 * Caches the ID of a recently deleted coupon.
	 *
	 * @param integer $post_id The coupon ID.
	 * @param WP_Post $post    The coupon object.
	 */
	public function cache_deleted_coupon( $post_id, $post ) {
		if ( 'shop_coupon' === $post->post_type && 'publish' === $post->post_status ) {
			$this->add_recently_deleted_item( $post_id, 'coupon' );
		}
	}

	/**
	 * Caches the ID of a recently deleted user.
	 *
	 * @param integer $user_id The user ID.
	 */
	public function cache_deleted_user( $user_id ) {
		$this->add_recently_deleted_item( $user_id, 'user' );
	}

	/**
	 * Caches the ID of a recently deleted product category.
	 *
	 * @param integer $term_id The category ID.
	 * @param integer $tt_id The term taxonomy ID.
	 * @param string  $taxonomy The taxonomy slug.
	 */
	public function cache_deleted_product_category( $term_id, $tt_id, $taxonomy ) {
		if ( 'product_cat' === $taxonomy ) {
			$this->add_recently_deleted_item( $term_id, 'product_category' );
		}
	}

	public function register_meta() {
		register_meta(
			'user',
			'wc_pos_user_card_number',
			[
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'string',
			]
		);

		register_meta(
			'user',
			'wc_pos_customer_status',
			[
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'string',
			]
		);

		register_meta(
			'user',
			'wc_pos_enable_tender_orders',
			[
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'string',
			]
		);

		register_meta(
			'user',
			'wc_pos_enable_discount',
			[
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'string',
			]
		);

		register_meta(
			'user',
			'wc_pos_assigned_outlets',
			[
				'show_in_rest' => [
					'schema' => [ 'items' => [ 'type' => 'number' ] ],
				],
				'single'       => true,
				'type'         => 'array',
			]
		);
	}

	/**
	 * Register a new WC data stores for our CPTs.
	 *
	 * @param array $stores Data stores.
	 * @return array
	 */
	public function register_data_stores( $stores ) {
		include_once dirname( __FILE__ ) . '/data-stores/class-wc-pos-register-data-store-cpt.php';
		include_once dirname( __FILE__ ) . '/data-stores/class-wc-pos-outlet-data-store-cpt.php';
		include_once dirname( __FILE__ ) . '/data-stores/class-wc-pos-grid-data-store-cpt.php';
		include_once dirname( __FILE__ ) . '/data-stores/class-wc-pos-receipt-data-store-cpt.php';
		include_once dirname( __FILE__ ) . '/data-stores/class-wc-pos-session-data-store-cpt.php';

		$stores['pos_register'] = 'WC_POS_Register_Data_Store_CPT';
		$stores['pos_outlet']   = 'WC_POS_Outlet_Data_Store_CPT';
		$stores['pos_grid']     = 'WC_POS_Grid_Data_Store_CPT';
		$stores['pos_receipt']  = 'WC_POS_Receipt_Data_Store_CPT';
		$stores['pos_session']  = 'WC_POS_Session_Data_Store_CPT';

		return $stores;
	}
}
