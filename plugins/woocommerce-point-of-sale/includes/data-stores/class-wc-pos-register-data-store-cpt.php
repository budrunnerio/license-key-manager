<?php
/**
 * Register Data Store CPT
 *
 * @since 5.0.0
 *
 * @package WooCommerce_Point_Of_Sales/Classes/Data_Stores
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Register_Data_Store_CPT.
 *
 * Sotres the register data in a custom post type.
 */
class WC_POS_Register_Data_Store_CPT extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta" for a register.
	 *
	 * @var array
	 */
	protected $internal_meta_keys = [];

	/**
	 * Internal meta type used to store register data.
	 *
	 * @var string
	 */
	protected $meta_type = 'post';

	/**
	 * Method to create a new register in the database.
	 *
	 * @param WC_POS_Register $register Register object.
	 */
	public function create( &$register ) {
		$register->set_date_created( time() );

		$register_id = wp_insert_post(
			/**
			 * New register data filter.
			 *
			 * @since 5.0.0
			 */
			apply_filters(
				'wc_pos_new_register_data',
				[
					'post_type'     => 'pos_register',
					'post_status'   => 'publish',
					'post_author'   => get_current_user_id(),
					'post_title'    => $register->get_name( 'edit' ),
					'post_content'  => '',
					'post_excerpt'  => '',
					'post_date'     => gmdate( 'Y-m-d H:i:s', $register->get_date_created()->getOffsetTimestamp() ),
					'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $register->get_date_created()->getTimestamp() ),
				]
			),
			true
		);

		if ( $register_id ) {
			$register->set_id( $register_id );
			$this->update_post_meta( $register );
			$register->save_meta_data();
			$register->apply_changes();
			delete_transient( 'rest_api_pos_registers_type_count' );

			/**
			 * New register created.
			 *
			 * @since 5.0.0
			 */
			do_action( 'wc_pos_new_pos_register', $register_id, $register );
		}
	}

	/**
	 * Method to read a register.
	 *
	 * @param WC_POS_Register $register Register object.
	 *
	 * @throws Exception If invalid register.
	 */
	public function read( &$register ) {
		$register->set_defaults();

		$post_object = get_post( $register->get_id() );

		if ( ! $register->get_id() || ! $post_object || 'pos_register' !== $post_object->post_type ) {
			throw new Exception( __( 'Invalid register.', 'woocommerce-point-of-sale' ) );
		}

		$register_id = $register->get_id();
		$register->set_props(
			[
				'name'            => $post_object->post_title,
				'slug'            => $post_object->post_name,
				'date_created'    => 0 < $post_object->post_date_gmt ? wc_string_to_timestamp( $post_object->post_date_gmt ) : null,
				'date_modified'   => 0 < $post_object->post_modified_gmt ? wc_string_to_timestamp( $post_object->post_modified_gmt ) : null,
				'date_opened'     => get_post_meta( $register_id, 'date_opened', true ),
				'date_closed'     => get_post_meta( $register_id, 'date_closed', true ),
				'open_first'      => (int) get_post_meta( $register_id, 'open_first', true ),
				'open_last'       => (int) get_post_meta( $register_id, 'open_last', true ),
				'current_session' => (int) get_post_meta( $register_id, 'current_session', true ),
				'grid'            => (int) get_post_meta( $register_id, 'grid', true ),
				'receipt'         => (int) get_post_meta( $register_id, 'receipt', true ),
				'grid_layout'     => get_post_meta( $register_id, 'grid_layout', true ),
				'prefix'          => get_post_meta( $register_id, 'prefix', true ),
				'suffix'          => get_post_meta( $register_id, 'suffix', true ),
				'outlet'          => (int) get_post_meta( $register_id, 'outlet', true ),
				'customer'        => (int) get_post_meta( $register_id, 'customer', true ),
				'cash_management' => 'yes' === get_post_meta( $register_id, 'cash_management', true ),
				'dining_option'   => get_post_meta( $register_id, 'dining_option', true ),
				'default_mode'    => get_post_meta( $register_id, 'default_mode', true ),
				'change_user'     => 'yes' === get_post_meta( $register_id, 'change_user', true ),
				'email_receipt'   => get_post_meta( $register_id, 'email_receipt', true ),
				'print_receipt'   => 'yes' === get_post_meta( $register_id, 'print_receipt', true ),
				'gift_receipt'    => 'yes' === get_post_meta( $register_id, 'gift_receipt', true ),
				'note_request'    => get_post_meta( $register_id, 'note_request', true ),
			]
		);
		$register->read_meta_data();
		$register->set_object_read( true );

		/**
		 * Register loaded.
		 *
		 * @since 5.0.0
		 */
		do_action( 'wc_pos_register_loaded', $register );
	}

	/**
	 * Updates a register in the database.
	 *
	 * @param WC_POS_Register $register Register object.
	 */
	public function update( &$register ) {
		$register->save_meta_data();

		if ( ! $register->get_date_created() ) {
			$register->set_date_created( time() );
		}

		$changes = $register->get_changes();

		if ( array_intersect( [ 'name', 'slug', 'date_created', 'date_modified' ], array_keys( $changes ) ) ) {

			$post_data = [
				'post_title'        => $register->get_name( 'edit' ),
				'post_name'         => $register->get_slug( 'edit' ),
				'post_excerpt'      => '',
				'post_date'         => gmdate( 'Y-m-d H:i:s', $register->get_date_created( 'edit' )->getOffsetTimestamp() ),
				'post_date_gmt'     => gmdate( 'Y-m-d H:i:s', $register->get_date_created( 'edit' )->getTimestamp() ),
				'post_modified'     => isset( $changes['date_modified'] ) ? gmdate( 'Y-m-d H:i:s', $register->get_date_modified( 'edit' )->getOffsetTimestamp() ) : current_time( 'mysql' ),
				'post_modified_gmt' => isset( $changes['date_modified'] ) ? gmdate( 'Y-m-d H:i:s', $register->get_date_modified( 'edit' )->getTimestamp() ) : current_time( 'mysql', 1 ),
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
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, [ 'ID' => $register->get_id() ] );
				clean_post_cache( $register->get_id() );
			} else {
				wp_update_post( array_merge( [ 'ID' => $register->get_id() ], $post_data ) );
			}
			$register->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		}
		$this->update_post_meta( $register );
		$register->apply_changes();
		delete_transient( 'rest_api_pos_registers_type_count' );

		/**
		 * Register updated.
		 *
		 * @since 5.0.0
		 */
		do_action( 'wc_pos_update_register', $register->get_id(), $register );
	}

	/**
	 * Deletes a register from the database.
	 *
	 * @param WC_POS_Register $register Register object.
	 * @param array           $args Array of args to pass to the delete method.
	 */
	public function delete( &$register, $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'force_delete' => false,
			]
		);

		$id = $register->get_id();

		if ( ! $id ) {
			return;
		}

		if ( $args['force_delete'] ) {
			wp_delete_post( $id );

			wp_cache_delete( WC_Cache_Helper::get_cache_prefix( 'registers' ) . 'register_id_from_code_' . $register->get_code(), 'registers' );

			$register->set_id( 0 );

			/**
			 * Register deleted.
			 *
			 * @param int Register ID.
			 *
			 * @since 5.0.0
			 */
			do_action( 'wc_pos_delete_register', $id );
		} else {
			wp_trash_post( $id );

			/**
			 * Register trashed.
			 *
			 * @param int Register ID.
			 *
			 * @since 5.0.0
			 */
			do_action( 'wc_pos_trash_register', $id );
		}
	}

	/**
	 * Helper method that updates all the post meta for a register based on it's settings in the WC_POS_Register class.
	 *
	 * @param WC_POS_Register $register Register object.
	 */
	private function update_post_meta( &$register ) {
		$updated_props     = [];
		$meta_key_to_props = [
			'date_opened'     => 'date_opened',
			'date_closed'     => 'date_closed',
			'open_first'      => 'open_first',
			'open_last'       => 'open_last',
			'current_session' => 'current_session',
			'grid'            => 'grid',
			'receipt'         => 'receipt',
			'grid_layout'     => 'grid_layout',
			'prefix'          => 'prefix',
			'suffix'          => 'suffix',
			'outlet'          => 'outlet',
			'customer'        => 'customer',
			'cash_management' => 'cash_management',
			'dining_option'   => 'dining_option',
			'default_mode'    => 'default_mode',
			'change_user'     => 'change_user',
			'email_receipt'   => 'email_receipt',
			'print_receipt'   => 'print_receipt',
			'gift_receipt'    => 'gift_receipt',
			'note_request'    => 'note_request',
		];

		$props_to_update = $this->get_props_to_update( $register, $meta_key_to_props );
		foreach ( $props_to_update as $meta_key => $prop ) {
			$value = $register->{"get_$prop"}( 'edit' );
			$value = is_string( $value ) ? wp_slash( $value ) : $value;
			switch ( $prop ) {
				case 'cash_management':
				case 'change_user':
				case 'print_receipt':
				case 'gift_receipt':
				case 'change_user':
				case 'print_receipt':
				case 'gift_receipt':
					$value = wc_bool_to_string( $value );
					break;
				case 'date_opened':
				case 'date_closed':
					$value = $value ? $value->getTimestamp() : null;
					break;
			}

			$updated = $this->update_or_delete_post_meta( $register, $meta_key, $value );

			if ( $updated ) {
				$this->updated_props[] = $prop;
			}
		}

		/**
		 * Register object updated props.
		 *
		 * @since 5.0.0
		 */
		do_action( 'wc_pos_register_object_updated_props', $register, $updated_props );
	}
}
