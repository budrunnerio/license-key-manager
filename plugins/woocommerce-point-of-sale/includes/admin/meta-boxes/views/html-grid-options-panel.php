<?php
/**
 * Grid options meta box.
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Meta_Boxes/Views
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="grid_options" class="panel-wrap grid_options">
	<div id="general_grid_options" class="panel woocommerce_options_panel">
		<?php
			woocommerce_wp_select(
				[
					'id'          => 'sort_by',
					'value'       => $grid_object->get_sort_by( 'edit' ),
					'label'       => __( 'Default Sort Order', 'woocommerce-point-of-sale' ),
					/**
					 * Grid sort by options.
					 *
					 * @since 5.0.0
					 */
					'options'     => apply_filters(
						'wc_pos_grid_sort_by_options',
						[
							'name'   => __( 'Name', 'woocommerce-point-of-sale' ),
							'custom' => __( 'Custom ordering', 'woocommerce-point-of-sale' ),
						]
					),
					'desc_tip'    => true,
					'description' => __( 'Determines the sort order of the products on the POS page. If using custom ordering, you can drag and drop the products in this grid.', 'woocommerce-point-of-sale' ),
				]
			);
			?>
	</div>
	<?php
	/**
	 * Grid options panels.
	 *
	 * @since 5.0.0
	 */
	do_action( 'wc_pos_grid_options_panels', $thepostid, $grid_object );
	?>
	<div class="clear"></div>
</div>
