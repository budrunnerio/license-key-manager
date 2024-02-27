<?php
/**
 * Outlet Options
 *
 * Display the outlet options meta box.
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Meta_Boxes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Meta_Box_Outlet_Options.
 */
class WC_POS_Meta_Box_Outlet_Options {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post Post object.
	 */
	public static function output( $post ) {
		global $thepostid, $outlet_object;

		$thepostid     = $post->ID;
		$outlet_object = wc_pos_get_outlet( $thepostid );

		wp_nonce_field( 'wc_pos_save_options', 'wc_pos_meta_nonce' );

		include 'views/html-outlet-options-panel.php';
	}

	public static function output_tabs() {
		global $thepostid, $post, $outlet_object;

		include 'views/html-outlet-options-address.php';
		include 'views/html-outlet-options-contact.php';
		include 'views/html-outlet-options-wireless.php';
		include 'views/html-outlet-options-social.php';
	}

	public static function get_outlet_options_tabs() {
		/**
		 * Outlet option tabs.
		 *
		 * @since 5.0.0
		 */
		return apply_filters(
			'wc_pos_outlet_options_tabs',
			[
				'address'  => [
					'label'  => __( 'Address', 'woocommerce-point-of-sale' ),
					'target' => 'address_outlet_options',
					'class'  => '',
				],
				'contact'  => [
					'label'  => __( 'Contact', 'woocommerce-point-of-sale' ),
					'target' => 'contact_outlet_options',
					'class'  => '',
				],
				'wireless' => [
					'label'  => __( 'WiFi', 'woocommerce-point-of-sale' ),
					'target' => 'wireless_outlet_options',
					'class'  => '',
				],
				'social'   => [
					'label'  => __( 'Social', 'woocommerce-point-of-sale' ),
					'target' => 'social_outlet_options',
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

		$outlet = new WC_POS_Outlet( $post_id );

		// Generate a unique post slug.
		$slug = wp_unique_post_slug( sanitize_title( $post->post_title ), $post_id, $post->post_status, $post->post_type, $post->post_parent );

		$base_country = WC()->countries->get_base_country();
		$base_state   = '*' === WC()->countries->get_base_state() ? '' : WC()->countries->get_base_state();

		// Country/state fields should not be empty.
		if ( empty( $_POST['country'] ) ) {
			$state   = $base_state;
			$country = $base_country;
		} elseif ( empty( $_POST['state'] ) ) {
			$state   = $base_state;
			$country = wc_clean( wp_unslash( $_POST['country'] ) );
		} else {
			$state   = wc_clean( wp_unslash( $_POST['state'] ) );
			$country = wc_clean( wp_unslash( $_POST['country'] ) );
		}

		/*
		 * At this point, the post_title has already been saved by wp_insert_post().
		 */
		$outlet->set_props(
			[
				'slug'            => $slug,
				'address_1'       => isset( $_POST['address_1'] ) ? wc_clean( wp_unslash( $_POST['address_1'] ) ) : '',
				'address_2'       => isset( $_POST['address_2'] ) ? wc_clean( wp_unslash( $_POST['address_2'] ) ) : '',
				'city'            => isset( $_POST['city'] ) ? wc_clean( wp_unslash( $_POST['city'] ) ) : '',
				'postcode'        => isset( $_POST['postcode'] ) ? wc_clean( wp_unslash( $_POST['postcode'] ) ) : '',
				'state'           => $state,
				'country'         => $country,
				'email'           => isset( $_POST['email'] ) ? wc_clean( wp_unslash( $_POST['email'] ) ) : '',
				'phone'           => isset( $_POST['phone'] ) ? wc_clean( wp_unslash( $_POST['phone'] ) ) : '',
				'fax'             => isset( $_POST['fax'] ) ? wc_clean( wp_unslash( $_POST['fax'] ) ) : '',
				'website'         => isset( $_POST['website'] ) ? wc_clean( wp_unslash( $_POST['website'] ) ) : '',
				'wifi_network'    => isset( $_POST['wifi_network'] ) ? wc_clean( wp_unslash( $_POST['wifi_network'] ) ) : '',
				'wifi_password'   => isset( $_POST['wifi_password'] ) ? wc_clean( wp_unslash( $_POST['wifi_password'] ) ) : '',
				'social_accounts' => isset( $_POST['social_accounts'] ) ? array_map( 'sanitize_text_field', $_POST['social_accounts'] ) : '',
			]
		);

		$outlet->save();

		/**
		 * Outlet options save.
		 *
		 * @since 5.0.0
		 */
		do_action( 'wc_pos_outlet_options_save', $post_id, $outlet );
	}
}
