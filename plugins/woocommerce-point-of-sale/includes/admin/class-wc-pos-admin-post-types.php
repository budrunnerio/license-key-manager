<?php
/**
 * Post Types Admin
 *
 * @package WooCommerce_Point_Of_Sale/Classes/Admin
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_POS_Admin_Post_Types', false ) ) {
	return new WC_POS_Admin_Post_Types();
}

/**
 * WC_POS_Admin_Post_Types.
 *
 * Handles the edit posts views and some functionality on the edit post screen for the plugin post types.
 */
class WC_POS_Admin_Post_Types {

	/**
	 * Constructor.
	 */
	public function __construct() {
		include_once dirname( __FILE__ ) . '/class-wc-pos-admin-meta-boxes.php';

		// Add/edit a receipt.
		add_action( 'load-post.php', [ $this, 'pos_receipt' ] );
		add_action( 'load-post-new.php', [ $this, 'pos_receipt' ] );
		add_filter( 'admin_body_class', [ $this, 'admin_body_class' ] );

		add_action( 'current_screen', [ $this, 'setup_screen' ], 999999, 0 );
		add_action( 'check_ajax_referer', [ $this, 'setup_screen' ], 999999, 0 );

		add_filter( 'post_updated_messages', [ $this, 'post_updated_messages' ] );
		add_action( 'admin_print_scripts', [ $this, 'disable_autosave' ] );
		add_filter( 'enter_title_here', [ $this, 'enter_title_here' ], 1, 2 );
		add_action( 'edit_form_advanced', [ $this, 'require_post_title' ] );

		add_filter( 'pre_trash_post', [ $this, 'disable_delete_default_posts' ], 10, 2 );
		add_filter( 'pre_delete_post', [ $this, 'disable_delete_default_posts' ], 10, 2 );

		add_filter( 'pre_trash_post', [ $this, 'disable_delete_open_registers' ], 10, 2 );
		add_filter( 'pre_delete_post', [ $this, 'disable_delete_open_registers' ], 10, 2 );

		add_action( 'trashed_post', [ $this, 'delete_post' ] );
		add_action( 'deleted_post', [ $this, 'delete_post' ] );
	}

	/**
	 * Loads the receipt customizer on pos_receipt screen.
	 */
	public function pos_receipt() {
		$action    = 'load-post.php' === current_action() ? 'edit' : 'new';
		$screen_id = false;

		if ( function_exists( 'get_current_screen' ) ) {
			$screen    = get_current_screen();
			$screen_id = isset( $screen, $screen->id ) ? $screen->id : '';
		}

		if ( ! empty( $_REQUEST['screen'] ) ) {
			$screen_id = wc_clean( wp_unslash( $_REQUEST['screen'] ) );
		}

		if ( ! $screen_id || 'pos_receipt' !== $screen_id ) {
			return;
		}

		if ( 'edit' === $action && isset( $_GET['action'] ) && 'edit' !== $_GET['action'] ) {
			return;
		}

		$receipt_object = 'edit' === $action && isset( $_GET['post'] ) ? wc_pos_get_receipt( (int) $_GET['post'] ) : new WC_POS_Receipt();

		// Load only what we need here.
		include ABSPATH . 'wp-admin/admin-header.php';
		include WC_POS_ABSPATH . '/includes/admin/views/html-admin-receipt-customize.php';
		include ABSPATH . 'wp-admin/admin-footer.php';

		// Stop here. Don't load anything else.
		die();
	}

	/**
	 * Add CSS classes to the body tag.
	 *
	 * @param  string $class
	 * @return string
	 */
	public function admin_body_class( $class ) {
		$screen_id = false;

		if ( function_exists( 'get_current_screen' ) ) {
			$screen    = get_current_screen();
			$screen_id = isset( $screen, $screen->id ) ? $screen->id : '';
		}

		if ( ! empty( $_REQUEST['screen'] ) ) { // WPCS: input var ok.
			$screen_id = wc_clean( wp_unslash( $_REQUEST['screen'] ) ); // WPCS: input var ok, sanitization ok.
		}

		if ( $screen_id && 'pos_receipt' === $screen_id ) {
			$class = 'wp-customizer';
		}

		return $class;
	}

