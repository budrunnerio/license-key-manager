<?php
/**
 * AJAX Event Handlers
 *
 * @package WooCommerce_Point_Of_Sale/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_AJAX.
 */
class WC_POS_AJAX {

	/**
	 * Hook in AJAX handlers.
	 */
	public static function init() {
		self::add_ajax_events();
	}

	/**
	 * Hook in methods.
	 */
	public static function add_ajax_events() {
		$ajax_events_nopriv = [
			'auth_user',
		];

		foreach ( $ajax_events_nopriv as $ajax_event ) {
			add_action( 'wp_ajax_wc_pos_' . $ajax_event, [ __CLASS__, $ajax_event ] );
			add_action( 'wp_ajax_nopriv_wc_pos_' . $ajax_event, [ __CLASS__, $ajax_event ] );
		}

		$ajax_events = [
			'filter_product_barcode',
			'change_stock',
			'add_product_for_barcode',
			'get_product_variations_for_barcode',
			'json_search_categories',
			'get_products_by_categories',
			'check_user_card_uniqueness',
			'load_grid_tiles',
			'add_grid_tile',
			'delete_grid_tile',
			'delete_all_grid_tiles',
			'reorder_grid_tile',
			'update_receipt',
			'date_i18n',
			'paymentsense_eod_report',
			'update_option',
			// New events @since 6.0.0
			'open_register',
			'close_register',
			'take_over_register',
			'session_data',
			'nonces',
			'set_cash_data',
			'set_session_lock',
			'check_updates',
			'delete_sync_cache',
		];

		foreach ( $ajax_events as $ajax_event ) {
			add_action( 'wp_ajax_wc_pos_' . $ajax_event, [ __CLASS__, $ajax_event ] );
		}
	}

	/**
	 * Check for DB updates since a given timestamp.
	 */
	public static function check_updates() {
		if ( ! check_ajax_referer( 'check-updates', 'security', false ) ) {
			wc_pos_send_json_error( __( 'Invalid nonce.', 'woocommerce-point-of-sale' ), 'invalid_nonce' );
		}

		$last_synced = isset( $_REQUEST['last_synced'] ) ? intval( $_REQUEST['last_synced'] ) : false;
		$collections = isset( $_REQUEST['collections'] ) ? explode( ',', wc_clean( wp_unslash( $_REQUEST['collections'] ) ) ) : [];
		$collections = array_map( 'trim', $collections );
		$register_id = isset( $_REQUEST['register_id'] ) ? intval( $_REQUEST['register_id'] ) : 0;

		$response = [
			'items'       => [],
			'last_synced' => time(),
		];

		if ( $last_synced && ! empty( $collections ) ) {
			global $wpdb;

			// Delay the timestamp to avoid race conditions.
			$delay_offset         = (int) get_option( 'wc_pos_check_updates_interval', 15 ) * 2;
			$last_synced          = $last_synced - $delay_offset;
			$last_synced_datetime = gmdate( 'Y-m-d H:i:s', $last_synced );

			// Products.
			if ( in_array( 'product', $collections, true ) ) {
				$products = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT ID as id, post_status, pm_vis.meta_value as visibility, pm_stk.meta_value as stock_status
						FROM $wpdb->posts p
						LEFT JOIN $wpdb->postmeta pm_vis ON pm_vis.post_id = p.ID AND pm_vis.meta_key = '_pos_visibility'
						LEFT JOIN $wpdb->postmeta pm_stk ON pm_stk.post_id = p.ID AND pm_stk.meta_key = '_stock_status'
						WHERE p.post_type = 'product'
						AND p.post_modified_gmt > %s
						",
						$last_synced_datetime
					)
				);

				// Include the updated products.
				if ( $products && is_array( $products ) ) {
					$response['items'] = array_merge(
						$response['items'],
						array_map(
							function( $product ) {
								$action = 'publish' === $product->post_status ? 'update' : 'delete';

								if ( 'yes' === get_option( 'wc_pos_visibility', 'no' ) && 'online' === $product->visibility ) {
									$action = 'delete';
								}

								if ( 'yes' !== get_option( 'wc_pos_show_out_of_stock_products', 'no' ) && 'outofstock' === $product->stock_status ) {
									$action = 'delete';
								}

								return [ (int) $product->id, 'product', $action ];
							},
							$products
						)
					);
				}

				// Include the recently deleted products.
				$response['items'] = array_merge(
					$response['items'],
					self::get_recently_deleted_items( $register_id, 'product' )
				);
			}

