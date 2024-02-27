<?php
/**
 * Product Bundle add-to-cart buttons wrapper template
 *
 * @package Self-service Dashboard for WooCommerce Subscriptions
 * @version 6.4.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="cart bundle_data bundle_data_<?php echo absint( $product_id ); ?>" data-bundle_form_data="<?php echo esc_attr( json_encode( $bundle_form_data ) ); ?>" data-bundle_id="<?php echo absint( $product_id ); ?>">
	<?php if ( $is_purchasable ) { ?>
	<div class="bundle_wrap">
		<div class="bundle_price"></div>
		<?php
		/**
		 * 'woocommerce_bundles_after_bundle_price' action.
		 *
		 * @since 6.7.6
		 */
		do_action( 'woocommerce_after_bundle_price' );
		?>
		<div class="bundle_error" style="display:none">
			<div class="woocommerce-info">
				<ul class="msg"></ul>
			</div>
		</div>
		<?php
		/**
		 * 'woocommerce_bundles_after_bundle_price' action.
		 *
		 * @since 6.7.6
		 */
		do_action( 'woocommerce_before_bundle_availability' );
		?>
		<div class="bundle_availability">
			<?php echo wp_kses_post( $availability_html ); ?>
		</div>
		<?php } else { ?>
			<div class="bundle_unavailable woocommerce-info">
				<?php echo wp_kses_post( $purchasable_notice ); ?>
			</div>
		<?php } ?>
	</div>
</div>
