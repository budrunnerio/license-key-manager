<?php
/**
 * REST API Customers Controller
 *
 * Handles requests to wc-pos/customers.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/API
 */

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore as CustomersDataStore;

/**
 * WC_POS_REST_Customers_Controller.
 */
class WC_POS_REST_Customers_Controller extends WC_REST_Customers_Controller {
	protected $namespace = 'wc-pos';
	protected $rest_base = 'customers';

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
	 * Overrides the original method to always include customer's meta data.
	 *
	 * @since  6.0.0
	 *
	 * @param  WC_Data $object WC_Data instance.
	 * @return array
	 */
	protected function get_formatted_item_data( $object ) {
		$formatted_data              = parent::get_formatted_item_data( $object );
		$data                        = $object->get_data();
		$formatted_data['meta_data'] = $data['meta_data'];

		return $formatted_data;
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

		$meta_data = array_map(
			function( $meta ) {
				return $meta->get_data();
			},
			$data['meta_data']
		);

		// Card number.
		$find_card_number    = array_search( 'wc_pos_user_card_number', array_column( $meta_data, 'key' ), true );
		$card_number         = $find_card_number ? $meta_data[ $find_card_number ] : null;
		$data['card_number'] = $card_number ? $card_number['value'] : '';

		// Customer status.
		$data['status'] = '';
		if ( 'default' === get_option( 'wc_pos_customer_status_field' ) ) {
			$find_customer_status = array_search( 'wc_pos_customer_status', array_column( $meta_data, 'key' ), true );
			$customer_status      = $find_customer_status ? $meta_data[ $find_customer_status ] : null;
			$data['status']       = $customer_status && isset( $customer_status['value'] ) ? $customer_status['value'] : '';
		} elseif ( 0 === strpos( get_option( 'wc_pos_customer_status_field' ), 'acf_' ) && function_exists( 'get_field' ) ) {
			$acf_field_name   = substr( get_option( 'wc_pos_customer_status_field' ), 4 );
			$acf_field_object = get_field_object( $acf_field_name, 'user_' . $data['id'] );

			$data['status'] = $acf_field_object && isset( $acf_field_object['value'] ) ? $acf_field_object['value'] : '';
		}

		// Additional customer data.
		$customer              = new WC_Customer( $data['id'] );
		$data['display_name']  = '';
		$data['is_vat_exempt'] = false;

		if ( $customer && is_a( $customer, 'WC_Customer' ) ) {
			$data['display_name']  = $customer->get_display_name();
			$data['is_vat_exempt'] = $customer->is_vat_exempt();
		}

		// Analtyics data.
		// FIXME: nesting REST requests is bad.
		$customer_id       = CustomersDataStore::get_customer_id_by_user_id( $data['id'] );
		$analytics_request = new WP_REST_Request( 'GET', "/wc-analytics/customers/{$customer_id}" );

		$analytics_response = rest_do_request( $analytics_request );
		$analytics_results  = $analytics_response->get_data();

		$data['orders_count']    = 0;
		$data['total_spend']     = 0;
		$data['avg_order_value'] = 0;

		if ( $analytics_results && ! empty( $analytics_results ) ) {
			$data['orders_count']    = $results[0]['orders_count'] ?? 0;
			$data['total_spend']     = $results[0]['total_spend'] ?? 0;
			$data['avg_order_value'] = $results[0]['avg_order_value'] ?? 0;
		}

		// ACF user form fields.
		if ( function_exists( 'get_field_objects' ) ) {
			$field_objects = get_field_objects( 'user_' . $data['id'] );

			if ( $field_objects && is_array( $field_objects ) ) {
				$data['acf'] = array_filter(
					array_map(
						function ( $field ) {
							$label = $field['label'];
							$name  = $field['name'];
							$type  = $field['type'];
							$value = $field['value'];

							switch ( $type ) {
								case 'checkbox':
									$value = array_map( [ $this, 'normalize_acf_field_value' ], $value );
									break;
								case 'button_group':
								case 'email':
								case 'number':
								case 'radio':
								case 'range':
								case 'select':
								case 'text':
								case 'textarea':
								case 'true_false':
								case 'url':
									$value = $this->normalize_acf_field_value( $value );
									break;
								default:
									return null; // Filter out unsupported field types.
							}

							return [
								'label' => $label,
								'type'  => $type,
								'value' => $value,
							];
						},
						array_values( get_field_objects( 'user_' . $data['id'] ) )
					)
				);

				// Ensure that $data['acf'] will be parsed as an array in the response.
				$data['acf'] = array_values( $data['acf'] );
			}
		}

		$data = array_filter(
			$data,
			function( $prop ) {
				// Props should always match TS interfaces.
				$props_filtered = [
					'acf',
					'avatar_url',
					'avg_order_value',
					'billing',
					'card_number',
					'status',
					'date_created',
					'display_name',
					'email',
					'first_name',
					'id',
					'is_paying_customer',
					'is_vat_exempt',
					'last_name',
					'orders_count',
					'role',
					'shipping',
					'total_spend',
					'username',
				];

				return in_array( $prop, $props_filtered, true );
			},
			ARRAY_FILTER_USE_KEY
		);

		$response->set_data( $data );
		return rest_ensure_response( $response );
	}

	private function normalize_acf_field_value( $value ) {
		if ( is_array( $value ) ) {
			return isset( $value['label'] ) ? $value['label'] : $value['value'];
		}

		return $value;
	}

	protected function prepare_links( $object ) {
		return []; // Remove links.
	}

	/**
	 * Create a single customer.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		try {
			if ( ! empty( $request['id'] ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_customer_exists', __( 'Cannot create existing resource.', 'woocommerce-point-of-sale' ), 400 );
			}

			$request['username'] = ! empty( $request['username'] ) ? $request['username'] : '';
			$request['password'] = ! empty( $request['password'] ) ? $request['password'] : wp_generate_password();

			// Create customer.
			$customer = new WC_Customer();
			$customer->set_username( $request['username'] );
			$customer->set_password( $request['password'] );
			$customer->set_email( $request['email'] );
			$customer->add_meta_data( '_created_via', 'pos', true );
			$this->update_customer_meta_fields( $customer, $request );
			$customer->set_display_name( $customer->get_first_name() . ' ' . $customer->get_last_name() );
			$customer->save();

			if ( ! $customer->get_id() ) {
				throw new WC_REST_Exception( 'woocommerce_rest_cannot_create', __( 'This resource cannot be created.', 'woocommerce-point-of-sale' ), 400 );
			}

			$user_data = get_userdata( $customer->get_id() );
			$this->update_additional_fields_for_object( $user_data, $request );

			/**
			 * Fires after a customer is created or updated via the REST API.
			 *
			 * @since 5.0.0
			 *
			 * @param WP_User         $user_data Data used to create the customer.
			 * @param WP_REST_Request $request   Request object.
			 * @param boolean         $creating  True when creating customer, false when updating customer.
			 */
			do_action( 'wc_pos_rest_insert_customer', $user_data, $request, true );

			$request->set_param( 'context', 'edit' );
			$response = $this->prepare_item_for_response( $user_data, $request );
			$response = rest_ensure_response( $response );
			$response->set_status( 201 );
			$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $customer->get_id() ) ) );

			return $response;
		} catch ( Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), [ 'status' => $e->getCode() ] );
		}
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

		$headers = $response->get_headers();

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
