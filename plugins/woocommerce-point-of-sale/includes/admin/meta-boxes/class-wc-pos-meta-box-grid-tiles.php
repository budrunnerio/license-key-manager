<?php
/**
 * Grid Tiles
 *
 * Display the grid tiles meta box.
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Meta_Boxes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Meta_Box_Grid_Tiles.
 */
class WC_POS_Meta_Box_Grid_Tiles {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post Post object.
	 */
	public static function output( $post ) {
		global $thepostid, $grid_object;

		$thepostid   = $post->ID;
		$grid_object = wc_pos_get_grid( $thepostid );

		include 'views/html-grid-tiles-panel.php';
	}
}
