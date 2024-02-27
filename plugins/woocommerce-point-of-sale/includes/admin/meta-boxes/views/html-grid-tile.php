<?php
/**
 * Grid Tile
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Meta_Boxes/Views
 */

defined( 'ABSPATH' ) || exit;

if ( 'product' === $tile['type'] ) {
	$tile_type = __( 'Product', 'woocommerce-point-of-sale' );
	$product   = wc_get_product( $tile['item_id'] );
	$name      = $product ? $product->get_name() : __( 'Deleted Product', 'woocommerce-point-of-sale' );
	$tile_link = $product ? admin_url( 'post.php?post=' . $tile['item_id'] . '&action=edit' ) : '';
	$thumb     = $product ? $product->get_image(
		'thumbnail',
		[
			'alt'   => __( 'Thumbnail', 'woocommerce-point-of-sale' ),
			'title' => '',
		],
		false
	) : '';

	$meta_fields = [];
	$sku         = $product ? $product->get_sku() : '';
	if ( ! empty( $sku ) ) {
		$meta_fields[] = [
			'name'  => __( 'SKU', 'woocommerce-point-of-sale' ),
			'value' => $sku,
		];
	}
}

if ( 'product_cat' === $tile['type'] ) {
	$tile_type = __( 'Product Category', 'woocommerce-point-of-sale' );
	$tile_term = get_term( $tile['item_id'] );
	$name      = $tile_term ? $tile_term->name : __( 'Deleted Category', 'woocommerce-point-of-sale' );
	$tile_link = $tile_term ? admin_url( 'term.php?taxonomy=product_cat&tag_ID=' . $tile['item_id'] . '&post_type=product' ) : '';
	$thumb_id  = get_term_meta( $tile['item_id'], 'thumbnail_id', true );
	$thumb     = $thumb_id ? wp_get_attachment_image( $thumb_id, 'thumbnail' ) : '';

	$meta_fields = [];
	$count       = $tile_term ? $tile_term->count : '';
	if ( ! empty( $count ) ) {
		$meta_fields[] = [
			'name'  => __( 'Products Count', 'woocommerce-point-of-sale' ),
			'value' => $count,
		];
	}
}
?>
<tr class="wc-pos-grid-tile" data-grid-tile-id="<?php echo esc_attr( $tile_id ); ?>">
	<td class="handle"></td>
	<td class="thumb">
		<div class="wc-pos-grid-tile-thumbnail"><?php echo wp_kses_post( $thumb ); ?></div>
	</td>
	<td class="tile">
		<?php echo empty( $tile_link ) ? esc_html( $name ) : '<a href="' . esc_url( $tile_link ) . '">' . esc_html( $name ) . '</a>'; ?>
		<?php foreach ( $meta_fields as $meta ) : ?>
		<div class="wc-pos-grid-tile-meta"><strong><?php echo esc_html( $meta['name'] ); ?>:</strong> <?php echo esc_html( $meta['value'] ); ?></div>
		<?php endforeach; ?>
	</td>
	<td class="type center"><span class="wc-pos-grid-tile-type <?php echo esc_attr( $tile['type'] ); ?> tips" data-tip="<?php esc_attr( $tile_type ); ?>"></span></td>
	<td class="wc-pos-grid-edit-tile" width="1%">
		<div class="wc-pos-grid-tile-actions">
			<a class="delete-tile tips" href="#" data-tip="<?php esc_attr_e( 'Delete tile', 'woocommerce-point-of-sale' ); ?>"></a>
		</div>
	</td>
</tr>
