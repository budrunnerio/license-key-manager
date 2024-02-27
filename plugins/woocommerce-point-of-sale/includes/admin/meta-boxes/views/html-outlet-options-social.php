<?php
/**
 * Outlet social options panel.
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Meta_Boxes/Views
 */

$social_accoutns = $outlet_object->get_social_accounts( 'edit' );

defined( 'ABSPATH' ) || exit;
?>
<div id="social_outlet_options" class="panel woocommerce_options_panel">
	<div class="options_group">
		<p class="options_group_description"><?php esc_html_e( 'Enter the social details of the outlet as this will appear on receipts that are printed from registers at this outlet.', 'woocommerce-point-of-sale' ); ?></p>
		<?php
			// Twitter.
			woocommerce_wp_text_input(
				[
					'id'          => 'social_accounts_twitter',
					'name'        => 'social_accounts[twitter]',
					'label'       => __( 'Twitter', 'woocommerce-point-of-sale' ),
					'description' => __( 'The Twitter name of the outlet. E.g. for twitter.com/acme enter just "acme".', 'woocommerce-point-of-sale' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'value'       => isset( $social_accoutns['twitter'] ) ? $social_accoutns['twitter'] : '',
				]
			);

			// Facebook.
			woocommerce_wp_text_input(
				[
					'id'          => 'social_accounts_facebook',
					'name'        => 'social_accounts[facebook]',
					'label'       => __( 'Facebook', 'woocommerce-point-of-sale' ),
					'description' => __( 'The Facebook name of the outlet. E.g. for facebook.com/acme enter just "acme".', 'woocommerce-point-of-sale' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'value'       => isset( $social_accoutns['facebook'] ) ? $social_accoutns['facebook'] : '',
				]
			);

			// Instagram.
			woocommerce_wp_text_input(
				[
					'id'          => 'social_accounts_instagram',
					'name'        => 'social_accounts[instagram]',
					'label'       => __( 'Instagram', 'woocommerce-point-of-sale' ),
					'description' => __( 'The Instagram name of the outlet. E.g. for instagram.com/acme enter just "acme".', 'woocommerce-point-of-sale' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'value'       => isset( $social_accoutns['instagram'] ) ? $social_accoutns['instagram'] : '',
				]
			);

			// Snapchat.
			woocommerce_wp_text_input(
				[
					'id'          => 'social_accounts_snapchat',
					'name'        => 'social_accounts[snapchat]',
					'label'       => __( 'Snapchat', 'woocommerce-point-of-sale' ),
					'description' => __( 'The Snapchat name of the outlet. E.g. for snapchat.com/acme enter just "acme".', 'woocommerce-point-of-sale' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'value'       => isset( $social_accoutns['snapchat'] ) ? $social_accoutns['snapchat'] : '',
				]
			);

			/**
			 * Outlet social options.
			 *
			 * @since 5.0.0
			 */
			do_action( 'wc_pos_outlet_options_social', $thepostid );
			?>
	</div>
</div>
