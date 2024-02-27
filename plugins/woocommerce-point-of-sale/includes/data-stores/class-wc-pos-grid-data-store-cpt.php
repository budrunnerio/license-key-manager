<?php
/**
 * Grid Data Store CPT
 *
 * @since 5.0.0
 *
 * @package WooCommerce_Point_Of_Sales/Classes/Data_Stores
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Grid_Data_Store_CPT.
 *
 * Sotres the grid data in a custom post type.
 */
class WC_POS_Grid_Data_Store_CPT extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta" for a grid.
	 *
	 * @var array
	 */
	protected $internal_meta_keys = [];

	/**
	 * Internal meta type used to store grid data.
	 *
	 * @var string
	 */
	protected $meta_type = 'post';

	/**
	 * Method to create a new grid in the database.
	 *
	 * @param WC_POS_Grid $grid Grid object.
	 */
	public function create( &$grid ) {
		$grid->set_date_created( time() );

		$grid_id = wp_insert_post(
			/**
			 * New grid data.
			 *
			 * @since 5.0.0
			 */
			apply_filters(
				'wc_pos_new_grid_data',
				[
					'post_type'     => 'pos_grid',
					'post_status'   => 'publish',
					'post_author'   => get_current_user_id(),
					'post_title'    => $grid->get_name( 'edit' ),
					'post_content'  => '',
					'post_excerpt'  => '',
					'post_date'     => gmdate( 'Y-m-d H:i:s', $grid->get_date_created()->getOffsetTimestamp() ),
					'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $grid->get_date_created()->getTimestamp() ),
				]
			),
			true
		);

		if ( $grid_id ) {
			$grid->set_id( $grid_id );
			$this->update_post_meta( $grid );
			$grid->save_meta_data();
			$grid->apply_changes();
			delete_transient( 'rest_api_pos_grids_type_count' );

			/**
			 * New grid is created.
			 *
			 * @since 5.0.0
			 */
			do_action( 'wc_pos_new_pos_grid', $grid_id, $grid );
		}
	}

	/**
	 * Method to read an grid.
	 *
	 * @param WC_POS_Grid $grid Grid object.
	 *
	 * @throws Exception If invalid grid.
	 */
	public function read( &$grid ) {
		$grid->set_defaults();

		$post_object = get_post( $grid->get_id() );

		if ( ! $grid->get_id() || ! $post_object || 'pos_grid' !== $post_object->post_type ) {
			throw new Exception( __( 'Invalid grid.', 'woocommerce-point-of-sale' ) );
		}

		$grid_id = $grid->get_id();
		$grid->set_props(
			[
				'name'          => $post_object->post_title,
				'slug'          => $post_object->post_name,
				'date_created'  => 0 < $post_object->post_date_gmt ? wc_string_to_timestamp( $post_object->post_date_gmt ) : null,
				'date_modified' => 0 < $post_object->post_modified_gmt ? wc_string_to_timestamp( $post_object->post_modified_gmt ) : null,
				'sort_by'       => get_post_meta( $grid_id, 'sort_by', true ),
			]
		);
		$grid->read_meta_data();
		$grid->set_object_read( true );

		/**
		 * Grid is loaded.
		 *
		 * @since 5.0.0
		 */
		do_action( 'wc_pos_grid_loaded', $grid );
	}

	/**
	 * Updates an grid in the database.
	 *
	 * @param WC_POS_Grid $grid Grid object.
	 */
	public function update( &$grid ) {
		$grid->save_meta_data();

		if ( ! $grid->get_date_created() ) {
			$grid->set_date_created( time() );
		}

		$changes = $grid->get_changes();

		if ( array_intersect( [ 'name', 'date_created', 'date_modified' ], array_keys( $changes ) ) ) {
			$post_data = [
				'post_title'        => $grid->get_name( 'edit' ),
				'post_excerpt'      => '',
				'post_date'         => gmdate( 'Y-m-d H:i:s', $grid->get_date_created( 'edit' )->getOffsetTimestamp() ),
				'post_date_gmt'     => gmdate( 'Y-m-d H:i:s', $grid->get_date_created( 'edit' )->getTimestamp() ),
				'post_modified'     => isset( $changes['date_modified'] ) ? gmdate( 'Y-m-d H:i:s', $grid->get_date_modified( 'edit' )->getOffsetTimestamp() ) : current_time( 'mysql' ),
				'post_modified_gmt' => isset( $changes['date_modified'] ) ? gmdate( 'Y-m-d H:i:s', $grid->get_date_modified( 'edit' )->getTimestamp() ) : current_time( 'mysql', 1 ),
			];

			/**
			 * When updating this object, to prevent infinite loops, use $wpdb
			 * to update data, since wp_update_post spawns more calls to the
			 * save_post action.
			 *
			 * This ensures hooks are fired by either WP itself (admin screen save),
			 * or an update purely from CRUD.
			 */
			if ( doing_action( 'save_post' ) ) {
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, [ 'ID' => $grid->get_id() ] );
				clean_post_cache( $grid->get_id() );
			} else {
				wp_update_post( array_merge( [ 'ID' => $grid->get_id() ], $post_data ) );
			}
			$grid->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		}
		$this->update_post_meta( $grid );
		$grid->apply_changes();
		delete_transient( 'rest_api_pos_grids_type_count' );

		/**
		 * Grid is updated.
		 *
		 * @param WC_POS_Grid Grid ID.
		 * @param int         Grid instance.
		 *
		 * @since 5.0.0
		 */
		do_action( 'wc_pos_update_grid', $grid->get_id(), $grid );
	}

	/**
	 * Deletes a grid from the database.
	 *
	 * @param WC_POS_Grid $grid Grid object.
	 * @param array       $args Array of args to pass to the delete method.
	 */
	public function delete( &$grid, $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'force_delete' => false,
			]
		);

		$id = $grid->get_id();

		if ( ! $id ) {
			return;
		}

		if ( $args['force_delete'] ) {
			wp_delete_post( $id );

			wp_cache_delete( WC_Cache_Helper::get_cache_prefix( 'grids' ) . 'grid_id_from_code_' . $grid->get_code(), 'grids' );

			$grid->set_id( 0 );

			/**
			 * Grid deleted.
			 *
			 * @param int Grid ID.
			 *
			 * @since 5.0.0
			 */
			do_action( 'wc_pos_delete_grid', $id );
		} else {
			wp_trash_post( $id );

			/**
			 * Grid trashed.
			 *
			 * @param int Grid ID.
			 *
			 * @since 5.0.0
			 */
			do_action( 'wc_pos_trash_grid', $id );
		}
	}

	/**
	 * Helper method that updates all the post meta for a grid based on it's settings in the WC_POS_Grid class.
	 *
	 * @param WC_POS_Grid $grid Grid object.
	 */
	private function update_post_meta( &$grid ) {
		$updated_props     = [];
		$meta_key_to_props = [
			'sort_by' => 'sort_by',
		];

		$props_to_update = $this->get_props_to_update( $grid, $meta_key_to_props );
		foreach ( $props_to_update as $meta_key => $prop ) {
			$value = $grid->{"get_$prop"}( 'edit' );
			$value = is_string( $value ) ? wp_slash( $value ) : $value;

			$updated = $this->update_or_delete_post_meta( $grid, $meta_key, $value );

			if ( $updated ) {
				$this->updated_props[] = $prop;
			}
		}

		/**
		 * Grid object updated props.
		 *
		 * @since 5.0.0
		 */
		do_action( 'wc_pos_grid_object_updated_props', $grid, $updated_props );
	}

	/**
	 * Read grid tiles from the database for this grid.
	 *
	 * @param WC_POS_Grid $grid Grid object.
	 * @return array Grid tiles.
	 */
	public function read_tiles( $grid ) {
		global $wpdb;

		$tiles = $wpdb->get_results( $wpdb->prepare( "SELECT id, type, item_id, display_order, grid_id FROM {$wpdb->prefix}wc_pos_grid_tiles WHERE grid_id = %d ORDER BY display_order;", $grid->get_id() ) );

		if ( ! empty( $tiles ) ) {
			$tiles = array_map(
				function( $item ) {
						return [
							'type'          => $item->type,
							'item_id'       => $item->item_id,
							'display_order' => $item->display_order,
							'grid_id'       => $item->grid_id,
							'parent_id'     => $this->get_tile_parent( $item ),
						];
				},
				array_combine( wp_list_pluck( $tiles, 'id' ), $tiles )
			);
		} else {
			$tiles = [];
		}

		return $tiles;
	}

	/**
	 * Add/update a tile in the database.
	 *
	 * @param int   $key     Tile key.
	 * @param array $value   Tile details.
	 * @param int   $grid_id Grid ID.
	 *
	 * @return mixed Tile ID or false if failed to add/update the database record.
	 */
	public function update_tile( $key, $value, $grid_id ) {
		global $wpdb;

		// New key?
		if ( false !== strpos( $key, 'new:' ) ) {
			$result = $wpdb->insert(
				$wpdb->prefix . 'wc_pos_grid_tiles',
				[
					'type'          => $value['type'],
					'item_id'       => $value['item_id'],
					'display_order' => $this->get_highest_tile_order( $grid_id ) + 1,
					'grid_id'       => $grid_id,
				],
				[ '%s', '%d', '%d', '%d' ]
			);

			return $result ? $wpdb->insert_id : false;
		}

		$result = $wpdb->update(
			$wpdb->prefix . 'wc_pos_grid_tiles',
			[
				'type'    => $value['type'],
				'item_id' => $value['item_id'],
				'grid_id' => $grid_id,
			],
			[
				'id'      => $key,
				'grid_id' => $grid_id,
			],
			[ '%s', '%d', '%d', '%d' ]
		);

		// Return the ID of the updated tile even if the value has not changed.
		return $key;
	}

	/**
	 * Remove a tile from the database.
	 *
	 * @param int $id      Tile ID (meta_id).
	 * @param int $grid_id Grid ID.
	 *
	 * @return bool Success/failure.
	 */
	public function delete_tile( $id, $grid_id ) {
		global $wpdb;

		// Get the display order of the tile before deletion.
		$display_order = $this->get_tile_display_order( $id );

		$result = $wpdb->delete(
			$wpdb->prefix . 'wc_pos_grid_tiles',
			[
				'id'      => $id,
				'grid_id' => $grid_id,
			]
		) ? true : false;

		// Update tiles ordering.
		if ( $result && $display_order ) {
			$result = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}wc_pos_grid_tiles
				SET display_order = (display_order - 1)
				WHERE display_order > %d
				AND grid_id = %d",
					$display_order,
					$grid_id
				)
			);
		}

		return false !== $result ? true : false;
	}

	/**
	 * Returns the display order of a grid tile.
	 *
	 * @param int $tile_id Tile ID.
	 * @return int|null The display order of the tile or null if tile not found.
	 */
	private function get_tile_display_order( $tile_id ) {
		global $wpdb;

		$display_order = $wpdb->get_var( $wpdb->prepare( "SELECT display_order FROM {$wpdb->prefix}wc_pos_grid_tiles WHERE id = %d", $tile_id ) );

		return $display_order ? absint( $display_order ) : null;
	}

	/**
	 * Returns the highest tile order within this grid.
	 *
	 * @return int
	 */
	private function get_highest_tile_order( $grid_id ) {
		global $wpdb;

		$highest_order = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(display_order) FROM {$wpdb->prefix}wc_pos_grid_tiles WHERE grid_id = %d", $grid_id ) );

		return $highest_order ? absint( $highest_order ) : 0;
	}

	/**
	 * Returns parent id of the tile.
	 *
	 * @param Object $tile
	 * @return int
	 */
	private function get_tile_parent( $tile ) {
		if ( 'product' === $tile->type ) {
			return wp_get_post_parent_id( $tile->item_id );
		}

		return get_term( $tile->item_id, 'product_cat' )->parent;
	}
}
