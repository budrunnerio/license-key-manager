<?php
/**
 * Receipt Data Store CPT
 *
 * @since 5.0.0
 *
 * @package WooCommerce_Point_Of_Sales/Classes/Data_Stores
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Receipt_Data_Store_CPT.
 *
 * Sotres the receipt data in a custom post type.
 */
class WC_POS_Receipt_Data_Store_CPT extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta" for a receipt.
	 *
	 * @var array
	 */
	protected $internal_meta_keys = [];

	/**
	 * Internal meta type used to store receipt data.
	 *
	 * @var string
	 */
	protected $meta_type = 'post';

	/**
	 * Method to create a new receipt in the database.
	 *
	 * @param WC_POS_Receipt $receipt Receipt object.
	 */
	public function create( &$receipt ) {
		$receipt->set_date_created( time() );

		$receipt_id = wp_insert_post(
			/**
			 * New receipt data.
			 *
			 * @since 5.0.0
			 */
			apply_filters(
				'wc_pos_new_receipt_data',
				[
					'post_type'     => 'pos_receipt',
					'post_status'   => 'publish',
					'post_author'   => get_current_user_id(),
					'post_title'    => $receipt->get_name( 'edit' ),
					'post_content'  => '',
					'post_excerpt'  => '',
					'post_date'     => gmdate( 'Y-m-d H:i:s', $receipt->get_date_created()->getOffsetTimestamp() ),
					'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $receipt->get_date_created()->getTimestamp() ),
				]
			),
			true
		);

		if ( $receipt_id ) {
			$receipt->set_id( $receipt_id );
			$this->update_post_meta( $receipt );
			$receipt->save_meta_data();
			$receipt->apply_changes();
			delete_transient( 'rest_api_pos_receipts_type_count' );

			/**
			 * New receipt is created..
			 *
			 * @since 5.0.0
			 */
			do_action( 'wc_pos_new_pos_receipt', $receipt_id, $receipt );
		}
	}

	/**
	 * Method to read a receipt.
	 *
	 * @param WC_POS_Receipt $receipt Receipt object.
	 *
	 * @throws Exception If invalid receipt.
	 */
	public function read( &$receipt ) {
		$receipt->set_defaults();

		$post_object = get_post( $receipt->get_id() );

		if ( ! $receipt->get_id() || ! $post_object || 'pos_receipt' !== $post_object->post_type ) {
			throw new Exception( __( 'Invalid receipt.', 'woocommerce-point-of-sale' ) );
		}

		$receipt_id = $receipt->get_id();
		$receipt->set_props(
			[
				'barcode_type'                   => get_post_meta( $receipt_id, 'barcode_type', true ),
				'cashier_name_format'            => get_post_meta( $receipt_id, 'cashier_name_format', true ),
				'custom_css'                     => get_post_meta( $receipt_id, 'custom_css', true ),
				'date_created'                   => 0 < $post_object->post_date_gmt ? wc_string_to_timestamp( $post_object->post_date_gmt ) : null,
				'date_modified'                  => 0 < $post_object->post_modified_gmt ? wc_string_to_timestamp( $post_object->post_modified_gmt ) : null,
				'footer_text'                    => get_post_meta( $receipt_id, 'footer_text', true ),
				'header_text'                    => get_post_meta( $receipt_id, 'header_text', true ),
				'logo'                           => get_post_meta( $receipt_id, 'logo', true ),
				'logo_position'                  => get_post_meta( $receipt_id, 'logo_position', true ),
				'logo_size'                      => get_post_meta( $receipt_id, 'logo_size', true ),
				'name'                           => $post_object->post_title,
				'num_copies'                     => (int) get_post_meta( $receipt_id, 'num_copies', true ),
				'order_date_format'              => get_post_meta( $receipt_id, 'order_date_format', true ),
				'order_time_format'              => get_post_meta( $receipt_id, 'order_time_format', true ),
				'outlet_details_position'        => get_post_meta( $receipt_id, 'outlet_details_position', true ),
				'print_copies'                   => get_post_meta( $receipt_id, 'print_copies', true ),
				'product_details_layout'         => get_post_meta( $receipt_id, 'product_details_layout', true ),
				'show_cashier_name'              => 'yes' === get_post_meta( $receipt_id, 'show_cashier_name', true ),
				'show_customer_billing_address'  => 'yes' === get_post_meta( $receipt_id, 'show_customer_billing_address', true ),
				'show_customer_email'            => 'yes' === get_post_meta( $receipt_id, 'show_customer_email', true ),
				'show_customer_name'             => 'yes' === get_post_meta( $receipt_id, 'show_customer_name', true ),
				'show_customer_phone'            => 'yes' === get_post_meta( $receipt_id, 'show_customer_phone', true ),
				'show_customer_shipping_address' => 'yes' === get_post_meta( $receipt_id, 'show_customer_shipping_address', true ),
				'show_num_items'                 => 'yes' === get_post_meta( $receipt_id, 'show_num_items', true ),
				'show_order_barcode'             => 'yes' === get_post_meta( $receipt_id, 'show_order_barcode', true ),
				'show_order_date'                => 'yes' === get_post_meta( $receipt_id, 'show_order_date', true ),
				'show_order_status'              => 'yes' === get_post_meta( $receipt_id, 'show_order_status', true ),
				'show_outlet_address'            => 'yes' === get_post_meta( $receipt_id, 'show_outlet_address', true ),
				'show_outlet_contact_details'    => 'yes' === get_post_meta( $receipt_id, 'show_outlet_contact_details', true ),
				'show_outlet_name'               => 'yes' === get_post_meta( $receipt_id, 'show_outlet_name', true ),
				'show_product_cost'              => 'yes' === get_post_meta( $receipt_id, 'show_product_cost', true ),
				'show_product_discount'          => 'yes' === get_post_meta( $receipt_id, 'show_product_discount', true ),
				'show_original_price'            => 'yes' === get_post_meta( $receipt_id, 'show_original_price', true ),
				'show_product_image'             => 'yes' === get_post_meta( $receipt_id, 'show_product_image', true ),
				'show_product_sku'               => 'yes' === get_post_meta( $receipt_id, 'show_product_sku', true ),
				'show_register_name'             => 'yes' === get_post_meta( $receipt_id, 'show_register_name', true ),
				'show_shop_name'                 => 'yes' === get_post_meta( $receipt_id, 'show_shop_name', true ),
				'show_social_facebook'           => 'yes' === get_post_meta( $receipt_id, 'show_social_facebook', true ),
				'show_social_instagram'          => 'yes' === get_post_meta( $receipt_id, 'show_social_instagram', true ),
				'show_social_snapchat'           => 'yes' === get_post_meta( $receipt_id, 'show_social_snapchat', true ),
				'show_social_twitter'            => 'yes' === get_post_meta( $receipt_id, 'show_social_twitter', true ),
				'show_tax_number'                => 'yes' === get_post_meta( $receipt_id, 'show_tax_number', true ),
				'show_tax_summary'               => 'yes' === get_post_meta( $receipt_id, 'show_tax_summary', true ),
				'show_title'                     => 'yes' === get_post_meta( $receipt_id, 'show_title', true ),
				'show_wifi_details'              => 'yes' === get_post_meta( $receipt_id, 'show_wifi_details', true ),
				'slug'                           => $post_object->post_name,
				'social_details_position'        => get_post_meta( $receipt_id, 'social_details_position', true ),
				'tax_number_label'               => get_post_meta( $receipt_id, 'tax_number_label', true ),
				'tax_number_position'            => get_post_meta( $receipt_id, 'tax_number_position', true ),
				'text_size'                      => get_post_meta( $receipt_id, 'text_size', true ),
				'title_position'                 => get_post_meta( $receipt_id, 'title_position', true ),
				'type'                           => get_post_meta( $receipt_id, 'type', true ),
				'width'                          => (int) get_post_meta( $receipt_id, 'width', true ),
			]
		);
		$receipt->read_meta_data();
		$receipt->set_object_read( true );

		/**
		 * New receipt is loaded.
		 *
		 * @since 5.0.0
		 */
		do_action( 'wc_pos_receipt_loaded', $receipt );
	}

	/**
	 * Updates a receipt in the database.
	 *
	 * @param WC_POS_Receipt $receipt Receipt object.
	 */
	public function update( &$receipt ) {
		$receipt->save_meta_data();

		if ( ! $receipt->get_date_created() ) {
			$receipt->set_date_created( time() );
		}

		$changes = $receipt->get_changes();

		if ( array_intersect( [ 'name', 'date_created', 'date_modified' ], array_keys( $changes ) ) ) {
			$post_data = [
				'post_title'        => $receipt->get_name( 'edit' ),
				'post_excerpt'      => '',
				'post_date'         => gmdate( 'Y-m-d H:i:s', $receipt->get_date_created( 'edit' )->getOffsetTimestamp() ),
				'post_date_gmt'     => gmdate( 'Y-m-d H:i:s', $receipt->get_date_created( 'edit' )->getTimestamp() ),
				'post_modified'     => isset( $changes['date_modified'] ) ? gmdate( 'Y-m-d H:i:s', $receipt->get_date_modified( 'edit' )->getOffsetTimestamp() ) : current_time( 'mysql' ),
				'post_modified_gmt' => isset( $changes['date_modified'] ) ? gmdate( 'Y-m-d H:i:s', $receipt->get_date_modified( 'edit' )->getTimestamp() ) : current_time( 'mysql', 1 ),
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
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, [ 'ID' => $receipt->get_id() ] );
				clean_post_cache( $receipt->get_id() );
			} else {
				wp_update_post( array_merge( [ 'ID' => $receipt->get_id() ], $post_data ) );
			}
			$receipt->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		}
		$this->update_post_meta( $receipt );
		$receipt->apply_changes();
		delete_transient( 'rest_api_pos_receipts_type_count' );

		/**
		 * Receipt is updated.
		 *
		 * @since 5.0.0
		 */
		do_action( 'wc_pos_update_receipt', $receipt->get_id(), $receipt );
	}

	/**
	 * Deletes a receipt from the database.
	 *
	 * @param WC_POS_Receipt $receipt Receipt object.
	 * @param array          $args Array of args to pass to the delete method.
	 */
	public function delete( &$receipt, $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'force_delete' => false,
			]
		);

		$id = $receipt->get_id();

		if ( ! $id ) {
			return;
		}

		if ( $args['force_delete'] ) {
			wp_delete_post( $id );

			wp_cache_delete( WC_Cache_Helper::get_cache_prefix( 'receipts' ) . 'receipt_id_from_code_' . $receipt->get_code(), 'receipts' );

			$receipt->set_id( 0 );

			/**
			 * Receipt is deleted.
			 *
			 * @since 5.0.0
			 */
			do_action( 'wc_pos_delete_receipt', $id );
		} else {
			wp_trash_post( $id );

			/**
			 * Receipt is trashed.
			 *
			 * @since 5.0.0
			 */
			do_action( 'wc_pos_trash_receipt', $id );
		}
	}

	/**
	 * Helper method that updates all the post meta for a receipt based on it's settings in the WC_POS_Receipt class.
	 *
	 * @param WC_POS_Receipt $receipt Receipt object.
	 */
	private function update_post_meta( &$receipt ) {
		$updated_props     = [];
		$meta_key_to_props = [
			'barcode_type'                   => 'barcode_type',
			'cashier_name_format'            => 'cashier_name_format',
			'custom_css'                     => 'custom_css',
			'footer_text'                    => 'footer_text',
			'header_text'                    => 'header_text',
			'logo'                           => 'logo',
			'logo_position'                  => 'logo_position',
			'logo_size'                      => 'logo_size',
			'num_copies'                     => 'num_copies',
			'order_date_format'              => 'order_date_format',
			'order_time_format'              => 'order_time_format',
			'outlet_details_position'        => 'outlet_details_position',
			'print_copies'                   => 'print_copies',
			'product_details_layout'         => 'product_details_layout',
			'show_cashier_name'              => 'show_cashier_name',
			'show_customer_billing_address'  => 'show_customer_billing_address',
			'show_customer_email'            => 'show_customer_email',
			'show_customer_name'             => 'show_customer_name',
			'show_customer_phone'            => 'show_customer_phone',
			'show_customer_shipping_address' => 'show_customer_shipping_address',
			'show_num_items'                 => 'show_num_items',
			'show_order_barcode'             => 'show_order_barcode',
			'show_order_date'                => 'show_order_date',
			'show_order_status'              => 'show_order_status',
			'show_outlet_address'            => 'show_outlet_address',
			'show_outlet_contact_details'    => 'show_outlet_contact_details',
			'show_outlet_name'               => 'show_outlet_name',
			'show_product_cost'              => 'show_product_cost',
			'show_product_discount'          => 'show_product_discount',
			'show_original_price'            => 'show_original_price',
			'show_product_image'             => 'show_product_image',
			'show_product_sku'               => 'show_product_sku',
			'show_register_name'             => 'show_register_name',
			'show_shop_name'                 => 'show_shop_name',
			'show_social_facebook'           => 'show_social_facebook',
			'show_social_instagram'          => 'show_social_instagram',
			'show_social_snapchat'           => 'show_social_snapchat',
			'show_social_twitter'            => 'show_social_twitter',
			'show_tax_number'                => 'show_tax_number',
			'show_tax_summary'               => 'show_tax_summary',
			'show_title'                     => 'show_title',
			'show_wifi_details'              => 'show_wifi_details',
			'social_details_position'        => 'social_details_position',
			'tax_number_label'               => 'tax_number_label',
			'tax_number_position'            => 'tax_number_position',
			'text_size'                      => 'text_size',
			'title_position'                 => 'title_position',
			'type'                           => 'type',
			'width'                          => 'width',
		];

		$props_to_update = $this->get_props_to_update( $receipt, $meta_key_to_props );
		foreach ( $props_to_update as $meta_key => $prop ) {
			$value = $receipt->{"get_$prop"}( 'edit' );
			$value = is_string( $value ) ? wp_slash( $value ) : $value;
			switch ( $prop ) {
				case 'show_title':
				case 'show_shop_name':
				case 'show_outlet_name':
				case 'show_outlet_address':
				case 'show_outlet_contact_details':
				case 'show_social_twitter':
				case 'show_social_facebook':
				case 'show_social_instagram':
				case 'show_social_snapchat':
				case 'show_wifi_details':
				case 'show_tax_number':
				case 'show_order_status':
				case 'show_order_date':
				case 'show_customer_name':
				case 'show_customer_email':
				case 'show_customer_phone':
				case 'show_customer_shipping_address':
				case 'show_customer_billing_address':
				case 'show_cashier_name':
				case 'show_register_name':
				case 'show_product_image':
				case 'show_product_sku':
				case 'show_product_cost':
				case 'show_product_discount':
				case 'show_original_price':
				case 'show_num_items':
				case 'show_tax_summary':
				case 'show_order_barcode':
					$value = wc_bool_to_string( $value );
					break;
			}

			$updated = $this->update_or_delete_post_meta( $receipt, $meta_key, $value );

			if ( $updated ) {
				$this->updated_props[] = $prop;
			}
		}

		/**
		 * Receipt object props updated.
		 *
		 * @since 5.0.0
		 */
		do_action( 'wc_pos_receipt_object_updated_props', $receipt, $updated_props );
	}
}
