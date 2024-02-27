<?php
/**
 * Register Options
 *
 * Display the register options meta box.
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Meta_Boxes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Meta_Box_Register_Options.
 */
class WC_POS_Meta_Box_Register_Options {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post Post object.
	 */
	public static function output( $post ) {
		global $thepostid, $register_object;

		$thepostid       = $post->ID;
		$register_object = wc_pos_get_register( $thepostid );

		wp_nonce_field( 'wc_pos_save_options', 'wc_pos_meta_nonce' );

		include 'views/html-register-options-panel.php';
	}

	public static function output_tabs() {
		global $thepostid, $post, $register_object;

		include 'views/html-register-options-general.php';
		include 'views/html-register-options-end-of-sale.php';
	}

	public static function get_register_options_tabs() {
		/**
		 * Register option tabs.
		 *
		 * @since 5.0.0
		 */
		return apply_filters(
			'wc_pos_register_options_tabs',
			[
				'general'     => [
					'label'  => __( 'General', 'woocommerce-point-of-sale' ),
					'target' => 'general_register_options',
					'class'  => '',
				],
				'end_of_sale' => [
					'label'  => __( 'End of Sale', 'woocommerce-point-of-sale' ),
					'target' => 'end_of_sale_register_options',
					'class'  => '',
				],
			]
		);
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

		$register = new WC_POS_Register( $post_id );

		// Generate a unique post slug.
		$slug = wp_unique_post_slug( sanitize_title( $post->post_title ), $post_id, $post->post_status, $post->post_type, $post->post_parent );

		/*
		 * At this point, the post_title has already been saved by wp_insert_post().
		 */
		$register->set_props(
			[
				'slug'            => $slug,
				'grid'            => isset( $_POST['grid'] ) ? absint( $_POST['grid'] ) : 0,
				'receipt'         => isset( $_POST['receipt'] ) ? absint( $_POST['receipt'] ) : 0,
				'grid_layout'     => isset( $_POST['grid_layout'] ) ? wc_clean( wp_unslash( $_POST['grid_layout'] ) ) : 'rectangular',
				'prefix'          => isset( $_POST['prefix'] ) ? wc_clean( wp_unslash( $_POST['prefix'] ) ) : '',
				'suffix'          => isset( $_POST['suffix'] ) ? wc_clean( wp_unslash( $_POST['suffix'] ) ) : '',
				'outlet'          => isset( $_POST['outlet'] ) ? absint( $_POST['outlet'] ) : 0,
				'customer'        => isset( $_POST['customer'] ) ? (int) $_POST['customer'] : 0,
				'cash_management' => isset( $_POST['cash_management'] ),
				'dining_option'   => isset( $_POST['dining_option'] ) ? wc_clean( wp_unslash( $_POST['dining_option'] ) ) : 'none',
				'default_mode'    => isset( $_POST['default_mode'] ) ? wc_clean( wp_unslash( $_POST['default_mode'] ) ) : 'search',
				'change_user'     => isset( $_POST['change_user'] ),
				'email_receipt'   => isset( $_POST['email_receipt'] ) ? wc_clean( wp_unslash( $_POST['email_receipt'] ) ) : 'no',
				'print_receipt'   => isset( $_POST['print_receipt'] ),
				'gift_receipt'    => isset( $_POST['gift_receipt'] ),
				'note_request'    => isset( $_POST['note_request'] ) ? wc_clean( wp_unslash( $_POST['note_request'] ) ) : 'none',
			]
		);

		$register->save();

		/**
		 * Register options save.
		 *
		 * @since 5.0.0
		 */
		do_action( 'wc_pos_register_options_save', $post_id, $register );
	}
}
