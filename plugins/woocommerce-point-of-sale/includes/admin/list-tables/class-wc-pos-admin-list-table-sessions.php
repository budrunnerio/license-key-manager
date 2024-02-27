<?php
/**
 * List tables: sessions.
 *
 * @package WooCommerce_Point_Of_Sale/Clases/Admin/List_Tables
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Admin_List_Table', false ) ) {
	include_once WC_ABSPATH . '/includes/admin/list-tables/abstract-class-wc-admin-list-table.php';
}

/**
 * WC_Admin_List_Table_Sessions.
 */
class WC_POS_Admin_List_Table_Sessions extends WC_Admin_List_Table {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $list_table_type = 'pos_session';

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
		return 'session';
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
			'register' => 'register',
			'outlet'   => 'outlet',
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

		$show_columns              = [];
		$show_columns['cb']        = '<input type="checkbox" />';
		$show_columns['register']  = esc_html__( 'Reigser', 'woocommerce-point-of-sale' );
		$show_columns['outlet']    = esc_html__( 'Outlet', 'woocommerce-point-of-sale' );
		$show_columns['opened_at'] = esc_html__( 'Opened at', 'woocommerce-point-of-sale' );
		$show_columns['closed_at'] = esc_html__( 'Closed at', 'woocommerce-point-of-sale' );
		$show_columns['opened_by'] = esc_html__( 'Opened by', 'woocommerce-point-of-sale' );
		$show_columns['closed_by'] = esc_html__( 'Closed by', 'woocommerce-point-of-sale' );
		$show_columns['sales']     = esc_html__( 'Sales', 'woocommerce-point-of-sale' );

		return array_merge( $show_columns, $columns );
	}

	/**
	 * Pre-fetch any data for the row each column has access to it. the_session global is there for bw compat.
	 *
	 * @param int $post_id Post ID being shown.
	 */
	protected function prepare_row_data( $post_id ) {
		global $the_session;

		if ( empty( $this->object ) || $this->object->get_id() !== $post_id ) {
			$the_session  = wc_pos_get_session( $post_id );
			$this->object = $the_session;
		}
	}

	/**
	 * Render blank state.
	 */
	protected function render_blank_state() {
		echo '<div class="woocommerce-BlankState">';
		echo '<h2 class="woocommerce-BlankState-message">There is no closed sessions yet.</h2>';
		echo '</div>';
	}

	/**
	 * Render column: register.
	 */
	protected function render_register_column() {
		$register = $this->object->get_register_id();
		$register = $register ? wc_pos_get_register( $register ) : $register;

		if ( $register ) {
			echo '<a href="' . esc_url( admin_url( "post.php?post={$register->get_id()}&action=edit" ) ) . '">' . esc_html( $register->get_name() ) . '</a>';
		} else {
			$session_data = $this->object->get_session_data();
			echo empty( $session_data['register'] ) ? esc_html__( 'Deleted Register', 'woocommerce-point-of-sale' ) : esc_html( $session_data['register'] );
		}
	}
}
