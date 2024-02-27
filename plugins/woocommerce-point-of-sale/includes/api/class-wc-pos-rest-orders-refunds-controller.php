<?php
/**
 * Order refunds API
 *
 * Handles requests to the wc-pos/orders/<order_id>/refunds endpoint.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/API
 */

defined( 'ABSPATH' ) || exit;

class WC_POS_REST_Orders_Refunds_Controller extends WC_REST_Order_Refunds_Controller {

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
	protected $rest_base = 'orders/(?P<order_id>[\d]+)/refunds';


	protected function prepare_links( $object, $request ) {
		return []; // Remove links.
	}

	/*
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

		$refunded_by_id      = $data['refunded_by'];
		$refunded_by_user    = get_user_by( 'id', $refunded_by_id );
		$data['refunded_by'] = [
			'id'            => $refunded_by_id,
			'display_name'  => $refunded_by_user ? $refunded_by_user->display_name : '',
			'user_login'    => $refunded_by_user ? $refunded_by_user->user_login : '',
			'user_nicename' => $refunded_by_user ? $refunded_by_user->user_nicename : '',
		];

		$response->set_data( $this->filter_data_for_response( $data, $request ) );
		return rest_ensure_response( $response );
	}

	/**
	 * Filters response data to match the TS interface Order.
	 *
	 * @param array           $data    Data array.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array Filtered data array.
	 */
	private function filter_data_for_response( $data, $request ) {
		/* @see OrderRefund in src/types */
		$defaults = [
			'amount'           => '',
			'date_created_gmt' => '',
			'fee_lines'        => [],
			'id'               => 0,
			'line_items'       => [],
			'meta_data'        => [],
			'reason'           => '',
			'refunded_by'      => [
				'id'            => 0,
				'display_name'  => '',
				'user_login'    => '',
				'user_nicename' => '',
			],
			'refunded_payment' => false,
			'shipping_lines'   => [],
			'tax_lines'        => [],
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
}
