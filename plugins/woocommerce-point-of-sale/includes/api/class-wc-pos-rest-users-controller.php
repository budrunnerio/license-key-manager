<?php
/**
 * REST API Users Controller
 *
 * Handles requests to wc-pos/users.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_REST_Users_Controller.
 */
class WC_POS_REST_Users_Controller extends WP_REST_Users_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->namespace = 'wc-pos';
		$this->rest_base = 'users';
	}

	/**
	 * Register additional routes for customers.
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
	 * @param WC_Data         $item  User object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = wc_pos_normalize_user_data( $item );
		return rest_ensure_response( $data );
	}

	protected function prepare_links( $object ) {
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
		add_filter( 'rest_user_query', [ __CLASS__, 'add_query_args' ], 10, 2 );
		add_filter( 'users_pre_query', [ __CLASS__, 'update_users_query' ], 10, 2 );

		$response = parent::get_items( $request );

		// Remove the added filters right away.
		remove_filter( 'rest_user_query', [ __CLASS__, 'add_query_args' ], 10 );
		remove_filter( 'users_pre_query', [ __CLASS__, 'add_query_filters' ], 10 );

		return $response;
	}

	public static function add_query_args( $prepared_args, $request ) {
		if ( isset( $request['outlet_id'] ) ) {
			$prepared_args['outlet_id'] = intval( $request['outlet_id'] );
		}

		return $prepared_args;
	}

	public static function update_users_query( $result, $query ) {
		global $wpdb;

		$outlet_id = empty( $query->query_vars['outlet_id'] ) ? null : $query->query_vars['outlet_id'];

		if ( $outlet_id ) {
			// DISTINCT.
			$query->query_fields = 'DISTINCT ' . $query->query_fields;

			// JOIN.
			$query->query_from .= " LEFT JOIN {$wpdb->usermeta} um_outlets ON um_outlets.user_id = {$wpdb->users}.ID AND um_outlets.meta_key = 'wc_pos_assigned_outlets'";

			// WHERE.
			$like                = esc_sql( sprintf( 's:%s:"%s";', strlen( $outlet_id ), $outlet_id ) );
			$query->query_where .= " AND um_outlets.meta_value LIKE '%{$like}%'";
		}

		return $result; // Returns null by default to run the normal WP queries.
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
	 * same query arguments as get_items() or the /customers endpoint and returns the
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

	/**
	 * Get the User's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function __x_get_item_schema() {
		$this->schema['properties']['outlet_id'] = [
			'type'        => 'number',
			'context'     => [ 'edit' ],
			'description' => __( 'Limit results to users assigned to specific outlet.', 'woocommerce-point-of-sale' ),
		];

		return $this->add_additional_fields_schema( $this->schema );
	}
}