	/**
	 * Looks at the current screen and loads the correct list table handler.
	 */
	public function setup_screen() {
		global $wc_list_table;

		$screen_id = false;

		if ( function_exists( 'get_current_screen' ) ) {
			$screen    = get_current_screen();
			$screen_id = isset( $screen, $screen->id ) ? $screen->id : '';
		}

		if ( ! empty( $_REQUEST['screen'] ) ) { // WPCS: input var ok.
			$screen_id = wc_clean( wp_unslash( $_REQUEST['screen'] ) ); // WPCS: input var ok, sanitization ok.
		}

		switch ( $screen_id ) {
			case 'edit-pos_register':
				include_once WC_POS_ABSPATH . '/includes/admin/list-tables/class-wc-pos-admin-list-table-registers.php';
				$wc_list_table = new WC_POS_Admin_List_Table_Registers();
				break;
			case 'edit-pos_outlet':
				include_once WC_POS_ABSPATH . '/includes/admin/list-tables/class-wc-pos-admin-list-table-outlets.php';
				$wc_list_table = new WC_POS_Admin_List_Table_Outlets();
				break;
			case 'edit-pos_grid':
				include_once WC_POS_ABSPATH . '/includes/admin/list-tables/class-wc-pos-admin-list-table-grids.php';
				$wc_list_table = new WC_POS_Admin_List_Table_Grids();
				break;
			case 'edit-pos_receipt':
				include_once WC_POS_ABSPATH . '/includes/admin/list-tables/class-wc-pos-admin-list-table-receipts.php';
				$wc_list_table = new WC_POS_Admin_List_Table_Receipts();
				break;
		}

		// Ensure the table handler is only loaded once. Prevents multiple loads if a plugin calls check_ajax_referer many times.
		remove_action( 'current_screen', [ $this, 'setup_screen' ] );
		remove_action( 'check_ajax_referer', [ $this, 'setup_screen' ] );
	}

	/**
	 * Change messages when a post type is updated.
	 *
	 * @param  array $messages Array of messages.
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		global $post;

		// Registers.
		$messages['pos_register'] = [
			0 => '', // Unused. Messages start at index 1.
			4 => __( 'Register Updated.', 'woocommerce-point-of-sale' ),
			6 => __( 'Register Created.', 'woocommerce-point-of-sale' ),
		];

		// Outlets.
		$messages['pos_outlet'] = [
			0 => '', // Unused. Messages start at index 1.
			4 => __( 'Outlet Updated.', 'woocommerce-point-of-sale' ),
			6 => __( 'Outlet Created.', 'woocommerce-point-of-sale' ),
		];

		// Grids.
		$messages['pos_grid'] = [
			0 => '', // Unused. Messages start at index 1.
			4 => __( 'Grid Updated.', 'woocommerce-point-of-sale' ),
			6 => __( 'Grid Created.', 'woocommerce-point-of-sale' ),
		];

		return $messages;
	}

	/**
	 * Disable the auto-save functionality for our custom post types.
	 */
	public function disable_autosave() {
		global $post;

		if ( $post && in_array( get_post_type( $post->ID ), [ 'pos_register', 'pos_outlet', 'pos_grid' ], true ) ) {
			wp_dequeue_script( 'autosave' );
		}
	}

	/**
	 * Change title boxes in admin.
	 *
	 * @param string  $text Text to show.
	 * @param WP_Post $post Current post object.
	 *
	 * @return string
	 */
	public function enter_title_here( $text, $post ) {
		switch ( $post->post_type ) {
			case 'pos_register':
				$text = esc_html__( 'Register name', 'woocommerce-point-of-sale' );
				break;
			case 'pos_outlet':
				$text = esc_html__( 'Outlet name', 'woocommerce-point-of-sale' );
				break;
			case 'pos_grid':
				$text = esc_html__( 'Grid name', 'woocommerce-point-of-sale' );
				break;
		}

		return $text;
	}

