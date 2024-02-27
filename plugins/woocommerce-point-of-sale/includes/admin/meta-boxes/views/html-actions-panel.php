<?php
/**
 * Actions Panel
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Meta_Boxes/Views
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="submitbox">
	<div id="major-publishing-actions">
		<?php if ( ! wc_pos_is_default_post( $post->ID, $post->post_type ) ) : ?>
		<div id="delete-action">
			<a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>"><?php echo esc_html( $delete_text ); ?></a>
		</div>
		<?php endif; ?>
		<div id="publishing-action">
			<span class="spinner"></span>
			<?php if ( 'publish' === $submit_action ) : ?>
				<?php submit_button( __( 'Publish', 'woocommerce-point-of-sale' ), 'primary large', 'publish', false ); ?>
			<?php else : ?>
			<input name="save" type="submit" class="button button-primary button-large" id="publish" value="<?php esc_attr_e( 'Update', 'woocommerce-point-of-sale' ); ?>" />
			<?php endif; ?>
		</div>
		<div class="clear"></div>
	</div>
</div>
