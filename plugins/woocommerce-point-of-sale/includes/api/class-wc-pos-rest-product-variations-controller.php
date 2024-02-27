<?php
/**
 * REST API Product Variations Controller
 *
 * Handles requests to wc-pos/products/variations.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_REST_Product_Variations_Controller.
 */
class WC_POS_REST_Product_Variations_Controller extends WC_REST_Product_Variations_Controller {

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
	protected $rest_base = 'products/(?P<product_id>[\d]+)/variations';

	/**
	 * Modify the response.
	 *
	 * @param WC_Data         $object  Object data.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_object_for_response( $object, $request ) {
		$response  = parent::prepare_object_for_response( $object, $request );
		$data      = $response->get_data();
		$variation = new WC_Product_Variation( $data['id'] );

		$data = array_filter(
			$data,
			function( $prop ) {
				// Any change to the allowed properties should be reflected in the TypeScript interfaces
				// that describe product data objects.
				$props_filtered = [
					'attributes',
					'backordered',
					'backorders',
					'backorders_allowed',
					'date_created_gmt',
					'date_on_sale_from_gmt',
					'date_on_sale_to_gmt',
					'description',
					'dimensions',
					'downloadable',
					'id',
					'image',
					'manage_stock',
					'meta_data',
					'name',
					'on_sale',
					'parent_id',
					'permalink',
					'price',
					'purchasable',
					'regular_price',
					'sale_price',
					'shipping_class',
					'shipping_class_id',
					'sku',
					'slug',
					'status',
					'stock_quantity',
					'stock_status',
					'tax_class',
					'tax_status',
					'type',
					'virtual',
					'weight',
				];

				return in_array( $prop, $props_filtered, true );
			},
			ARRAY_FILTER_USE_KEY
		);

		if ( isset( $data['meta_data'] ) && is_array( $data['meta_data'] ) ) {
			/**
			 * Removed meta data fields.
			 *
			 * @since 5.0.0
			 */
			$remove_meta_data_fields = array_unique( apply_filters( 'wc_pos_rest_product_variations_removed_meta_data_fields', [] ) );

			$data['meta_data'] = array_values(
				array_filter(
					$data['meta_data'],
					function( $field ) use ( $remove_meta_data_fields ) {
						return ! in_array( $field->key, $remove_meta_data_fields, true );
					}
				)
			);
		}

		// Add additional properties.
		$data['type']      = 'variation';
		$data['name']      = $variation->get_name();
		$data['slug']      = $variation->get_slug();
		$data['parent_id'] = $variation->get_parent_id();

		// Add outlet stock data.
		$headers_data      = WC_POS_API::get_headers_data();
		$outlet_stock_data = wc_pos_get_outlet_stock_data( $data['id'], $headers_data['outlet_id'] );
		$data              = array_merge( $data, $outlet_stock_data );

		$response->set_data( $data );

		return rest_ensure_response( $response );
	}

	protected function prepare_links( $object, $request ) {
		return []; // Remove links.
	}

	/**
	 * Get the attributes for a product variation.
	 *
	 * @param WC_Product_Variation $product Variation instance.
	 *
	 * @return array
	 */
	protected function get_attributes( $variation ) {
		$parent     = wc_get_product( $variation->get_parent_id() );
		$attributes = [];

		foreach ( $variation->get_variation_attributes() as $attribute_name => $attribute ) {
			$name = str_replace( 'attribute_', '', $attribute_name );

			if ( empty( $attribute ) && '0' !== $attribute ) {
				continue;
			}

			// Taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`.
			if ( 0 === strpos( $attribute_name, 'attribute_pa_' ) ) {
				$option_term = get_term_by( 'slug', urldecode( $attribute ), urldecode( $name ) );

				$attributes[] = [
					'id'     => wc_attribute_taxonomy_id_by_name( $name ),
					'name'   => $this->get_attribute_taxonomy_name( $name, $parent ),
					'slug'   => sanitize_title( $name ),
					'option' => [
						'name' => $option_term && ! is_wp_error( $option_term ) ? $option_term->name : $attribute,
						'slug' => $option_term && ! is_wp_error( $option_term ) ? $option_term->slug : $attribute_name,
					],
				];
			} else {
				$attributes[] = [
					'id'     => 0,
					'name'   => $this->get_attribute_taxonomy_name( $name, $parent ),
					'slug'   => $name,
					'option' => [
						'name' => $attribute,
						'slug' => sanitize_title( $attribute ),
					],
				];
			}
		}

		return $attributes;
	}

	/**
	 * Get the image for a product variation.
	 *
	 * @param WC_Product_Variation $variation Variation data.
	 * @return array
	 */
	protected function get_image( $variation, $context = 'view' ) {
		if ( ! $variation->get_image_id() ) {
			return;
		}

		$attachment_id   = $variation->get_image_id();
		$attachment_post = get_post( $attachment_id );
		if ( is_null( $attachment_post ) ) {
			return;
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
			return;
		}

		if ( ! isset( $image ) ) {
			return [
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
	}
}
