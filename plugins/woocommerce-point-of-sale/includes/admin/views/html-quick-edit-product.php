<?php
/**
 * Admin View: Quick Edit Product
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Views
 */

defined( 'ABSPATH' ) || exit;
?>
<fieldset class="inline-edit-col-left clear">
	<div id="visibility-fields" class="inline-edit-col">
		<h4><?php esc_html_e( 'Point of Sale', 'woocommerce-point-of-sale' ); ?></h4>
		<div class="inline-edit-group">
			<label class="alignleft">
				<span class="title"><?php esc_html_e( 'Visibility', 'woocommerce-point-of-sale' ); ?></span>
				<span class="input-text-wrap">
					<select class="pos_visibility" name="_pos_visibility">
					<?php
					$pos_visibility = get_post_meta( $post->ID, '_pos_visibility', true );
					$pos_visibility = $pos_visibility ? $pos_visibility : 'pos_online';

					/**
					 * Visibility options.
					 *
					 * @since 5.0.0
					 */
					$visibility_options = apply_filters(
						'wc_pos_visibility_options',
						[
							'pos_online' => __( 'POS &amp; Online', 'woocommerce-point-of-sale' ),
							'pos'        => __( 'POS Only', 'woocommerce-point-of-sale' ),
							'online'     => __( 'Online Only', 'woocommerce-point-of-sale' ),
						]
					);

					foreach ( $visibility_options as $key => $value ) {
						echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $pos_visibility, false ) . '>' . esc_html( $value ) . '</option>';
					}
					?>
					</select>
				</span>
			</label>
		</div>
	</div>
</fieldset>
