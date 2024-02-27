<?php
/**
 * Product grids meta box.
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Meta_Boxes/Views
 *
 * @var array $product_grids
 * @var array $all_grids
 */

defined( 'ABSPATH' ) || exit;
?>
<?php if ( ! empty( $all_grids ) ) : ?>
	<div class="categorydiv" id="posgrid">
		<ul id="product_cat-tabs" class="category-tabs">
			<li class="tabs"><?php esc_html_e( 'All grids', 'woocommerce-point-of-sale' ); ?></li>
		</ul>
		<div class="tabs-panel">
			<ul class="categorychecklist form-no-clear">
				<?php foreach ( $all_grids as $grid ) : ?>
					<li>
						<label class="selectit">
							<input value="<?php echo esc_attr( $grid->ID ); ?>" name="product_grids[]" type="checkbox" <?php checked( in_array( $grid->ID, $product_grids, true ), true, true ); ?>><?php echo esc_html( $grid->post_title ); ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
<?php else : ?>
	<p>
	<?php
	/* translators: %s add new grid link */
	echo wp_kses_post( sprintf( __( '<a href="%s">Add product grids</a>', 'woocommerce-point-of-sale' ), esc_url( admin_url( 'post-new.php?post_type=pos_grid' ) ) ) );
	?>
	</p>
<?php endif; ?>