			// Coupons.
			if ( in_array( 'coupon', $collections, true ) ) {
				$coupons = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT ID as id, post_status from $wpdb->posts WHERE post_type = 'shop_coupon' AND post_modified_gmt > %s",
						$last_synced_datetime
					)
				);

				// Include the updated coupons.
				if ( $coupons && is_array( $coupons ) ) {
					$response['items'] = array_merge(
						$response['items'],
						array_map(
							function( $coupon ) {
								$action = 'publish' === $coupon->post_status ? 'update' : 'delete';
								return [ (int) $coupon->id, 'coupon', $action ];
							},
							$coupons
						)
					);
				}

				// Include the recently deleted coupons.
				$response['items'] = array_merge(
					$response['items'],
					self::get_recently_deleted_items( $register_id, 'coupon' )
				);
			}

			// Users.
			if ( in_array( 'user', $collections, true ) ) {
				$users = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT ID as id from $wpdb->users u
						LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id AND um.meta_key = 'last_update'
						WHERE CAST(um.meta_value AS UNSIGNED) > %d
						",
						$last_synced
					)
				);

				// Include the updated users.
				if ( $users && is_array( $users ) ) {
					$response['items'] = array_merge(
						$response['items'],
						array_map(
							function( $user ) {
								return [ (int) $user->id, 'user', 'update' ];
							},
							$users
						)
					);
				}

				// Include the recently deleted users.
				$response['items'] = array_merge(
					$response['items'],
					self::get_recently_deleted_items( $register_id, 'user' )
				);
			}

			// Categories.
			if ( in_array( 'product_category', $collections, true ) ) {
				$categories = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT t.term_id as id from $wpdb->terms t
						LEFT JOIN $wpdb->term_taxonomy tt ON t.term_id = tt.term_id AND tt.taxonomy = 'product_cat'
						LEFT JOIN $wpdb->termmeta tm ON t.term_id = tm.term_id AND tm.meta_key = 'last_update'
						WHERE CAST(tm.meta_value AS UNSIGNED) > %d
						",
						$last_synced
					)
				);

				// Include the updated categories.
				if ( $categories && is_array( $categories ) ) {
					$response['items'] = array_merge(
						$response['items'],
						array_map(
							function( $category ) {
								return [ (int) $category->id, 'product_category', 'update' ];
							},
							$categories
						)
					);
				}

				// Include the recently deleted categories.
				$response['items'] = array_merge(
					$response['items'],
					self::get_recently_deleted_items( $register_id, 'product_category' )
				);
			}
		}

		wc_pos_send_json_success( $response );
	}

	private static function get_recently_deleted_items( $register_id, $type ) {
		global $wpdb;

		$meta_key = '_wc_pos_recently_deleted_' . $type;

		$items_deleted = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_id, meta_value as item_id FROM $wpdb->postmeta
				WHERE meta_key = %s AND post_id = %d",
				$meta_key,
				$register_id
			)
		);

		if ( $items_deleted ) {
			return array_map(
				function( $item ) use ( $type ) {
					delete_metadata_by_mid( 'post', $item->meta_id );
					return [ (int) $item->item_id, $type, 'delete' ];
				},
				$items_deleted
			);
		}

		return [];
	}

	public static function delete_sync_cache() {
		if ( ! check_ajax_referer( 'delete-sync-cache', 'security', false ) ) {
			wc_pos_send_json_error( __( 'Invalid nonce.', 'woocommerce-point-of-sale' ), 'invalid_nonce' );
		}

		$register_id = isset( $_REQUEST['register_id'] ) ? absint( $_REQUEST['register_id'] ) : 0;
		$delete_all  = 0 === $register_id;

		// @todo maybe deleting data directly from the database could be more performant?
		$coupons    = delete_metadata( 'post', $register_id, '_wc_pos_recently_deleted_coupon', '', $delete_all );
		$products   = delete_metadata( 'post', $register_id, '_wc_pos_recently_deleted_product', '', $delete_all );
		$categories = delete_metadata( 'post', $register_id, '_wc_pos_recently_deleted_product_category', '', $delete_all );
		$users      = delete_metadata( 'post', $register_id, '_wc_pos_recently_deleted_user', '', $delete_all );

		wc_pos_send_json_success( [ 'ok' => $coupons && $products && $categories && $users ] );
	}

	public static function filter_product_barcode() {
		check_ajax_referer( 'filter-product', 'security' );

		global $wpdb;
		$barcode    = isset( $_POST['barcode'] ) ? wc_clean( wp_unslash( $_POST['barcode'] ) ) : '';
		$product_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_sku' AND meta_value = %s LIMIT 1", $barcode ) );

		$result = [];
		if ( $product_id ) {

			$result['status']   = 'success';
			$result['response'] = self::get_sku_controller_product( $product_id );

		} else {
			$result['response'] = '<h2>No product found</h2>';
			$result['status']   = '404';
		}

		wp_send_json( $result );
	}

	public static function change_stock() {
		check_ajax_referer( 'change-stock', 'security' );

		global $wpdb;

		$product_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$operation  = isset( $_POST['operation'] ) ? wc_clean( wp_unslash( $_POST['operation'] ) ) : '';
		$value      = isset( $_POST['value'] ) ? intval( $_POST['value'] ) : 0;
		$note       = __( 'Product ', 'woocommerce-point-of-sale' );

		$result = [];
		if ( $product_id ) {
			$product               = wc_get_product( $product_id );
			$product->manage_stock = 'yes';
			$stock                 = $product->get_stock_quantity();

			if ( 'increase' === $operation ) {
				$stock += $value;
				$note  .= '<strong>' . esc_html( $product->get_name() ) . '</strong>' . esc_html__( ' stock increased by ', 'woocommerce-point-of-sale' ) . esc_html( $value );
			} elseif ( 'replace' === $operation ) {
				$stock = $value;
				$note .= '<strong>' . esc_html( $product->get_name() ) . '</strong>' . esc_html__( ' stock replaced by ', 'woocommerce-point-of-sale' ) . esc_html( $value );
			} else {
				$stock -= $value;
				$note  .= esc_html( $product->get_name() . __( ' stock reduced by ', 'woocommerce-point-of-sale' ) . $value );
			}

			wc_update_product_stock( $product, $stock );

			$post_modified     = current_time( 'mysql' );
			$post_modified_gmt = current_time( 'mysql', 1 );

			wp_update_post(
				[
					'ID'                => $product_id,
					'post_modified'     => $post_modified,
					'post_modified_gmt' => $post_modified_gmt,
				]
			);

			if ( 'variation' === $product->get_type() && $product->get_parent_id() && $product->get_parent_id() > 0 ) {
				wp_update_post(
					[
						'ID'                => $product->parent->id,
						'post_modified'     => $post_modified,
						'post_modified_gmt' => $post_modified_gmt,
					]
				);
			}

			$order_id = isset( $_POST['order_id'] ) ? wc_clean( wp_unslash( $_POST['order_id'] ) ) : '';
			$order    = wc_get_order( $order_id );

			if ( $order ) {
				$order->add_order_note( $note );
			}

			$result['status']   = 'success';
			$result['response'] = self::get_sku_controller_product( $product_id );

		} else {
			$result['status'] = '404';
		}

		wp_send_json( $result );
	}

	public static function get_sku_controller_product( $product_id = 0 ) {
		$product_data = [];
		if ( $product_id ) {
			$post = get_post( $product_id );
			if ( 'product' === $post->post_type ) {
				$product                      = new WC_Product( $product_id );
				$product_data['id']           = $product_id;
				$product_data['name']         = $product->get_title();
				$product_data['sku']          = $product->get_sku();
				$product_data['image']        = $product->get_image( [ 85, 85 ] );
				$product_data['price']        = $product->get_price_html();
				$product_data['manage_stock'] = $product->get_manage_stock();
				$product_data['stock']        = wc_stock_amount( $product->get_stock_quantity() );
				$product_data['stock_status'] = '';
				if ( $product->is_in_stock() ) {
					$product_data['stock_status'] = '<mark class="instock">' . __( 'In stock', 'woocommerce-point-of-sale' ) . '</mark>';
				} else {
					$product_data['stock_status'] = '<mark class="outofstock">' . __( 'Out of stock', 'woocommerce-point-of-sale' ) . '</mark>';
				}
				$product_data['stock_status'] .= ' &times; ' . wc_stock_amount( $product->get_stock_quantity() );
			} elseif ( 'product_variation' === $post->post_type ) {
				$product                      = new WC_Product_Variation( $product_id );
				$product_data['id']           = $product_id;
				$product_data['name']         = $post->post_title;
				$product_data['sku']          = $product->get_name();
				$product_data['image']        = $product->get_image( [ 85, 85 ] );
				$product_data['price']        = $product->get_price_html();
				$product_data['manage_stock'] = $product->get_manage_stock();
				$product_data['stock']        = $product->get_stock_quantity();
				$product_data['stock_status'] = '';
				if ( $product_data['stock'] ) {
					$product_data['stock_status'] = '<mark class="instock">' . __( 'In stock', 'woocommerce-point-of-sale' ) . '</mark>';
				} else {
					$product_data['stock_status'] = '<mark class="outofstock">' . __( 'Out of stock', 'woocommerce-point-of-sale' ) . '</mark>';
				}
				$product_data['stock_status'] .= ' &times; ' . wc_stock_amount( $product_data['stock'], 2 );
			}
		}
		return $product_data;
	}

	public static function add_product_for_barcode() {
		check_ajax_referer( 'product_for_barcode', 'security' );

		if ( ! current_user_can( 'manage_woocommerce_point_of_sale' ) ) {
			die( -1 );
		}

		$item_to_add = isset( $_POST['item_to_add'] ) ? sanitize_text_field( $_POST['item_to_add'] ) : '';

		// Find the item
		if ( ! is_numeric( $item_to_add ) ) {
			die();
		}

		$post = get_post( $item_to_add );

		if ( ! $post || ( 'product' !== $post->post_type && 'product_variation' !== $post->post_type ) ) {
			die();
		}

		$_product = wc_get_product( $post->ID );
		$class    = 'new_row ' . $_product->get_type();

		include 'views/html-admin-barcode-item.php';

		die();
	}

	public static function get_product_variations_for_barcode() {
		check_ajax_referer( 'product_for_barcode', 'security' );

		if ( ! current_user_can( 'manage_woocommerce_point_of_sale' ) ) {
			die( -1 );
		}

		$prid = isset( $_POST['prid'] ) ? array_map( 'absint', wp_unslash( $_POST['prid'] ) ) : [];

		// Find the item.
		if ( ! is_array( $prid ) ) {
			die();
		}

		$variations = [];

		foreach ( $prid as $id ) {
			$args           = [
				'post_parent' => $id,
				'post_type'   => 'product_variation',
				'numberposts' => -1,
				'fields'      => 'ids',
			];
			$children_array = get_children( $args, ARRAY_A );
			if ( $children_array ) {

				$variations = array_merge( $variations, $children_array );
			}
		}

		wp_send_json( $variations );

		die();
	}

	public static function json_search_categories() {
		global $wpdb;

		ob_start();

		check_ajax_referer( 'search-products', 'security' );

		$search = isset( $_GET['term'] ) ? wc_clean( wp_unslash( $_GET['term'] ) ) : '';

		if ( empty( $search ) ) {
			die();
		}

		$categories = array_unique(
			$wpdb->get_col(
				$wpdb->prepare(
					"SELECT terms.term_id FROM {$wpdb->terms} terms
					LEFT JOIN {$wpdb->term_taxonomy} taxonomy ON terms.term_id = taxonomy.term_id
					WHERE taxonomy.taxonomy = 'product_cat'
					AND terms.name LIKE %s
					",
					'%' . $wpdb->esc_like( $search ) . '%'
				)
			)
		);

		$found_categories = [];

		if ( ! empty( $categories ) ) {
			foreach ( $categories as $term_id ) {
				$category = get_term( $term_id );

				if ( is_wp_error( $category ) || ! $category ) {
					continue;
				}

				$found_categories[ $term_id ] = rawurldecode( $category->name );
			}
		}

		/**
		 * JSON search categories.
		 *
		 * @since 4.0.0
		 */
		$found_categories = apply_filters( 'wc_pos_json_search_categories', $found_categories );

		wp_send_json( $found_categories );
	}

	public static function get_products_by_categories() {
		check_ajax_referer( 'product_for_barcode', 'security' );

		if ( ! current_user_can( 'manage_woocommerce_point_of_sale' ) || ! isset( $_POST['categories'] ) ) {
			die( -1 );
		}

		$cats = isset( $_POST['categories'] ) ? wc_clean( wp_unslash( $_POST['categories'] ) ) : '';

		// Find the item
		if ( ! is_array( $cats ) ) {
			die();
		}

		$args     = [
			'post_type'   => 'product',
			'numberposts' => -1,
			'fields'      => 'ids',
			'tax_query'   => [
				[
					'terms'    => $cats,
					'taxonomy' => 'product_cat',
				],
			],
		];
		$products = [];
		$posts    = get_posts( $args, ARRAY_A );

		if ( $posts ) {
			$products = $posts;
		}

		wp_send_json( $products );
	}

	public static function check_user_card_uniqueness() {
		check_ajax_referer( 'check-user-card-uniqueness', 'security' );

		$code = isset( $_POST['code'] ) ? wc_clean( wp_unslash( ( $_POST['code'] ) ) ) : '';

		$users = get_users(
			[
				'meta_key'   => 'wc_pos_user_card_number',
				'meta_value' => $code,
			]
		);

		if ( 0 === count( $users ) ) {
			wp_send_json_success( __( 'You can use this code', 'woocommerce-point-of-sale' ) );
		} else {
			wp_send_json_error( __( 'Sorry, this code is already present', 'woocommerce-point-of-sale' ) );
		}
	}

	/**
	 * Ajax action to open the register and initialize a new session.
	 */
	public static function open_register() {
		if ( ! check_ajax_referer( 'open-register', 'security', false ) ) {
			wc_pos_send_json_error( __( 'Invalid nonce.', 'woocommerce-point-of-sale' ), 'invalid_nonce' );
		}

		if ( ! current_user_can( 'view_register' ) ) {
			wc_pos_send_json_error( __( 'You are not allowed to open the register.', 'woocommerce-point-of-sale' ), 'not_allowed' );
		}

		$register_id = isset( $_REQUEST['register_id'] ) ? absint( $_REQUEST['register_id'] ) : 0;
		$register    = wc_pos_get_register( $register_id );

		if ( ! $register || ! is_a( $register, 'WC_POS_Register' ) ) {
			wc_pos_send_json_error( __( 'Invalid register.', 'woocommerce-point-of-sale' ), 'invalid_register' );
		}

		$session = wc_pos_get_session( $register->get_current_session() );
		if ( ! $session || ! is_a( $session, 'WC_POS_Session' ) || $session->get_date_closed() ) {
			$session = new WC_POS_Session();
		}

		$date_opened = time(); // GMT.
		$user        = wp_get_current_user();

		$session->set_props(
			[
				'date_opened'    => $date_opened,
				'open_first'     => $user->ID,
				'open_last'      => $user->ID,
				'register_id'    => $register->get_id(),
				'outlet_id'      => $register->get_outlet(),
				'counted_totals' => [],
			]
		);

		$session_id = $session->save();

		// Update register meta.
		$register->set_date_opened( $date_opened ); // @todo deprecated, use session
		$register->set_open_first( $user->ID ); // @todo deprecated, use session
		$register->set_open_last( $user->ID ); // @todo deprecated, use session
		$register->set_current_session( $session_id );
		$register->save();

		$session_data = WC_POS_App::instance()->get_session_data( $session->get_id() );

		wc_pos_send_json_success( $session_data );
	}

	/**
	 * Ajax action to close the register and end the current session.
	 */
	public static function close_register() {
		if ( ! check_ajax_referer( 'close-register', 'security', false ) ) {
			wc_pos_send_json_error( __( 'Invalid nonce.', 'woocommerce-point-of-sale' ), 'invalid_nonce' );
		}

		if ( ! current_user_can( 'view_register' ) ) {
			wc_pos_send_json_error( __( 'You are not allowed to close the register.', 'woocommerce-point-of-sale' ), 'not_allowed' );
		}

		$register_id = isset( $_REQUEST['register_id'] ) ? absint( $_REQUEST['register_id'] ) : 0;
		$register    = wc_pos_get_register( $register_id );

		if ( ! $register || ! is_a( $register, 'WC_POS_Register' ) ) {
			wc_pos_send_json_error( __( 'Invalid register.', 'woocommerce-point-of-sale' ), 'invalid_register' );
		}

		// @todo check if session is already closed.

		$user = wp_get_current_user();

		$date_closed    = time(); // GMT.
		$counted_totals = ! empty( $_POST['counted_totals'] ) ? (array) json_decode( stripslashes( wc_clean( $_POST['counted_totals'] ) ) ) : [];
		$closing_note   = ! empty( $_POST['closing_note'] ) ? wc_clean( wp_unslash( $_POST['closing_note'] ) ) : '';

		$session = wc_pos_get_session( $register->get_current_session() );
		if ( ! $session || ! is_a( $session, 'WC_POS_Session' ) ) {
			wc_pos_send_json_error( __( 'Invalid session.', 'woocommerce-point-of-sale' ), 'invalid_session' );
		}

		$session->set_props(
			[
				'date_closed'    => $date_closed,
				'open_last'      => $open_last,
				'counted_totals' => $counted_totals,
				'closing_note'   => $closing_note,
			]
		);

		$session_id = $session->save();

		/**
		 * The wc_pos_end_of_day_report action.
		 *
		 * Triggers the end of day email notification.
		 *
		 * @since 4.0.0
		 * @param int            $session_id Session ID.
		 * @param WC_POS_Session $session    Session object.
		 */
		do_action( 'wc_pos_end_of_day_report', $session_id, $session );
		if ( isset( $_POST['logout'] ) && wc_string_to_bool( sanitize_text_field( $_POST['logout'] ) ) ) {
			wp_clear_auth_cookie();
		}

		$session_data = WC_POS_App::instance()->get_session_data( $session_id );
		wc_pos_send_json_success( $session_data );
	}

	/**
	 * Ajax action to take over the register from the currently logged in user.
	 *
	 * Note: this action does not end the current session.
	 */
	public static function take_over_register() {
		if ( ! check_ajax_referer( 'take-over-register', 'security', false ) ) {
			wc_pos_send_json_error( __( 'Invalid nonce.', 'woocommerce-point-of-sale' ), 'invalid_nonce' );
		}

		if ( 'yes' !== get_option( 'wc_pos_force_logout' ) ) {
			wc_pos_send_json_error( __( 'Force logout is not allowed.', 'woocommerce-point-of-sale' ), 'not_allowed' );
		}

		if ( ! current_user_can( 'view_register' ) || ! current_user_can( 'force_logout_register' ) ) {
			wc_pos_send_json_error( __( 'You are not allowed to take over the register.', 'woocommerce-point-of-sale' ), 'not_allowed' );
		}

		$register_id = isset( $_REQUEST['register_id'] ) ? absint( $_REQUEST['register_id'] ) : 0;
		$register    = wc_pos_get_register( $register_id );

		if ( ! $register || ! is_a( $register, 'WC_POS_Register' ) ) {
			wc_pos_send_json_error( __( 'Invalid register.', 'woocommerce-point-of-sale' ), 'invalid_register' );
		}

		$user = wp_get_current_user();

		$session = wc_pos_get_session( $register->get_current_session() );
		if ( ! $session || ! is_a( $session, 'WC_POS_Session' ) ) {
			wc_pos_send_json_error( __( 'Invalid session.', 'woocommerce-point-of-sale' ), 'invalid_session' );
		}

		$session->set_props(
			[
				'open_last' => $user->ID,
			]
		);

		$session_id = $session->save();

		$session_data = WC_POS_App::instance()->get_session_data( $session_id );
		wc_pos_send_json_success( $session_data );
	}

	/**
	 * Set cash data via Ajax.
	 */
	public static function set_cash_data() {
		check_ajax_referer( 'set-cash-data', 'security' );

		if ( ! current_user_can( 'view_register' ) ) {
			wc_pos_send_json_error( __( 'You are not allowed to close the register.', 'woocommerce-point-of-sale' ), 'not_allowed' );
		}

		$register_id = isset( $_REQUEST['register_id'] ) ? absint( $_REQUEST['register_id'] ) : 0;
		$register    = wc_pos_get_register( $register_id );

		if ( ! $register || ! is_a( $register, 'WC_POS_Register' ) ) {
			wc_pos_send_json_error( __( 'Invalid register.', 'woocommerce-point-of-sale' ), 'invalid_register' );
		}

		$session = wc_pos_get_session( $register->get_current_session() );

		if ( ! $session || ! is_a( $session, 'WC_POS_Session' ) ) {
			wc_pos_send_json_error( [ 'error' => __( 'Invalid session.', 'woocommerce-point-of-sale' ) ] );
		}

		$data = [
			'opening_cash_total' => isset( $_POST['opening_cash_total'] ) ? floatval( $_POST['opening_cash_total'] ) : 0.0,
			'opening_note'       => isset( $_POST['opening_note'] ) ? wc_clean( wp_unslash( $_POST['opening_note'] ) ) : '',
		];

		$session->set_props( $data );

		if ( ! $session->save() ) {
			wc_pos_send_json_error( [ 'error' => __( 'Could not update session.', 'woocommerce-point-of-sale' ) ] );
		}

		wc_pos_send_json_success( WC_POS_App::instance()->get_session_data( $session->get_id() ) );
	}

	/**
	 * Lock/unlock the session.
	 */
	public static function set_session_lock() {
		check_ajax_referer( 'set-session-lock', 'security' );

		if ( ! current_user_can( 'view_register' ) ) {
			wc_pos_send_json_error( __( 'You are not allowed to lock/unlock the session.', 'woocommerce-point-of-sale' ), 'not_allowed' );
		}

		$register_id = isset( $_REQUEST['register_id'] ) ? absint( $_REQUEST['register_id'] ) : 0;
		$register    = wc_pos_get_register( $register_id );

		if ( ! $register || ! is_a( $register, 'WC_POS_Register' ) ) {
			wc_pos_send_json_error( __( 'Invalid register.', 'woocommerce-point-of-sale' ), 'invalid_register' );
		}

		$session = wc_pos_get_session( $register->get_current_session() );

		if ( ! $session || ! is_a( $session, 'WC_POS_Session' ) ) {
			wc_pos_send_json_error( [ 'error' => __( 'Invalid session.', 'woocommerce-point-of-sale' ) ] );
		}

		if ( isset( $_POST['lock'] ) ) {
			$session->set_locked( wc_string_to_bool( sanitize_text_field( $_POST['lock'] ) ) );

			if ( ! $session->save() ) {
				wc_pos_send_json_error( [ 'error' => __( 'Updating session failed.', 'woocommerce-point-of-sale' ) ] );
			}
		}

		wc_pos_send_json_success( WC_POS_App::instance()->get_session_data( $session->get_id() ) );
	}

	/**
	 * Ajax action to authenticate user credentials to access the register.
	 */
	public static function auth_user() {
		if ( ! check_ajax_referer( 'auth-user', 'security', false ) ) {
			wc_pos_send_json_error( __( 'Invalid nonce.', 'woocommerce-point-of-sale' ), 'invalid_nonce' );
		}

		$username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
		// @todo Fix me. wc_clean() is used to pass PHPCS checks however it could remove valid password characters @see POS-61.
		$password = isset( $_POST['password'] ) ? wc_clean( $_POST['password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash

		$user = wp_authenticate_username_password( null, $username, $password );

		if ( is_wp_error( $user ) ) {
			$error_message = $user->get_error_message();
			$error_code    = $user->get_error_code();

			switch ( $error_code ) {
				case 'incorrect_password':
					$error_message = __( 'The password you entered is incorrect.', 'woocommerce-point-of-sale' );
					break;

				case 'empty_password':
					$error_message = __( 'The password field is empty.', 'woocommerce-point-of-sale' );
					break;
				default:
			}

			wc_pos_send_json_error( $error_message, $error_code );
		}

		$register_id = isset( $_REQUEST['register_id'] ) ? absint( $_REQUEST['register_id'] ) : 0;
		$register    = wc_pos_get_register( $register_id );

		if ( ! $register || ! is_a( $register, 'WC_POS_Register' ) ) {
			wc_pos_send_json_error( __( 'Invalid register.', 'woocommerce-point-of-sale' ), 'invalid_register' );
		}

		$user_outlets = get_user_meta( $user->ID, 'wc_pos_assigned_outlets', true );
		$user_outlets = empty( $user_outlets ) ? [] : array_map( 'absint', (array) $user_outlets );

		if ( ! in_array( $register->get_outlet(), $user_outlets, true ) ) {
			wc_pos_send_json_error( __( 'You are not assigned to this outlet.', 'woocommerce-point-of-sale' ), 'not_allowed' );
		}

		$session = wc_pos_get_session( $register->get_current_session() );
		if ( ! $session || ! is_a( $session, 'WC_POS_Session' ) ) {
			wc_pos_send_json_error( __( 'Invalid session. Please refresh the page.', 'woocommerce-point-of-sale' ), 'invalid_session' );
		}

		$current_user = wp_get_current_user();
		if ( $current_user && $current_user->ID !== $user->ID ) {
			wp_clear_auth_cookie();
			wc_set_customer_auth_cookie( $user->ID );

			$session->set_open_last( $user->ID );
		}

		// Authorization unlocks the session if locked.
		if ( $session->get_locked() ) {
			$session->set_locked( false );
		}

		if ( ! $session->save() ) {
			wc_pos_send_json_error( [ 'error' => __( 'Updating session failed.', 'woocommerce-point-of-sale' ) ] );
		}

		wc_pos_send_json_success(
			[
				'user_data'    => WC_POS_App::instance()->get_current_user(),
				'session_data' => WC_POS_App::instance()->get_session_data( $register->get_current_session() ),
			]
		);
	}

	public static function nonces() {
		wc_pos_send_json_success( WC_POS_App::instance()->get_nonces() );
	}

	public static function session_data() {
		if ( ! check_ajax_referer( 'session-data', 'security', false ) ) {
			wc_pos_send_json_error( __( 'Invalid nonce.', 'woocommerce-point-of-sale' ), 'invalid_nonce' );
		}

		// @todo wrap this repeated logic in a helper function:
		// 1. user can view register
		// 2. register_id is passed
		// 3. register is valid

		if ( ! current_user_can( 'view_register' ) ) {
			wc_pos_send_json_error( __( 'You are not allowed to retrieve session data..', 'woocommerce-point-of-sale' ), 'not_allowed' );
		}

		$register_id = isset( $_REQUEST['register_id'] ) ? absint( $_REQUEST['register_id'] ) : 0;
		$register    = wc_pos_get_register( $register_id );

		if ( ! $register || ! is_a( $register, 'WC_POS_Register' ) ) {
			wc_pos_send_json_error( __( 'Invalid register.', 'woocommerce-point-of-sale' ), 'invalid_register' );
		}

		wc_pos_send_json_success( WC_POS_App::instance()->get_session_data( $register->get_current_session() ) );
	}

	/**
	 * Load grid tiles via AJAX.
	 */
	public static function load_grid_tiles() {
		check_ajax_referer( 'grid-tile', 'security' );

		if ( ! current_user_can( 'manage_woocommerce_point_of_sale' ) || ! isset( $_POST['grid_id'] ) ) {
			wp_die( -1 );
		}

		$grid_object = new WC_POS_Grid( (int) $_POST['grid_id'] );

		try {
			// Get HTML to return.
			ob_start();
			include WC_POS_ABSPATH . '/includes/admin/meta-boxes/views/html-grid-tiles-panel.php';
			$html = ob_get_clean();
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'error' => $e->getMessage() ] );
		}

		wp_send_json_success(
			[
				'html' => $html,
			]
		);
	}

	/**
	 * Add a grid tile via AJAX.
	 *
	 * @throws Exception
	 */
	public static function add_grid_tile() {
		check_ajax_referer( 'grid-tile', 'security' );

		if ( ! current_user_can( 'manage_woocommerce_point_of_sale' ) || ! isset( $_POST['grid_id'] ) ) {
			wp_die( -1 );
		}

		try {
			$grid_object = new WC_POS_Grid( (int) $_POST['grid_id'] );

			if ( ! isset( $_POST['data']['tile_type'] ) || ! in_array( $_POST['data']['tile_type'], [ 'product', 'product_cat' ] ) ) {
				throw new Exception( 'Invalid tile type', 'woocommerce-point-of-sale' );
			}

			if ( 'product' === $_POST['data']['tile_type'] ) {
				$id      = isset( $_POST['data']['product_id'] ) ? (int) $_POST['data']['product_id'] : 0;
				$product = wc_get_product( $id );

				if ( ! $product ) {
					throw new Exception( 'Invalid product ID' );
				}

				$grid_object->add_tile(
					[
						'type'    => isset( $_POST['data']['tile_type'] ) ? wc_clean( wp_unslash( $_POST['data']['tile_type'] ) ) : '',
						'item_id' => $id,
					]
				);
			}

			if ( 'product_cat' === $_POST['data']['tile_type'] ) {
				$term        = isset( $_POST['data']['product_cat'] ) ? wc_clean( wp_unslash( $_POST['data']['product_cat'] ) ) : '';
				$product_cat = get_term_by( 'slug', $term, 'product_cat' );

				if ( ! $product_cat ) {
					throw new Exception( 'Invalid product category ID' . json_encode( $product_cat ) );
				}

				$grid_object->add_tile(
					[
						'type'    => isset( $_POST['data']['tile_type'] ) ? wc_clean( wp_unslash( $_POST['data']['tile_type'] ) ) : '',
						'item_id' => $product_cat->term_id,
					]
				);
			}

			$grid_object->save();
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'error' => $e->getMessage() ] );
		}

		wp_send_json_success();
	}

	/**
	 * Delete a grid tile via AJAX.
	 *
	 * @throws Exception
	 */
	public static function delete_grid_tile() {
		check_ajax_referer( 'grid-tile', 'security' );

		if ( ! current_user_can( 'manage_woocommerce_point_of_sale' ) || ! isset( $_POST['grid_id'], $_POST['tile_id'] ) ) {
			wp_die( -1 );
		}

		try {
			$grid_object = new WC_POS_Grid( (int) $_POST['grid_id'] );
			$grid_object->delete_tile( (int) $_POST['tile_id'] );
			$grid_object->save();
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'error' => $e->getMessage() ] );
		}

		wp_send_json_success();
	}

	/**
	 * Delete all tiles in a grid via AJAX.
	 *
	 * @throws Exception
	 */
	public static function delete_all_grid_tiles() {
		global $wpdb;

		check_ajax_referer( 'grid-tile', 'security' );

		if ( ! current_user_can( 'manage_woocommerce_point_of_sale' ) || ! isset( $_POST['grid_id'] ) ) {
			wp_die( -1 );
		}

		try {
			$result = $wpdb->delete(
				$wpdb->prefix . 'wc_pos_grid_tiles',
				[
					'grid_id' => (int) $_POST['grid_id'],
				],
				[ '%d' ]
			);

			if ( ! $result ) {
				wp_send_json_error( [ 'error' => __( 'No tiles to be deleted!', 'woocommerce-point-of-sale' ) ] );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'error' => $e->getMessage() ] );
		}

		wp_send_json_success();
	}

	/**
	 * Re-order grid tile via Ajax.
	 */
	public static function reorder_grid_tile() {
		check_ajax_referer( 'grid-tile', 'security' );

		$grid_id          = isset( $_POST['grid_id'] ) ? absint( $_POST['grid_id'] ) : 0;
		$current_position = isset( $_POST['current_position'] ) ? absint( $_POST['current_position'] ) : 0;
		$new_position     = isset( $_POST['new_position'] ) ? absint( $_POST['new_position'] ) : 0;

		try {
			$result = wc_pos_reorder_grid_tiles( $grid_id, $current_position, $new_position );
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'error' => $e->getMessage() ] );
		}

		if ( $result ) {
			wp_send_json_success();
		}

		wp_send_json_error( [ 'error' => __( 'Tile could not be moved.', 'woocommerce-point-of-sale' ) ] );
	}

	/**
	 * Update a receipt via AJAX.
	 *
	 * @throws Exception
	 */
	public static function update_receipt() {
		check_ajax_referer( 'update-receipt', 'security' );

		if ( ! current_user_can( 'manage_woocommerce_point_of_sale' ) ) {
			wp_die( -1 );
		}

		if ( ! isset( $_POST['data'] ) ) {
			wp_send_json_error( [ 'error' => __( 'No data sent', 'woocommerce-point-of-sale' ) ] );
		}

		$receipt_id = isset( $_POST['receipt_id'] ) ? (int) $_POST['receipt_id'] : 0;

		if ( ! empty( $_POST['data']['order_date_format'] ) ) {
			$order_date_format = sanitize_option( 'date_format', wp_unslash( $_POST['data']['order_date_format'] ) );
		} elseif ( ! empty( $_POST['data']['order_date_format_custom'] ) ) {
			$order_date_format = sanitize_option( 'date_format', wp_unslash( $_POST['data']['order_date_format_custom'] ) );
		} else {
			$order_date_format = 'jS F Y';
		}

		if ( ! empty( $_POST['data']['order_time_format'] ) ) {
			$order_time_format = sanitize_option( 'date_format', wp_unslash( $_POST['data']['order_time_format'] ) );
		} elseif ( ! empty( $_POST['data']['order_time_format_custom'] ) ) {
			$order_time_format = sanitize_option( 'date_format', wp_unslash( $_POST['data']['order_time_format_custom'] ) );
		} else {
			$order_time_format = 'g:i a';
		}

		try {
			$fields = [
				'barcode_type'                   => isset( $_POST['data']['barcode_type'] ) ? wc_clean( wp_unslash( $_POST['data']['barcode_type'] ) ) : 'code128',
				'cashier_name_format'            => isset( $_POST['data']['cashier_name_format'] ) ? wc_clean( wp_unslash( $_POST['data']['cashier_name_format'] ) ) : 'display_name',
				'custom_css'                     => isset( $_POST['data']['custom_css'] ) ? sanitize_textarea_field( $_POST['data']['custom_css'] ) : '',
				'footer_text'                    => isset( $_POST['data']['footer_text'] ) ? wp_kses_post( $_POST['data']['footer_text'] ) : '',
				'header_text'                    => isset( $_POST['data']['header_text'] ) ? wp_kses_post( $_POST['data']['header_text'] ) : '',
				'logo'                           => isset( $_POST['data']['logo'] ) ? (int) $_POST['data']['logo'] : 0,
				'logo_position'                  => isset( $_POST['data']['logo_position'] ) ? wc_clean( wp_unslash( $_POST['data']['logo_position'] ) ) : 'center',
				'logo_size'                      => isset( $_POST['data']['logo_size'] ) ? wc_clean( wp_unslash( $_POST['data']['logo_size'] ) ) : 'normal',
				'name'                           => isset( $_POST['data']['name'] ) ? wc_clean( wp_unslash( $_POST['data']['name'] ) ) : __( 'Receipt', 'woocommerce-point-of-sale' ),
				'num_copies'                     => isset( $_POST['data']['num_copies'] ) ? (int) $_POST['data']['num_copies'] : 1,
				'order_date_format'              => $order_date_format,
				'order_time_format'              => $order_time_format,
				'outlet_details_position'        => isset( $_POST['data']['outlet_details_position'] ) ? wc_clean( wp_unslash( $_POST['data']['outlet_details_position'] ) ) : 'center',
				'print_copies'                   => isset( $_POST['data']['print_copies'] ) ? wc_clean( wp_unslash( $_POST['data']['print_copies'] ) ) : 'print_copies',
				'product_details_layout'         => isset( $_POST['data']['product_details_layout'] ) ? wc_clean( wp_unslash( $_POST['data']['product_details_layout'] ) ) : 'single',
				'show_cashier_name'              => isset( $_POST['data']['show_cashier_name'] ),
				'show_customer_billing_address'  => isset( $_POST['data']['show_customer_billing_address'] ),
				'show_customer_email'            => isset( $_POST['data']['show_customer_email'] ),
				'show_customer_name'             => isset( $_POST['data']['show_customer_name'] ),
				'show_customer_phone'            => isset( $_POST['data']['show_customer_phone'] ),
				'show_customer_shipping_address' => isset( $_POST['data']['show_customer_shipping_address'] ),
				'show_num_items'                 => isset( $_POST['data']['show_num_items'] ),
				'show_order_barcode'             => isset( $_POST['data']['show_order_barcode'] ),
				'show_order_date'                => isset( $_POST['data']['show_order_date'] ),
				'show_order_status'              => isset( $_POST['data']['show_order_status'] ),
				'show_outlet_address'            => isset( $_POST['data']['show_outlet_address'] ),
				'show_outlet_contact_details'    => isset( $_POST['data']['show_outlet_contact_details'] ),
				'show_outlet_name'               => isset( $_POST['data']['show_outlet_name'] ),
				'show_product_cost'              => isset( $_POST['data']['show_product_cost'] ),
				'show_product_discount'          => isset( $_POST['data']['show_product_discount'] ),
				'show_original_price'            => isset( $_POST['data']['show_original_price'] ),
				'show_product_image'             => isset( $_POST['data']['show_product_image'] ),
				'show_product_sku'               => isset( $_POST['data']['show_product_sku'] ),
				'show_register_name'             => isset( $_POST['data']['show_register_name'] ),
				'show_shop_name'                 => isset( $_POST['data']['show_shop_name'] ),
				'show_social_facebook'           => isset( $_POST['data']['show_social_facebook'] ),
				'show_social_instagram'          => isset( $_POST['data']['show_social_instagram'] ),
				'show_social_snapchat'           => isset( $_POST['data']['show_social_snapchat'] ),
				'show_social_twitter'            => isset( $_POST['data']['show_social_twitter'] ),
				'show_tax_number'                => isset( $_POST['data']['show_tax_number'] ),
				'show_tax_summary'               => isset( $_POST['data']['show_tax_summary'] ),
				'show_title'                     => isset( $_POST['data']['show_title'] ),
				'show_wifi_details'              => isset( $_POST['data']['show_wifi_details'] ),
				'social_details_position'        => isset( $_POST['data']['social_details_position'] ) ? wc_clean( wp_unslash( $_POST['data']['social_details_position'] ) ) : 'header',
				'tax_number_label'               => isset( $_POST['data']['tax_number_label'] ) ? wc_clean( wp_unslash( $_POST['data']['tax_number_label'] ) ) : '',
				'tax_number_position'            => isset( $_POST['data']['tax_number_position'] ) ? wc_clean( wp_unslash( $_POST['data']['tax_number_position'] ) ) : 'center',
				'text_size'                      => isset( $_POST['data']['text_size'] ) ? wc_clean( wp_unslash( $_POST['data']['text_size'] ) ) : 'normal',
				'title_position'                 => isset( $_POST['data']['title_position'] ) ? wc_clean( wp_unslash( $_POST['data']['title_position'] ) ) : 'center',
				'type'                           => isset( $_POST['data']['type'] ) ? wc_clean( wp_unslash( $_POST['data']['type'] ) ) : 'normal',
				'width'                          => isset( $_POST['data']['width'] ) ? (int) $_POST['data']['width'] : 0,
			];

			$receipt = new WC_POS_Receipt( $receipt_id );
			$receipt->set_props( $fields );
			$receipt->save();
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'error' => $e->getMessage() ] );
		}

		wp_send_json_success(
			[
				'id' => $receipt->get_id(),
			]
		);

	}

	/**
	 * Returns the formatted date/time string from a timestamp.
	 *
	 * @throws Exception
	 */
	public static function date_i18n() {
		check_ajax_referer( 'date-i18n', 'security' );

		try {
			$format = isset( $_POST['data']['format'] ) ? wc_clean( wp_unslash( $_POST['data']['format'] ) ) : '';
			$time   = isset( $_POST['data']['time'] ) ? wc_clean( wp_unslash( $_POST['data']['time'] ) ) : '';

			$date = date_i18n( $format, $time );
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'error' => $e->getMessage() ] );
		}

		wp_send_json_success(
			[
				'date' => $date,
			]
		);
	}

	public static function paymentsense_eod_report() {
		check_ajax_referer( 'paymentsense-eod-report', 'security' );

		$terminal_id = isset( $_POST['terminal_id'] ) ? wc_clean( wp_unslash( $_POST['terminal_id'] ) ) : 0;
		if ( empty( $_POST['terminal_id'] ) || 'none' === $terminal_id ) {
			wp_send_json_error(
				[
					'message' => 'invalid terminal id',
				],
				400
			);
		}

		$message       = __( 'no data found', 'woocommerce-point-of-sale' );
		$payment_sense = new WC_POS_Gateway_Paymentsense_API();
		$request       = $payment_sense->pac_reports(
			$terminal_id,
			0,
			[
				'method' => 'POST',
				'body'   => json_encode(
					[
						'reportType' => 'END_OF_DAY',
					]
				),
			]
		);

		if ( ! is_wp_error( $request ) ) {
			$body = wp_remote_retrieve_body( $request );
			$body = json_decode( $body );
			if ( isset( $body->requestId ) ) {
				$report      = null;
				$report_body = null;

				while ( ! isset( $report_body->balances ) ) {
					sleep( 1 );
					$report      = $payment_sense->pac_reports( $terminal_id, $body->requestId );
					$report_body = json_decode( wp_remote_retrieve_body( $report ) );

					if ( empty( $report_body ) || isset( $report_body->messages ) ) {
						break;
					}
				}

				if ( isset( $report_body->balances ) ) {
					ob_start();

					include trailingslashit( WC_POS()->plugin_path() ) . 'includes/gateways/paymentsense/includes/views/html-paymentsense-report.php';

					$template = ob_get_clean();

					if ( $template ) {
						$meta_key    = 'wc_pos_payment_sense_EOD_' . strtotime( gmdate( 'Ymd' ) );
						$register_id = isset( $_POST['register'] ) ? intval( $_POST['register'] ) : 0;
						update_post_meta( $register_id, $meta_key, $report_body );

						wp_send_json_success( $template );
					}
				} elseif ( isset( $report_body->messages ) ) {
					$message = $report_body->messages->error[0];
				}
			} elseif ( isset( $body->messages ) ) {
				$message = $body->messages->error[0];
			}
		}

		wp_send_json_error(
			[
				'message' => $message,
			],
			400
		);
	}

	/**
	 * Update site option.
	 */
	public static function update_option() {
		if ( ! check_ajax_referer( 'update-option', 'security', false ) ) {
			wc_pos_send_json_error( __( 'Invalid nonce.', 'woocommerce-point-of-sale' ), 'invalid_nonce' );
		}

		$option = isset( $_POST['option'] ) ? wc_clean( wp_unslash( $_POST['option'] ) ) : '';
		$value  = isset( $_POST['value'] ) ? wc_clean( wp_unslash( $_POST['value'] ) ) : '';

		if ( ! empty( $option ) ) {
			$allowed = [
				'wc_pos_force_refresh_db',
				'wc_pos_max_concurrent_requests',
				'wc_pos_api_per_page',
			];

			if ( ! in_array( $option, $allowed, true ) ) {
				wc_pos_send_json_error( __( 'You are not allowed to update this option.', 'woocommerce-point-of-sale' ) );
			}

			$success = update_option( $option, $value );
		}

		if ( $success ) {
			wc_pos_send_json_success( [ 'value' => $value ] );
		}

		wc_pos_send_json_error( __( 'Failed to update option.', 'woocommerce-point-of-sale' ) );
	}
}

WC_POS_AJAX::init();
