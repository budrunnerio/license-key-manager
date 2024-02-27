<?php
/**
 * REST API Product Categories Controller
 *
 * Handles requests to wc-pos/products/categories.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_REST_Product_Categories_Controller.
 */
class WC_POS_REST_Product_Categories_Controller extends WC_REST_Product_Categories_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-pos';

	/**
	 * Register additional routes for products/categories.
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

	protected function prepare_links( $object, $request ) {
		return []; // Remove links.
	}

	/**
	 * Modify the response.
	 *
	 * @param WC_Data         $object  Object data.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $object, $request ) {
		$response = parent::prepare_item_for_response( $object, $request );
		$data     = $response->get_data();

		// Ignore object?
		$ignore = array_map( 'intval', explode( ',', $request['ignore'] ) );
		if ( ! empty( $ignore ) && in_array( intval( $data['id'] ), $ignore ) ) {
			return null;
		}

		// Decode escaped ampersands (&amp;).
		$data['name'] = htmlspecialchars_decode( $data['name'] );

		$response->set_data( $data );

		return rest_ensure_response( $response );
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
	 * This a lighter endpoint to get only the totals instead of using get_items(). It takes the
	 * same query arguments as get_items() or the /products/categories endpoint and returns the
	 * totals based on these passed arguments.
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
