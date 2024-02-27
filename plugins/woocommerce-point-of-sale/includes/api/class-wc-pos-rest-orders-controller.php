<?php
/**
 * Orders API
 *
 * Handles requests to the wc-pos/orders endpoint.
 *
 * @package WooCommerce_Point_Of_Sale/Classes
 */

defined( 'ABSPATH' ) || exit;

class WC_POS_REST_Orders_Controller extends WC_REST_Orders_Controller {
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
	protected $rest_base = 'orders';

	/**
	 * Register additional routes for orders.
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
	 * Modifies the response.
	 *
	 * @param WC_Data         $object  Object data.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_object_for_response( $object, $request ) {
		$response = parent::prepare_object_for_response( $object, $request );
		$data     = $response->get_data();

		// Include register and outlet data.
		$register = wc_pos_get_register( absint( $object->get_meta( 'wc_pos_register_id', true ) ) );
		if ( $register ) {
			$data['register_data'] = [
				'id'   => $register->get_id(),
				'name' => $register->get_name(),
			];

			$outlet = wc_pos_get_outlet( $register->get_outlet() );
			if ( $outlet ) {
				$data['outlet_data'] = [
					'id'   => $outlet->get_id(),
					'name' => $outlet->get_name(),
				];
			}
		}

		// Include order note.
		$order_note_id      = $object->get_meta( '_wc_pos_order_note_id', true );
		$order_note         = wc_get_order_note( $order_note_id );
		$data['order_note'] = $order_note ? $order_note->content : '';

		// Include clerk details.
		$clerk_id   = (int) $object->get_meta( 'wc_pos_served_by', true );
		$clerk_name = $object->get_meta( 'wc_pos_served_by_name', true );

		if ( $clerk_id || $clerk_name ) {
			$clerk = get_userdata( $clerk_id );

			$data['clerk'] = [
				'id'            => $clerk_id,
				'display_name'  => $clerk ? $clerk->display_name : $clerk_name,
				'user_nicename' => $clerk ? $clerk->user_nicename : $clerk_name,
				'user_login'    => $clerk ? $clerk->user_login : $clerk_name,
			];
		}

		// Include customer details.
		$data['customer'] = [];
		if ( isset( $data['customer_id'] ) ) {
			$customer = new WC_Customer( $data['customer_id'] );

			if ( $customer && is_a( $customer, 'WC_Customer' ) ) {
				$data['customer'] = array_filter(
					$customer->get_data(),
					function( $prop ) {
						$props_filtered = [
							'display_name',
							'email',
							'first_name',
							'id',
							'is_paying_customer',
							'is_vat_exempt', // @todo fix me: get_data() does not return this
							'last_name',
							'role',
							'username',
						];

						return in_array( $prop, $props_filtered, true );
					},
					ARRAY_FILTER_USE_KEY
				);
			}
		}

		// Order items.
		if ( isset( $data['line_items'] ) ) {
			$data['line_items'] = array_map(
				function( $line_item ) {
					$product_cat_ids = wc_get_product_cat_ids( $line_item['product_id'] );
					sort( $product_cat_ids );

					$product_cats = array_map(
						function( $cat_id ) {
							$cat = get_term( $cat_id, 'product_cat' );

							return [
								'id'       => $cat->term_id,
								'name'     => $cat->name,
								'slug'     => $cat->slug,
								'parent'   => $cat->parent,
								'children' => get_term_children( $cat->term_id, 'product_cat' ),
							];
						},
						$product_cat_ids
					);

					$product_cats = array_filter(
						$product_cats,
						function( $cat ) {
							return empty( $cat['children'] );
						}
					);

					if ( count( $product_cats ) > 1 ) {
						$product_cats = array_filter(
							$product_cats,
							function( $cat ) {
								return 0 !== $cat['parent'];
							}
						);
					}

					$product_cats = array_map(
						function( $cat ) {
							unset( $cat['parent'] );
							unset( $cat['children'] );

							return $cat;
						},
						$product_cats
					);

					$line_item['product_categories'] = array_values( $product_cats );

					return $line_item;
				},
				$data['line_items']
			);
		}

		// Include order refunds.
		// @todo refactor
		$page        = 1;
		$total_pages = 1;
		$refunds     = [];

		while ( $page <= $total_pages ) {
			$request = new WP_REST_Request(
				'GET',
				"/wc-pos/orders/{$data['id']}/refunds"
			);
			$request->set_param( 'page', $page );
			$request->set_param( 'per_page', 100 );

			$res     = rest_do_request( $request );
			$headers = $res->get_headers();
			$results = $res->get_data();

			$refunds     = array_merge( $refunds, $results );
			$total_pages = intval( $headers['X-WP-TotalPages'] );
			$page++;
		}

		$data['refunds'] = $refunds;

		$response->set_data( $this->filter_data_for_response( $data, $request ) );
		return rest_ensure_response( $response );
	}

	/**
	 * Filters response data to match the TS interface Order.
	 *
	 * @todo replicate this method for the other API controllers products, variations, etc.
	 * @param array           $data    Data array.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array Filtered data array.
	 */
	private function filter_data_for_response( $data, $request ) {
		$dp = is_null( $request['dp'] ) ? wc_get_price_decimals() : absint( $request['dp'] );

		/* @see Order in src/types */
		$defaults = [
			'billing'              => [
				'first_name' => '',
				'last_name'  => '',
				'company'    => '',
				'address_1'  => '',
				'address_2'  => '',
				'city'       => '',
				'postcode'   => '',
				'country'    => '',
				'state'      => '',
				'email'      => '',
				'phone'      => '',
			],
			'clerk'                => [
				'id'            => 0,
				'display_name'  => '',
				'user_nicename' => '',
				'user_login'    => '',
			],
			'coupon_lines'         => [],
			'created_via'          => '',
			'currency'             => '',
			'currency_symbol'      => '',
			'customer'             => [
				'display_name'       => '',
				'email'              => '',
				'first_name'         => '',
				'id'                 => 0,
				'is_paying_customer' => false,
				'is_vat_exempt'      => false,
				'last_name'          => '',
				'role'               => '',
				'username'           => '',
			],
			'customer_id'          => 0,
			'customer_note'        => 'default customer note',
			'date_completed_gmt'   => null,
			'date_created_gmt'     => null,
			'date_modified_gmt'    => null,
			'date_paid_gmt'        => null,
			'discount_tax'         => wc_format_decimal( 0, $dp ),
			'discount_total'       => wc_format_decimal( 0, $dp ),
			'fee_lines'            => [],
			'id'                   => 0,
			'is_editable'          => true,
			'line_items'           => [],
			'meta_data'            => [],
			'needs_payment'        => true,
			'needs_processing'     => true,
			'number'               => '',
			'order_key'            => '',
			'order_note'           => '',
			'parent_id'            => 0,
			'payment_method'       => '',
			'payment_method_title' => '',
			'payment_url'          => '',
			'prices_include_tax'   => false,
			'refunds'              => [],
			'register_data'        => [
				'id'   => 0,
				'name' => '',
			],
			'outlet_data'          => [
				'id'   => 0,
				'name' => '',
			],
			'shipping'             => [
				'first_name' => '',
				'last_name'  => '',
				'company'    => '',
				'address_1'  => '',
				'address_2'  => '',
				'city'       => '',
				'postcode'   => '',
				'country'    => '',
				'state'      => '',
			],
			'shipping_lines'       => [],
			'shipping_tax'         => wc_format_decimal( 0, $dp ),
			'shipping_total'       => wc_format_decimal( 0, $dp ),
			'status'               => '',
			'tax_lines'            => [],
			'total'                => wc_format_decimal( 0, $dp ),
			'total_tax'            => wc_format_decimal( 0, $dp ),
			'transaction_id'       => '',
		];

		array_walk(
			$defaults,
			function ( &$value, $key, $data ) {
				if ( isset( $data[ $key ] ) ) {
					$value = $data[ $key ];
				}
			},
			$data
		);

		return $defaults;
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
	 * query arguments as get_items() or the /orders endpoint and returns the totals based on
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

	/**
	 * Prepare objects query.
	 *
	 * @since 6.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		$field_query = $args['field_query'] ?? [];
		$meta_query  = $args['meta_query'] ?? [];

		// Filter by order type.
		if ( isset( $request['type'] ) ) {
			$type = wc_clean( wp_unslash( $request['type'] ) );

			if ( wc_pos_custom_orders_table_usage_is_enabled() ) {
				if ( 'pos' === $type ) {
					$field_query[] = [
						'field'   => 'created_via',
						'value'   => 'pos',
						'compare' => '=',
					];
				} elseif ( 'online' === $type ) {
					$field_query[] = [
						'field'   => 'created_via',
						'value'   => 'pos',
						'compare' => '!=',
					];
				}
			} else {
				if ( 'pos' === $type ) {
					$meta_query[] = [
						'key'     => '_created_via',
						'value'   => 'pos',
						'compare' => '=',
					];
				} elseif ( 'online' === $type ) {
					$meta_qquer[] = [
						'key'     => '_created_via',
						'value'   => 'pos',
						'compare' => '!=',
					];
				}
			}
		}

		// Filter by logged in user.
		if ( 'yes' === get_option( 'wc_pos_display_orders_for_logged_in_user', 'yes' ) ) {
			$current_user = wp_get_current_user();

			$meta_query[] = [
				'key'     => 'wc_pos_served_by',
				'value'   => $current_user->ID,
				'compare' => '=',
			];
		}

		if ( ! empty( $field_query ) ) {
			$args['field_query'] = $field_query;
		}

		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		return $args;
	}

	/**
	 * Calculate coupons.
	 *
	 * @throws WC_REST_Exception When fails to set any item.
	 * @param WP_REST_Request $request Request object.
	 * @param WC_Order        $order   Order data.
	 * @return bool
	 */
	protected function calculate_coupons( $request, $order ) {
		if ( ! isset( $request['coupon_lines'] ) ) {
			return false;
		}

		// Validate input and at the same time store the processed coupon codes to apply.

		$coupon_codes = [];
		$discounts    = new WC_Discounts( $order );

		$current_order_coupons      = array_values( $order->get_coupons() );
		$current_order_coupon_codes = array_map(
			function( $coupon ) {
				return $coupon->get_code();
			},
			$current_order_coupons
		);

		foreach ( $request['coupon_lines'] as $item ) {
			if ( ! empty( $item['id'] ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_coupon_item_id_readonly', __( 'Coupon item ID is readonly.', 'woocommerce-point-of-sale' ), 400 );
			}

			if ( empty( $item['code'] ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_coupon', __( 'Coupon code is required.', 'woocommerce-point-of-sale' ), 400 );
			}

			$item['code'] = wc_format_coupon_code( wc_clean( $item['code'] ) );
			$coupon       = new WC_Coupon( $item['code'] );

			if ( isset( $item['virtual'] ) && $item['virtual'] ) {
				$coupon->set_virtual( true );
			}

			// Skip check if the coupon is already applied to the order, as this could wrongly throw an error for single-use coupons.
			if ( ! in_array( $coupon_code, $current_order_coupon_codes, true ) ) {
				$check_result = $discounts->is_coupon_valid( $coupon );
				if ( is_wp_error( $check_result ) ) {
					throw new WC_REST_Exception( 'woocommerce_rest_' . $check_result->get_error_code(), $check_result->get_error_message(), 400 );
				}
			}

			$coupons[] = $item;
		}

		// Remove all coupons first to ensure calculation is correct.
		foreach ( $order->get_items( 'coupon' ) as $existing_coupon ) {
			$order->remove_coupon( $existing_coupon->get_code() );
		}

		// Apply the coupons.
		foreach ( $coupons as $item ) {
			$coupon = new WC_Coupon( $item['code'] );

			if ( isset( $item['virtual'] ) && $item['virtual'] ) {
				$coupon->set_amount( $item['amount'] );
				$coupon->set_discount_type( $item['discount_type'] );
				$coupon->set_virtual( true );
			}

			$results = $order->apply_coupon( $coupon );

			if ( is_wp_error( $results ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_' . $results->get_error_code(), $results->get_error_message(), 400 );
			}
		}

		return true;
	}

	/**
	 * Get the Order's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		// Collect coupons' data for custom discounts.
		$schema['properties']['coupon_lines']['items']['properties']['amount']        = [
			'type'        => 'number',
			'context'     => [ 'edit' ],
			'description' => __( 'The amount of discount', 'woocommerce-point-of-sale' ),
		];
		$schema['properties']['coupon_lines']['items']['properties']['discount_type'] = [
			'type'        => 'string',
			'context'     => [ 'edit' ],
			'enum'        => array_keys( wc_get_coupon_types() ),
			'description' => __( 'Coupon discount type.', 'woocommerce-point-of-sale' ),
		];
		$schema['properties']['coupon_lines']['items']['properties']['virtual']       = [
			'type'        => 'boolean',
			'context'     => [ 'edit' ],
			'description' => __( 'Wether the coupon is virtual.', 'woocommerce-point-of-sale' ),
		];

		// Allow decimal quantities.
		$schema['properties']['line_items']['items']['properties']['quantity']['type'] = 'number';

		// Allow setting created_via.
		$schema['properties']['created_via']['context'] = [ 'edit', 'view' ];

		// Order note.
		$schema['properties']['order_note'] = [
			'type'        => 'string',
			'context'     => [ 'edit' ],
			'description' => __( 'Note left by clerk during checkout.', 'woocommerce-point-of-sale' ),
		];

		// Whether the order is being hold.
		$schema['properties']['hold'] = [
			'type'        => 'boolean',
			'context'     => [ 'edit' ],
			'description' => __( 'Wether the order is being hold.', 'woocommerce-point-of-sale' ),
		];

		return $schema;
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['type'] = [
			'default'           => 'all',
			'description'       => __( 'Limit result set to a certain type of orders. Accepted values: all, pos, online.', 'woocommerce-point-of-sale' ),
			'type'              => 'string',
			'enum'              => [ 'all', 'pos', 'online' ],
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		];

		return $params;
	}

	/**
	 * Prepares a single order for create or update.
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

		if ( ! current_user_can( 'view_register' ) ) {
			throw new WC_REST_Exception( 'wc_pos_rest_user_cannot_create_order', __( 'You do not have permission to create orders via the register.', 'woocommerce-point-of-sale' ), 401 );
		}

		if ( $creating && 0 === intval( $request['customer_id'] ) && 'no' === get_option( 'wc_pos_guest_checkout', 'yes' ) ) {
			throw new WC_REST_Exception( 'wc_pos_rest_invalid_customer', __( 'Guest checkout is not allowed.', 'woocommerce-point-of-sale' ), 403 );
		}

		return $object;
	}
}

/**
 * WC_API_POS_Orders.
 */
class DEPRECATED_WC_API_POS_Orders extends WC_REST_Orders_Controller {
	/**
	 * Save an object data.
	 *
	 * @throws WC_REST_Exception But all errors are validated before returning any data.
	 * @param  WP_REST_Request $request  Full details about the request.
	 * @param  bool            $creating If is creating a new object.
	 * @return WC_Data|WP_Error
	 */
	protected function save_object( $request, $creating = false ) {
		// Validate order items.
		foreach ( $object->get_items() as $order_item ) {
			$this->validate_order_item( $order_item );
		}
	}

	/**
	 * Set item.
	 *
	 * @param WC_Order $order order object.
	 * @param string   $item_type The item type.
	 * @param array    $posted item provided in the request body.
	 * @throws WC_REST_Exception If item ID is not associated with order.
	 */
	protected function set_item( $order, $item_type, $posted ) {
		if ( ! empty( $posted['id'] ) ) {
			$action = 'update';
		} else {
			$action = 'create';
		}

		$method = 'prepare_' . $item_type;
		$item   = null;

		// Verify provided line item ID is associated with order.
		if ( 'update' === $action ) {
			$item = $order->get_item( absint( $posted['id'] ), false );

			if ( ! $item ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_item_id', __( 'Order item ID provided is not associated with order.', 'woocommerce-point-of-sale' ), 400 );
			}
		}

		if ( 'line_items' === $item_type ) {
			$posted_tax_class = $posted['tax_class'];

			// @todo No none.
			if ( 'none' === $posted_tax_class ) {
				$posted_tax_class = '0';
			}

			if ( 'parent' !== $posted_tax_class ) {
				$posted['tax_class'] = $posted_tax_class;
			}
		}

		// Prepare item data.
		$item = $this->$method( $posted, $action, $item );

		/**
		 * This action is documented in WC core.
		 *
		 * @since 5.0.0
		 */
		do_action( 'woocommerce_rest_set_order_item', $item, $posted );

		// If creating the order, add the item to it.
		if ( 'create' === $action ) {
			$order->add_item( $item );
		} else {
			$item->save();
		}
	}

	/**
	 * Validates an existing order item and returns any errors.
	 *
	 * @throws Exception Exception if invalid data is detected.
	 *
	 * @param array $order_item Cart item array.
	 */
	public function validate_order_item( $order_item ) {
		$product = $order_item->get_product();

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		// Skip for custom products.
		if ( intval( get_option( 'wc_pos_custom_product_id', 0 ) ) === $product->get_id() ) {
			return;
		}

		if ( ! $product->is_purchasable() ) {
			throw new WC_REST_Exception(
				'woocommerce_rest_cart_product_not_purchasable',
				sprintf(
					/* translators: %s: product name */
					__( 'Sorry, &quot;%s&quot; cannot be purchased.', 'woocommerce-point-of-sale' ),
					$product->get_name()
				),
				400
			);
		}

		if ( $product->is_sold_individually() && $order_item->get_quantity() > 1 ) {
			throw new WC_REST_Exception(
				'woocommerce_rest_cart_product_sold_individually',
				sprintf(
					/* translators: %s: product name */
					__( 'There are too many &quot;%s&quot; in the cart. Only 1 can be purchased.', 'woocommerce-point-of-sale' ),
					$product->get_name()
				),
				400
			);
		}

		if ( ! $product->is_in_stock() ) {
			throw new WC_REST_Exception(
				'woocommerce_rest_cart_product_no_stock',
				sprintf(
					/* translators: %s: product name */
					__( '&quot;%s&quot; is out of stock and cannot be purchased.', 'woocommerce-point-of-sale' ),
					$product->get_name()
				),
				400
			);
		}
	}
}
