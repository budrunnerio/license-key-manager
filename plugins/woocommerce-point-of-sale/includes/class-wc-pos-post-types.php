<?php
/**
 * Post Types
 *
 * Registers post types and taxonomies.
 *
 * @package WooCommerce_Point_Of_Sale/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Post_Types class.
 *
 * @since 5.0.0
 */
class WC_POS_Post_Types {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_post_types' ], 5 );
		add_action( 'wc_pos_after_register_post_type', [ __CLASS__, 'maybe_flush_rewrite_rules' ] );
		add_action( 'wc_pos_installed', [ __CLASS__, 'flush_rewrite_rules' ] );
	}

	/**
	 * Register custom post types.
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public static function register_post_types() {
		if ( ! post_type_exists( 'pos_register' ) ) {
			register_post_type(
				'pos_register',
				/**
				 * The pos_register post type.
				 *
				 * @since 5.0.0
				 */
				apply_filters(
					'wc_pos_register_post_type_pos_register',
					[
						'labels'              => [
							'name'                  => __( 'Registers', 'woocommerce-point-of-sale' ),
							'singular_name'         => __( 'Register', 'woocommerce-point-of-sale' ),
							'all_items'             => __( 'Registers', 'woocommerce-point-of-sale' ),
							'menu_name'             => _x( 'Registers', 'Admin menu name', 'woocommerce-point-of-sale' ),
							'add_new'               => __( 'Add New', 'woocommerce-point-of-sale' ),
							'add_new_item'          => __( 'Add new register', 'woocommerce-point-of-sale' ),
							'edit'                  => __( 'Edit', 'woocommerce-point-of-sale' ),
							'edit_item'             => __( 'Edit register', 'woocommerce-point-of-sale' ),
							'new_item'              => __( 'New register', 'woocommerce-point-of-sale' ),
							'view_item'             => __( 'View register', 'woocommerce-point-of-sale' ),
							'view_items'            => __( 'View registers', 'woocommerce-point-of-sale' ),
							'search_items'          => __( 'Search registers', 'woocommerce-point-of-sale' ),
							'not_found'             => __( 'No registers found', 'woocommerce-point-of-sale' ),
							'not_found_in_trash'    => __( 'No registers found in trash', 'woocommerce-point-of-sale' ),
							'parent'                => __( 'Parent register', 'woocommerce-point-of-sale' ),
							'insert_into_item'      => __( 'Insert into register', 'woocommerce-point-of-sale' ),
							'uploaded_to_this_item' => __( 'Uploaded to this register', 'woocommerce-point-of-sale' ),
							'filter_items_list'     => __( 'Filter registers', 'woocommerce-point-of-sale' ),
							'items_list_navigation' => __( 'Registers navigation', 'woocommerce-point-of-sale' ),
							'items_list'            => __( 'Registers list', 'woocommerce-point-of-sale' ),
						],
						'description'         => __( 'This is where you can add new registers to the Point of Sale.', 'woocommerce-point-of-sale' ),
						'public'              => false,
						'show_ui'             => true,
						'capability_type'     => 'post',
						'map_meta_cap'        => true,
						'publicly_queryable'  => false,
						'exclude_from_search' => true,
						'hierarchical'        => false,
						'query_var'           => true,
						'supports'            => [ 'title' ],
						'has_archive'         => false,
						'show_in_nav_menus'   => true,
						'show_in_menu'        => 'point-of-sale',
						'show_in_rest'        => false,
					]
				)
			);
		}

		if ( ! post_type_exists( 'pos_outlet' ) ) {
			register_post_type(
				'pos_outlet',
				/**
				 * The pos_outlet post type.
				 *
				 * @since 5.0.0
				 */
				apply_filters(
					'wc_pos_register_post_type_pos_outlet',
					[
						'labels'              => [
							'name'                  => __( 'Outlets', 'woocommerce-point-of-sale' ),
							'singular_name'         => __( 'Outlet', 'woocommerce-point-of-sale' ),
							'all_items'             => __( 'Outlets', 'woocommerce-point-of-sale' ),
							'menu_name'             => _x( 'Outlets', 'Admin menu name', 'woocommerce-point-of-sale' ),
							'add_new'               => __( 'Add New', 'woocommerce-point-of-sale' ),
							'add_new_item'          => __( 'Add new outlet', 'woocommerce-point-of-sale' ),
							'edit'                  => __( 'Edit', 'woocommerce-point-of-sale' ),
							'edit_item'             => __( 'Edit outlet', 'woocommerce-point-of-sale' ),
							'new_item'              => __( 'New outlet', 'woocommerce-point-of-sale' ),
							'view_item'             => __( 'View outlet', 'woocommerce-point-of-sale' ),
							'view_items'            => __( 'View outlets', 'woocommerce-point-of-sale' ),
							'search_items'          => __( 'Search outlets', 'woocommerce-point-of-sale' ),
							'not_found'             => __( 'No outlets found', 'woocommerce-point-of-sale' ),
							'not_found_in_trash'    => __( 'No outlets found in trash', 'woocommerce-point-of-sale' ),
							'parent'                => __( 'Parent outlet', 'woocommerce-point-of-sale' ),
							'featured_image'        => __( 'Outlet image', 'woocommerce-point-of-sale' ),
							'set_featured_image'    => __( 'Set outlet image', 'woocommerce-point-of-sale' ),
							'remove_featured_image' => __( 'Remove outlet image', 'woocommerce-point-of-sale' ),
							'use_featured_image'    => __( 'Use as outlet image', 'woocommerce-point-of-sale' ),
							'insert_into_item'      => __( 'Insert into outlet', 'woocommerce-point-of-sale' ),
							'uploaded_to_this_item' => __( 'Uploaded to this outlet', 'woocommerce-point-of-sale' ),
							'filter_items_list'     => __( 'Filter outlets', 'woocommerce-point-of-sale' ),
							'items_list_navigation' => __( 'Outlets navigation', 'woocommerce-point-of-sale' ),
							'items_list'            => __( 'Outlets list', 'woocommerce-point-of-sale' ),
						],
						'description'         => __( 'This is where you can add new outlets to the Point of Sale.', 'woocommerce-point-of-sale' ),
						'public'              => false,
						'show_ui'             => true,
						'capability_type'     => 'post',
						'map_meta_cap'        => true,
						'publicly_queryable'  => false,
						'exclude_from_search' => true,
						'hierarchical'        => false,
						'query_var'           => true,
						'supports'            => [ 'title' ],
						'has_archive'         => false,
						'show_in_nav_menus'   => true,
						'show_in_menu'        => 'point-of-sale',
						'show_in_rest'        => false,
					]
				)
			);
		}

		if ( ! post_type_exists( 'pos_grid' ) ) {
			register_post_type(
				'pos_grid',
				/**
				 * The pos_grid post type.
				 *
				 * @since 5.0.0
				 */
				apply_filters(
					'wc_pos_register_post_type_pos_grid',
					[
						'labels'              => [
							'name'                  => __( 'Grids', 'woocommerce-point-of-sale' ),
							'singular_name'         => __( 'Grid', 'woocommerce-point-of-sale' ),
							'all_items'             => __( 'Grids', 'woocommerce-point-of-sale' ),
							'menu_name'             => _x( 'Grids', 'Admin menu name', 'woocommerce-point-of-sale' ),
							'add_new'               => __( 'Add New', 'woocommerce-point-of-sale' ),
							'add_new_item'          => __( 'Add new grid', 'woocommerce-point-of-sale' ),
							'edit'                  => __( 'Edit', 'woocommerce-point-of-sale' ),
							'edit_item'             => __( 'Edit grid', 'woocommerce-point-of-sale' ),
							'new_item'              => __( 'New grid', 'woocommerce-point-of-sale' ),
							'view_item'             => __( 'View grid', 'woocommerce-point-of-sale' ),
							'view_items'            => __( 'View grids', 'woocommerce-point-of-sale' ),
							'search_items'          => __( 'Search grids', 'woocommerce-point-of-sale' ),
							'not_found'             => __( 'No grids found', 'woocommerce-point-of-sale' ),
							'not_found_in_trash'    => __( 'No grids found in trash', 'woocommerce-point-of-sale' ),
							'parent'                => __( 'Parent grid', 'woocommerce-point-of-sale' ),
							'featured_image'        => __( 'Grid image', 'woocommerce-point-of-sale' ),
							'set_featured_image'    => __( 'Set grid image', 'woocommerce-point-of-sale' ),
							'remove_featured_image' => __( 'Remove grid image', 'woocommerce-point-of-sale' ),
							'use_featured_image'    => __( 'Use as grid image', 'woocommerce-point-of-sale' ),
							'insert_into_item'      => __( 'Insert into grid', 'woocommerce-point-of-sale' ),
							'uploaded_to_this_item' => __( 'Uploaded to this grid', 'woocommerce-point-of-sale' ),
							'filter_items_list'     => __( 'Filter grids', 'woocommerce-point-of-sale' ),
							'items_list_navigation' => __( 'Grids navigation', 'woocommerce-point-of-sale' ),
							'items_list'            => __( 'Grids list', 'woocommerce-point-of-sale' ),
						],
						'description'         => __( 'This is where you can add new grids to the Point of Sale.', 'woocommerce-point-of-sale' ),
						'public'              => false,
						'show_ui'             => true,
						'capability_type'     => 'post',
						'map_meta_cap'        => true,
						'publicly_queryable'  => false,
						'exclude_from_search' => true,
						'hierarchical'        => false,
						'query_var'           => true,
						'supports'            => [ 'title' ],
						'has_archive'         => false,
						'show_in_nav_menus'   => true,
						'show_in_menu'        => 'point-of-sale',
						'show_in_rest'        => false,
					]
				)
			);
		}

		if ( ! post_type_exists( 'pos_receipt' ) ) {
			register_post_type(
				'pos_receipt',
				/**
				 * The pos_receipt post type.
				 *
				 * @since 5.0.0
				 */
				apply_filters(
					'wc_pos_register_post_type_pos_receipt',
					[
						'labels'              => [
							'name'                  => __( 'Receipts', 'woocommerce-point-of-sale' ),
							'singular_name'         => __( 'Receipt', 'woocommerce-point-of-sale' ),
							'all_items'             => __( 'Receipts', 'woocommerce-point-of-sale' ),
							'menu_name'             => _x( 'Receipts', 'Admin menu name', 'woocommerce-point-of-sale' ),
							'add_new'               => __( 'Add New', 'woocommerce-point-of-sale' ),
							'add_new_item'          => __( 'Add new receipt', 'woocommerce-point-of-sale' ),
							'edit'                  => __( 'Edit', 'woocommerce-point-of-sale' ),
							'edit_item'             => __( 'Edit receipt', 'woocommerce-point-of-sale' ),
							'new_item'              => __( 'New receipt', 'woocommerce-point-of-sale' ),
							'view_item'             => __( 'View receipt', 'woocommerce-point-of-sale' ),
							'view_items'            => __( 'View receipts', 'woocommerce-point-of-sale' ),
							'search_items'          => __( 'Search receipts', 'woocommerce-point-of-sale' ),
							'not_found'             => __( 'No receipts found', 'woocommerce-point-of-sale' ),
							'not_found_in_trash'    => __( 'No receipts found in trash', 'woocommerce-point-of-sale' ),
							'parent'                => __( 'Parent receipt', 'woocommerce-point-of-sale' ),
							'featured_image'        => __( 'Receipt image', 'woocommerce-point-of-sale' ),
							'set_featured_image'    => __( 'Set receipt image', 'woocommerce-point-of-sale' ),
							'remove_featured_image' => __( 'Remove receipt image', 'woocommerce-point-of-sale' ),
							'use_featured_image'    => __( 'Use as receipt image', 'woocommerce-point-of-sale' ),
							'insert_into_item'      => __( 'Insert into receipt', 'woocommerce-point-of-sale' ),
							'uploaded_to_this_item' => __( 'Uploaded to this receipt', 'woocommerce-point-of-sale' ),
							'filter_items_list'     => __( 'Filter receipts', 'woocommerce-point-of-sale' ),
							'items_list_navigation' => __( 'Receipts navigation', 'woocommerce-point-of-sale' ),
							'items_list'            => __( 'Receipts list', 'woocommerce-point-of-sale' ),
						],
						'description'         => __( 'This is where you can add new grids to the Point of Sale.', 'woocommerce-point-of-sale' ),
						'public'              => false,
						'show_ui'             => true,
						'capability_type'     => 'post',
						'map_meta_cap'        => true,
						'publicly_queryable'  => false,
						'exclude_from_search' => true,
						'hierarchical'        => false,
						'query_var'           => true,
						'supports'            => [ 'title' ],
						'has_archive'         => false,
						'show_in_nav_menus'   => true,
						'show_in_menu'        => 'point-of-sale',
						'show_in_rest'        => false,
					]
				)
			);
		}

		if ( ! post_type_exists( 'pos_session' ) ) {
			register_post_type(
				'pos_session',
				/**
				 * The pos_session post type.
				 *
				 * @since 5.0.0
				 */
				apply_filters(
					'wc_pos_register_post_type_pos_session',
					[
						'labels'              => [
							'name'                  => __( 'Sessions', 'woocommerce-point-of-sale' ),
							'singular_name'         => __( 'Session', 'woocommerce-point-of-sale' ),
							'all_items'             => __( 'Sessions', 'woocommerce-point-of-sale' ),
							'menu_name'             => _x( 'Sessions', 'Admin menu name', 'woocommerce-point-of-sale' ),
							'add_new'               => __( 'Add New', 'woocommerce-point-of-sale' ),
							'add_new_item'          => __( 'Add new session', 'woocommerce-point-of-sale' ),
							'edit'                  => __( 'Edit', 'woocommerce-point-of-sale' ),
							'edit_item'             => __( 'Edit session', 'woocommerce-point-of-sale' ),
							'new_item'              => __( 'New session', 'woocommerce-point-of-sale' ),
							'view_item'             => __( 'View session', 'woocommerce-point-of-sale' ),
							'view_items'            => __( 'View sessions', 'woocommerce-point-of-sale' ),
							'search_items'          => __( 'Search sessions', 'woocommerce-point-of-sale' ),
							'not_found'             => __( 'No sessions found', 'woocommerce-point-of-sale' ),
							'not_found_in_trash'    => __( 'No sessions found in trash', 'woocommerce-point-of-sale' ),
							'parent'                => __( 'Parent session', 'woocommerce-point-of-sale' ),
							'featured_image'        => __( 'Session image', 'woocommerce-point-of-sale' ),
							'set_featured_image'    => __( 'Set session image', 'woocommerce-point-of-sale' ),
							'remove_featured_image' => __( 'Remove session image', 'woocommerce-point-of-sale' ),
							'use_featured_image'    => __( 'Use as session image', 'woocommerce-point-of-sale' ),
							'insert_into_item'      => __( 'Insert into session', 'woocommerce-point-of-sale' ),
							'uploaded_to_this_item' => __( 'Uploaded to this session', 'woocommerce-point-of-sale' ),
							'filter_items_list'     => __( 'Filter sessions', 'woocommerce-point-of-sale' ),
							'items_list_navigation' => __( 'Sessions navigation', 'woocommerce-point-of-sale' ),
							'items_list'            => __( 'Sessions list', 'woocommerce-point-of-sale' ),
						],
						'description'         => __( 'This is where you can add new sessions to the Point of Sale.', 'woocommerce-point-of-sale' ),
						'public'              => false,
						'show_ui'             => true,
						'capability_type'     => 'post',
						'map_meta_cap'        => true,
						'publicly_queryable'  => false,
						'exclude_from_search' => true,
						'hierarchical'        => false,
						'query_var'           => true,
						'supports'            => [],
						'has_archive'         => false,
						'show_in_nav_menus'   => false,
						'show_in_menu'        => false,
						'show_in_rest'        => false,
					]
				)
			);
		}

		/**
		 * After registering custom POS post type.
		 *
		 * @since 5.0.0
		 */
		do_action( 'wc_pos_after_register_post_type' );
	}

	/**
	 * Flush rules if the event is queued.
	 *
	 * @since 5.0.0
	 */
	public static function maybe_flush_rewrite_rules() {
		if ( 'yes' === get_option( 'woocommerce_queue_flush_rewrite_rules' ) ) {
			update_option( 'woocommerce_queue_flush_rewrite_rules', 'no' );
			self::flush_rewrite_rules();
		}
	}

	/**
	 * Flush rewrite rules.
	 */
	public static function flush_rewrite_rules() {
		flush_rewrite_rules();
	}
}

WC_POS_Post_types::init();
