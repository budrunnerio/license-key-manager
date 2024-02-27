<?php
/**
 * List tables: receipts.
 *
 * @package WooCommerce_Point_Of_Sale/Clases/Admin/List_Tables
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Admin_List_Table', false ) ) {
	include_once WC_ABSPATH . '/includes/admin/list-tables/abstract-class-wc-admin-list-table.php';
}

/**
 * WC_Admin_List_Table_Receipts.
 */
class WC_POS_Admin_List_Table_Receipts extends WC_Admin_List_Table {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $list_table_type = 'pos_receipt';

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
		return 'receipt';
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

		if ( wc_pos_is_default_receipt( $this->object->get_id() ) ) {
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
			'receipt' => 'title',
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

		$show_columns                     = [];
		$show_columns['cb']               = '<input type="checkbox" />';
		$show_columns['receipt']          = esc_html__( 'Receipt', 'woocommerce-point-of-sale' );
		$show_columns['num_copies']       = esc_html__( 'Number of Copies', 'woocommerce-point-of-sale' );
		$show_columns['width']            = esc_html__( 'Print Width', 'woocommerce-point-of-sale' );
		$show_columns['show_tax_summary'] = esc_html__( 'Show Tax Summary', 'woocommerce-point-of-sale' );

		return array_merge( $show_columns, $columns );
	}

	/**
	 * Pre-fetch any data for the row each column has access to it. the_receipt global is there for bw compat.
	 *
	 * @param int $post_id Post ID being shown.
	 */
	protected function prepare_row_data( $post_id ) {
		global $the_receipt;

		if ( empty( $this->object ) || $this->object->get_id() !== $post_id ) {
			$the_receipt  = wc_pos_get_receipt( $post_id );
			$this->object = $the_receipt;
		}
	}

	/**
	 * Render column: receipt.
	 */
	protected function render_receipt_column() {
		$name   = empty( $this->object->get_name() ) ? __( '(no-title)', 'woocommerce-point-of-sale' ) : $this->object->get_name();
		$output = '<strong><a href="' . esc_url( admin_url( "post.php?post={$this->object->get_id()}&action=edit" ) ) . '">' . esc_html( $name ) . '</a></strong>';

		/*
		 * We cannot modify the cb column for some reason. So we will put this tooltip here and
		 * move it via JavaScript to the cb column.
		 */
		if ( wc_pos_is_default_receipt( $this->object->get_id() ) ) {
			$output .= wc_help_tip( __( 'This is the default receipt and it cannot be deleted.', 'woocommerce-point-of-sale' ) );

			wc_enqueue_js(
				"(function( $ ) {
					'use strict';
					var receipt = $( 'tr#post-" . absint( $this->object->get_id() ) . "' );
					receipt.find( 'th' ).empty();
					receipt.find( 'td.receipt .woocommerce-help-tip' ).detach( '.woocommerce-help-tip' ).appendTo( receipt.find( 'th' ) );
				})( jQuery );"
			);
		}

		echo wp_kses_post( $output );
	}

	/**
	 * Render column: num_copies.
	 */
	protected function render_num_copies_column() {
		$print_copies = $this->object->get_print_copies();

		if ( 'per_category' === $print_copies ) {
			echo esc_html__( 'Per Category', 'woocommerce-point-of-sale' );
		} elseif ( 'per_quantity' === $print_copies ) {
			echo esc_html__( 'Per Quantity', 'woocommerce-point-of-sale' );
		} else {
			echo esc_html( $this->object->get_num_copies() );
		}
	}

	/**
	 * Render column: width.
	 */
	protected function render_width_column() {
		echo $this->object->get_width() ? esc_html( $this->object->get_width() ) . ' ' . esc_html_x( 'mm', 'Millimeter', 'woocommerce-point-of-sale' ) : esc_html__( 'Dynamic', 'woocommerce-point-of-sale' );
	}

	/**
	 * Render column: show_tax_summary.
	 */
	protected function render_show_tax_summary_column() {
		echo $this->object->get_show_tax_summary() ? esc_html__( 'Yes', 'woocommerce-point-of-sale' ) : esc_html__( 'No', 'woocommerce-point-of-sale' );
	}
}