	public function require_post_title( $post ) {
		$post_types = [
			'pos_register',
			'pos_outlet',
			'pos_grid',
		];

		if ( ! in_array( $post->post_type, $post_types, true ) ) {
			return;
		}
		?>
		<script type='text/javascript'>
			( function ( $ ) {
				$( document ).ready( function () {
					$( "#title" ).prop( 'required', true );
				} );
			} ( jQuery ) );
		</script>
		<?php
	}

	/**
	 * Prevent deleting the default posts.
	 *
	 * @param WP_Post $post Post object.
	 * @return bool
	 */
	public function disable_delete_default_posts( $check, $post ) {
		if (
			( 'pos_register' === $post->post_type && wc_pos_is_default_register( $post->ID ) ) ||
			( 'pos_outlet' === $post->post_type && wc_pos_is_default_outlet( $post->ID ) ) ||
			( 'pos_receipt' === $post->post_type && wc_pos_is_default_receipt( $post->ID ) )
		) {
			return false;
		}

		return null;
	}

	/**
	 * Prevent deleting open registers.
	 *
	 * @param WP_Post $post Post object.
	 * @return bool
	 */
	public function disable_delete_open_registers( $check, $post ) {
		if ( 'pos_register' !== $post->post_type ) {
			return $check;
		}

		$locked = wc_pos_is_register_locked( $post->ID );
		if ( $locked ) {
			$by = get_user_by( 'id', $locked );

			/* translators: %s Display name */
			wp_die( sprintf( esc_html__( 'This register is opened by %s and cannot be deleted at the moment.', 'woocommerce-point-of-sale' ), esc_html( $by->display_name ) ) );
		}

		if ( wc_pos_is_register_open( $post->ID ) ) {
			wp_die( esc_html__( 'This register is opened and cannot be deleted at the moment. Please make sure to close the register before you can delete it.', 'woocommerce-point-of-sale' ) );
		}

		return $check;
	}

	/**
	 * Do specific actions if a post is trashed/deleted.
	 *
	 * @param $post_id Post ID.
	 */
	public function delete_post( $post_id ) {
		if ( ! $post_id ) {
			return;
		}

		global $wpdb;

		// Register.
		if ( 'pos_register' === get_post_type( $post_id ) ) {
			// Re-assign the orders assigned to this register to the default register.
			if ( wc_pos_custom_orders_table_usage_is_enabled() ) {
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->prefix}wc_orders_meta om
						SET om.meta_value = %s
						WHERE om.meta_key = 'wc_pos_register_id' AND om.meta_value = %s
						",
						get_option( 'wc_pos_default_register' ),
						$post_id
					)
				);
			} else {
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->postmeta} pm
						LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID AND p.post_type = 'shop_order'
						SET pm.meta_value = %d
						WHERE pm.meta_key = 'wc_pos_register_id' AND pm.meta_value = %d
						",
						absint( get_option( 'wc_pos_default_register' ) ),
						$post_id
					)
				);
			}
		}

		// Outlet.
		if ( 'pos_outlet' === get_post_type( $post_id ) ) {
			// Re-assign the registers assigned to this outlet to the default outlet.
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->postmeta} pm
					LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID AND p.post_type = 'pos_register'
					SET pm.meta_value = %d
					WHERE pm.meta_key = 'outlet' AND pm.meta_value = %d
					",
					absint( get_option( 'wc_pos_default_outlet' ) ),
					$post_id
				)
			);
		}

		// Grid.
		if ( 'pos_grid' === get_post_type( $post_id ) ) {
			// Re-assign the registers assigned to this grid to the categories grid.
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->postmeta} pm
					LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID AND p.post_type = 'pos_register'
					SET pm.meta_value = 0
					WHERE pm.meta_key = 'grid' AND pm.meta_value = %d
					",
					$post_id
				)
			);
		}

		// Receipt.
		if ( 'pos_receipt' === get_post_type( $post_id ) ) {
			// Re-assign registers assigned to this receipt template to the default receipt.
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->postmeta} pm
					LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID AND p.post_type = 'pos_register'
					SET pm.meta_value = %d
					WHERE pm.meta_key = 'receipt' AND pm.meta_value = %d
					",
					absint( get_option( 'wc_pos_default_receipt' ) ),
					$post_id
				)
			);
		}
	}
}

new WC_POS_Admin_Post_Types();
