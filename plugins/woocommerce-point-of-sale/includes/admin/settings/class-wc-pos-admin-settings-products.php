<?php
/**
 * Settings > Point of Sale > Products.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/Admin/Settings
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Admin_Settings_Products.
 */
class WC_POS_Admin_Settings_Products {

	/**
	 * Returns section settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$attribute_taxonomies = wc_get_attribute_taxonomies();
		$product_attributes   = [];

		if ( ! empty( $attribute_taxonomies ) ) {
			foreach ( $attribute_taxonomies as $tax ) {
				$attribute_taxonomy_name = wc_attribute_taxonomy_name( $tax->attribute_name );
				$label                   = $tax->attribute_label ? $tax->attribute_label : $tax->attribute_name;

				$product_attributes[ $attribute_taxonomy_name ] = $label;
			}
		}

		// The already added custom attributes.
		foreach ( get_option( 'wc_pos_display_product_attributes', [] ) as $option ) {
			if ( ! isset( $product_attributes[ $option ] ) ) {
				$product_attributes[ $option ] = $option;
			}
		}

		/**
		 * The products settings section.
		 *
		 * @since 6.0.0
		 */
		return apply_filters(
			'wc_pos_settings_products_section',
			[
				/*
				 * Grid options.
				 */
				[
					'title' => __( 'Grid Options', 'woocommerce-point-of-sale' ),
					'type'  => 'title',
					'desc'  => __( 'The following options affect what products are displayed in the register.', 'woocommerce-point-of-sale' ),
					'id'    => 'grid_options',
				],
				[
					'name'     => __( 'Out of Stock', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_show_out_of_stock_products',
					'type'     => 'checkbox',
					'desc'     => __( 'Show out of stock products', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Displays out of stock products within the product grid and product search.', 'woocommerce-point-of-sale' ),
					'default'  => 'no',
					'autoload' => true,
				],
				[
					'name'     => __( 'Restock Scanning', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_allow_scanning_out_of_stock_products',
					'type'     => 'checkbox',
					'desc'     => __( 'Restock out of stock products', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Restock out of stock products when scanned to the register.', 'woocommerce-point-of-sale' ),
					'default'  => 'no',
				],
				[
					'title'    => __( 'Product Visiblity', 'woocommerce-point-of-sale' ),
					'desc'     => __( 'Enable product visibility control', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Allows you to show and hide products from either the POS, web or both shops.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_visibility',
					'default'  => 'no',
					'type'     => 'checkbox',
				],
				[
					'title'   => __( 'Hide Uncategorized', 'woocommerce-point-of-sale' ),
					'desc'    => __( 'Check this box to hide the Uncategorized product category.', 'woocommerce-point-of-sale' ),
					'id'      => 'wc_pos_hide_uncategorized',
					'default' => 'no',
					'type'    => 'checkbox',
				],
				[
					'type' => 'sectionend',
					'id'   => 'grid_options',
				],

				/*
				 * Search options.
				 */
				[
					'title' => __( 'Search Options', 'woocommerce-point-of-sale' ),
					'desc'  => __( 'The following options affect the searching of products in the register.', 'woocommerce-point-of-sale' ),
					'type'  => 'title',
					'id'    => 'search_options',
				],
				[
					'name'              => __( 'Search Includes', 'woocommerce-point-of-sale' ),
					'desc_tip'          => __( 'Select the product fields to be used when performing a product search.', 'woocommerce-point-of-sale' ),
					'id'                => 'wc_pos_search_includes',
					'class'             => 'wc-enhanced-select',
					'type'              => 'multiselect',
					/**
					 * Search terms.
					 *
					 * @since 5.0.0
					 */
					'options'           => apply_filters(
						'wc_pos_search_includes',
						[
							'title'      => __( 'Product Title', 'woocommerce-point-of-sale' ),
							'sku'        => __( 'Product SKU', 'woocommerce-point-of-sale' ),
							'content'    => __( 'Product Description', 'woocommerce-point-of-sale' ),
							'excerpt'    => __( 'Product Short Description', 'woocommerce-point-of-sale' ),
							'attributes' => __( 'Product Attributes', 'woocommerce-point-of-sale' ),
						]
					),
					'default'           => [ 'title', 'sku' ],
					'custom_attributes' => [ 'data-tags' => 'true' ],
				],
				[
					'name'              => __( 'Display Attributes', 'woocommerce-point-of-sale' ),
					'desc_tip'          => __( 'Select the product attirbutes to display in the product search results.', 'woocommerce-point-of-sale' ),
					'id'                => 'wc_pos_display_product_attributes',
					'class'             => 'wc-enhanced-select',
					'type'              => 'multiselect',
					/**
					 * Product attributes to display in the search dropdown.
					 *
					 * @since 5.0.0
					 */
					'options'           => apply_filters( 'wc_pos_display_product_attributes', $product_attributes ),
					'default'           => [],
					'custom_attributes' => [ 'data-tags' => 'true' ],
				],
				[
					'type' => 'sectionend',
					'id'   => 'search_options',
				],

				/**
				 * Product fields options.
				 */
				[
					'title' => __( 'Product Fields', 'woocommerce-point-of-sale' ),
					'desc'  => __( 'The following options affect fields when creating new products in the register.', 'woocommerce-point-of-sale' ),
					'type'  => 'title',
					'id'    => 'product_fields_options',
				],
				[
					'name'     => __( 'Required Fields', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_custom_product_required_fields',
					'type'     => 'multiselect',
					'class'    => 'wc-enhanced-select-required-fields',
					'desc_tip' => __( 'Select the fields that are required when creating a custom product through the register.', 'woocommerce-point-of-sale' ),
					'options'  => [
						'sku' => __( 'SKU', 'woocommerce-point-of-sale' ),
					],
					'default'  => [],
				],
				[
					'title'    => __( 'Publish Toggle', 'woocommerce-point-of-sale' ),
					'desc'     => __( 'Toggle publishing of product by default', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'User roles and capabilities are required to publish products.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_publish_product_default',
					'default'  => 'yes',
					'type'     => 'checkbox',
				],
				[
					'type' => 'sectionend',
					'id'   => 'product_fields_options',
				],

				/*
				 * Tile options.
				 */
				[
					'title' => __( 'Tile Options', 'woocommerce-point-of-sale' ),
					'desc'  => __( 'The following options affect the tiles on the product grid.', 'woocommerce-point-of-sale' ),
					'type'  => 'title',
					'id'    => 'tile_options',
				],
				[
					'title'    => __( 'Default Tile Sorting', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'This controls the default sort order of the tile.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_default_tile_orderby',
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'default'  => 'menu_order',
					'type'     => 'select',
					/**
					 * Filter: woocommerce_default_catalog_orderby_options
					 *
					 * @since 5.0.0
					 */
					'options'  => apply_filters(
						'woocommerce_default_catalog_orderby_options',
						[
							'menu_order' => __( 'Default sorting (custom ordering + name)', 'woocommerce-point-of-sale' ),
							'popularity' => __( 'Popularity (sales)', 'woocommerce-point-of-sale' ),
							'rating'     => __( 'Average Rating', 'woocommerce-point-of-sale' ),
							'date'       => __( 'Sort by most recent', 'woocommerce-point-of-sale' ),
							'price'      => __( 'Sort by price (asc)', 'woocommerce-point-of-sale' ),
							'price-desc' => __( 'Sort by price (desc)', 'woocommerce-point-of-sale' ),
							'title-asc'  => __( 'Name (asc)', 'woocommerce-point-of-sale' ),
						]
					),
				],
				[
					'title'    => __( 'Image Resolution', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Select the resolution for product images shown in the product grid tiles.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_image_resolution',
					'default'  => 'thumbnail',
					'class'    => 'wc-enhanced-select',
					'type'     => 'select',
					'options'  => [
						'thumbnail' => __( 'Thumbnail', 'woocommerce-point-of-sale' ),
						'medium'    => __( 'Medium', 'woocommerce-point-of-sale' ),
						'large'     => __( 'Large', 'woocommerce-point-of-sale' ),
						'full'      => __( 'Full Size', 'woocommerce-point-of-sale' ),
					],
				],
				[
					'name'     => __( 'Information Panel', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_show_product_preview',
					'type'     => 'checkbox',
					'desc'     => __( 'Enable product information panel', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Shows a button on each tile for cashiers to view full product details.', 'woocommerce-point-of-sale' ),
					'default'  => 'no',
					'autoload' => true,
				],
				[
					'title'    => __( 'Add to Cart Behaviour', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Control what happens to the grid after a product is added to the basket.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_after_add_to_cart_behavior',
					'default'  => 'home',
					'class'    => 'wc-enhanced-select',
					'type'     => 'select',
					'options'  => [
						'product'  => __( 'Stay on the selected product', 'woocommerce-point-of-sale' ),
						'category' => __( 'Return to selected category', 'woocommerce-point-of-sale' ),
						'home'     => __( 'Return to home grid', 'woocommerce-point-of-sale' ),
					],
				],
				[
					'type' => 'sectionend',
					'id'   => 'tile_options',
				],
			]
		);
	}
}
