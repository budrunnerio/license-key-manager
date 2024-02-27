<?php
/**
 * Add to Next Shipment
 *
 * @package Self-service Dashboard for WooCommerce Subscriptions
 * @since 1.0.11
 */

use Automattic\WooCommerce\Utilities\NumberUtil;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

if ( ! class_exists( 'Add_To_Next_Shipment' ) ) {
	/**
	 * Class Add_To_Next_Shipment
	 */
	class Add_To_Next_Shipment {
		/**
		 * Add_To_Next_Shipment constructor.
		 */
		public function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'sf_enqueue_scripts' ) );
			add_action( 'sdd_after_simple_product_form', array( $this, 'sf_display_simple_add_button' ), 10, 2 );
			add_action( 'sdd_after_variable_product_form', array( $this, 'sf_display_variable_add_button' ), 10, 2 );
			add_action( 'sdd_after_bundle_product_form', array( $this, 'sf_display_bundle_add_button' ), 10, 2 );
			add_action( 'sdd_after_composite_product_form', array( $this, 'sf_display_composite_add_button' ), 10, 2 );
			add_action( 'woocommerce_subscription_renewal_payment_complete', array( $this, 'sf_remove_items_from_subscription' ), 10, 2 );
			add_action( 'wp_ajax_wpr_add_simple_product_next_shipment', array( $this, 'sf_add_simple_product_next_shipment_form' ) );
			add_action( 'wp_ajax_wpr_add_variable_product_next_shipment', array( $this, 'sf_add_variable_product_next_shipment_form' ) );
			add_action( 'wp_ajax_wpr_add_composite_product_next_shipment', array( $this, 'sf_add_composite_product_next_shipment_form' ) );
			add_action( 'wp_ajax_wpr_add_bundle_product_next_shipment', array( $this, 'sf_add_bundle_product_next_shipment_form' ) );
			add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'sf_woocommerce_hidden_order_itemmeta' ), 10, 1 );
			add_filter( 'woocommerce_order_formatted_line_subtotal', array( $this, 'sf_next_shipment_only' ), 10, 3 );
		}

		/**
		 * Display normal price for one time
		 *
		 * @param string $subtotal Item total.
		 * @param object $item Item object.
		 * @param object $subscription Subscription object.
		 *
		 * @return mixed|string
		 */
		public function sf_next_shipment_only( $subtotal, $item, $subscription ) {
			if ( $item->get_meta( '_sf_add_to_next_shipment' ) ) {
				$tax_display = get_option( 'woocommerce_tax_display_cart' );
				if ( 'excl' == $tax_display ) {
					$line_subtotal = $subscription->get_line_subtotal( $item );
				} else {
					$line_subtotal = $subscription->get_line_subtotal( $item, true );
				}
				return wc_price( $line_subtotal );
			}

			return $subtotal;
		}

		/**
		 * Enqueue scripts
		 */
		public function sf_enqueue_scripts() {
			$plugin_data = get_plugin_data( __FILE__ );
			wp_enqueue_script( 'sf_nx_frontend', SSD_FUNC_URL . '/add-to-next-shipment/assets/js/front-end.js', array( 'jquery' ), $plugin_data['Version'], true );

			wp_localize_script(
				'sf_nx_frontend',
				'sf_nx_js',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'sf-nx-nonce' ),
				)
			);
		}

		/**
		 * Display the Add to Next Shipment button for simple products
		 *
		 * @param int $subscription_id Subscription ID.
		 * @param int $product_id Product ID.
		 */
		public function sf_display_simple_add_button( $subscription_id, $product_id ) {
			?>
			<a href="#" class="wpr-add-simple-product-next-shipment button" data-id="<?php echo absint( $subscription_id ); ?>" data-subscription-in="<?php echo absint( $product_id ); ?>"><?php echo esc_html__( 'Add to next shipment', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></a>
			<?php
		}

		/**
		 * Display the Add to Next Shipment button for variable products
		 *
		 * @param int $subscription_id Subscription ID.
		 * @param int $product_id Product ID.
		 */
		public function sf_display_variable_add_button( $subscription_id, $product_id ) {
			?>
			<a href="#" class="wpr-add-variable-product-next-shipment button" data-id="<?php echo absint( $subscription_id ); ?>" data-subscription-in="<?php echo absint( $product_id ); ?>"><?php echo esc_html__( 'Add to next shipment', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></a>
			<?php
		}

		/**
		 * Display the Add to Next Shipment button for bundle products
		 *
		 * @param int $subscription_id Subscription ID.
		 * @param int $product_id Product ID.
		 */
		public function sf_display_bundle_add_button( $subscription_id, $product_id ) {
			?>
			<a href="#" class="wpr-add-bundle-product-next-shipment button" data-id="<?php echo absint( $subscription_id ); ?>" data-subscription-in="<?php echo absint( $product_id ); ?>"><?php echo esc_html__( 'Add to next shipment', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></a>
			<?php
		}

		/**
		 * Display the Add to Next Shipment button for composite products
		 *
		 * @param int $subscription_id Subscription ID.
		 * @param int $product_id Product ID.
		 */
		public function sf_display_composite_add_button( $subscription_id, $product_id ) {
			?>
			<a href="#" class="wpr-add-composite-product-next-shipment button" data-id="<?php echo absint( $subscription_id ); ?>" data-subscription-in="<?php echo absint( $product_id ); ?>"><?php echo esc_html__( 'Add to next shipment', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></a>
			<?php
		}

		/**
		 * Remove the one time shipping
		 *
		 * @param object $subscription Subscription object.
		 * @param string $last_order Last order.
		 */
		public function sf_remove_items_from_subscription( $subscription, $last_order ) {
			if ( false === $subscription ) {
				return;
			}

			foreach ( $subscription->get_items() as $item_id => $item ) {
				if ( $item->get_meta( '_sf_add_to_next_shipment' ) ) {
					$product = $item->get_product();
					if ( $product->is_type( 'bundle' ) ) {
						$bundled_items = wc_pb_get_bundled_order_items( $item, $subscription );

						if ( ! empty( $bundled_items ) ) {
							foreach ( $bundled_items as $bundled_item ) {
								$bundled_item_keys[] = $bundled_item->get_id();

								$bundled_product_id = wcs_get_canonical_product_id( $bundled_item );
								wcs_update_order_item_type( $bundled_item->get_id(), 'line_item_removed', $subscription->get_id() );
								WCS_Download_Handler::revoke_downloadable_file_permission( $bundled_product_id, $subscription->get_id(), $subscription->get_user_id() );

								/**
								 * Removed item action
								 *
								 * @param $bundled_item
								 * @param $subscription
								 *
								 * @since 1.0.0
								 */
								do_action( 'wcs_user_removed_item', $bundled_item, $subscription );
							}
						}
					} elseif ( $product->is_type( 'composite' ) ) {
						$composite_items = wc_cp_get_composited_order_items( $item, $subscription );

						if ( ! empty( $composite_items ) ) {
							foreach ( $composite_items as $composite_item ) {
								$composite_item_keys[] = $composite_item->get_id();

								$composite_product_id = wcs_get_canonical_product_id( $composite_item );
								wcs_update_order_item_type( $composite_item->get_id(), 'line_item_removed', $subscription->get_id() );
								WCS_Download_Handler::revoke_downloadable_file_permission( $composite_product_id, $subscription->get_id(), $subscription->get_user_id() );

								/**
								 * Removed item action
								 *
								 * @param $composite_item
								 * @param $subscription
								 *
								 * @since 1.0.0
								 */
								do_action( 'wcs_user_removed_item', $composite_item, $subscription );
							}
						}
					}

					wcs_update_order_item_type( $item_id, 'line_item_removed', $subscription->get_id() );

					$add_note = sprintf( '"%s" deleted from the next shipment.', esc_attr( $product->get_name() ) );
					$subscription->add_order_note( $add_note );
				}
			}

			$subscription->save();
			wcs_get_subscription( $subscription->get_id() )->calculate_totals();
		}

		/**
		 * Add new subscription product to order
		 */
		public function sf_add_simple_product_next_shipment_form() {
			check_ajax_referer( 'sf-nx-nonce', 'nonce' );

			$message = esc_html__( 'Product not added.', 'self-service-dashboard-for-woocommerce-subscriptions' );

			$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
			$sub_id   = isset( $_POST['sub_id'] ) ? absint( $_POST['sub_id'] ) : 0;
			$qty      = 1;
			$product  = wc_get_product( $sub_id );

			if ( empty( $order_id ) ) {
				wp_die( esc_html__( 'Order ID is invalid', 'self-service-dashboard-for-woocommerce-subscriptions' ) );
			}

			if ( ! empty( $product ) && ! empty( $order_id ) ) {
				$order = new WC_Subscription( $order_id );

				if ( empty( $order ) ) {
					wp_die( esc_html__( 'Order is invalid.', 'self-service-dashboard-for-woocommerce-subscriptions' ) );
				}

				$order_item_id = $order->add_product( $product, $qty );

				$order->save();
				$order->calculate_totals();

				wc_add_order_item_meta( $order_item_id, '_sf_add_to_next_shipment', 1 );
				wc_add_order_item_meta( $order_item_id, esc_html__( 'Product added for', 'self-service-dashboard-for-woocommerce-subscriptions' ), esc_html__( 'Next shipment only', 'self-service-dashboard-for-woocommerce-subscriptions' ) );

				$add_note = sprintf( 'Customer added "%s" to the subscription for the next shipment only.', esc_attr( $product->get_name() ) );
				$order->add_order_note( $add_note );

				$email_notifications = WC()->mailer()->get_emails();
				// Sending the email.
				if ( isset( $email_notifications['WCS_Email_Completed_Switch_Order'] ) ) {
					$email_notifications['WCS_Email_Completed_Switch_Order']->trigger( $order_id );
				}

				$message = esc_html__( 'Product added for next shipment only.', 'self-service-dashboard-for-woocommerce-subscriptions' );
			}

			wp_send_json_success(
				array(
					'html' => sprintf( '<div class="ssd-display-notification">%s</div>', $message ),
				)
			);
		}

		/**
		 * Add new variable subscription product to order
		 */
		public function sf_add_variable_product_next_shipment_form() {
			check_ajax_referer( 'sf-nx-nonce', 'nonce' );

			if ( ! isset( $_POST['data'] ) ) {
				wp_die( esc_html__( 'Invalid data, please try again.', 'self-service-dashboard-for-woocommerce-subscriptions' ) );
			}

			$message = esc_html__( 'Product not added.', 'self-service-dashboard-for-woocommerce-subscriptions' );

			$post_parameters = wp_unslash( $_POST );
			parse_str( $post_parameters['data'], $parameters );

			$order_id     = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
			$sub_id       = isset( $_POST['sub_id'] ) ? absint( $_POST['sub_id'] ) : 0;
			$variation_id = isset( $parameters['variation_id'] ) ? absint( $parameters['variation_id'] ) : 0;
			$qty          = 1;
			$product      = wc_get_product( $sub_id );

			if ( empty( $order_id ) ) {
				wp_die( esc_html__( 'Order ID is invalid.', 'self-service-dashboard-for-woocommerce-subscriptions' ) );
			}

			if ( ! empty( $product ) && ! empty( $order_id ) ) {
				$order = new WC_Subscription( $order_id );

				if ( empty( $order ) ) {
					wp_die( esc_html__( 'Order is invalid.', 'self-service-dashboard-for-woocommerce-subscriptions' ) );
				}

				if ( ! $variation_id ) {
					$variation_id = self::wpr_get_variation_id( $product, $parameters );
				}

				if ( $variation_id ) {
					$product = new WC_Product_Variation( $variation_id );

					$order_item_id = $order->add_product( $product, $qty );

					$order->save();
					$order->calculate_totals();

					$add_note = sprintf( 'Customer added "%s" to the subscription for the next shipment only.', esc_attr( $product->get_name() ) );
					$order->add_order_note( $add_note );

					wc_add_order_item_meta( $order_item_id, '_sf_add_to_next_shipment', 1 );
					wc_add_order_item_meta( $order_item_id, esc_html__( 'Product added for', 'self-service-dashboard-for-woocommerce-subscriptions' ), esc_html__( 'Next shipment only', 'self-service-dashboard-for-woocommerce-subscriptions' ) );

					// Remove if added!
					if ( wc_get_order_item_meta( $order_item_id, 'variation_id' ) ) {
						wc_delete_order_item_meta( $order_item_id, 'variation_id' );
					}

					$email_notifications = WC()->mailer()->get_emails();
					// Sending the email.
					if ( isset( $email_notifications['WCS_Email_Completed_Switch_Order'] ) ) {
						$email_notifications['WCS_Email_Completed_Switch_Order']->trigger( $order_id );
					}

					$message = esc_html__( 'Product added for next shipment only.', 'self-service-dashboard-for-woocommerce-subscriptions' );
				}
			}

			wp_send_json_success(
				array(
					'html' => sprintf( '<div class="ssd-display-notification">%s</div>', $message ),
				)
			);
		}

		/**
		 * Add new bundle subscription product to order
		 */
		public function sf_add_bundle_product_next_shipment_form() {
			check_ajax_referer( 'sf-nx-nonce', 'nonce' );

			if ( ! isset( $_POST['data'] ) ) {
				wp_send_json_error(
					array(
						'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'Invalid data, please try again.', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
					)
				);
			}

			$message = esc_html__( 'Product not added.', 'self-service-dashboard-for-woocommerce-subscriptions' );

			/**
			 * Filter bos_use_regular_price.
			 *
			 * @param bool false
			 *
			 * @since 2.0.2
			 */
			$display_the_price = apply_filters( 'bos_use_regular_price', false );

			$post_parameters = wp_unslash( $_POST );
			parse_str( $post_parameters['data'], $parameters );

			$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
			$sub_id   = isset( $_POST['sub_id'] ) ? absint( $_POST['sub_id'] ) : 0;
			$qty      = 1;
			$product  = wc_get_product( $sub_id );

			if ( empty( $order_id ) ) {
				wp_send_json_error(
					array(
						'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'Order ID is invalid.', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
					)
				);
			}

			if ( ! empty( $product ) && ! empty( $order_id ) ) {
				$order = new WC_Subscription( $order_id );

				if ( empty( $order ) ) {
					wp_send_json_error(
						array(
							'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'Order is invalid.', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
						)
					);
				}

				$posted_configuration = WC_PB()->cart->get_posted_bundle_configuration( $product );

				foreach ( $parameters as $bundle_key => $bundle_item ) {
					if ( strpos( $bundle_key, 'bundle_selected_optional_' ) !== false ) {
						$get_entry = explode( '_', $bundle_key );
						$bundle_no = end( $get_entry );

						$posted_configuration[ $bundle_no ]['optional_selected'] = 'yes';
						$posted_configuration[ $bundle_no ]['quantity']          = isset( $parameters[ 'bundle_quantity_' . $bundle_no ] ) ? absint( $parameters[ 'bundle_quantity_' . $bundle_no ] ) : 1;
					}

					if ( strpos( $bundle_key, 'bundle_variation_id_' ) !== false ) {
						$get_entry = explode( '_', $bundle_key );
						$bundle_no = end( $get_entry );

						$posted_configuration[ $bundle_no ]['variation_id'] = $bundle_item;

						$product_v_id = wp_get_post_parent_id( $bundle_item );
						$product_v    = wc_get_product( $product_v_id );
						$attributes   = $product_v->get_attributes();

						if ( $attributes ) {
							$selected_attributes = array();
							foreach ( $attributes as $type => $attribute_data ) {
								if ( isset( $parameters[ 'bundle_attribute_' . $type . '_' . $bundle_no ] ) ) {
									$selected_attributes[ 'attribute_' . $type ] = $parameters[ 'bundle_attribute_' . $type . '_' . $bundle_no ];
								}
							}
							$posted_configuration[ $bundle_no ]['attributes'] = $selected_attributes;
						}
					}

					if ( strpos( $bundle_key, 'bundle_quantity_' ) !== false ) {
						$get_entry = explode( '_', $bundle_key );
						$bundle_no = end( $get_entry );
						$posted_configuration[ $bundle_no ]['quantity']  = absint( $parameters[ 'bundle_quantity_' . $bundle_no ] );
					}
				}

				$result = WC_PB()->order->add_bundle_to_order(
					$product,
					$order,
					$qty,
					array(
						'configuration' => $posted_configuration,
					)
				);

				if ( is_int( $result ) ) {
					$order->save();
					$order->calculate_totals();

					$add_note = sprintf( 'Customer added "%s" to the subscription for the next shipment only.', esc_attr( $product->get_name() ) );
					$order->add_order_note( $add_note );

					wc_add_order_item_meta( $result, '_sf_add_to_next_shipment', 1 );
					wc_add_order_item_meta( $result, esc_html__( 'Product added for', 'self-service-dashboard-for-woocommerce-subscriptions' ), esc_html__( 'Next shipment only', 'self-service-dashboard-for-woocommerce-subscriptions' ) );

					$email_notifications = WC()->mailer()->get_emails();
					// Sending the email.
					if ( isset( $email_notifications['WCS_Email_Completed_Switch_Order'] ) ) {
						$email_notifications['WCS_Email_Completed_Switch_Order']->trigger( $order_id );
					}

					$message = esc_html__( 'Product added for next shipment only.', 'self-service-dashboard-for-woocommerce-subscriptions' );
				}
			}

			wp_send_json_success(
				array(
					'html' => sprintf( '<div class="ssd-display-notification">%s</div>', $message ),
				)
			);
		}

		/**
		 * Add new composite subscription product to order
		 */
		public function sf_add_composite_product_next_shipment_form() {
			check_ajax_referer( 'sf-nx-nonce', 'nonce' );

			if ( ! isset( $_POST['data'] ) ) {
				wp_send_json_error(
					array(
						'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'Invalid data, please try again.', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
					)
				);
			}

			/**
			 * Filter bos_use_regular_price.
			 *
			 * @param bool false
			 *
			 * @since 2.0.2
			 */
			$display_the_price = apply_filters( 'bos_use_regular_price', false );

			$message = esc_html__( 'Product not added.', 'self-service-dashboard-for-woocommerce-subscriptions' );

			$post_parameters = wp_unslash( $_POST );
			parse_str( $post_parameters['data'], $parameters );

			$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
			$sub_id   = isset( $_POST['sub_id'] ) ? absint( $_POST['sub_id'] ) : 0;
			$qty      = 1;
			$product  = wc_get_product( $sub_id );

			if ( empty( $order_id ) ) {
				wp_send_json_error(
					array(
						'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'Order ID is invalid.', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
					)
				);
			}

			if ( ! empty( $product ) && ! empty( $order_id ) ) {
				$order = new WC_Subscription( $order_id );

				if ( empty( $order ) ) {
					wp_send_json_error(
						array(
							'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'Order is invalid.', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
						)
					);
				}

				$posted_c_configuration = WC_CP()->cart->get_posted_composite_configuration( $product );

				foreach ( $parameters['wccp_component_selection'] as $the_id => $comp_item ) {
					$posted_c_configuration[ $the_id ]['product_id'] = $parameters['wccp_component_selection'][ $the_id ];

					if ( isset( $parameters['wccp_variation_id'][ $the_id ] ) ) {
						$posted_c_configuration[ $the_id ]['variation_id'] = $parameters['wccp_variation_id'][ $the_id ];

						$product_v_id = wp_get_post_parent_id( $parameters['wccp_variation_id'][ $the_id ] );
						$product_v    = wc_get_product( $product_v_id );
						$attributes   = $product_v->get_attributes();

						if ( $attributes ) {
							$selected_attributes = array();
							foreach ( $attributes as $type => $attribute_data ) {
								if ( isset( $parameters[ 'wccp_attribute_' . $type ][ $the_id ] ) ) {
									$selected_attributes[ 'attribute_' . $type ] = $parameters[ 'wccp_attribute_' . $type ][ $the_id ];
								}
							}
							$posted_c_configuration[ $the_id ]['attributes'] = $selected_attributes;
						}
					}

					if ( isset( $parameters['wccp_component_quantity'][ $the_id ] ) ) {
						$posted_c_configuration[ $the_id ]['quantity'] = absint( $parameters['wccp_component_quantity'][ $the_id ] );
					}
				}

				$result = WC_CP()->order->add_composite_to_order(
					$product,
					$order,
					$qty,
					array(
						'configuration' => $posted_c_configuration,
					)
				);

				if ( is_int( $result ) ) {
					$order->save();
					$order->calculate_totals();

					$add_note = sprintf( 'Customer added "%s" to the subscription for the next shipment only.', esc_attr( $product->get_name() ) );
					$order->add_order_note( $add_note );

					wc_add_order_item_meta( $result, '_sf_add_to_next_shipment', 1 );
					wc_add_order_item_meta( $result, esc_html__( 'Product added for', 'self-service-dashboard-for-woocommerce-subscriptions' ), esc_html__( 'Next shipment only', 'self-service-dashboard-for-woocommerce-subscriptions' ) );

					$email_notifications = WC()->mailer()->get_emails();
					// Sending the email.
					if ( isset( $email_notifications['WCS_Email_Completed_Switch_Order'] ) ) {
						$email_notifications['WCS_Email_Completed_Switch_Order']->trigger( $order_id );
					}

					$message = esc_html__( 'Product added for next shipment only.', 'self-service-dashboard-for-woocommerce-subscriptions' );
				}
			}

			wp_send_json_success(
				array(
					'html' => sprintf( '<div class="ssd-display-notification">%s</div>', $message ),
				)
			);
		}

		/**
		 * Get variation ID
		 *
		 * @param object $product Represents the product object.
		 * @param object $variations Represents the product variations.
		 *
		 * @return mixed|null
		 */
		public static function wpr_get_variation_id( $product, $variations = array() ) {
			$variation_id          = null;
			$variations_normalized = array();

			if ( $product->is_type( 'variable' ) && $product->has_child() ) {
				if ( isset( $variations ) && is_array( $variations ) ) {

					foreach ( $variations as $key => $value ) {
						$key                           = str_replace( 'attribute_', '', wc_attribute_taxonomy_slug( $key ) );
						$variations_normalized[ $key ] = strtolower( $value );
					}

					foreach ( $product->get_children() as $variation ) {
						$meta = array();
						foreach ( get_post_meta( $variation ) as $key => $value ) {
							$value        = $value[0];
							$key          = str_replace( 'attribute_', '', wc_attribute_taxonomy_slug( $key ) );
							$meta[ $key ] = strtolower( $value );
						}

						if ( self::array_contains( $variations_normalized, $meta ) ) {
							$variation_id = $variation;
							break;
						}
					}
				}
			}

			return $variation_id;
		}

		/**
		 * Utility function to see if the meta array contains data from variations
		 *
		 * @param array $needles Needles.
		 * @param array $haystack Haystack.
		 *
		 * @return bool
		 */
		protected static function array_contains( $needles, $haystack ) {
			foreach ( $needles as $key => $value ) {
				if ( $haystack[ $key ] !== $value ) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Hide the meta on order display.
		 *
		 * @param array $metas Item metas.
		 *
		 * @return mixed
		 */
		public function sf_woocommerce_hidden_order_itemmeta( $metas ) {
			$metas[] = '_sf_add_to_next_shipment';

			return $metas;
		}
	}
}

new Add_To_Next_Shipment();
