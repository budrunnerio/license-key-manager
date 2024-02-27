<?php
/**
 * Grid Functions
 *
 * @since 5.0.0
 *
 * @package WooCommerce_Point_Of_Sale/Functions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get grid.
 *
 * @since 5.0.0
 *
 * @param int|WC_POS_Grid $id Grid ID or object.
 *
 * @throws Exception If grid cannot be read/found and $data parameter of WC_POS_Grid class constructor is set.
 * @return WC_POS_Grid|null
 */
function wc_pos_get_grid( $id ) {
	$grid = new WC_POS_Grid( (int) $id );
	return 0 !== $grid->get_id() ? $grid : null;
}

/**
 * Get the grids that a product/category is added to.
 *
 * @param int    $item_id     Product ID or category ID.
 * @param string $type        Optional. Tile type: product or product_cat. Default product.
 * @param bool   $associative Optional. Whether to return an array of IDs or an associative array that holds both grid ID and name. Default false.
 *
 * @return array
 */
function wc_pos_get_tile_grids( $item_id, $type = 'product', $associative = false ) {
	global $wpdb;

	$grids = [];

	$get_grids = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT gt.grid_id AS id, p.post_title AS title
			FROM {$wpdb->prefix}wc_pos_grid_tiles as gt
			LEFT JOIN {$wpdb->posts} as p ON p.ID = gt.grid_id
			WHERE gt.type = %s AND gt.item_id = %d",
			$type,
			$item_id
		)
	);

	if ( $get_grids ) {
		foreach ( $get_grids as $grid ) {
			if ( $associative ) {
				$grids[ absint( $grid->id ) ] = $grid->title;
			} else {
				$grids[] = absint( $grid->id );
			}
		}
	}

	return array_unique( $grids );
}

/**
 * Returns the tile ID of a product/category within a grid.
 *
 * @param int $grid_id Grid ID.
 * @param int $item_id Item ID.
 *
 * @return int|null Tile ID or null if the item is not added to the grid.
 */
function wc_pos_get_grid_tile_by_item_id( $grid_id, $item_id, $type = 'product' ) {
	global $wpdb;

	$result = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}wc_pos_grid_tiles WHERE type = %s AND item_id = %d AND grid_id = %d",
			$type,
			$item_id,
			$grid_id
		)
	);

	return $result ? absint( $result ) : null;
}

/**
 * Updates tiles ordering within a grid.
 *
 * @param int $grid_id          Grid ID.
 * @param int $current_position The current position of the dragged tile.
 * @param int $new_position     The new position of the dragged tile.
 *
 * @return bool True on success.
 */
function wc_pos_reorder_grid_tiles( $grid_id, $current_position, $new_position ) {
	global $wpdb;

	// Determine if the tile is being moved up or down in the listing.
	$move = $new_position > $current_position ? 'down' : 'up';

	// Set the order for the dragged tile to be 0 so we can update this record later.
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->prefix}wc_pos_grid_tiles SET display_order = 0 WHERE display_order = %d AND grid_id = %d",
			$current_position,
			$grid_id
		)
	);

	// Move down: Update the tiles between the current position and the new position, decreasing each tile by 1 to make space for the new tile.
	if ( 'down' === $move ) {
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}wc_pos_grid_tiles SET display_order = (display_order - 1) WHERE display_order > %d AND display_order <= %d AND grid_id = %d",
				$current_position,
				$new_position,
				$grid_id
			)
		);
	}

	// Move up: Update the tiles between the new position and the current position, increasing each tile by 1 to make space for the new tile.
	if ( 'up' === $move ) {
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}wc_pos_grid_tiles SET display_order = (display_order + 1) WHERE display_order >= %d AND display_order < %d AND grid_id = %d",
				$new_position,
				$current_position,
				$grid_id
			)
		);
	}

	// Update the tile that was dragged and set it to be the new position now that the slot is opend up.
	$result = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->prefix}wc_pos_grid_tiles SET display_order = %d WHERE display_order = 0 AND grid_id = %d",
			$new_position,
			$grid_id
		)
	);

	return false !== $result ? true : false;
}

/**
 * Check if a tile (product or category) is added to a grid.
 *
 * @param int    $item_id Product ID or category ID.
 * @param int    $grid_id Grid ID.
 * @param string $type    Optional. The tile type: product or product_cat. Default product.
 *
 * @return bool True if found.
 */
function wc_pos_is_in_grid( $grid_id, $item_id, $type = 'product' ) {
	global $wpdb;

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}wc_pos_grid_tiles
		WHERE grid_id = %d AND item_id = %d AND type = %s
		",
			$grid_id,
			$item_id,
			$type
		)
	);

	return $results ? true : false;
}

function wc_pos_grid_thumbnail( $thumbnail_id, $size = 'thumbnail' ) {

	$src = wp_get_attachment_image_src( intval( $thumbnail_id ), $size );

	if ( $src && count( $src ) > 2 && is_array( $size ) ) {
		$width  = $src[1];
		$height = $src[2];

		if ( $width != $size[0] || $height != $size[1] ) {
			$src = wp_get_attachment_image_src( $thumbnail_id );
		}
	}

	return $src && current( $src ) ? current( $src ) : wc_placeholder_img_src();
}
