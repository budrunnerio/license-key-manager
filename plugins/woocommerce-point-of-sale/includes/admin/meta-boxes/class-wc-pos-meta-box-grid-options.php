<?php
/**
 * Grid Options
 *
 * Display the grid options meta box.
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Meta_Boxes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Meta_Box_Grid_Options.
 */
class WC_POS_Meta_Box_Grid_Options {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post Post object.
	 */
	public static function output( $post ) {
		global $thepostid, $grid_object;

		$thepostid   = $post->ID;
		$grid_object = wc_pos_get_grid( $thepostid );

		wp_nonce_field( 'wc_pos_save_options', 'wc_pos_meta_nonce' );

		include 'views/html-grid-options-panel.php';
	}

	/**
	 * Save meta box data.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public static function save( $post_id, $post ) {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'update-post_' . $post_id ) ) {
			wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'woocommerce-point-of-sale' ) );
		}

		$grid = new WC_POS_Grid( $post_id );

		// Generate a unique post slug.
		$slug = wp_unique_post_slug( sanitize_title( $post->post_title ), $post_id, $post->post_status, $post->post_type, $post->post_parent );

		/*
		 * At this point, the post_title has already been saved by wp_insert_post().
		 */

		$grid->set_props(
			[
				'slug'    => $slug,
				'sort_by' => isset( $_POST['sort_by'] ) ? wc_clean( wp_unslash( $_POST['sort_by'] ) ) : 'name',
			]
		);

		$grid->save();

		/**
		 * Grid options save.
		 *
		 * @since 5.0.0
		 */
		do_action( 'wc_pos_grid_options_save', $post_id, $grid );
	}
}
