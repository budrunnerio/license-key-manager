<?php
/**
 * Settings > Point of Sale > Scanning.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/Admin/Settings
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Admin_Settings_Scanning.
 */
class WC_POS_Admin_Settings_Scanning {

	/**
	 * Returns section settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		/**
		 * The scanning settings section.
		 *
		 * @since 6.0.0
		 */
		return apply_filters(
			'wc_pos_settings_scanning_section',
			[
				/*
				 * Scanning options.
				 */
				[
					'title' => __( 'Scanning Options', 'woocommerce-point-of-sale' ),
					'type'  => 'title',
					'desc'  => __( 'The following options affect the scanning options in POS.', 'woocommerce-point-of-sale' ),
					'id'    => 'scanning_options',
				],
				[
					'title'    => __( 'Camera Scanning', 'woocommerce-point-of-sale' ),
					'desc'     => __( 'Enable camera scanning', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Enable scanning products using device\'s cameras.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_camera_scanning',
					'default'  => 'no',
					'type'     => 'checkbox',
				],
				[
					'name'              => __( 'Scanning Fields', 'woocommerce-point-of-sale' ),
					'desc_tip'          => __( 'Control what fields are used when using the scanner on the register. You can select multiple fields. Default is SKU.', 'woocommerce-point-of-sale' ),
					'id'                => 'wc_pos_scanning_fields',
					'class'             => 'wc-enhanced-select',
					'type'              => 'multiselect',
					/**
					 * Scanning fields.
					 *
					 * @since 5.0.0
					 */
					'options'           => apply_filters(
						'wc_pos_scanning_fields',
						[ '_sku' => __( 'WooCommerce SKU', 'woocommerce-point-of-sale' ) ]
					),
					'default'           => [ '_sku' ],
					'custom_attributes' => [ 'data-tags' => 'true' ],
				],
				[
					'title'    => __( 'Embedded Barcodes', 'woocommerce-point-of-sale' ),
					'desc'     => __( 'Enable the use of price and weight embedded barcodes', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Price or weight-based barcodes can be scanned from the register. Supported formats are EAN-13 and UPC-A.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_enable_weight_embedded_barcodes',
					'default'  => 'no',
					'type'     => 'checkbox',
				],
				[
					'type' => 'sectionend',
					'id'   => 'scanning_options',
				],

				/**
				 * UPC options.
				 */
				[
					'title' => __( 'UPC Options', 'woocommerce-point-of-sale' ),
					/* translators: %1$s code tag %2$s closing code tag */
					'desc'  => sprintf( __( 'Adjust how the scanned UPC-A barcodes are processed before adding to cart. UPC-A barcodes follow the pattern %1$s2IIIIICVVVVC%2$s, where %1$sI%2$s is the product identifier, %1$sC%2$s are check digits and %1$sV%2$s is the value of the barcode.', 'woocommerce-point-of-sale' ), '<code>', '</code>' ),
					'type'  => 'title',
					'id'    => 'upc_options',
				],
				[
					'name'     => __( 'Middle Check Digit', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_upca_disable_middle_check_digit',
					'type'     => 'checkbox',
					'desc'     => __( 'Disable middle check digit', 'woocommerce-point-of-sale' ),
					/* translators: %1$s code tag %2$s closing code tag */
					'desc_tip' => sprintf( __( 'Replaces the middle check digit %1$sC%2$s for price or quantity value %1$sV%2$s of the barcode.', 'woocommerce-point-of-sale' ), '<code>', '</code>' ),
					'default'  => 'no',
					'autoload' => true,
				],
				[
					'name'     => __( 'Use Middle Check Digit', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_upca_use_middle_check_digit',
					'type'     => 'select',
					/* translators: %1$s code tag %2$s closing code tag */
					'desc'     => sprintf( __( 'Use the extra check digit %1$sC%2$s for product identifier %1$sI%2$s or value %1$sV%2$s of the barcode.', 'woocommerce-point-of-sale' ), '<code>', '</code>' ),
					'default'  => 'value',
					'options'  => [
						'value'      => __( 'Value', 'woocommerce-point-of-sale' ),
						'identifier' => __( 'Identifier', 'woocommerce-point-of-sale' ),
					],
					'autoload' => true,
				],
				[
					'title'   => __( 'Barcode Type', 'woocommerce-point-of-sale' ),
					/* translators: %1$s code tag %2$s closing code tag */
					'desc'    => sprintf( __( 'Choose what the value %1$sV%2$s represents i.e. price or weight.', 'woocommerce-point-of-sale' ), '<code>', '</code>' ),
					'id'      => 'wc_pos_upca_type',
					'default' => 'price',
					'type'    => 'select',
					'options' => [
						'price'  => 'Price',
						'weight' => 'Weight',
					],
				],
				[
					'title'    => __( 'Multiplier', 'woocommerce-point-of-sale' ),
					/* translators: %1$s code tag %2$s closing code tag */
					'desc'     => sprintf( __( 'Choose how the value %1$sV%2$s is calculated.', 'woocommerce-point-of-sale' ), '<code>', '</code>' ),
					'desc_tip' => __( 'E.g. a multiplier of 10 means that the embdded value will be divided by 10 before adding to cart.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_upca_multiplier',
					'default'  => '100',
					'type'     => 'select',
					'options'  => [
						1   => '1',
						10  => '10',
						100 => '100',
					],
				],
				[
					'type' => 'sectionend',
					'id'   => 'upc_options',
				],
			]
		);
	}

	/**
	 * Filter scanning fields.
	 *
	 * @param $fields Fields.
	 */
	public static function filter_scanning_fields( $fields ) {
		global $wpdb;

		$product_meta_keys = get_transient( 'wc_pos_product_meta_keys' );

		if ( ! $product_meta_keys ) {
			// Get used meta keys from the database.
			$result            = $wpdb->get_results( "SELECT DISTINCT pm.meta_key FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE p.post_type = 'product'" );
			$product_meta_keys = array_map(
				function( $item ) {
					return $item->meta_key;
				},
				$result
			);
			set_transient( 'wc_pos_product_meta_keys', $product_meta_keys, 24 * HOUR_IN_SECONDS );
		}

		if ( $product_meta_keys ) {
			foreach ( $product_meta_keys as $key ) {
				// Filter known meta keys.
				switch ( $key ) {
					case 'total_sales':
					case '_edit_last':
					case '_edit_lock':
					case '_tax_status':
					case '_tax_class':
					case '_manage_stock':
					case '_backorders':
					case '_sold_individually':
					case '_virtual':
					case '_downloadable':
					case '_download_limit':
					case '_download_expiry':
					case '_wc_average_rating':
					case '_wc_review_count':
					case '_product_version':
					case '_wpcom_is_markdown':
					case '_wp_old_slug':
					case '_product_image_gallery':
					case '_thumbnail_id':
					case '_product_attributes':
					case '_price':
					case '_regular_price':
					case '_sale_price':
					case '_downloadable_files':
					case '_children':
					case '_product_url':
					case '_button_text':
					case '_stock':
					case '_stock_status':
					case '_variation_description':
					case '_sku':
					case '_pos_visibility':
					case '_wpm_gtin_code_label':
					case '_wc_pos_outlet_stock':
						continue 2;
					case 'hwp_product_gtin':
					case '_wpm_gtin_code':
						$label = __( 'GTIN', 'woocommerce-point-of-sale' );
						break;
					default:
						$label = $key;
				}

				$fields[ $key ] = $label;
			}
		}

		return $fields;
	}
}
