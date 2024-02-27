<?php
/**
 * Class Change Frequency
 *
 * @package Subscription Force
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Utilities\NumberUtil;

if ( ! class_exists( 'Change_Frequency' ) ) {

	/**
	 * Smart Shipping functionality
	 */
	class Change_Frequency {
		/**
		 * Change_Frequency constructor.
		 */
		public function __construct() {
			add_action( 'woocommerce_subscription_before_actions', array( $this, 'table_row' ), 10, 100 );

			add_action( 'woocommerce_update_options_subscription_force', array( $this, 'settings_page_update' ) );
			add_action( 'woocommerce_admin_field_available_frequency', array( $this, 'admin_field_available_frequency' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );

			add_action( 'rest_api_init', array( $this, 'register_custom_endpoints' ) );
			add_filter( 'woocommerce_email_classes', array( $this, 'sf_add_active_woocommerce_email' ), 10, 1 );

			add_filter( 'settings_tabs_ssd_settings', array( $this, 'sf_add_ssd_settings_option' ), 15, 1 );
		}

		/**
		 * Add the option in SSD admin settings tab
		 *
		 * @param array $settings Settings array data.
		 *
		 * @return mixed
		 */
		public function sf_add_ssd_settings_option( $settings ) {
			if ( ! $this->get_settings() ) {
				return $settings;
			}
			$change_freq = array();
			foreach ( $this->get_settings() as $new_settings ) {
				if ( 'sectionend' === $new_settings['type'] || 'title' === $new_settings['type'] ) {
					continue;
				}
				$change_freq[ $new_settings['id'] . '_' . $new_settings['type'] ] = $new_settings;
			}

			return ssd_array_insert_after( $settings, 'hide_change_payment_date', $change_freq );
		}

		/**
		 * Register custom made endpoints
		 *
		 * @hook rest_api_init
		 * @return void
		 */
		public function register_custom_endpoints() {
			register_rest_route(
				'sforce/change_frequency',
				'/update',
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'rest_change_frequency_update_callback' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'frequency'       => array(),
						'subscription_id' => array(),
					),
				)
			);
		}

		/**
		 * Rest_change_frequency_update_callback
		 *
		 * @param resource WP_REST_Request $request The Request.
		 *
		 * @throws Exception The exception.
		 */
		public function rest_change_frequency_update_callback( WP_REST_Request $request ) {

			$params = $request->get_params();

			$frequency = $this->get_single_frequency_from_available( $params['frequency'] );

			$frequency_string = $frequency->interval_text . ' ' . $frequency->period_text;

			$frequency_update = $this->update_frequency( $params['frequency'], $params['subscription_id'] );

			$format_date = strtotime( $frequency_update->get_date( 'next_payment', 'site' ) );

			$message = sprintf( '%s %s %s %s', esc_html__( 'Your subscription will switch to', 'self-service-dashboard-for-woocommerce-subscriptions' ), esc_html( $frequency_string ), esc_html__( 'after your next payment date on', 'self-service-dashboard-for-woocommerce-subscriptions' ), esc_attr( gmdate( 'Y-m-d', $format_date ) ) );

			wp_send_json_success(
				array(
					'html'           => $message,
					'frequency_text' => ucwords( $frequency_string ),
				)
			);
		}

		/**
		 * Update_frequency
		 *
		 * @param int $frequency_id Frequency ID.
		 * @param int $subscription_id Subscription ID.
		 *
		 * @return WC_Subscription
		 */
		public function update_frequency( $frequency_id, $subscription_id ) {
			$frequency    = $this->get_single_frequency_from_available( $frequency_id );
			$subscription = new \WC_Subscription( $subscription_id );

			$old_interval = wcs_get_subscription_period_interval_strings( $subscription->get_billing_interval() );
			$old_period   = $subscription->get_billing_period();

			// Update metas.
			$subscription->update_meta_data( '_billing_interval', $frequency->interval );
			$subscription->update_meta_data( '_billing_period', $frequency->period );
			$subscription->save();

			/**
			 * Filter bos_use_regular_price.
			 *
			 * @param bool false
			 *
			 * @since 2.0.2
			 */
			$display_the_price = apply_filters( 'bos_use_regular_price', false );

			// Get subscription object.
			$subscription = new \WC_Subscription( $subscription_id );

			foreach ( $subscription->get_items() as $item_id => $item ) {
				$current_qty = wc_get_order_item_meta( $item_id, '_qty' );

				$product_id = $item->get_variation_id() > 0 ? $item->get_variation_id() : $item->get_product_id();
				$discount   = $this->fetch_discount_plans( $frequency->interval, $frequency->period, $item->get_product_id() );

				$product = wc_get_product( $product_id );

				$order_item = new WC_Order_Item_Product( $item_id );

				$is_discounted = false;
				if ( $discount && ! $item->get_meta( '_sf_add_to_next_shipment' ) ) {
					$is_discounted = true;
				}

				if ( $product ) {
					if ( $product->is_type( 'bundle' ) ) {
						$new_product = $item->get_product();

						$bundled_cart_items = wc_pb_get_bundled_order_items( $item, $subscription );

						if ( ! empty( $bundled_cart_items ) ) {
							$the_bundle = new WC_Product_Bundle( $new_product );
							foreach ( $bundled_cart_items as $bundled_cart_item ) {
								$item_priced_individually = $bundled_cart_item->get_meta( '_bundled_item_priced_individually', true );

								if ( 'yes' === $item_priced_individually ) {
									$bun_product            = $bundled_cart_item->get_product();
									$bundled_item_raw_price = ! $display_the_price ? $bun_product->get_price() : $bun_product->get_regular_price();

									$bundled_item_price = WC_PB_Product_Prices::get_product_price(
										$bun_product,
										array(
											'price' => $bundled_item_raw_price,
											'calc'  => 'excl_tax',
											'qty'   => $bundled_cart_item->get_quantity(),
										)
									);

									$bundled_item = $the_bundle->get_bundled_item( $bundled_cart_item->get_meta( '_bundled_item_id', true ) );

									if ( $bundled_item->item_data['discount'] && ! $item->get_meta( '_sf_add_to_next_shipment' ) ) {
										$bundle_item_price = WC_CP_Products::get_discounted_price( $bundled_item_price, $bundled_item->item_data['discount'] );
									} else {
										$bundle_item_price = wc_format_decimal( (float) $bundled_item_price, wc_cp_price_num_decimals() );
									}

									if ( $is_discounted ) {
										$calculated_price = wc_format_decimal( $bundle_item_price - ( $bundle_item_price * ( (float) wc_format_decimal( $discount ) / 100 ) ), wc_get_price_decimals() );
									} else {
										$calculated_price = $bundle_item_price;
									}

									$bundled_cart_item->set_subtotal( NumberUtil::round( $calculated_price * $item->get_quantity(), wc_get_price_decimals() ) );
									$bundled_cart_item->set_total( NumberUtil::round( $calculated_price * $item->get_quantity(), wc_get_price_decimals() ) );
									$bundled_cart_item->save();
								}
							}
						}

						$bundle_price = WC_PB_Product_Prices::get_product_price(
							$new_product,
							array(
								'price' => ! $display_the_price ? $new_product->get_price() : $new_product->get_regular_price(),
								'calc'  => 'excl_tax',
								'qty'   => 1,
							)
						);

						if ( $is_discounted ) {
							$bundle_discount = wc_format_decimal( $bundle_price - ( $bundle_price * ( (float) wc_format_decimal( $discount ) / 100 ) ), wc_get_price_decimals() );
						} else {
							$bundle_discount = $bundle_price;
						}

						$item->set_subtotal( NumberUtil::round( $bundle_discount * $item->get_quantity(), wc_get_price_decimals() ) );
						$item->set_total( NumberUtil::round( $bundle_discount * $item->get_quantity(), wc_get_price_decimals() ) );

						$item->calculate_taxes();
						$item->save();
					} elseif ( $product->is_type( 'composite' ) ) {
						$new_product = $item->get_product();

						$bundled_cart_items = wc_cp_get_composited_order_items( $order_item, $subscription );

						if ( ! empty( $bundled_cart_items ) ) {
							$bundled_data = new WC_Product_Composite( $new_product->get_id() );
							foreach ( $bundled_cart_items as $bundled_cart_item ) {
								$item_priced_individually = $bundled_cart_item->get_meta( '_component_priced_individually', true );

								if ( 'yes' === $item_priced_individually ) {
									$bun_product            = $bundled_cart_item->get_product();
									$bundled_item_raw_price = ! $display_the_price ? $bun_product->get_price() : $bun_product->get_regular_price();

									$bundled_item_price = WC_CP_Products::get_product_price(
										$bun_product,
										array(
											'price' => $bundled_item_raw_price,
											'calc'  => 'excl_tax',
											'qty'   => $bundled_cart_item->get_quantity(),
										)
									);

									$component_id = $bundled_cart_item->get_meta( '_composite_item' );
									$discounts    = $bundled_data->get_component_discount( $component_id );

									if ( $discounts && ! $item->get_meta( '_sf_add_to_next_shipment' ) ) {
										$bundle_item_price = WC_CP_Products::get_discounted_price( $bundled_item_price, $discounts );
									} else {
										$bundle_item_price = wc_format_decimal( (float) $bundled_item_price, wc_cp_price_num_decimals() );
									}

									if ( $is_discounted ) {
										$calculated_price = wc_format_decimal( $bundle_item_price - ( $bundle_item_price * ( (float) wc_format_decimal( $discount ) / 100 ) ), wc_get_price_decimals() );
									} else {
										$calculated_price = $bundle_item_price;
									}

									$bundled_cart_item->set_subtotal( NumberUtil::round( $calculated_price, wc_get_price_decimals() ) );
									$bundled_cart_item->set_total( NumberUtil::round( $calculated_price, wc_get_price_decimals() ) );
									$bundled_cart_item->save();
								}
							}
						}

						$bundle_price = WC_CP_Products::get_product_price(
							$new_product,
							array(
								'price' => $new_product->get_price(),
								'calc'  => 'excl_tax',
								'qty'   => 1,
							)
						);

						if ( $is_discounted ) {
							$bundle_discount = wc_format_decimal( $bundle_price - ( $bundle_price * ( (float) wc_format_decimal( $discount ) / 100 ) ), wc_get_price_decimals() );
						} else {
							$bundle_discount = $bundle_price;
						}

						$item->set_subtotal( NumberUtil::round( $bundle_discount * $item->get_quantity(), wc_get_price_decimals() ) );
						$item->set_total( NumberUtil::round( $bundle_discount * $item->get_quantity(), wc_get_price_decimals() ) );

						$item->calculate_taxes();
						$item->save();
					} elseif ( ( ( function_exists( 'wc_cp_is_composited_order_item' ) && ! wc_cp_is_composited_order_item( $item, $subscription ) ) && ( function_exists( 'wc_pb_is_bundled_order_item' ) && ! wc_pb_is_bundled_order_item( $item, $subscription ) ) ) ) {
						$item_price = wc_get_price_excluding_tax( $product, array( 'qty' => 1 ) );
						if ( $is_discounted ) {
							$discounted_price = $item_price - ( $item_price * ( $discount / 100 ) );
						} else {
							$discounted_price = $item_price;
						}

						$new_total = wc_format_decimal( $discounted_price * $current_qty, wc_get_price_decimals() );

						wc_update_order_item_meta( $item_id, '_line_subtotal', $new_total );
						wc_update_order_item_meta( $item_id, '_line_total', $new_total );
					}
				}
			}
			$subscription->save();

			// Re-calculate totals.
			$the_subscription = wcs_get_subscription( $subscription->get_id() );

			$the_subscription->calculate_totals();
			$the_subscription->calculate_taxes();
			$the_subscription->save();

			// Add subscription note.
			$subscription->add_order_note(
				sprintf(
					'%s "%s %s" %s "%s %s"',
					esc_html__( 'Customer changed the subscription frequency from', 'self-service-dashboard-for-woocommerce-subscriptions' ),
					esc_html( $old_interval ),
					esc_html( $old_period ),
					esc_html__( 'to', 'self-service-dashboard-for-woocommerce-subscriptions' ),
					esc_html( $frequency->interval_text ),
					esc_html( $frequency->period_text )
				)
			);

			/**
			 * Run email
			 *
			 * @since 1.0.0
			 */
			do_action( 'sf_frequency_changed_email', $subscription_id, $frequency->interval_text . ' ' . $frequency->period_text );

			return $subscription;
		}

		/**
		 * Fetch the product discount
		 *
		 * @param string $interval Subscription interval.
		 * @param string $period Subscription period.
		 * @param int    $product_id Product ID.
		 *
		 * @return int|mixed
		 */
		public function fetch_discount_plans( $interval, $period, $product_id ) {
			$discount      = 0;
			$product_plans = get_post_meta( $product_id, '_bos4w_saved_subs', true );
			if ( $product_plans ) {
				foreach ( $product_plans as $plan ) {
					if ( $interval === $plan['subscription_period_interval'] && $period === $plan['subscription_period'] ) {
						$discount = $plan['subscription_discount'];
					}
				}
			}

			return $discount;
		}

		/**
		 * Register public assets JS/CSS
		 *
		 * @return void
		 */
		public function assets() {
			$plugin_data = get_plugin_data( __FILE__ );

			wp_enqueue_script( 'sf_change_frequency', SSD_FUNC_URL . 'change-frequency/assets/js/change_frequency.js', array( 'jquery' ), $plugin_data['Version'], false );
			wp_add_inline_script(
				'sf_change_frequency',
				'var sf_change_frequency = ' .
				json_encode(
					array(
						'root'  => esc_url_raw( rest_url() ) . 'sforce/change_frequency',
						'nonce' => wp_create_nonce( 'wp_rest' ),
					)
				),
				'before'
			);
		}

		/**
		 * Register admin/dashboard assets JS/CSS
		 *
		 * @return void
		 */
		public function admin_assets() {
			$plugin_data = get_plugin_data( __FILE__ );

			if ( ( isset( $_GET['page'] ) && 'wc-settings' == $_GET['page'] ) && ( isset( $_GET['tab'] ) && ( 'ssd_settings_tab' == $_GET['tab'] || 'subscription_force' == $_GET['tab'] ) ) ) {

				// Register styles.
				wp_enqueue_style( 'sf_change_frequency_available_frequencies_field_style', SSD_FUNC_URL . 'change-frequency/assets/css/field_available_frequency.css', array(), $plugin_data['Version'] );

				// Register scripts.
				wp_enqueue_script( 'sf_change_frequency_available_frequencies_field', SSD_FUNC_URL . 'change-frequency/assets/js/field_available_frequency.js', array( 'jquery' ), $plugin_data['Version'], false );
			}
		}

		/**
		 * Admin_field_available_frequency
		 *
		 * @param string $value The value.
		 */
		public function admin_field_available_frequency( $value ) {
			$this->get_template( 'admin-field-available-frequency' );
		}

		/**
		 * Save WC settings fields in db - callback
		 *
		 * @return void
		 */
		public function settings_page_update() {
			woocommerce_update_options( $this->get_settings() );
		}

		/**
		 * Get settings array
		 *
		 * @return array
		 */
		public function get_settings() {

			$settings = array(

				/**
				 *    For settings types, see:
				 *    https://github.com/woocommerce/woocommerce/blob/fb8d959c587ee95f543e682e065192553b3cc7ec/includes/admin/class-wc-admin-settings.php#L246
				 */

				// License input.
				array(
					'title' => __( 'Available frequencies for customers', 'self-service-dashboard-for-woocommerce-subscriptions' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'wc_sf_change_frequency_section',
				),

				array(
					'title' => __( 'Visible: Custom Available frequencies', 'self-service-dashboard-for-woocommerce-subscriptions' ),
					'type'  => 'available_frequency',
					'desc'  => '',
					'id'    => 'sf_change_frequency_custom_available_frequencies',
				),

				array(
					'title' => __( 'Hidden: Available frequencies', 'self-service-dashboard-for-woocommerce-subscriptions' ),
					'type'  => 'text',
					'desc'  => '',
					'id'    => 'sf_change_frequency_available_frequencies',
				),

				array(
					'type' => 'sectionend',
					'id'   => 'wc_sf_change_frequency_section',
				),
			);

			/**
			 * Woocommerce_get_settings_subscription_force
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'woocommerce_get_settings_subscription_force', $settings );
		}

		/**
		 * Add change frequency row into subscription details table
		 *
		 * @hook wcs_subscription_details_table_after_dates
		 *
		 * @param array $subscription The subscription.
		 *
		 * @return void
		 */
		public function table_row( $subscription ) {
			set_query_var( 'subscription', $subscription );
			$this->get_template( 'change-frequency-row' );
		}

		/**
		 * Get the specified template
		 *
		 * @param string $template_name Template name.
		 *
		 * @return void
		 */
		public function get_template( $template_name = '' ) {
			include_once SSD_FUNC_PATH . 'change-frequency/templates/' . esc_html( $template_name ) . '.php';
		}

		/**
		 * Get_single_frequency_from_available
		 *
		 * @param int $id The ID.
		 *
		 * @return mixed
		 */
		public function get_single_frequency_from_available( $id ) {
			$frequencies = $this->get_available_frequencies();
			foreach ( $frequencies as $_id => $val ) {
				if ( $_id == $id ) {
					return $frequencies[ $id ];
				}
			}
		}

		/**
		 * Get_available_frequencies
		 *
		 * @return array
		 */
		public function get_available_frequencies() {
			$available_frequencies = '';
			if ( get_option( 'sf_change_frequency_available_frequencies' ) ) {
				$available_frequencies = get_option( 'sf_change_frequency_available_frequencies' );
			}

			return (array) json_decode( $available_frequencies );
		}

		/**
		 * Change Payment Date Email
		 *
		 * @param array $email_classes Woo Classes.
		 *
		 * @return mixed
		 */
		public function sf_add_active_woocommerce_email( $email_classes ) {
			require_once( 'class-wc-frequency-changed-email.php' );

			$email_classes['WC_Frequency_Changed_Email'] = new WC_Frequency_Changed_Email();

			return $email_classes;
		}
	}

	$sforce_change_frequency = new Change_Frequency();
}
