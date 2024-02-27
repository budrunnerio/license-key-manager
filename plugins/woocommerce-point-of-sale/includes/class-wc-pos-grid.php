<?php
/**
 * Point of Sale Grid
 *
 * @since 5.0.0
 *
 * @package WooCommerce_Point_Of_Sale/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Grid.
 */
class WC_POS_Grid extends WC_Data {

	/**
	 * Data array, with defaults.
	 *
	 * @var array
	 */
	protected $data = [
		'name'          => '',
		'slug'          => '',
		'date_created'  => null,
		'date_modified' => null,
		'sort_by'       => 'name',
	];

	/**
	 * Grid tiles will be stored here, sometimes before they persist in the DB.
	 *
	 * @var array
	 */
	protected $tiles = [];

	/**
	 * Array of tile IDs to be deleted on save.
	 *
	 * @var array
	 */
	protected $tiles_to_delete = [];

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'pos_grid';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'pos_grid';

	/**
	 * Cache group.
	 *
	 * @var string
	 */
	protected $cache_group = 'pos_grids';

	/**
	 * Constructor.
	 *
	 * Loads grid data.
	 *
	 * @param mixed $data Grid data, object or ID.
	 */
	public function __construct( $data = '' ) {
		parent::__construct( $data );

		// If we already have a grid object, read it again.
		if ( $data instanceof WC_POS_Grid ) {
			$this->set_id( absint( $data->get_id() ) );
			$this->read_object_from_database();
			return;
		}

		/**
		 * Allows custom grid objects to be created on the fly.
		 *
		 * @since 5.0.0
		 */
		$grid = apply_filters( 'wc_pos_get_pos_grid_data', false, $data, $this );

		if ( $grid ) {
			$this->read_manual_grid( $data, $grid );
			return;
		}

		// Try to load grid using ID.
		if ( is_int( $data ) && 'pos_grid' === get_post_type( $data ) ) {
			$this->set_id( $data );
		} else {
			$this->set_object_read( true );
		}

		$this->read_object_from_database();
	}

