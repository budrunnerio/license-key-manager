<?php
/**
 * Product Grids
 *
 * Display the product grids meta box.
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Meta_Boxes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Meta_Box_Product_Grids.
 */
class WC_POS_Meta_Box_Product_Grids {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post Post object.
	 */
	public static function output( $post ) {
		global $thepostid, $grid_object;

		$thepostid     = $post->ID;
		$product_grids = wc_pos_get_tile_grids( $thepostid );
		$all_grids     = get_posts(
			[
				'numberposts' => -1,
				'post_type'   => 'pos_grid',
			]
		);

		include 'views/html-product-grids-panel.php';
	}
}
