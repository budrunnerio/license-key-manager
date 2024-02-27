<?php
/**
 * List tables: grids.
 *
 * @package WooCommerce_Point_Of_Sale/Clases/Admin/List_Tables
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Admin_List_Table', false ) ) {
	include_once WC_ABSPATH . '/includes/admin/list-tables/abstract-class-wc-admin-list-table.php';
}

/**
 * WC_Admin_List_Table_Grids.
 */
class WC_POS_Admin_List_Table_Grids extends WC_Admin_List_Table {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $list_table_type = 'pos_grid';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Define primary column.
	 *
	 * @return string
	 */
	protected function get_primary_column() {
		return 'grid';
	}

	/**
	 * Get row actions to show in the list table.
	 *
	 * @param array   $actions Array of actions.
	 * @param WP_Post $post Current post object.
	 *
	 * @return array
	 */
	protected function get_row_actions( $actions, $post ) {
		unset( $actions['inline hide-if-no-js'] );

		if ( ! current_user_can( 'manage_woocommerce_point_of_sale' ) ) {
			$actions = [];
		}

		return $actions;
	}

	/**
	 * Define which columns are sortable.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function define_sortable_columns( $columns ) {
		$custom = [
			'grid'  => 'title',
			'tiles' => 'tiles',
		];

		return wp_parse_args( $custom, $columns );
	}

	/**
	 * Define which columns to show on this screen.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function define_columns( $columns ) {
		if ( empty( $columns ) && ! is_array( $columns ) ) {
			$columns = [];
		}

		// Remove default CPT columns.
		unset( $columns['title'], $columns['comments'], $columns['date'] );

		$show_columns          = [];
		$show_columns['cb']    = '<input type="checkbox" />';
		$show_columns['grid']  = esc_html__( 'Grid', 'woocommerce-point-of-sale' );
		$show_columns['tiles'] = esc_html__( 'Tiles', 'woocommerce-point-of-sale' );

		return array_merge( $show_columns, $columns );
	}

	/**
	 * Pre-fetch any data for the row each column has access to it. the_grid global is there for bw compat.
	 *
	 * @param int $post_id Post ID being shown.
	 */
	protected function prepare_row_data( $post_id ) {
		global $the_grid;

		if ( empty( $this->object ) || $this->object->get_id() !== $post_id ) {
			$the_grid     = wc_pos_get_grid( $post_id );
			$this->object = $the_grid;
		}
	}

	/**
	 * Render blank state.
	 */
	protected function render_blank_state() {
		echo '<div class="woocommerce-BlankState">';
		echo '<h2 class="woocommerce-BlankState-message"></h2>';
		echo '<a class="woocommerce-BlankState-cta button-primary button" href="' . esc_url( admin_url( 'post-new.php?post_type=pos_grid' ) ) . '">' . esc_html__( 'Create your first product grid', 'woocommerce-point-of-sale' ) . '</a>';
		echo '<a class="woocommerce-BlankState-cta button" target="_blank" href="#">' . esc_html__( 'Learn more about grids', 'woocommerce-point-of-sale' ) . '</a>';
		echo '</div>';
	}

	/**
	 * Render column: grid.
	 */
	protected function render_grid_column() {
		$name = empty( $this->object->get_name() ) ? __( '(no-title)', 'woocommerce-point-of-sale' ) : $this->object->get_name();
		echo '<strong><a href="' . esc_url( admin_url( "post.php?post={$this->object->get_id()}&action=edit" ) ) . '">' . esc_html( $name ) . '</a></strong>';
	}

	/**
	 * Render column: tiles.
	 */
	protected function render_tiles_column() {
		global $wpdb;

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
				FROM {$wpdb->prefix}wc_pos_grid_tiles
				WHERE grid_id = %d
				",
				$this->object->get_id()
			)
		);

		echo esc_html( $count );
	}

		/**
		 * Handle any custom filters.
		 *
		 * @param array $query_vars Query vars.
		 * @return array
		 */
	protected function query_filters( $query_vars ) {
		// Custom order by arguments.
		if ( isset( $query_vars['orderby'] ) ) {
			$orderby = strtolower( $query_vars['orderby'] );
			$order   = isset( $query_vars['order'] ) ? strtoupper( $query_vars['order'] ) : 'DESC';

			if ( 'tiles' === $orderby ) {
				$callback = 'DESC' === $order ? 'order_by_tiles_desc_post_clauses' : 'order_by_tiles_asc_post_clauses';
				add_filter( 'posts_clauses', [ $this, $callback ] );
			}
		}

		return $query_vars;
	}

	/**
	 * Handle desc sorting by number of tiles.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function order_by_tiles_desc_post_clauses( $args ) {
		global $wpdb;

		$args['orderby']  = " (SELECT COUNT(*) FROM {$wpdb->prefix}wc_pos_grid_tiles tiles_lookup";
		$args['orderby'] .= " WHERE tiles_lookup.grid_id = {$wpdb->posts}.ID) DESC";

		return $args;
	}

	/**
	 * Handle asc sorting by number of tile.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function order_by_tiles_asc_post_clauses( $args ) {
		global $wpdb;

		$args['orderby']  = " (SELECT COUNT(*) FROM {$wpdb->prefix}wc_pos_grid_tiles";
		$args['orderby'] .= " WHERE {$wpdb->prefix}wc_pos_grid_tiles.grid_id = {$wpdb->posts}.ID) ASC";

		return $args;
	}
}
