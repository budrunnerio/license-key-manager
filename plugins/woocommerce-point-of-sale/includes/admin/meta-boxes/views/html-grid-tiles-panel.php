<?php
/**
 * Grid tiles meta box.
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Meta_Boxes/Views
 */

defined( 'ABSPATH' ) || exit;

$tiles = $grid_object->get_tiles();

?>
<div class="wc-pos-grid-tiles-wrapper">
	<table cellpadding="0" cellspacing="0" class="wc-pos-grid-tiles">
		<thead>
			<tr>
				<th class="handle" width="1%">&nbsp</th>
				<th class="tile" colspan="2"><?php esc_html_e( 'Tile', 'woocommerce-point-of-sale' ); ?></th>
				<th class="type center" width="1%"><span class="wc-pos-grid-tile-type tips" data-tip="<?php esc_attr_e( 'Tile Type', 'woocommerce-point-of-sale' ); ?>"></span></th>
				<th class="wc-pos-grid-edit-tile" width="1%">&nbsp;</th>
			</tr>
		</thead>
		<tbody id="grid_tiles">
			<?php
			foreach ( $tiles as $tile_id => $tile ) {
				/**
				 * Before grid title HTML.
				 *
				 * @since 5.0.0
				 */
				do_action( 'wc_pos_before_grid_tile_html', $tile_id, $tile, $grid_object );

				include 'html-grid-tile.php';

				/**
				 * After grid title HTML.
				 *
				 * @since 5.0.0
				 */
				do_action( 'wc_pos_grid_tile_html', $tile_id, $tile, $grid_object );
			}
			?>
		</tbody>
	</table>
</div>

<?php if ( empty( $tiles ) ) : ?>
<div class="wc-pos-grid-tiles-row no-tiles">
	<p><?php esc_html_e( 'Tiles let you define custom grids for your register. You can add a tile that represents a product or a category.', 'woocommerce-point-of-sale' ); ?></p>
</div>
<?php endif; ?>

<div class="wc-pos-grid-tiles-row wc-pos-grid-tiles-actions">
	<button type="button" class="button add-tile"><?php esc_html_e( 'Add Tile', 'woocommerce-point-of-sale' ); ?></button>
	<button type="button" class="button button-primary delete-all-tiles"><?php esc_html_e( 'Delete All Tiles', 'woocommerce-point-of-sale' ); ?></button>
</div>

<script type="text/template" id="tmpl-wc-pos-modal-add-tile">
	<div class="wc-backbone-modal" id="wc-pos-modal-add-tile">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php esc_html_e( 'Add tile', 'woocommerce-point-of-sale' ); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'woocommerce-point-of-sale' ); ?></span>
					</button>
				</header>
				<article>
					<form action="" method="post">
						<table class="widefat">
							<tbody>
								<tr>
									<td width="50%">
									<?php
										// Type.
										woocommerce_wp_select(
											[
												'id'      => 'tile_type',
												'label'   => null,
												/**
												 * Filter: wc_pos_grid_tile_type_options.
												 *
												 * @since 5.0.0
												 */
												'options' => apply_filters(
													'wc_pos_grid_tile_type_options',
													[
														'product'     => __( 'Product', 'woocommerce-point-of-sale' ),
														'product_cat' => __( 'Product Category', 'woocommerce-point-of-sale' ),
													]
												),
											]
										);
										?>
									</td>
									<td>
										<?php
										// Product.
										woocommerce_wp_select(
											[
												'class'   => 'wc-product-search',
												'id'      => 'product_id',
												'label'   => null,
												'options' => [],
												'custom_attributes' => [
													'data-allow_clear'   => 'true',
													'data-display_stock' => 'false',
													'data-placeholder'   => esc_attr__( 'Search for a product&hellip;', 'woocommerce-point-of-sale' ),
												],
											]
										);
										// Product Category.
										woocommerce_wp_select(
											[
												'class'   => 'wc-category-search',
												'id'      => 'product_cat',
												'label'   => null,
												'options' => [],
												'custom_attributes' => [
													'data-allow_clear'   => 'true',
													'data-placeholder'   => esc_attr__( 'Search for a category&hellip;', 'woocommerce-point-of-sale' ),
												],
											]
										);
										?>
									</td>
								</tr>
							</tbody>
						</table>
					</form>
				</article>
				<footer>
					<div class="inner">
						<button id="btn-ok" class="button button-primary button-large"><?php esc_html_e( 'Add', 'woocommerce-point-of-sale' ); ?></button>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
