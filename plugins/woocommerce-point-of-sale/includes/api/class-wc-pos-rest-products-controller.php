<?php
/**
 * REST API Products Controller
 *
 * Handles requests to wc-pos/products.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_REST_Products_Controller.
 */
class WC_POS_REST_Products_Controller extends WC_REST_Products_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-pos';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'products';

	/**
	 * Register additional routes for products.
	 *
	 * @todo create schemas.
	 */
	public function register_routes() {
		parent::register_routes();

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/totals',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_totals' ],
					'permission_callback' => [ $this, 'get_totals_permissions_check' ],
					'args'                => $this->get_collection_params(),
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/ids',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_ids' ],
					'permission_callback' => [ $this, 'get_ids_permissions_check' ],
					'args'                => $this->get_collection_params(),
				],
			]
		);
	}

	/**
	 * Prepares the response object.
	 *
	 * @param WC_Data         $object  Object data.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_object_for_response( $object, $request ) {
		$response = parent::prepare_object_for_response( $object, $request );
		$data     = $response->get_data();

		// Ignore this item?
		// Used to save resources on the items that have already been collected from a previous response.
		if ( isset( $request['ignore'] ) ) {
			$ignore = array_map( 'intval', explode( ',', $request['ignore'] ) );

			if ( ! empty( $ignore ) && in_array( intval( $data['id'] ), $ignore ) ) {
				return null;
			}
		}

		$data = array_filter(
			$data,
			function( $prop ) {
				// Any change to the allowed properties should be reflected in the TypeScript interfaces
				// that describe product data objects.
				// @todo Consider using spatie/laravel-data
				$props_filtered = [
					'attributes',
					'backordered',
					'backorders',
					'backorders_allowed',
					'categories',
					'cross_sell_ids',
					'date_created_gmt',
					'date_on_sale_from_gmt',
					'date_on_sale_to_gmt',
					'description',
					'dimensions',
					'downloadable',
					'featured',
					'grouped_products',
					'id',
					'images',
					'manage_stock',
					'meta_data',
					'name',
					'on_sale',
					'parent_id',
					'permalink',
					'price',
					'purchasable',
					'purchase_note',
					'regular_price',
					'related_ids',
					'sale_price',
					'shipping_class',
					'shipping_class_id',
					'shipping_required',
					'shipping_taxable',
					'short_description',
					'sku',
					'slug',
					'sold_individually',
					'status',
					'stock_quantity',
					'stock_status',
					'tags',
					'tax_class',
					'tax_status',
					'total_sales',
					'type',
					'upsell_ids',
					'variations',
					'virtual',
					'weight',
				];

				return in_array( $prop, $props_filtered, true );
			},
			ARRAY_FILTER_USE_KEY
		);

		// Exclude meta data.
		if ( isset( $data['meta_data'] ) && is_array( $data['meta_data'] ) ) {
			/**
			 * Removed meta data fields.
			 *
			 * @since 5.0.0
			 */
			$remove_meta_data_fields = array_unique( apply_filters( 'wc_pos_rest_products_removed_meta_data_fields', [] ) );

			$data['meta_data'] = array_values(
				array_filter(
					$data['meta_data'],
					function( $field ) use ( $remove_meta_data_fields ) {
						return ! in_array( $field->key, $remove_meta_data_fields, true );
					}
				)
			);
		}

		// Add product variations.
		if ( isset( $data['variations'] ) ) {
			$page        = 1;
			$total_pages = 1;
			$variations  = [];

			while ( $page <= $total_pages ) {
				$request = new WP_REST_Request(
					'GET',
					"/wc-pos/products/{$data['id']}/variations"
				);
				$request->set_param( 'page', $page );
				$request->set_param( 'per_page', 100 );

				$res     = rest_do_request( $request );
				$headers = $res->get_headers();
				$results = $res->get_data();

				$variations  = array_merge( $variations, $results );
				$total_pages = intval( $headers['X-WP-TotalPages'] );
				$page++;
			}

			// Sort variations based on the order of $data['variations] which is the correct order.
			$variations = array_map(
				function( $id ) use ( $variations ) {
					return $variations[ array_search( $id, array_column( $variations, 'id' ) ) ];
				},
				$data['variations']
			);

			$data['variations'] = $variations;
		}

		// Modify product attributes to include slugs.
		// @todo move this to self::get_attributes().
		if ( isset( $data['attributes'] ) ) {
			foreach ( $data['attributes'] as &$attribute ) {
				$taxonomy          = wc_get_attribute( $attribute['id'] );
				$attribute['slug'] = sanitize_title( $taxonomy ? $taxonomy->slug : wc_sanitize_taxonomy_name( $attribute['name'] ) );

				$terms   = wc_get_product_terms( $data['id'], $attribute['slug'], [ 'fields' => 'all' ] );
				$options = [];
				if ( $taxonomy && count( $terms ) ) {
					$options = array_map(
						function( $term ) {
							return [
								'slug' => sanitize_title( $term->slug ),
								'name' => $term->name,
							];
						},
						$terms
					);
				} elseif ( isset( $attribute['options'] ) ) {
					foreach ( $attribute['options'] as $option ) {
						$options[] = [
							'slug' => sanitize_title( wc_sanitize_taxonomy_name( $option ) ),
							'name' => $option,
						];
					}
				}

				$attribute['options'] = $options;
			}
		}

		// Add category ancestors.
		foreach ( $data['categories'] as &$category ) {
			$category['ancestors'] = get_ancestors( $category['id'], 'product_cat' );
		}

		// Add outlet stock data.
		$headers_data      = WC_POS_API::get_headers_data();
		$outlet_stock_data = wc_pos_get_outlet_stock_data( $data['id'], $headers_data['outlet_id'] );
		$data              = array_merge( $data, $outlet_stock_data );

		$response->set_data( $data );

		return rest_ensure_response( $response );
	}

	/**
	 * Prepares a single product for create or update.
	 *
	 * @param  WP_REST_Request $request Request object.
	 * @param  boolean         $creating If creating a new object.
	 *
	 * @throws WC_REST_Exception When fails to set any item.
	 *
	 * @return WP_Error|WC_Data
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$object = parent::prepare_object_for_database( $request, $creating );

		if ( 'yes' === get_option( 'wc_pos_manage_outlet_stock' ) && isset( $request['outlet_stock_quantity'] ) ) {
			$outlet_stock_quantity = intval( $request['outlet_stock_quantity'] );
			$headers_data          = WC_POS_API::get_headers_data();

			if ( $headers_data['outlet_id'] ) {
				wc_pos_update_product_outlet_stock( $object, [ $headers_data['outlet_id'] => $outlet_stock_quantity ], 'update', true );
			}
		}

		return $object;
	}

	protected function prepare_links( $object, $request ) {
		return []; // Remove links.
	}

	/**
	 * Get a collection of products.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		// Add query filters.
		add_filter( 'posts_join', [ $this, 'add_wp_query_join' ], 10, 2 );
		add_filter( 'posts_where', [ $this, 'add_wp_query_filter' ], 10, 2 );
		add_filter( 'posts_distinct_request', [ $this, 'add_wp_query_distinct' ], 10, 2 );

		$response = parent::get_items( $request );

		// Remove the added filters right away.
		remove_filter( 'posts_join', [ $this, 'add_wp_query_join' ], 10 );
		remove_filter( 'posts_where', [ $this, 'add_wp_query_filter' ], 10 );
		remove_filter( 'posts_distinct_request', [ $this, 'add_wp_query_distinct' ], 10 );

		return $response;
	}

	/**
	 * Get product attribute taxonomy name.
	 *
	 * Important: this function is overriden here to apply sanitize_text() on $slug before using it.
	 * This is needed to make sure slugs are encoded before using them in comparisons.
	 *
	 * @param string     $slug    Taxonomy name.
	 * @param WC_Product $product Product data.
	 *
	 * @return string
	 */
	protected function get_attribute_taxonomy_name( $slug, $product ) {
		// Format slug so it matches attributes of the product.
		$slug       = wc_attribute_taxonomy_slug( $slug );
		$attributes = $product->get_attributes();
		$attribute  = false;

		// pa_ attributes.
		if ( isset( $attributes[ sanitize_title( wc_attribute_taxonomy_name( $slug ) ) ] ) ) {
			$attribute = $attributes[ sanitize_title( wc_attribute_taxonomy_name( $slug ) ) ];
		} elseif ( isset( $attributes[ sanitize_title( $slug ) ] ) ) {
			$attribute = $attributes[ sanitize_title( $slug ) ];
		}

		if ( ! $attribute ) {
			return $slug;
		}

		// Taxonomy attribute name.
		if ( $attribute->is_taxonomy() ) {
			$taxonomy = $attribute->get_taxonomy_object();
			return $taxonomy->attribute_label;
		}

		// Custom product attribute name.
		return $attribute->get_name();
	}

	/**
	 * Join posts meta tables when product search or low stock query is present.
	 *
	 * @param string $join Join clause used to search posts.
	 * @param object $wp_query WP_Query object.
	 * @return string
	 */
	public function add_wp_query_join( $join, $wp_query ) {
		if ( ! $wp_query->is_main_query() ) {
			return $join;
		}

		global $wpdb;
		$wpdb->query( 'SET SESSION SQL_BIG_SELECTS = 1' );

		$search = isset( $_REQUEST['search'] ) ? wc_clean( $_REQUEST['search'] ) : false;
		if ( $search ) {
			// Join scanning fields.
			$scanning_fields = get_option( 'wc_pos_scanning_fields', [ '_sku' ] );
			$scanning_fields = empty( $scanning_fields ) ? [ '_sku' ] : array_unique( $scanning_fields );

			foreach ( $scanning_fields as $field ) {
				$join .= " LEFT JOIN {$wpdb->postmeta} pm_{$field} ON pm_{$field}.post_id = {$wpdb->posts}.ID AND pm_{$field}.meta_key = '{$field}'";
			}

			// Join postmeta on _variation_description
			$join .= " LEFT JOIN {$wpdb->postmeta} pm_vardesc ON pm_vardesc.post_id = {$wpdb->posts}.ID AND pm_vardesc.meta_key = '_variation_description'";
		}

		// Join postmeta on _pos_visibility.
		$join .= " LEFT JOIN {$wpdb->postmeta} pm_vis ON pm_vis.post_id = {$wpdb->posts}.ID AND pm_vis.meta_key = '_pos_visibility'";

		// Join postmeta on _stock_status
		$join .= " LEFT JOIN {$wpdb->postmeta} pm_stk ON pm_stk.post_id = {$wpdb->posts}.ID AND pm_stk.meta_key = '_stock_status'";

		// Join on post terms.
		$join .= " INNER JOIN {$wpdb->term_relationships} AS term_rel ON {$wpdb->posts}.ID = term_rel.object_id"
			. " INNER JOIN {$wpdb->term_taxonomy} AS term_tax ON term_rel.term_taxonomy_id = term_tax.term_taxonomy_id"
			. " INNER JOIN {$wpdb->terms} AS terms ON term_tax.term_id = terms.term_id";

		return $join;
	}

	/**
	 * Add in conditional search filters for products.
	 *
	 * @param string $where Where clause used to search posts.
	 * @param object $wp_query WP_Query object.
	 * @return string
	 */
	public function add_wp_query_filter( $where, $wp_query ) {
		if ( ! $wp_query->is_main_query() ) {
			return $where;
		}

		global $wpdb;

		$search = isset( $_REQUEST['search'] ) ? wc_clean( $_REQUEST['search'] ) : false;
		if ( $search ) {
			$includes = get_option( 'wc_pos_search_includes', [ 'title', 'sku' ] );
			$q        = $wp_query->query_vars;
			$where    = '';

			if ( ! empty( $q['post__not_in'] ) ) {
				$where .= " AND {$wpdb->posts}.ID NOT IN (" . implode( ',', array_map( 'absint', $q['post__not_in'] ) ) . ')';
			}

			$scanning_fields = get_option( 'wc_pos_scanning_fields', [ '_sku' ] );
			$scanning_fields = empty( $scanning_fields ) ? [ '_sku' ] : array_unique( $scanning_fields );

			// Barcode scanning. Exact match of the SKU and/or the other scanning fields.
			$scanning = isset( $_GET['scanning'] ) ? boolval( $_GET['scanning'] ) : false;
			if ( $scanning ) {
				$like = '1 != 1';

				foreach ( $scanning_fields as $field ) {
					$like .= " OR (REPLACE(pm_{$field}.meta_value, ' ', '') LIKE REPLACE('{$search}', ' ', ''))";
				}

				$where .= " AND ({$like})";
			} else {
				$where .= ' AND (';
				$where .= "(REPLACE({$wpdb->posts}.post_title, ' ', '') LIKE REPLACE('%{$search}%', ' ', ''))";
				$where .= " OR (REPLACE({$wpdb->posts}.post_name, ' ', '') LIKE REPLACE('%{$search}%', ' ', ''))";

				if ( in_array( 'content', $includes, true ) ) {
					$where .= " OR (REPLACE({$wpdb->posts}.post_content, ' ', '') LIKE REPLACE('%{$search}%', ' ', ''))";
					$where .= " OR (REPLACE(pm_vardesc.meta_value, ' ', '') LIKE REPLACE('%{$search}%', ' ', ''))";
				}

				if ( in_array( 'excerpt', $includes, true ) ) {
					$where .= " OR (REPLACE({$wpdb->posts}.post_excerpt, ' ', '') LIKE REPLACE('%{$search}%', ' ', ''))";
				}

				if ( in_array( 'sku', $includes, true ) ) {
					// Scanning fields.
					foreach ( $scanning_fields as $field ) {
						$where .= " OR (REPLACE(pm_{$field}.meta_value, ' ', '') LIKE REPLACE('%{$search}%', ' ', ''))";
					}
				}

				// Close AND.
				$where .= ')';
			}

			$where .= " AND {$wpdb->posts}.post_type IN ('product', 'product_variation')";
			$where .= " AND {$wpdb->posts}.post_status = 'publish'";
		}

		// Filter by product type.
		$where .= " AND term_tax.taxonomy = 'product_type' AND terms.slug IN ('simple', 'variable', 'grouped', 'external')";

		if ( 'yes' === get_option( 'wc_pos_visibility', 'no' ) ) {
			// Variations does not have a _pos_visibility meta, so we need to check their parent's visibliity.
			$where .= " AND (
				(
					pm_vis.meta_value IS NULL AND
					{$wpdb->posts}.post_type = 'product'
				)
				OR
				(
					pm_vis.meta_value IS NULL AND
					{$wpdb->posts}.post_type = 'product_variation' AND
					{$wpdb->posts}.post_parent != 0 AND
					( SELECT ppm.meta_value FROM {$wpdb->postmeta} ppm
						WHERE ppm.post_id = {$wpdb->posts}.post_parent
						AND ppm.meta_key = '_pos_visibility' LIMIT 1
					) NOT IN ('online')
				)
				OR pm_vis.meta_value NOT IN ('online')
			)";
		}

		if ( 'yes' !== get_option( 'wc_pos_show_out_of_stock_products', 'no' ) ) {
			$where .= " AND pm_stk.meta_value != 'outofstock'";
		}

		return $where;
	}

	public function add_wp_query_distinct( $distinct, $wp_query ) {
		if ( ! $wp_query->is_main_query() ) {
			return $distinct;
		}

		return 'DISTINCT';
	}

	/**
	 * Get the images for a product.
	 *
	 * @param WC_Product $product Product instance.
	 * @return array
	 */
	protected function get_images( $product ) {
		$images         = [];
		$attachment_ids = [];

		// Add featured image.
		if ( $product->get_image_id() ) {
			$attachment_ids[] = $product->get_image_id();
		}

		// Add gallery images.
		$attachment_ids = array_merge( $attachment_ids, $product->get_gallery_image_ids() );

		// Build image data.
		foreach ( $attachment_ids as $attachment_id ) {
			$attachment_post = get_post( $attachment_id );
			if ( is_null( $attachment_post ) ) {
				continue;
			}

			$attachment_thumbnail = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
			$attachment_medium    = wp_get_attachment_image_src( $attachment_id, 'medium' );
			$attachment_large     = wp_get_attachment_image_src( $attachment_id, 'large' );
			$attachment_full      = wp_get_attachment_image_src( $attachment_id, 'full' );

			if (
				! is_array( $attachment_thumbnail ) ||
				! is_array( $attachment_medium ) ||
				! is_array( $attachment_large ) ||
				! is_array( $attachment_full )
			) {
				continue;
			}

			$images[] = [
				'src'  => [
					'thumbnail' => current( $attachment_thumbnail ),
					'medium'    => current( $attachment_medium ),
					'large'     => current( $attachment_large ),
					'full'      => current( $attachment_full ),
				],
				'name' => get_the_title( $attachment_id ),
				'alt'  => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			];
		}

		return $images;
	}

	/**
	 * Check if a given request has access to read totals.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_totals_permissions_check( $request ) {
		if ( ! current_user_can( 'view_register' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce-point-of-sale' ), [ 'status' => rest_authorization_required_code() ] );
		}

		return true;
	}

	/**
	 * Check if a given request has access to read IDs.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_ids_permissions_check( $request ) {
		if ( ! current_user_can( 'view_register' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce-point-of-sale' ), [ 'status' => rest_authorization_required_code() ] );
		}

		return true;
	}

	/**
	 * Get request totals.
	 *
	 * A lighter endpoint to get the totals only instead of using get_items(). It takes the same
	 * query arguments as get_items() or the /products endpoint and returns the totals based on
	 * these passed arguments.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_totals( $request ) {
		$response = $this->get_items( $request );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$headers  = $response->get_headers();
		$response = rest_ensure_response(
			[
				'total'      => $headers['X-WP-Total'],
				'totalPages' => $headers['X-WP-TotalPages'],
			]
		);

		return $response;
	}

	/**
	 * Get item IDs.
	 *
	 * A lighter endpoint that only returns the item IDs.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_ids( $request ) {
		$response = $this->get_items( $request );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = $response->get_data();
		$data = array_map(
			function( $item ) {
				return $item['id'];
			},
			$data
		);

		$response->set_data( $data );
		$response = rest_ensure_response( $data );

		return $response;
	}
}