	/**
	 * If the object has an ID, read using the data store.
	 */
	protected function read_object_from_database() {
		$this->data_store = WC_Data_Store::load( 'pos_grid' );

		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @return string
	 */
	protected function get_hook_prefix() {
		return 'wc_pos_grid_get_';
	}

	/*
	 * CRUD Methods
	 */


	/**
	 * Save data to the database.
	 *
	 * @return int Grid ID.
	 */
	public function save() {
		if ( ! $this->data_store ) {
			return $this->get_id();
		}

		try {
			/**
			 * Trigger action before saving to the DB. Allows you to adjust object props before save.
			 *
			 * @since 5.0.0
			 * @param WC_Data          $this The object being saved.
			 * @param WC_Data_Store_WP $data_store THe data store persisting the data.
			 */
			do_action( 'woocommerce_before_' . $this->object_type . '_object_save', $this, $this->data_store );

			if ( $this->get_id() ) {
				$this->data_store->update( $this );
			} else {
				$this->data_store->create( $this );
			}

			$this->save_tiles( $this );

			/**
			 * Trigger action after saving to the DB.
			 *
			 * @since 5.0.0
			 * @param WC_Data          $this The object being saved.
			 * @param WC_Data_Store_WP $data_store THe data store persisting the data.
			 */
			do_action( 'woocommerce_after_' . $this->object_type . '_object_save', $this, $this->data_store );

		} catch ( Exception $e ) {
			$this->handle_exception( $e, __( 'Error saving grid.', 'woocommerce-point-of-sale' ) );
		}

		return $this->get_id();
	}

	/*
	 * Save all grid tiles which are part of this grid.
	 */
	protected function save_tiles( &$grid ) {
		$tiles_changed = false;

		foreach ( $this->tiles_to_delete as $tile_id ) {
			$this->data_store->delete_tile( $tile_id, $grid->get_id() );
			$tiles_changed = true;
		}

		$this->tiles_to_delete = [];

		// Add/save tiles.
		foreach ( $this->tiles as $tile_key => $value ) {
			$tile_id = $this->data_store->update_tile( $tile_key, $value, $grid->get_id() );

			// If ID changed (new tile saved to DB).
			if ( $tile_id && $tile_id !== $tile_key ) {
				$this->tiles[ $tile_id ] = $value;

				unset( $this->tiles[ $tile_key ] );

				$tiles_changed = true;
			}
		}

		if ( $tiles_changed ) {
			delete_transient( 'wc_pos_grid_' . $this->get_id() . '_needs_processing' );
		}
	}

	/*
	 * Getters
	 *
	 * Methods for getting data from the grid object.
	 */

	/**
	 * Get grid slug.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_slug( $context = 'view' ) {
		return $this->get_prop( 'slug', $context );
	}

	/**
	 * Get grid name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return $this->get_prop( 'name', $context );
	}

	/**
	 * Get date_created
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Get date_modified
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Get the sort_by option value.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_sort_by( $context = 'view' ) {
		return $this->get_prop( 'sort_by', $context );
	}

	/*
	 * Setters
	 *
	 * Functions for setting grid data. These should not update anything in the
	 * database itself and should only change what is stored in the class
	 * object.
	 */

	/**
	 * Set grid name.
	 *
	 * @param string $name Grid name.
	 */
	public function set_name( $name ) {
		$this->set_prop( 'name', $name );
	}

	/**
	 * Set grid slug.
	 *
	 * @param string $slug Grid slug.
	 */
	public function set_slug( $slug ) {
		$this->set_prop( 'slug', $slug );
	}

	/**
	 * Set date_created
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 */
	public function set_date_created( $date ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set date_modified
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 */
	public function set_date_modified( $date ) {
		$this->set_date_prop( 'date_modified', $date );
	}

	/**
	 * Set how the tiles should be sorted in this grid.
	 *
	 * @param string $sort_by Sort by.
	 */
	public function set_sort_by( $sort_by ) {
		$this->set_prop( 'sort_by', $sort_by );
	}

	/*
	 * Grid Tiles Handling
	 *
	 * Grid tiles are stored in the database as a 'tile' post meta.
	 */

	/**
	 * Add/update a grid tile. The changes will occur in the database after calling WC_POS_Grid::save().
	 *
	 * @param array $tile Tile details.
	 */
	public function add_tile( $tile ) {
		$this->tiles = $this->get_tiles();

		// If tile exists, then we will update its value.
		if ( isset( $tile['id'], $this->tiles[ $tile['id'] ] ) ) {
			$tile_id = $tile['id'];
			unset( $tile['id'] );
			$this->tiles[ $tile_id ] = $tile;
		} else {
			// Append new row with generated temporary ID.
			$this->tiles[ 'new:' . count( $this->tiles ) ] = $tile;
		}
	}

	/**
	 * Delete tile from the grid.
	 *
	 * @param int $tile_id Tile ID to delete.
	 */
	public function delete_tile( $tile_id ) {
		$this->tiles = $this->get_tiles();

		// Unset and remove later.
		$this->tiles_to_delete[] = $tile_id;
		unset( $this->tiles[ $tile_id ] );
	}

	/**
	 * Return an array of tiles within this grid.
	 *
	 * @return array Array of tiles.
	 */
	public function get_tiles() {
		// Don't use array_merge here because keys are numeric.
		$this->tiles = $this->tiles + $this->data_store->read_tiles( $this );

		// Exclude the tiles pending deletion.
		foreach ( $this->tiles_to_delete as $tile_id ) {
			unset( $this->tiles[ $tile_id ] );
		}

		if ( 'yes' === get_option( 'wc_pos_visibility', 'no' ) ) {
			$this->tiles = array_filter(
				$this->tiles,
				function ( $tile ) {
					return get_post_meta( $tile['item_id'], '_pos_visibility', true ) !== 'online';
				}
			);
		}

		/**
		 * Grid tiles.
		 *
		 * @since 5.0.0
		 */
		return apply_filters( 'wc_pos_grid_get_tiles', $this->tiles, $this );
	}

	/*
	 * Other Actions
	 */

	/**
	 * Developers can programmatically return grids. This function will read those values into our WC_POS_Grid class.
	 *
	 * @param string $slug Grid slug.
	 * @param array  $grid Array of grid properties.
	 */
	public function read_manual_grid( $slug, $grid ) {
		$this->set_props( $grid );
		$this->set_slug( $slug );
		$this->set_id( 0 );
		$this->set_virtual( true );
	}
}
