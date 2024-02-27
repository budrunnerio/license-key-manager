<?php
/**
 * REST API Coupons Controller
 *
 * Handles requests to wc-pos/coupons.
 *
 * @todo Filter response to unset uneeded data.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_REST_Coupons_Controller.
 */
class WC_POS_REST_Coupons_Controller extends WC_REST_Coupons_Controller {

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
	protected $rest_base = 'coupons';

	/**
	 * Register additional routes for coupons.
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
	public function prepare_item_for_response( $user_data, $request ) {
		$response = parent::prepare_item_for_response( $user_data, $request );
		$data     = $response->get_data();

		$data = array_filter(
			$data,
			function( $prop ) {
				// Props should always match TS interfaces.
				$props_filtered = [
					'amount',
					'code',
					'date_created_gmt',
					'date_expires_gmt',
					'date_modified_gmt',
					'description',
					'discount_type',
					'email_restrictions',
					'exclude_sale_items',
					'excluded_product_categories',
					'excluded_product_ids',
					'free_shipping',
					'id',
					'individual_use',
					'limit_usage_to_x_items',
					'maximum_amount',
					'meta_data',
					'minimum_amount',
					'product_categories',
					'product_ids',
					'status',
					'usage_count',
					'usage_limit',
					'usage_limit_per_user',
					'used_by',
				];

				return in_array( $prop, $props_filtered, true );
			},
			ARRAY_FILTER_USE_KEY
		);

		$response->set_data( $data );
		return rest_ensure_response( $response );
	}

	protected function prepare_links( $object, $request ) {
		return []; // Remove links.
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
	 * query arguments as get_items() or the /coupons endpoint and returns the totals based on
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
			function( &$item ) {
				return $item['id'];
			},
			$data
		);

		$response->set_data( $data );
		$response = rest_ensure_response( $data );

		return $response;
	}
}
