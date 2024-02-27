<?php
/**
 * List tables: outlets.
 *
 * @package WooCommerce_Point_Of_Sale/Clases/Admin/List_Tables
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Admin_List_Table', false ) ) {
	include_once WC_ABSPATH . '/includes/admin/list-tables/abstract-class-wc-admin-list-table.php';
}

/**
 * WC_Admin_List_Table_Outlets.
 */
class WC_POS_Admin_List_Table_Outlets extends WC_Admin_List_Table {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $list_table_type = 'pos_outlet';

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
		return 'outlet';
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

		if ( wc_pos_is_default_outlet( $this->object->get_id() ) ) {
			unset( $actions['trash'] );
		}

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
			'outlet'    => 'title',
			'registers' => 'registers',
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
		$show_columns['outlet']    = esc_html__( 'Outlet', 'woocommerce-point-of-sale' );
		$show_columns['contact']   = esc_html__( 'Contact Details', 'woocommerce-point-of-sale' );
		$show_columns['registers'] = esc_html__( 'Registers', 'woocommerce-point-of-sale' );

		return array_merge( $show_columns, $columns );
	}

	/**
	 * Pre-fetch any data for the row each column has access to it. the_outlet global is there for bw compat.
	 *
	 * @param int $post_id Post ID being shown.
	 */
	protected function prepare_row_data( $post_id ) {
		global $the_outlet;

		if ( empty( $this->object ) || $this->object->get_id() !== $post_id ) {
			$the_outlet   = wc_pos_get_outlet( $post_id );
			$this->object = $the_outlet;
		}
	}

	/**
	 * Render column: outlet.
	 */
	protected function render_outlet_column() {
		$name   = empty( $this->object->get_name() ) ? __( '(no-title)', 'woocommerce-point-of-sale' ) : $this->object->get_name();
		$output = '<strong><a href="' . esc_url( admin_url( "post.php?post={$this->object->get_id()}&action=edit" ) ) . '">' . esc_html( $name ) . '</a></strong>';

		// Show outlet address.
		$address = [
			'address_1' => $this->object->get_address_1(),
			'address_2' => $this->object->get_address_2(),
			'city'      => $this->object->get_city(),
			'postcode'  => $this->object->get_postcode(),
			'state'     => empty( $this->object->get_state() ) ? $this->object->get_state() : '',
			'country'   => $this->object->get_country(),
		];

		$output .= '<div class="meta address"><a href="' . esc_url( 'https://www.google.com/maps/search/' . str_replace( ' ', '+', WC()->countries->get_formatted_address( $address, '+' ) ) ) . '" target="_blank">' . WC()->countries->get_formatted_address( $address ) . '</a></div>';

		/*
		 * We cannot modify the cb column for some reason. So we will put this tooltip here and
		 * move it via JavaScript to the cb column.
		 */
		if ( wc_pos_is_default_outlet( $this->object->get_id() ) ) {
			$output .= wc_help_tip( __( 'This is the default outlet and it cannot be deleted.', 'woocommerce-point-of-sale' ) );

			wc_enqueue_js(
				"(function( $ ) {
					'use strict';
					var outlet = $( 'tr#post-" . absint( $this->object->get_id() ) . "' );
					outlet.find( 'th' ).empty();
					outlet.find( 'td.outlet .woocommerce-help-tip' ).detach( '.woocommerce-help-tip' ).appendTo( outlet.find( 'th' ) );
				})( jQuery );"
			);
		}

		echo wp_kses_post( $output );
	}

	/**
	 * Render column: contact.
	 */
	protected function render_contact_column() {
		$output = '';

		if ( ! empty( $this->object->get_phone() ) ) {
			$output .= '<small class="meta phone"><a href="tel:' . esc_attr( $this->object->get_phone() ) . '">' . esc_html( $this->object->get_phone() ) . '</a></small>';
		}

		if ( ! empty( $this->object->get_email() ) ) {
			$output .= '<small class="meta email"><a href="mailto:' . esc_attr( $this->object->get_email() ) . '">' . esc_html( $this->object->get_email() ) . '</a></small>';
		}

		if ( ! empty( $this->object->get_website() ) ) {
			$output .= '<small class="meta website"><a href="' . esc_attr( $this->object->get_website() ) . '" target="_blank">' . esc_html( $this->object->get_website() ) . '</a></small>';
		}

		echo wp_kses_post( $output );
	}

	/**
	 * Render column: registers.
	 */
	protected function render_registers_column() {
		global $wpdb;

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
				FROM $wpdb->posts p
				LEFT JOIN $wpdb->postmeta pm
				ON p.ID = pm.post_id
				WHERE p.post_type = 'pos_register' AND pm.meta_key = 'outlet' AND pm.meta_value = %d
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

			if ( 'registers' === $orderby ) {
				$callback = 'DESC' === $order ? 'order_by_registers_desc_post_clauses' : 'order_by_registers_asc_post_clauses';
				add_filter( 'posts_clauses', [ $this, $callback ] );
			}
		}

		return $query_vars;
	}

	/**
	 * Handle desc sorting by number of registers.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function order_by_registers_desc_post_clauses( $args ) {
		global $wpdb;

		$args['orderby']  = " (SELECT COUNT(*) FROM {$wpdb->posts} registers_lookup";
		$args['orderby'] .= " LEFT JOIN {$wpdb->postmeta} registers_postmeta_lookup ON registers_lookup.ID = registers_postmeta_lookup.post_id";
		$args['orderby'] .= " WHERE registers_lookup.post_type = 'pos_register' AND registers_postmeta_lookup.meta_key = 'outlet' AND registers_postmeta_lookup.meta_value = {$wpdb->posts}.ID) DESC";

		return $args;
	}

	/**
	 * Handle asc sorting by number of registers.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function order_by_registers_asc_post_clauses( $args ) {
		global $wpdb;

		$args['orderby']  = " (SELECT COUNT(*) FROM {$wpdb->posts} registers_lookup";
		$args['orderby'] .= " LEFT JOIN {$wpdb->postmeta} registers_postmeta_lookup ON registers_lookup.ID = registers_postmeta_lookup.post_id";
		$args['orderby'] .= " WHERE registers_lookup.post_type = 'pos_register' AND registers_postmeta_lookup.meta_key = 'outlet' AND registers_postmeta_lookup.meta_value = {$wpdb->posts}.ID) ASC";

		return $args;
	}
}
