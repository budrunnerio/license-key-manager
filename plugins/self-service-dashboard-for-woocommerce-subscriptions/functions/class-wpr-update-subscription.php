<?php
/**
 * Update Subscription
 *
 * @package Self-service Dashboard for WooCommerce Subscriptions
 * @since   0.1
 * @extends \WC_Email
 */

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

use Automattic\WooCommerce\Utilities\NumberUtil;

if ( ! class_exists( 'WPR_Update_Subscription' ) ) {
	/**
	 * Class WPR_Update_Subscription
	 */
	class WPR_Update_Subscription {
		/**
		 * Product types selected by the user in the settings panel.
		 *
		 * @var array
		 */
		public $selected_product_types = array();

		/**
		 * WPR_Update_Subscription constructor.
		 */
		public function __construct() {
			add_action( 'wp_head', array( $this, 'ssd_hook_js' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'woocommerce_order_item_meta_end', array( $this, 'display_the_order_subscription_update_buttons' ), 10, 3 );
			add_action( 'wp_ajax_wpr_update_qty', array( $this, 'wpr_update_subscription_qty' ) );
			add_action( 'wp_ajax_wpr_display_variation', array( $this, 'wpr_display_variation_form' ) );
			add_action( 'wp_ajax_wpr_update_variable', array( $this, 'wpr_update_variable_action' ) );
			add_action( 'wp_ajax_wpr_update_bundle', array( $this, 'wpr_update_bundle_action' ) );
			add_action( 'wp_ajax_wpr_update_composite', array( $this, 'wpr_update_composite_action' ) );
			add_action( 'wp_ajax_wpr_add_new_product', array( $this, 'wpr_add_new_product_form' ) );
			add_action( 'wp_ajax_wpr_add_simple_product', array( $this, 'wpr_add_simple_product_form' ) );
			add_action( 'wp_ajax_wpr_add_variable_product', array( $this, 'wpr_add_variable_product_form' ) );
			add_action( 'wp_ajax_wpr_add_composite_product', array( $this, 'wpr_add_composite_product_form' ) );
			add_action( 'wp_ajax_wpr_add_bundle_product', array( $this, 'wpr_add_bundle_product_form' ) );
			add_filter( 'wcs_view_subscription_actions', array( $this, 'wpr_pause_subscription_action' ), 10, 2 );
			add_action( 'wp_ajax_wpr_pause_subscription', array( $this, 'wpr_pause_subscription_form' ) );
			add_action( 'wp_ajax_wpr_pause_subscription_submit', array( $this, 'wpr_pause_subscription_submit' ) );
			add_action( 'woocommerce_subscription_totals', array( $this, 'wpr_subscription_totals_table_before' ), 5, 4 );
			add_action( 'ssd_before_add_to_cart_form', array( $this, 'ssd_before_add_to_cart_form_content' ) );
			add_filter( 'woocommerce_email_classes', array( $this, 'ssd_add_paused_woocommerce_email' ), 10, 1 );

			add_action( 'ssd_before_add_to_cart_form_search', array( $this, 'ssd_search_products_form' ), 10, 1 );
			add_filter( 'woocommerce_product_data_store_cpt_get_products_query', array( $this, 'handle_custom_search_query_var' ), 10, 2 );
			add_action( 'wp_ajax_wpr_search_products', array( $this, 'wpr_search_products_submit' ) );

			add_filter( 'woocommerce_subscriptions_switch_link', array( $this, 'ssd_remove_switch_button' ), 10, 4 );

			add_action( 'wcs_user_removed_item', array( $this, 'ssd_user_removed_parent_subscription_item' ), 10, 2 );

			add_action( 'wp_footer', array( $this, 'ssd_load_modal_structure' ) );

			// Get selected product types.
			foreach ( wc_get_product_types() as $type => $label ) {
				$val = get_option( 'settings_tabs_product_type_' . $type );

				if ( 'yes' == $val ) {
					if ( 'grouped' != $type || 'external' != $type ) {
						$this->selected_product_types[] = $type;
					}
				}

				// Edge case, when settings are not saved.
				if ( 'subscription' == $type ) {
					if ( null == $val ) {
						$this->selected_product_types[] = 'subscription';
					}
				}
			}

			add_filter( 'woocommerce_get_item_data', array( $this, 'ssd_hide_data_on_checkout' ), 10, 2 );
		}

		/**
		 * Hide data in checkout
		 *
		 * @param array  $item_data Data item array.
		 * @param object $cart_item Cart item data.
		 *
		 * @return mixed
		 */
		public function ssd_hide_data_on_checkout( $item_data, $cart_item ) {
			if ( ! empty( $item_data ) ) {
				$i = 0;
				foreach ( $item_data as $entry_data ) {
					if ( isset( $entry_data['key'] ) && 'bos4w_data' === $entry_data['key'] ) {
						$item_data[ $i ]['hidden'] = 1;
					}
					$i ++;
				}
			}

			return $item_data;
		}

		/**
		 * Add the modal structure
		 */
		public function ssd_load_modal_structure() { ?>
			<div class="ssd-modal-wrapper">
				<div class="ssd-modal">
					<div class="ssd-close-modal"><?php echo esc_html__( 'Close', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></div>
					<div id="ssd-modal-content" class="wpr-add-new-form-content"></div>
				</div>
			</div>
			<?php
		}


		/**
		 * Load cart variation script
		 */
		public function ssd_hook_js() {
			if ( is_account_page() ) {
				wp_enqueue_script( 'wc-add-to-cart-variation' );

				// Enqueue scripts.
				wp_enqueue_script( 'wc-add-to-cart-composite' );

				// Enqueue styles.
				wp_enqueue_style( 'wc-composite-single-css' );

				// Enqueue variation scripts.
				wp_enqueue_script( 'wc-add-to-cart-bundle' );

				wp_enqueue_style( 'wc-bundle-css' );
			}
		}

		/**
		 * Enqueue scripts
		 */
		public function enqueue_scripts() {
			$plugin_data = get_plugin_data( SFORCE_PLUGIN_FILE );
			wp_enqueue_style( 'wpr_sd_frontend', SSD_FUNC_URL . 'assets/css/frontend.css', array(), $plugin_data['Version'] );
			wp_enqueue_script( 'wpr_sd_frontend', SSD_FUNC_URL . 'assets/js/front-end.js', array( 'jquery' ), $plugin_data['Version'], true );

			$cancel_text = esc_html__( 'Cancel', 'self-service-dashboard-for-woocommerce-subscriptions' );

			wp_localize_script(
				'wpr_sd_frontend',
				'wpr_sd_js',
				array(
					'ajax_url'                     => admin_url( 'admin-ajax.php' ),
					'nonce'                        => wp_create_nonce( 'wpr-ssd-nonce' ),
					'wpr_change_qty'               => esc_html__( 'Change quantity', 'self-service-dashboard-for-woocommerce-subscriptions' ),
					'wpr_save_qty'                 => esc_html__( 'Save', 'self-service-dashboard-for-woocommerce-subscriptions' ),
					'wpr_cancel_qty'               => wp_kses_post( $cancel_text ),
					'wpr_change_subs'              => esc_html__( 'Change Subscription', 'self-service-dashboard-for-woocommerce-subscriptions' ),
					'wpr_product_variations_alert' => esc_html__( 'Please select product variations', 'self-service-dashboard-for-woocommerce-subscriptions' ),
					'wpr_product_search_alert'     => esc_html__( 'Please fill the search input', 'self-service-dashboard-for-woocommerce-subscriptions' ),
					'wpr_cancel_add_new'           => sprintf( '<a  href="#" class="wpr-cancel-add-product button">%s</a>', wp_kses_post( $cancel_text ) ),
				)
			);

			wp_register_style( 'jquery-ui-style', WC()->plugin_url() . '/assets/css/jquery-ui/jquery-ui.min.css', array(), '4.8.0' );
			wp_enqueue_style( 'jquery-ui-style' );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script( 'wc-add-to-cart-variation' );
		}

		/**
		 * Register the search for
		 *
		 * @param array $query Query vars array.
		 * @param array $query_vars Query vars array.
		 *
		 * @return mixed
		 */
		public function handle_custom_search_query_var( $query, $query_vars ) {
			if ( isset( $query_vars['like_name'] ) && ! empty( $query_vars['like_name'] ) ) {
				$query['s'] = esc_attr( $query_vars['like_name'] );
			}

			return $query;
		}

		/**
		 * Display the search form inside Add new product section
		 *
		 * @param int $order_id Order id.
		 */
		public function ssd_search_products_form( $order_id ) {
			?>
			<div class="ssd-search-modal-header">
				<div class="ssd-search-modal">
					<input type="search" minlength="3" id="ssd-search-products" class="ssd-search-products" placeholder="<?php echo esc_html__( 'Search products', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?>" data-id="<?php echo absint( $order_id ); ?>"/>
					<button id="ssd-submit-search"><?php echo esc_html__( 'Search', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></button>
				</div>
				<div class="ssd-close-modal">
					<button class="ssd-close-modal-link"><?php echo esc_html__( 'Close', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></button>
				</div>
			</div>
			<?php
		}

		/**
		 * Search for products
		 */
		public function wpr_search_products_submit() {
			check_ajax_referer( 'wpr-ssd-nonce', 'nonce' );

			$order_id       = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
			$item_to_search = isset( $_POST['search_for'] ) ? sanitize_text_field( wp_unslash( $_POST['search_for'] ) ) : '';

			if ( $order_id ) {
				$subscription_get = wcs_get_subscription( $order_id );

				$exclude_ids = array();
				foreach ( $subscription_get->get_items() as $item_id => $item ) {
					if ( $item->get_variation_id() ) {
						$exclude_ids[] = $item->get_variation_id();

						$parent_product = wc_get_product( $item->get_product_id() );
						$variations     = $parent_product->get_children();
						$check_ids      = array_intersect( $exclude_ids, $variations );

						if ( $variations && ( count( $check_ids ) == count( $variations ) && ! array_diff( $check_ids, $variations ) ) ) {
							$exclude_ids[] = $item->get_product_id();
						}
					} else {
						$exclude_ids[] = $item->get_product_id();
					}
				}
				$product_types = array_intersect( array( 'simple', 'variable' ), ( ! empty( $this->selected_product_types ) ? $this->selected_product_types : array( 'no_cpt' ) ) );

				$interval = $subscription_get->get_billing_interval();
				$period   = $subscription_get->get_billing_period();

				$prod_bos4w = array();
				if ( ssd_is_bos_active() ) {
					// Load the products with associated subscription coming from BOS4W!
					$prod_args = array(
						'status'       => 'publish',
						'orderby'      => 'name',
						'order'        => 'ASC',
						'limit'        => - 1,
						'type'         => $product_types,
						'like_name'    => esc_html( $item_to_search ),
						'meta_query'   => array(
							array(
								'key'     => '_bos4w_saved_subs',
								'compare' => 'EXISTS',
							),
						),
						'exclude'      => $exclude_ids,
						'stock_status' => 'instock',
					);
					/**
					 * Query array attributes
					 *
					 * @param array $prod_args Query array attributes.
					 *
					 * @since 1.0.0
					 */
					$prod_args = apply_filters( 'bos4w_product_query_args', $prod_args );

					$prod_bos4w = wc_get_products( $prod_args );

					remove_filter( 'woocommerce_get_price_html', array( 'BOS4W_Front_End', 'bos4w_display_the_discount' ), 9999 );

					if ( $prod_bos4w ) {
						$add_on_list = array();
						foreach ( $prod_bos4w as $index => $buy_prod ) {
							$saved_plans   = $buy_prod->get_meta( '_bos4w_saved_subs', true );
							$add_on_list[] = $buy_prod->get_id();

							if ( $saved_plans ) {
								foreach ( $saved_plans as $plan ) {
									if ( $interval === $plan['subscription_period_interval'] && $period === $plan['subscription_period'] ) {
										if ( $buy_prod->is_type( 'variable' ) ) {
											foreach ( $buy_prod->get_children() as $variation ) {
												$child_product = wc_get_product( $variation );
												if ( $child_product ) {
													/**
													 * Filter bos_use_regular_price.
													 *
													 * @param bool false
													 *
													 * @since 2.0.2
													 */
													$display_the_price = apply_filters( 'bos_use_regular_price', false );

													$child_price = ! $display_the_price ? $child_product->get_price() : $child_product->get_regular_price();

													$child_product->set_price( (float) $child_price - ( (float) $child_price * ( (float) $plan['subscription_discount'] / 100 ) ) );
												}
											}
										} else {
											/**
											 * Filter bos_use_regular_price.
											 *
											 * @param bool false
											 *
											 * @since 2.0.2
											 */
											$display_the_price = apply_filters( 'bos_use_regular_price', false );

											$buy_price = ! $display_the_price ? $buy_prod->get_price() : $buy_prod->get_regular_price();

											$discounted_price = (float) $buy_price - ( (float) $buy_price * ( (float) $plan['subscription_discount'] / 100 ) );
											$buy_prod->set_price( $discounted_price );
										}
									}
								}
							}
						}
						$exclude_ids = array_merge( $exclude_ids, $add_on_list );
					}
				}

				$args = array(
					'post_type'      => 'product',
					'posts_per_page' => - 1,
					'status'         => 'publish',
					'orderby'        => 'name',
					'order'          => 'ASC',
					'like_name'      => esc_html( $item_to_search ),
					'type'           => ( ! empty( $this->selected_product_types ) ? $this->selected_product_types : array( 'no_cpt' ) ),
					'exclude'        => $exclude_ids,
					'stock_status'   => 'instock',
				);
				/**
				 * Query array attributes
				 *
				 * @param array $args Query array attributes.
				 *
				 * @since 1.0.0
				 */
				$args = apply_filters( 'ssd_product_query_args', $args );

				$subscriptions = wc_get_products( $args );

				$subscriptions = (object) array_merge(
					(array) $prod_bos4w,
					(array) $subscriptions
				);

				/**
				 * Filter subscriptions object
				 *
				 * @param object $subscriptions Subscriptions object.
				 *
				 * @since 1.0.0
				 */
				$subscriptions = apply_filters( 'ssd_add_new_product_list', $subscriptions );
				/**
				 * Before add to cart form search
				 *
				 * @param int $order_id Order ID.
				 *
				 * @since 1.0.0
				 */
				do_action( 'ssd_before_add_to_cart_form_search', $order_id );
				?>
				<div class="wpr-add-new-subscription">
					<?php

					/**
					 * Before add to cart form
					 *
					 * @param int $order_id Order ID.
					 *
					 * @since 1.0.0
					 */
					do_action( 'ssd_before_add_to_cart_form', $order_id );
					if ( ! empty( get_object_vars( $subscriptions ) ) ) {
						$p = 0;
						foreach ( $subscriptions as $subscription ) {
							if ( WC_Subscriptions_Product::get_sign_up_fee( $subscription ) ) {
								if ( 1 === count( (array) $subscriptions ) ) {
									printf( '<div>%s</div>', esc_html__( 'There are no subscription products available.', 'self-service-dashboard-for-woocommerce-subscriptions' ) );
								}
								continue;
							}

							$is_visible = $this->ssd_is_product_visible_to_add( $subscription );

							if ( ! $is_visible ) {
								if ( 1 === count( (array) $subscriptions ) ) {
									printf( '<div>%s</div>', esc_html__( 'There are no subscription products available.', 'self-service-dashboard-for-woocommerce-subscriptions' ) );
								}
								continue;
							}

							$current_user    = get_current_user_id();
							$get_limitations = wcs_is_product_limited_for_user( $subscription, absint( $current_user ) );

							if ( $get_limitations ) {
								if ( 1 === count( (array) $subscriptions ) ) {
									printf( '<div>%s</div>', esc_html__( 'There are no subscription products available.', 'self-service-dashboard-for-woocommerce-subscriptions' ) );
								}
								continue;
							}

							if ( $subscription->is_sold_individually() && wcs_order_contains_product( $subscription_get, $subscription ) ) {
								if ( 1 === count( (array) $subscriptions ) ) {
									printf( '<div>%s</div>', esc_html__( 'There are no subscription products available.', 'self-service-dashboard-for-woocommerce-subscriptions' ) );
								}
								continue;
							}

							$price_to_display = $subscription->get_price_html();
							$discount         = $this->ssd_fetch_plan_discount( $interval, $period, absint( $subscription->get_id() ) );
							if ( $discount ) {
								if ( $subscription->is_type( 'variable' ) ) {
									$min_price = $subscription->get_variation_price( 'min', true ) - ( $subscription->get_variation_price( 'min', true ) * ( $discount / 100 ) );
									$max_price = $subscription->get_variation_price( 'max', true ) - ( $subscription->get_variation_price( 'max', true ) * ( $discount / 100 ) );

									$price_to_display = wcs_price_string( BOS4W_Cart_Options::bos4w_display_format_the_frequency( $min_price, $period, $interval ) ) . ' - ' . wcs_price_string( BOS4W_Cart_Options::bos4w_display_format_the_frequency( $max_price, $period, $interval ) );
								} else {
									$price_to_display = wcs_price_string( BOS4W_Cart_Options::bos4w_display_format_the_frequency( $this->ssd_display_bos4w_price( $subscription->get_price_html(), $subscription->get_id() ), $period, $interval ) );
								}
							}
							?>
							<div class="wpr-product-<?php echo absint( $subscription->get_id() ); ?>">
								<div class="wpr-product-image"><?php echo wp_kses_post( $subscription->get_image() ); ?></div>
								<div class="wpr-product-name"><?php echo esc_attr( $subscription->get_name() ); ?></div>
								<div class="wpr-product-price"><?php echo wp_kses_post( $price_to_display ); ?></div>
								<div class="wpr-product-add-button">
									<?php
									if ( $subscription->is_type( 'variable' ) ) {
										$product = wc_get_product( absint( $subscription->get_id() ) );

										/**
										 * Get Available variations
										 *
										 * @since 1.0.0
										 */
										$ajax_threshold       = apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
										$get_variations       = count( $product->get_children() ) <= $ajax_threshold;
										$available_variations = $get_variations ? $product->get_available_variations() : false;
										$attributes           = $product->get_variation_attributes();

										$exclude_variations = array();
										$to_be_removed      = array();
										if ( $available_variations ) {
											foreach ( $available_variations as $key => $the_variable ) {
												if ( ! $the_variable['is_in_stock'] && ! $the_variable['backorders_allowed'] ) {
													$exclude_variations[] = $the_variable['attributes']['attribute_type'];
												}

												if ( in_array( $the_variable['variation_id'], $exclude_ids ) ) {
													$to_be_removed[] = $the_variable['attributes'];
												}
											}

											if ( $discount ) {
												foreach ( $available_variations as $key => $the_variable ) {
													$new_price                                     = $the_variable['display_price'] - ( $the_variable['display_price'] * ( $discount / 100 ) );
													$available_variations[ $key ]['display_price'] = $new_price;
													$the_variable['display_regular_price']         = $new_price;

													$available_variations[ $key ]['price_html'] = wcs_price_string( BOS4W_Cart_Options::bos4w_display_format_the_frequency( $new_price, $period, $interval ) );
												}
											}
										}

										$variations_json = wp_json_encode( $available_variations );
										$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );
										?>
										<?php
										if ( empty( $available_variations ) && false !== $available_variations ) :
											/**
											 * Out of stock message
											 *
											 * @since 1.0.0
											 */
											$out_of_stock = apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'self-service-dashboard-for-woocommerce-subscriptions' ) );
											?>
											<p class="stock out-of-stock"><?php echo esc_html( $out_of_stock ); ?></p>
										<?php else : ?>
											<form style="display: none" class="wpr-add-product-<?php echo absint( $subscription->get_id() ); ?> variations_form cart" method="post" enctype='multipart/form-data' data-subscription-in="<?php echo absint( $subscription->get_id() ); ?>" data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo esc_attr( $variations_attr ); ?>">
												<table class="variations">
													<tbody>
													<?php
													foreach ( $attributes as $attribute_name => $options ) :
														if ( ! empty( $exclude_variations ) ) {
															$options = array_diff( $options, $exclude_variations );
														}

														if ( $to_be_removed ) {
															$options = array_diff( $options, self::array_flatten( $to_be_removed ) );
														}
														?>
														<tr>
															<td class="label">
																<label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>">
																	<?php echo esc_attr( wc_attribute_label( $attribute_name ) ); ?>
																</label>
															</td>
															<td class="value">
																<?php
																wc_dropdown_variation_attribute_options(
																	array(
																		'options'   => $options,
																		'attribute' => esc_attr( $attribute_name ),
																		'product'   => $product,
																	)
																);
																?>
															</td>
														</tr>
													<?php endforeach; ?>
													</tbody>
												</table>
												<div class="single_variation_wrap">

													<div class="woocommerce-variation single_variation" style="">

														<div class="woocommerce-variation-description"></div>

														<div class="woocommerce-variation-price">

															<span class="price"></span>

														</div>

														<div class="woocommerce-variation-availability"></div>

													</div>

												</div>

												<input type="hidden" name="variation_id" class="variation_id" value="">
												<input type="button" class="button wpr-subscription-add-submit" data-id="<?php echo absint( $order_id ); ?>" data-subscription-in="<?php echo absint( $subscription->get_id() ); ?>" value="<?php echo esc_html__( 'Add to subscription', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?>"/>
												<input type="button" class="button wpr-subscription-cancel-submit" value="<?php echo esc_html__( 'Cancel', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?>"/>
												<?php
												/**
												 * After add to cart variable form
												 *
												 * @param int $order_id Order ID.
												 * @param int $subscription_id Subscription ID.
												 *
												 * @since 1.0.0
												 */
												do_action( 'sdd_after_variable_product_form', $order_id, $subscription->get_id() );
												?>
											</form>
											<a href="#" class="wpr-select-add-variable button"><?php echo esc_html__( 'Select option', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></a>
										<?php endif; ?>
										<?php
									} elseif ( $subscription->is_type( 'bundle' ) ) {
										$bundled_items = $subscription->get_bundled_items();
										$form_classes  = array( 'layout_' . $subscription->get_layout(), 'group_mode_' . $subscription->get_group_mode() );

										if ( ! $subscription->is_in_stock() ) {
											$form_classes[] = 'bundle_out_of_stock';
										}

										if ( 'outofstock' === $subscription->get_bundled_items_stock_status() ) {
											$form_classes[] = 'bundle_insufficient_stock';
										}

										if ( ! empty( $bundled_items ) ) {
											/**
											 * Form CSS classes
											 *
											 * @param array $form_classes Form CSS classes.
											 * @param object $subscription Subscription object.
											 *
											 * @since 1.0.0
											 */
											$form_classes = apply_filters( 'woocommerce_bundle_form_classes', $form_classes, $subscription );
											wc_get_template(
												'single-product/add-to-cart/bundle-add.php',
												array(
													'bundled_items'     => $bundled_items,
													'product'           => $subscription,
													'classes'           => implode( ' ', $form_classes ),
													'product_id'        => $subscription->get_id(),
													'order_id'          => $order_id,
													'availability_html' => wc_get_stock_html( $subscription ),
													'bundle_price_data' => $subscription->get_bundle_form_data(),
												),
												false,
												SSD_FUNC_PATH . '/templates/'
											);
										}
										?>
										<a href="#" class="wpr-select-add-bundle button"><?php echo esc_html__( 'Select option', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></a>
										<?php
									} elseif ( $subscription->is_type( 'composite' ) ) {
										$navigation_style           = $subscription->get_composite_layout_style();
										$navigation_style_variation = $subscription->get_composite_layout_style_variation();
										$components                 = $subscription->get_components();

										if ( ! empty( $components ) ) {
											/**
											 * Form CSS classes
											 *
											 * @param array $navigation_style Form CSS classes.
											 * @param object $product Product object.
											 *
											 * @since 1.0.0
											 */
											$classes = apply_filters( 'woocommerce_composite_form_classes', array( $navigation_style, $navigation_style_variation ), $product );

											wc_get_template(
												'single-product/add-to-cart/composite-add.php',
												array(
													'navigation_style' => $navigation_style,
													'classes'          => implode(
														' ',
														$classes
													),
													'components'       => $components,
													'product'          => $subscription,
													'product_id'       => $subscription->get_id(),
													'order_id'         => $order_id,
												),
												'',
												SSD_FUNC_PATH . '/templates/'
											);
										}
										?>
										<a href="#" class="wpr-select-add-composite button"><?php echo esc_html__( 'Select option', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></a>
										<?php
									} else {
										?>

										<a href="#" class="wpr-add-simple-product button" data-id="<?php echo absint( $order_id ); ?>" data-subscription-in="<?php echo absint( $subscription->get_id() ); ?>"><?php echo esc_html__( 'Add to subscription', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></a>

										<?php
										/**
										 * After add to cart form
										 *
										 * @param int $order_id Order ID.
										 * @param int $subscription_id Subscription ID.
										 *
										 * @since 1.0.0
										 */
										do_action( 'sdd_after_simple_product_form', $order_id, $subscription->get_id() );
										?>
									<?php } ?>
								</div>
							</div>
							<?php
							$p++;
						}

						if ( ! $p ) {
							printf( '<div>%s</div>', esc_html__( 'There are no subscription products available.', 'self-service-dashboard-for-woocommerce-subscriptions' ) );
						}
					} else {
						printf( '<div>%s</div>', esc_html__( 'There are no subscription products available.', 'self-service-dashboard-for-woocommerce-subscriptions' ) );
					}
					?>
				</div>
				<?php
			} else {
				printf( '<div>%s</div>', esc_html__( 'There are no subscription products available.', 'self-service-dashboard-for-woocommerce-subscriptions' ) );
			}

			$result = ob_get_contents();
			ob_end_clean();

			wp_send_json_success(
				array(
					'html' => $result,
				)
			);
		}

		/**
		 * Display the Pause date field
		 */
		public function wpr_pause_subscription_form() {
			check_ajax_referer( 'wpr-ssd-nonce', 'nonce' );
			ob_start();
			$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
			$result   = '';
			if ( $order_id > 0 ) {
				$default_date_format = 'yy-mm-dd';
				/**
				 * Date format filter.
				 *
				 * @param string $default_date_format Date format.
				 *
				 * @since 1.0.0
				 */
				$date_format = apply_filters( 'ssd_custom_date_format', $default_date_format );
				?>
				<div id="wpr-pause-date-subscription">
					<script type="text/javascript">
						jQuery(document).ready(function () {
							var enableDisableSubmitBtn = function () {
								var startVal = jQuery('#ssd-datepicker').val().trim();
								var disableBtn = startVal.length === 0;
								jQuery('#wpr-subscription-pause-submit').attr('disabled', disableBtn);
							}

							jQuery('#ssd-datepicker').datepicker({
								dateFormat: '<?php echo esc_html( $date_format ); ?>',
								minDate: 1,
								onSelect: function (selected) {
									jQuery('#ssd-datepicker').datepicker('option', '<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( 'tomorrow' ) ) ); ?>', selected);
									enableDisableSubmitBtn();
								}
							});
						});
					</script>
					<form method="post" id="ssd-pause-form">
						<input type="text" id="ssd-datepicker" readonly placeholder="<?php echo esc_html__( 'Select a date', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?>" class="input-text datepicker" name="wpr-pause-until"/>
						<input type="hidden" name="ssd-subscription" value="<?php echo esc_attr( $order_id ); ?>"/>
						<input type="button" id="wpr-subscription-pause-submit" disabled="disabled" value="<?php echo esc_html__( 'Save', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?>"/>
						<input type="button" id="wpr-subscription-pause-cancel" value="<?php echo esc_html__( 'Cancel', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?>"/>
					</form>
				</div>
				<?php
				$result = ob_get_contents();
				ob_end_clean();
			}

			wp_send_json_success(
				array(
					'html' => $result,
				)
			);
		}

		/**
		 * Update Subscription Qty
		 */
		public function wpr_update_subscription_qty() {
			check_ajax_referer( 'wpr-ssd-nonce', 'nonce' );

			$message = esc_html__( 'Subscription quantity not updated', 'self-service-dashboard-for-woocommerce-subscriptions' );

			$order_id      = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
			$order_item_id = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;
			$qty           = isset( $_POST['qty'] ) ? absint( $_POST['qty'] ) : 0;

			if ( empty( $qty ) ) {
				wp_send_json_error(
					array(
						'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'Quantity must have a value', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
					)
				);
			}

			if ( $qty < 1 ) {
				wp_send_json_error(
					array(
						'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'Quantity must be greater than 0', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
					)
				);
			}

			if ( ! empty( $order_item_id ) ) {

				$order = wc_get_order( $order_id );

				if ( empty( $order ) ) {
					wp_send_json_error(
						array(
							'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'Order ID is invalid', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
						)
					);
				}

				$order_item = new WC_Order_Item_Product( $order_item_id );

				$current_qty   = wc_get_order_item_meta( $order_item_id, '_qty' );
				$current_total = wc_get_order_item_meta( $order_item_id, '_line_subtotal' );

				$unit_price = $current_total;
				if ( $current_qty > 1 ) {
					$unit_price = esc_attr( $current_total ) / absint( $current_qty );
				}

				if ( function_exists( 'wc_pb_is_bundled_order_item' ) ) {
					$bundled_items = wc_pb_get_bundled_order_items( $order_item, $order );
					foreach ( $bundled_items as $bundle_item_id => $bundled_item ) {
						$current_bundle_qty = wc_get_order_item_meta( $bundle_item_id, '_qty' );

						if ( absint( $current_qty ) > $qty ) {
							$new_qty = ( absint( $current_bundle_qty ) / absint( $current_qty ) ) * $qty;
						} else {
							$new_qty = absint( $current_bundle_qty ) * $qty;
						}

						if ( 'yes' === wc_get_order_item_meta( $bundle_item_id, '_bundled_item_priced_individually' ) ) {
							$current_bundle_total = wc_get_order_item_meta( $bundle_item_id, '_line_subtotal' );

							if ( $order->get_prices_include_tax() && wc_tax_enabled() && 'taxable' === $bundled_item->get_tax_status() ) {
								$current_bundle_tax = wc_get_order_item_meta( $bundle_item_id, '_line_subtotal_tax' );

								$tax_unit_price = $current_total;
								if ( $current_bundle_qty > 1 ) {
									$tax_unit_price = esc_attr( $current_bundle_tax ) / absint( $current_bundle_qty );
								}

								$new_tax_total = wc_format_decimal( $tax_unit_price * $new_qty, wc_get_price_decimals() );

								wc_update_order_item_meta( $bundle_item_id, '_line_subtotal_tax', $new_tax_total );
								wc_update_order_item_meta( $bundle_item_id, '_line_tax', $new_tax_total );
							}

							$unit_bundle_price = $current_bundle_total;
							if ( $current_bundle_qty > 1 ) {
								$unit_bundle_price = esc_attr( $current_bundle_total ) / absint( $current_bundle_qty );
							}

							$new_bundle_total = wc_format_decimal( $unit_bundle_price * $new_qty, wc_get_price_decimals() );

							wc_update_order_item_meta( $bundle_item_id, '_line_subtotal', $new_bundle_total );
							wc_update_order_item_meta( $bundle_item_id, '_line_total', $new_bundle_total );
						}

						wc_update_order_item_meta( $bundle_item_id, '_qty', $new_qty );
					}
				}

				if ( function_exists( 'wc_cp_is_composited_order_item' ) ) {
					$composite_items = wc_cp_get_composited_order_items( $order_item, $order );
					foreach ( $composite_items as $composite_item_id => $composite_item ) {
						$current_composite_qty = wc_get_order_item_meta( $composite_item_id, '_qty' );

						if ( absint( $current_qty ) > $qty ) {
							$new_c_qty = ( absint( $current_composite_qty ) / absint( $current_qty ) ) * $qty;
						} else {
							$new_c_qty = absint( $current_composite_qty ) * $qty;
						}

						if ( 'yes' === wc_get_order_item_meta( $composite_item_id, '_component_priced_individually' ) ) {
							$current_composite_total = wc_get_order_item_meta( $composite_item_id, '_line_subtotal' );
							if ( $order->get_prices_include_tax() && wc_tax_enabled() && 'taxable' === $composite_item->get_tax_status() ) {
								$current_composite_tax = wc_get_order_item_meta( $bundle_item_id, '_line_subtotal_tax' );

								$tax_unit_price = $current_total;
								if ( $current_composite_qty > 1 ) {
									$tax_unit_price = esc_attr( $current_composite_tax ) / absint( $current_composite_qty );
								}

								$new_tax_total = wc_format_decimal( $tax_unit_price * $new_c_qty, wc_get_price_decimals() );

								wc_update_order_item_meta( $composite_item_id, '_line_subtotal_tax', $new_tax_total );
								wc_update_order_item_meta( $composite_item_id, '_line_tax', $new_tax_total );
							}

							$unit_composite_price = $current_composite_total;
							if ( $current_composite_qty > 1 ) {
								$unit_composite_price = esc_attr( $current_composite_total ) / absint( $current_composite_qty );
							}

							$new_composite_total = wc_format_decimal( $unit_composite_price * $new_c_qty, wc_get_price_decimals() );

							wc_update_order_item_meta( $composite_item_id, '_line_subtotal', $new_composite_total );
							wc_update_order_item_meta( $composite_item_id, '_line_total', $new_composite_total );
						}
						wc_update_order_item_meta( $composite_item_id, '_qty', $new_c_qty );
					}
				}

				if ( $order->get_prices_include_tax() && wc_tax_enabled() && 'taxable' === $order_item->get_tax_status() ) {
					$current_tax = wc_get_order_item_meta( $order_item_id, '_line_subtotal_tax' );

					$tax_unit_price = $current_total;
					if ( $current_qty > 1 ) {
						$tax_unit_price = esc_attr( $current_tax ) / absint( $current_qty );
					}

					$new_tax_total = wc_format_decimal( $tax_unit_price * $qty, wc_get_price_decimals() );

					wc_update_order_item_meta( $order_item_id, '_line_subtotal_tax', $new_tax_total );
					wc_update_order_item_meta( $order_item_id, '_line_tax', $new_tax_total );
				}

				$new_total = wc_format_decimal( $unit_price * $qty, wc_get_price_decimals() );

				wc_update_order_item_meta( $order_item_id, '_qty', $qty );
				wc_update_order_item_meta( $order_item_id, '_line_subtotal', $new_total );
				wc_update_order_item_meta( $order_item_id, '_line_total', $new_total );

				/**
				 * Do custom code before calculate totals
				 *
				 * @param object $order Order object.
				 * @param int $order_item_id Order item ID.
				 * @param int $qty Qty.
				 *
				 * @since 1.0.0
				 */
				do_action( 'ssd_quantity_update_before_calculate_totals', $order, $order_item_id, $qty );

				// Get Order after totals update!
				$order = wc_get_order( $order_id );

				$order->calculate_totals();
				$order->calculate_taxes();
				$order->save();

				$add_note = sprintf( 'Customer changed quantity on "%s" to "%s".', esc_attr( $order_item->get_name() ), $qty );
				$order->add_order_note( $add_note );
				ssd_register_note();

				$email_notifications = WC()->mailer()->get_emails();
				// Sending the email.
				$email_notifications['WCS_Email_Completed_Switch_Order']->trigger( $order_id );

				$message = esc_html__( 'Product quantity changed.', 'self-service-dashboard-for-woocommerce-subscriptions' );
			}

			wp_send_json_success(
				array(
					'html' => sprintf( '<div class="ssd-display-notification">%s</div>', $message ),
				)
			);
		}

		/**
		 * Update variable subscription
		 */
		public function wpr_update_variable_action() {
			check_ajax_referer( 'wpr-ssd-nonce', 'nonce' );

			if ( ! isset( $_POST['data'] ) ) {
				wp_send_json_error(
					array(
						'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'Invalid data, please try again.', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
					)
				);
			}

			$message = esc_html__( 'Subscription variation not updated', 'self-service-dashboard-for-woocommerce-subscriptions' );

			$post_parameters = wp_unslash( $_POST );
			parse_str( $post_parameters['data'], $parameters );
			$order_id      = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
			$order_item_id = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;
			$variation_id  = isset( $parameters['variation_id'] ) ? absint( $parameters['variation_id'] ) : 0;
			$qty           = wc_get_order_item_meta( $order_item_id, '_qty' );

			$product_id   = wc_get_order_item_meta( $order_item_id, '_product_id' );
			$product_data = wc_get_product( $product_id );

			if ( ! empty( $order_item_id ) ) {
				$order               = new WC_Subscription( $order_id );
				$old_order_item      = new WC_Order_Item_Product( $order_item_id );
				$old_order_item_name = esc_attr( $old_order_item->get_name() );

				if ( empty( $order ) ) {
					wp_send_json_error(
						array(
							'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'Order ID is invalid.', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
						)
					);
				}

				if ( ! $variation_id ) {
					$variation_id = self::wpr_get_variation_id( $product_data, $parameters );
				}

				if ( $variation_id ) {
					$product = new WC_Product_Variation( $variation_id );
					wc_update_order_item( $order_item_id, array( 'order_item_name' => esc_attr( $product->get_name() ) ) );

					foreach ( $parameters as $key => $value ) {
						wc_update_order_item_meta( $order_item_id, str_replace( 'attribute_', '', $key ), $value );
					}
					wc_update_order_item_meta( $order_item_id, '_variation_id', $variation_id );

					$interval = $order->get_billing_interval();
					$period   = $order->get_billing_period();
					$discount = $this->ssd_fetch_plan_discount( $interval, $period, $product_id );

					/**
					 * Filter bos_use_regular_price.
					 *
					 * @param bool false
					 *
					 * @since 2.0.2
					 */
					$display_the_price = apply_filters( 'bos_use_regular_price', false );

					$new_price = ! $display_the_price ? wc_get_price_excluding_tax( $product, array( 'price' => $product->get_price() ) ) : wc_get_price_excluding_tax( $product, array( 'price' => $product->get_regular_price() ) );

					if ( $discount && ! $old_order_item->get_meta( '_sf_add_to_next_shipment' ) ) {
						$new_price = $new_price - ( $new_price * ( $discount / 100 ) );
					}
					$new_price = wc_format_decimal( $new_price * $qty, wc_get_price_decimals() );

					wc_update_order_item_meta( $order_item_id, '_line_subtotal', $new_price );
					wc_update_order_item_meta( $order_item_id, '_line_total', $new_price );

					/**
					 * Do custom code before calculate totals
					 *
					 * @param object $order Order object.
					 * @param int $order_item_id Order item ID.
					 * @param object $product Product object.
					 * @param int $variation_id Variation ID.
					 *
					 * @since 1.0.0
					 */
					do_action( 'ssd_update_variable_item_before_calculate_totals', $order, $order_item_id, $product, $variation_id );

					$order->save();
					$order->calculate_totals();

					$new_order_item = new WC_Order_Item_Product( $order_item_id );
					$add_note       = sprintf( 'Customer switched "%s" variation from "%s" to "%s".', esc_attr( $product_data->get_name() ), esc_attr( $old_order_item_name ), esc_attr( $new_order_item->get_name() ) );
					$order->add_order_note( $add_note );
					ssd_register_note();

					// Remove if added!
					if ( wc_get_order_item_meta( $order_item_id, 'variation_id' ) ) {
						wc_delete_order_item_meta( $order_item_id, 'variation_id' );
					}

					$email_notifications = WC()->mailer()->get_emails();
					// Sending the email.
					$email_notifications['WCS_Email_Completed_Switch_Order']->trigger( $order_id );

					$message = esc_html__( 'Your product has been switched.', 'self-service-dashboard-for-woocommerce-subscriptions' );
				}
			}

			wp_send_json_success(
				array(
					'html' => sprintf( '<div class="ssd-display-notification">%s</div>', $message ),
				)
			);
		}

		/**
		 * Update composite product subscription
		 */
		public function wpr_update_composite_action() {
			check_ajax_referer( 'wpr-ssd-nonce', 'nonce' );

			if ( ! isset( $_POST['data'] ) ) {
				wp_send_json_error(
					array(
						'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'Invalid data, please try again.', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
					)
				);
			}

			$message = esc_html__( 'Subscription composite not updated', 'self-service-dashboard-for-woocommerce-subscriptions' );

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

			$order_id = isset( $_POST['order_id'] ) ? absint( wc_clean( wp_unslash( $_POST['order_id'] ) ) ) : 0;
			$order    = wc_get_order( $order_id );
			$item_id  = isset( $_POST['item_id'] ) ? absint( wc_clean( wp_unslash( $_POST['item_id'] ) ) ) : 0;

			$item = $order->get_item( $item_id );

			if ( ! ( $item instanceof WC_Order_Item ) ) {
				wp_send_json_error(
					array(
						'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'Invalid data, please try again.', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
					)
				);
			}

			$saved_plan = array();
			if ( $item->get_meta( 'bos4w_data' ) ) {
				$saved_plan = $item->get_meta( 'bos4w_data' );
			}

			$product = $item->get_product();

			if ( ! ( $product instanceof WC_Product_Composite ) ) {
				wp_send_json_error(
					array(
						'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'Invalid data, please try again.', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
					)
				);
			}

			$posted_configuration  = WC_CP()->cart->get_posted_composite_configuration( $product );
			$current_configuration = WC_CP_Order::get_current_composite_configuration( $item, $order );

			foreach ( $parameters['wccp_component_selection'] as $the_id => $comp_item ) {
				$posted_configuration[ $the_id ]['product_id'] = $parameters['wccp_component_selection'][ $the_id ];

				if ( isset( $parameters['wccp_variation_id'][ $the_id ] ) ) {
					$posted_configuration[ $the_id ]['variation_id'] = $parameters['wccp_variation_id'][ $the_id ];

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
						$posted_configuration[ $the_id ]['attributes'] = $selected_attributes;
					}
				}

				if ( isset( $parameters['wccp_component_quantity'][ $the_id ] ) ) {
					$posted_configuration[ $the_id ]['quantity'] = absint( $parameters['wccp_component_quantity'][ $the_id ] );
				}
			}

			// Compare posted against current configuration.
			if ( $posted_configuration !== $current_configuration ) {
				$added_to_order = WC_CP()->order->add_composite_to_order(
					$product,
					$order,
					$item->get_quantity(),
					array(
						/**
						 * 'woocommerce_editing_composite_in_order_configuration' filter.
						 *
						 * Use this filter to modify the posted configuration.
						 *
						 * @param  $config   array
						 * @param  $product  WC_Product_Composite
						 * @param  $item     WC_Order_Item
						 * @param  $order    WC_Order
						 *
						 * @since 1.0.0
						 */
						'configuration' => apply_filters( 'woocommerce_editing_composite_in_order_configuration', $posted_configuration, $product, $item, $order ),
					)
				);

				// Invalid configuration?
				if ( is_wp_error( $added_to_order ) ) {

					$message = __( 'The submitted configuration is invalid.', 'woocommerce-composite-products' );
					$data    = $added_to_order->get_error_data();
					$notice  = isset( $data['notices'] ) ? current( $data['notices'] ) : '';

					if ( $notice ) {
						$notice_text = WC_CP_Core_Compatibility::is_wc_version_gte( '3.9' ) ? $notice['notice'] : $notice;
						/* translators: %1$s: Message, %2$s: Notice text. */
						$message = sprintf( _x( '%1$s %2$s', 'edit composite in order: formatted validation message', 'woocommerce-composite-products' ), $message, html_entity_decode( $notice_text ) );
					}

					$response = array(
						'result' => 'failure',
						'error'  => $message,
					);

					wp_send_json( $response );

					// Adjust stock and remove old items.
				} else {

					$new_container_item = $order->get_item( $added_to_order );

					/**
					 * 'woocommerce_editing_composite_in_order' action.
					 *
					 * @param WC_Order_Item_Product $new_item
					 * @param WC_Order_Item_Product $old_item
					 *
					 * @since  3.15.1
					 */
					do_action( 'woocommerce_editing_composite_in_order', $new_container_item, $item, $order );

					if ( $saved_plan ) {
						$new_container_item->add_meta_data( 'bos4w_data', $saved_plan );
					}

					if ( $item->get_meta( '_sf_add_to_next_shipment' ) ) {
						$new_container_item->add_meta_data( '_sf_add_to_next_shipment', 1 );
					}

					$new_container_item->save();

					if ( $saved_plan ) {
						$new_product = $new_container_item->get_product();

						$plan_data = explode( '_', $saved_plan['selected_subscription'] );

						$bundled_cart_items = wc_cp_get_composited_order_items( $new_container_item, $order );

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
									$discount     = $bundled_data->get_component_discount( $component_id );

									if ( $discount && ! $item->get_meta( '_sf_add_to_next_shipment' ) ) {
										$bundle_item_price = WC_CP_Products::get_discounted_price( $bundled_item_price, $discount );
									} else {
										$bundle_item_price = wc_format_decimal( (float) $bundled_item_price, wc_cp_price_num_decimals() );
									}

									$calculated_price = wc_format_decimal( $bundle_item_price - ( $bundle_item_price * ( (float) wc_format_decimal( $plan_data[2] ) / 100 ) ), wc_get_price_decimals() );

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

						$bundle_discount = wc_format_decimal( $bundle_price - ( $bundle_price * ( (float) wc_format_decimal( $plan_data[2] ) / 100 ) ), wc_get_price_decimals() );

						$new_container_item->set_subtotal( NumberUtil::round( $bundle_discount * $item->get_quantity(), wc_get_price_decimals() ) );
						$new_container_item->set_total( NumberUtil::round( $bundle_discount * $item->get_quantity(), wc_get_price_decimals() ) );

						$new_container_item->calculate_taxes();
						$new_container_item->save();
					}

					$components_to_remove = wc_cp_get_composited_order_items( $item, $order );
					$items_to_remove      = array( $item ) + wc_cp_get_composited_order_items( $item, $order, false, true );
					$changes_map          = array();

					foreach ( $components_to_remove as $component_to_remove ) {

						$component_id = $component_to_remove->get_meta( '_composite_item', true );
						$product_id   = $component_to_remove->get_product_id();
						$variation_id = $component_to_remove->get_variation_id();

						if ( $variation_id ) {
							$product_id = $variation_id;
						}

						// Store change to add in order note.
						$changes_map[ $component_id ] = array(
							'id'      => $product_id,
							'actions' => array(
								'remove' => array(
									'title' => $component_to_remove->get_name(),
									'sku'   => '#' . $product_id,
								),
							),
						);
					}

					$components_to_add = wc_cp_get_composited_order_items( $new_container_item, $order );

					foreach ( $components_to_add as $order_item_id => $order_item ) {

						$component_id          = $order_item->get_meta( '_composite_item', true );
						$composited_product    = $order_item->get_product();
						$composited_product_id = $composited_product->get_id();
						$action                = 'add';

						// Store change to add in order note.
						if ( isset( $changes_map[ $component_id ] ) ) {

							// If the selection didn't change, log it as an adjustment.
							if ( $composited_product_id === $changes_map[ $component_id ]['id'] ) {

								$action = 'adjust';

								$changes_map[ $component_id ]['actions'] = array(
									'adjust' => array(
										'title' => $order_item->get_name(),
										'sku'   => '#' . $composited_product_id,
									),
								);

								// Otherwise, log another 'add' action.
							} else {

								$changes_map[ $component_id ]['actions']['add'] = array(
									'title' => $order_item->get_name(),
									'sku'   => '#' . $composited_product_id,
								);
							}

							// If we're seeing this item for the first time, log an 'add' action.
						} else {

							$changes_map[ $component_id ] = array(
								'id'      => $composited_product_id,
								'actions' => array(
									'add' => array(
										'title' => $order_item->get_name(),
										'sku'   => '#' . $composited_product_id,
									),
								),
							);
						}
					}

					$change_strings = array(
						'add'    => array(),
						'remove' => array(),
						'adjust' => array(),
					);

					foreach ( $changes_map as $component_id => $item_changes ) {

						$actions   = array( 'add', 'remove', 'adjust' );
						$component = $product->get_component( $component_id );
						/* translators: Component ID. */
						$component_title = $component ? $component->get_title() : sprintf( __( 'Component #%s', 'woocommerce-composite-products' ), $component_id );

						foreach ( $actions as $action ) {
							if ( isset( $item_changes['actions'][ $action ] ) ) {
								/* translators: Component Title, Item title, Item SKU. */
								$change_strings[ $action ][] = sprintf( _x( '%1$s: %2$s (%3$s)', 'component change note format', 'woocommerce-composite-products' ), $component_title, $item_changes['actions'][ $action ]['title'], $item_changes['actions'][ $action ]['sku'] );
							}
						}
					}

					if ( ! empty( $change_strings['remove'] ) ) {
						/* translators: notification for updated components. */
						$order->add_order_note( sprintf( __( 'Deleted component line items: %s', 'woocommerce-composite-products' ), implode( ', ', $change_strings['remove'] ) ), false, true );
					}

					if ( ! empty( $change_strings['add'] ) ) {
						/* translators: notification for updated components. */
						$order->add_order_note( sprintf( __( 'Added component line items: %s', 'woocommerce-composite-products' ), implode( ', ', $change_strings['add'] ) ), false, true );
					}

					if ( ! empty( $change_strings['adjust'] ) ) {
						/* translators: notification for updated components. */
						$order->add_order_note( sprintf( __( 'Adjusted component line items: %s', 'woocommerce-composite-products' ), implode( ', ', $change_strings['adjust'] ) ), false, true );
					}

					/*
					 * Remove old items.
					 */
					foreach ( $items_to_remove as $remove_item ) {
						$order->remove_item( $remove_item->get_id() );
						$remove_item->delete();
					}

					$order = wcs_get_subscription( $order_id );

					$order->calculate_totals();
					$order->calculate_taxes();
					$order->save();

					$email_notifications = WC()->mailer()->get_emails();
					// Sending the email.
					$email_notifications['WCS_Email_Completed_Switch_Order']->trigger( $order_id );

					$message = esc_html__( 'Product edited.', 'self-service-dashboard-for-woocommerce-subscriptions' );
				}
			}

			wp_send_json_success(
				array(
					'html' => sprintf( '<div class="ssd-display-notification">%s</div>', $message ),
				)
			);
		}

		/**
		 * Update bundle product subscription
		 */
		public function wpr_update_bundle_action() {
			check_ajax_referer( 'wpr-ssd-nonce', 'nonce' );

			if ( ! isset( $_POST['data'] ) ) {
				wp_send_json_error(
					array(
						'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'Invalid data, please try again.', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
					)
				);
			}

			$message = esc_html__( 'Subscription bundle not updated', 'self-service-dashboard-for-woocommerce-subscriptions' );

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

			$order_id = isset( $_POST['order_id'] ) ? absint( wc_clean( wp_unslash( $_POST['order_id'] ) ) ) : 0;
			$order    = wc_get_order( $order_id );
			$item_id  = isset( $_POST['item_id'] ) ? absint( wc_clean( wp_unslash( $_POST['item_id'] ) ) ) : 0;

			$item = $order->get_item( $item_id );

			$saved_plan = array();
			if ( $item->get_meta( 'bos4w_data' ) ) {
				$saved_plan = $item->get_meta( 'bos4w_data' );
			}

			if ( ! ( $item instanceof WC_Order_Item ) ) {
				wp_send_json_error(
					array(
						'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'Invalid data, please try again.', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
					)
				);
			}

			$product = $item->get_product();

			if ( ! ( $product instanceof WC_Product_Bundle ) ) {
				wp_send_json_error(
					array(
						'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'Invalid data, please try again.', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
					)
				);
			}

			$posted_configuration  = WC_PB()->cart->get_posted_bundle_configuration( $product );
			$current_configuration = WC_PB_Order::get_current_bundle_configuration( $item, $order );

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

					$posted_configuration[ $bundle_no ]['quantity'] = absint( $parameters[ 'bundle_quantity_' . $bundle_no ] );
				}
			}

			// Compare posted against current configuration.
			if ( $posted_configuration !== $current_configuration ) {
				$added_to_order = WC_PB()->order->add_bundle_to_order(
					$product,
					$order,
					$item->get_quantity(),
					array(
						/**
						 * 'woocommerce_editing_bundle_in_order_configuration' filter.
						 *
						 * Use this filter to modify the posted configuration.
						 *
						 * @param  $config   array
						 * @param  $product  WC_Product_Bundle
						 * @param  $item     WC_Order_Item
						 * @param  $order    WC_Order
						 *
						 * @since 1.0.0
						 */
						'configuration' => apply_filters( 'woocommerce_editing_bundle_in_order_configuration', $posted_configuration, $product, $item, $order ),
					)
				);

				// Invalid configuration?
				if ( is_wp_error( $added_to_order ) ) {
					$message = __( 'The submitted configuration is invalid.', 'woocommerce-product-bundles' );
					$data    = $added_to_order->get_error_data();
					$notice  = isset( $data['notices'] ) ? current( $data['notices'] ) : '';

					if ( $notice ) {
						$notice_text = WC_PB_Core_Compatibility::is_wc_version_gte( '3.9' ) ? $notice['notice'] : $notice;
						/* translators: %1$s: error, %2$s: reason */
						$message = sprintf( _x( '%1$s %2$s', 'edit bundle in order: formatted validation message', 'woocommerce-product-bundles' ), $message, html_entity_decode( $notice_text ) );
					}

					$response = array(
						'result' => 'failure',
						'error'  => $message,
					);

					wp_send_json( $response );

					// Adjust stock and remove old items.
				} else {
					$new_container_item = $order->get_item( $added_to_order );
					/**
					 * 'woocommerce_editing_bundle_in_order' action.
					 *
					 * @param WC_Order_Item_Product $new_item
					 * @param WC_Order_Item_Product $old_item
					 *
					 * @since  5.9.2
					 */
					do_action( 'woocommerce_editing_bundle_in_order', $new_container_item, $item, $order );

					if ( $saved_plan ) {
						$new_container_item->add_meta_data( 'bos4w_data', $saved_plan );
					}

					if ( $item->get_meta( '_sf_add_to_next_shipment' ) ) {
						$new_container_item->add_meta_data( '_sf_add_to_next_shipment', 1 );
					}

					$new_container_item->save();

					if ( $saved_plan ) {
						$new_product = $new_container_item->get_product();

						$plan_data = explode( '_', $saved_plan['selected_subscription'] );

						$bundled_cart_items = wc_pb_get_bundled_order_items( $new_container_item, $order );

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

									$calculated_price = wc_format_decimal( $bundle_item_price - ( $bundle_item_price * ( (float) wc_format_decimal( $plan_data[2] ) / 100 ) ), wc_get_price_decimals() );

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

						$bundle_discount = wc_format_decimal( $bundle_price - ( $bundle_price * ( (float) wc_format_decimal( $plan_data[2] ) / 100 ) ), wc_get_price_decimals() );

						$new_container_item->set_subtotal( NumberUtil::round( $bundle_discount * $item->get_quantity(), wc_get_price_decimals() ) );
						$new_container_item->set_total( NumberUtil::round( $bundle_discount * $item->get_quantity(), wc_get_price_decimals() ) );

						$new_container_item->calculate_taxes();
						$new_container_item->save();
					}

					$bundled_items_to_remove = wc_pb_get_bundled_order_items( $item, $order );
					$items_to_remove         = array( $item ) + $bundled_items_to_remove;

					$changes_map = array();
					$product_ids = array();

					foreach ( $bundled_items_to_remove as $bundled_item_to_remove ) {
						$bundled_item_id = $bundled_item_to_remove->get_meta( '_bundled_item_id', true );
						$product_id      = $bundled_item_to_remove->get_product_id();
						$variation_id    = $bundled_item_to_remove->get_variation_id();

						if ( $variation_id ) {
							$product_id = $variation_id;
						}

						$product_ids[ $bundled_item_id ] = $product_id;

						// Store change to add in order note.
						$changes_map[ $bundled_item_id ] = array(
							'id'      => $product_id,
							'actions' => array(
								'remove' => array(
									'title' => $bundled_item_to_remove->get_name(),
									'sku'   => '#' . $product_id,
								),
							),
						);
					}

					$bundled_order_items = wc_pb_get_bundled_order_items( $new_container_item, $order );

					foreach ( $bundled_order_items as $order_item_id => $order_item ) {

						$bundled_item_id = $order_item->get_meta( '_bundled_item_id', true );
						$product         = $order_item->get_product();
						$product_id      = $product->get_id();
						$action          = 'add';

						$product_ids[ $bundled_item_id ] = $product_id;

						// Store change to add in order note.
						if ( isset( $changes_map[ $bundled_item_id ] ) ) {

							// If the selection didn't change, log it as an adjustment.
							if ( $product_id === $changes_map[ $bundled_item_id ]['id'] ) {

								$action = 'adjust';

								$changes_map[ $bundled_item_id ]['actions'] = array(
									'adjust' => array(
										'title' => $order_item->get_name(),
										'sku'   => '#' . $product_id,
									),
								);

								// Otherwise, log another 'add' action.
							} else {

								$changes_map[ $bundled_item_id ]['actions']['add'] = array(
									'title' => $order_item->get_name(),
									'sku'   => '#' . $product_id,
								);
							}

							// If we're seeing this bundled item for the first, time, log an 'add' action.
						} else {

							$changes_map[ $bundled_item_id ] = array(
								'id'      => $product_id,
								'actions' => array(
									'add' => array(
										'title' => $order_item->get_name(),
										'sku'   => '#' . $product_id,
									),
								),
							);
						}
					}

					$duplicate_product_ids              = array_diff_assoc( $product_ids, array_unique( $product_ids ) );
					$duplicate_product_bundled_item_ids = array_keys( array_intersect( $product_ids, $duplicate_product_ids ) );

					$change_strings = array(
						'add'    => array(),
						'remove' => array(),
						'adjust' => array(),
					);

					foreach ( $changes_map as $item_id => $item_changes ) {

						$actions = array( 'add', 'remove', 'adjust' );

						foreach ( $actions as $action ) {

							if ( isset( $item_changes['actions'][ $action ] ) ) {

								if ( in_array( $item_id, $duplicate_product_bundled_item_ids ) ) {
									/* translators: %1$s: SKU, %2$s: Bundled item ID */
									$stock_id = sprintf( _x( '%1$s:%2$s', 'bundled items stock change note sku with id format', 'woocommerce-product-bundles' ), $item_changes['actions'][ $action ]['sku'], $item_id );
								} else {
									$stock_id = $item_changes['actions'][ $action ]['sku'];
								}

								/* translators: %1$s: Product title, %2$s: SKU */
								$change_strings[ $action ][] = sprintf( _x( '%1$s (%2$s)', 'bundled items change note format', 'woocommerce-product-bundles' ), $item_changes['actions'][ $action ]['title'], $stock_id );
							}
						}
					}

					if ( ! empty( $change_strings['remove'] ) ) {
						/* translators: List of items */
						$order->add_order_note( sprintf( __( 'Deleted bundled line items: %s', 'woocommerce-product-bundles' ), implode( ', ', $change_strings['remove'] ) ), false, true );
					}

					if ( ! empty( $change_strings['add'] ) ) {
						/* translators: List of items */
						$order->add_order_note( sprintf( __( 'Added bundled line items: %s', 'woocommerce-product-bundles' ), implode( ', ', $change_strings['add'] ) ), false, true );
					}

					if ( ! empty( $change_strings['adjust'] ) ) {
						/* translators: List of items */
						$order->add_order_note( sprintf( __( 'Adjusted bundled line items: %s', 'woocommerce-product-bundles' ), implode( ', ', $change_strings['adjust'] ) ), false, true );
					}

					/*
					 * Remove old items.
					 */
					foreach ( $items_to_remove as $remove_item ) {
						$order->remove_item( $remove_item->get_id() );
						$remove_item->delete();
					}

					/*
					 * Recalculate totals.
					 */
					$order = wcs_get_subscription( $order_id );

					$order->calculate_totals();
					$order->calculate_taxes();
					$order->save();

					$email_notifications = WC()->mailer()->get_emails();
					// Sending the email.
					$email_notifications['WCS_Email_Completed_Switch_Order']->trigger( $order_id );

					$message = esc_html__( 'Product edited.', 'self-service-dashboard-for-woocommerce-subscriptions' );
				}
			}

			wp_send_json_success(
				array(
					'html' => sprintf( '<div class="ssd-display-notification">%s</div>', $message ),
				)
			);
		}

		/**
		 * Form with product variations
		 */
		public function wpr_display_variation_form() {
			check_ajax_referer( 'wpr-ssd-nonce', 'nonce' );

			ob_start();

			$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
			$item_id  = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;
			if ( empty( $item_id ) ) {
				wp_send_json_error(
					array(
						'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'The order item does not exist.', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
					)
				);
			}

			$product_id = wc_get_order_item_meta( $item_id, '_product_id', true );
			if ( empty( $product_id ) ) {
				wp_send_json_error(
					array(
						'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'The selected product does not exists.', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
					)
				);
			}

			$product = wc_get_product( $product_id );
			/**
			 * Do custom code before add to cart form
			 *
			 * @param int $order_id Order id.
			 *
			 * @since 1.0.0
			 */
			do_action( 'ssd_before_add_to_cart_form', $order_id );
			if ( $product->is_type( 'bundle' ) ) {
				$bundled_items = $product->get_bundled_items();
				$form_classes  = array( 'layout_' . $product->get_layout(), 'group_mode_' . $product->get_group_mode() );

				if ( ! $product->is_in_stock() ) {
					$form_classes[] = 'bundle_out_of_stock';
				}

				if ( 'outofstock' === $product->get_bundled_items_stock_status() ) {
					$form_classes[] = 'bundle_insufficient_stock';
				}

				if ( ! empty( $bundled_items ) ) {
					/**
					 * Form CSS classes
					 *
					 * @param array $form_classes Form CSS classes.
					 * @param object $subscription Subscription object.
					 *
					 * @since 1.0.0
					 */
					$form_classes = apply_filters( 'woocommerce_bundle_form_classes', $form_classes, $product );
					wc_get_template(
						'single-product/add-to-cart/bundle.php',
						array(
							'bundled_items'     => $bundled_items,
							'product'           => $product,
							'classes'           => implode( ' ', $form_classes ),
							'product_id'        => $product->get_id(),
							'order_id'          => $order_id,
							'item_id'           => $item_id,
							'availability_html' => wc_get_stock_html( $product ),
							'bundle_price_data' => $product->get_bundle_form_data(),
						),
						false,
						SSD_FUNC_PATH . '/templates/'
					);
				}
			} elseif ( $product->is_type( 'composite' ) ) {
				$navigation_style           = $product->get_composite_layout_style();
				$navigation_style_variation = $product->get_composite_layout_style_variation();
				$components                 = $product->get_components();

				if ( ! empty( $components ) ) {
					/**
					 * Form CSS classes
					 *
					 * @param array $navigation_style Form CSS classes.
					 * @param object $product Product object.
					 *
					 * @since 1.0.0
					 */
					$classes = apply_filters( 'woocommerce_composite_form_classes', array( $navigation_style, $navigation_style_variation ), $product );

					wc_get_template(
						'single-product/add-to-cart/composite.php',
						array(
							'navigation_style' => $navigation_style,
							'classes'          => implode( ' ', $classes ),
							'components'       => $components,
							'product'          => $product,
							'product_id'       => $product->get_id(),
							'order_id'         => $order_id,
							'item_id'          => $item_id,
						),
						'',
						SSD_FUNC_PATH . '/templates/'
					);
				}
			} else {
				/**
				 * Get Available variations
				 *
				 * @since 1.0.0
				 */
				$ajax_threshold       = apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
				$get_variations       = count( $product->get_children() ) <= $ajax_threshold;
				$available_variations = $get_variations ? $product->get_available_variations() : false;
				$attributes           = $product->get_variation_attributes();

				$variations_json = wp_json_encode( $available_variations );
				$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

				?>

				<form class="wpr-change-subscription-variation variations_form cart" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo esc_attr( $variations_attr ); ?>">
					<?php
					/**
					 * Do custom code before variation form
					 *
					 * @since 1.0.0
					 */
					do_action( 'woocommerce_before_variations_form' );
					?>

					<?php
					if ( empty( $available_variations ) && false !== $available_variations ) :
						/**
						 * Out of stock message
						 *
						 * @since 1.0.0
						 */
						$out_of_stock = apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'self-service-dashboard-for-woocommerce-subscriptions' ) );
						?>
						<p class="stock out-of-stock"><?php echo esc_html( $out_of_stock ); ?></p>
					<?php else : ?>
						<table class="variations">
							<tbody>
							<?php foreach ( $attributes as $attribute_name => $options ) : ?>
								<tr>
									<td class="label">
										<label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>">
											<?php echo esc_attr( wc_attribute_label( $attribute_name ) ); ?>
										</label>
									</td>
									<td class="value">
										<?php
										$selected_variation = wc_get_order_item_meta( $item_id, esc_attr( sanitize_title( $attribute_name ) ), true );
										wc_dropdown_variation_attribute_options(
											array(
												'options'   => $options,
												'attribute' => esc_attr( $attribute_name ),
												'product'   => $product,
												'selected'  => $selected_variation,
											)
										);
										?>
									</td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
						<div class="single_variation_wrap">

							<div class="woocommerce-variation single_variation" style="">

								<div class="woocommerce-variation-description"></div>

								<div class="woocommerce-variation-price">

									<span class="price"></span>

								</div>

								<div class="woocommerce-variation-availability"></div>

							</div>

						</div>
					<?php endif; ?>

					<?php
					/**
					 * Do custom code after variation form
					 *
					 * @since 1.0.0
					 */
					do_action( 'woocommerce_after_variations_form' );
					?>

					<input type="hidden" name="variation_id" class="variation_id" value="">
					<input type="button" data-id="<?php echo absint( $order_id ); ?>" disabled="disabled" data-item-id="<?php echo absint( $item_id ); ?>" class="wpr-subscription-update-submit" value="<?php echo esc_html__( 'Save', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?>"/>
					<input type="button" class="wpr-subscription-cancel-submit" value="<?php echo esc_html__( 'Cancel', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?>"/>
				</form>

				<?php
			}
			/**
			 * Do custom code after add to cart form
			 *
			 * @since 1.0.0
			 */
			do_action( 'woocommerce_after_add_to_cart_form' );

			$result = ob_get_contents();
			ob_end_clean();

			wp_send_json_success(
				array(
					'html' => $result,
				)
			);
		}

		/**
		 * Display Add New Product form
		 */
		public function wpr_add_new_product_form() {
			check_ajax_referer( 'wpr-ssd-nonce', 'nonce' );

			ob_start();

			$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

			if ( empty( $order_id ) ) {
				wp_send_json_error(
					array(
						'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'The subscription does not exists.', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
					)
				);
			}

			$subscription_get = wcs_get_subscription( $order_id );

			$exclude_ids = array();
			foreach ( $subscription_get->get_items() as $item_id => $item ) {
				if ( $item->get_variation_id() ) {
					$exclude_ids[] = $item->get_variation_id();

					$parent_product = wc_get_product( $item->get_product_id() );
					$variations     = $parent_product->get_children();
					$check_ids      = array_intersect( $exclude_ids, $variations );

					if ( $variations && ( count( $check_ids ) == count( $variations ) && ! array_diff( $check_ids, $variations ) ) ) {
						$exclude_ids[] = $item->get_product_id();
					}
				} else {
					$exclude_ids[] = $item->get_product_id();
				}
			}
			$product_types = array_intersect( array( 'simple', 'variable' ), ( ! empty( $this->selected_product_types ) ? $this->selected_product_types : array( 'no_cpt' ) ) );

			$interval = $subscription_get->get_billing_interval();
			$period   = $subscription_get->get_billing_period();

			$prod_bos4w = array();
			if ( ssd_is_bos_active() ) {
				// Load the products with associated subscription coming from BOS4W!
				$prod_args = array(
					'status'       => 'publish',
					'orderby'      => 'name',
					'order'        => 'ASC',
					'limit'        => - 1,
					'type'         => $product_types,
					'meta_query'   => array(
						array(
							'key'     => '_bos4w_saved_subs',
							'compare' => 'EXISTS',
						),
					),
					'exclude'      => $exclude_ids,
					'stock_status' => 'instock',
				);
				/**
				 * Query array attributes
				 *
				 * @param array $prod_args Query array attributes.
				 *
				 * @since 1.0.0
				 */
				$prod_args = apply_filters( 'bos4w_product_query_args', $prod_args );

				$prod_bos4w = wc_get_products( $prod_args );

				remove_filter( 'woocommerce_get_price_html', array( 'BOS4W_Front_End', 'bos4w_display_the_discount' ), 9999 );

				if ( $prod_bos4w ) {
					$add_on_list = array();
					foreach ( $prod_bos4w as $index => $buy_prod ) {
						$saved_plans   = $buy_prod->get_meta( '_bos4w_saved_subs', true );
						$add_on_list[] = $buy_prod->get_id();

						/**
						 * Filter bos_use_regular_price.
						 *
						 * @param bool false
						 *
						 * @since 2.0.2
						 */
						$display_the_price = apply_filters( 'bos_use_regular_price', false );

						if ( $saved_plans ) {
							foreach ( $saved_plans as $plan ) {
								if ( $interval === $plan['subscription_period_interval'] && $period === $plan['subscription_period'] ) {
									if ( $buy_prod->is_type( 'variable' ) ) {
										foreach ( $buy_prod->get_children() as $variation ) {
											$child_product = wc_get_product( $variation );
											if ( $child_product ) {
												$item_price = ! $display_the_price ? $child_product->get_price() : $child_product->get_regular_price();

												$child_product->set_price( (float) $item_price - ( (float) $item_price * ( (float) $plan['subscription_discount'] / 100 ) ) );
											}
										}
									} else {
										$item_price = ! $display_the_price ? $buy_prod->get_price() : $buy_prod->get_regular_price();

										$discounted_price = (float) $item_price - ( (float) $item_price * ( (float) $plan['subscription_discount'] / 100 ) );
										$buy_prod->set_price( $discounted_price );
									}
								}
							}
						}
					}
					$exclude_ids = array_merge( $exclude_ids, $add_on_list );
				}
			}

			$args = array(
				'status'       => 'publish',
				'orderby'      => 'name',
				'order'        => 'ASC',
				'limit'        => 30,
				'type'         => ( ! empty( $this->selected_product_types ) ? $this->selected_product_types : array( 'no_cpt' ) ),
				'exclude'      => $exclude_ids,
				'stock_status' => 'instock',
			);

			/**
			 * Query array attributes
			 *
			 * @param array $args Query array attributes.
			 *
			 * @since 1.0.0
			 */
			$args = apply_filters( 'ssd_product_query_args', $args );

			$subscriptions = wc_get_products( $args );

			$subscriptions = (object) array_merge(
				(array) $prod_bos4w,
				(array) $subscriptions
			);
			/**
			 * Filter subscriptions object
			 *
			 * @param object $subscriptions Subscriptions object.
			 *
			 * @since 1.0.0
			 */
			$subscriptions = apply_filters( 'ssd_add_new_product_list', $subscriptions );
			/**
			 * Before add to cart form search
			 *
			 * @param int $order_id Order ID.
			 *
			 * @since 1.0.0
			 */
			do_action( 'ssd_before_add_to_cart_form_search', $order_id );
			?>
			<div class="wpr-add-new-subscription">
				<?php

				/**
				 * Do custom code before add to cart form
				 *
				 * @param int $order_id Order id.
				 *
				 * @since 1.0.0
				 */
				do_action( 'ssd_before_add_to_cart_form', $order_id );
				if ( $subscriptions ) {
					foreach ( $subscriptions as $subscription ) {
						if ( WC_Subscriptions_Product::get_sign_up_fee( $subscription ) ) {
							continue;
						}

						$is_visible = $this->ssd_is_product_visible_to_add( $subscription );

						if ( ! $is_visible ) {
							continue;
						}

						$current_user    = get_current_user_id();
						$get_limitations = wcs_is_product_limited_for_user( $subscription, absint( $current_user ) );

						if ( $get_limitations ) {
							continue;
						}

						if ( $subscription->is_sold_individually() && wcs_order_contains_product( $subscription_get, $subscription ) ) {
							continue;
						}

						$price_to_display = $subscription->get_price_html();
						$discount         = $this->ssd_fetch_plan_discount( $interval, $period, absint( $subscription->get_id() ) );
						if ( $discount ) {
							if ( $subscription->is_type( 'variable' ) ) {
								$min_price = $subscription->get_variation_price( 'min', true ) - ( $subscription->get_variation_price( 'min', true ) * ( $discount / 100 ) );
								$max_price = $subscription->get_variation_price( 'max', true ) - ( $subscription->get_variation_price( 'max', true ) * ( $discount / 100 ) );

								$price_to_display = wcs_price_string( BOS4W_Cart_Options::bos4w_display_format_the_frequency( $min_price, $period, $interval ) ) . ' - ' . wcs_price_string( BOS4W_Cart_Options::bos4w_display_format_the_frequency( $max_price, $period, $interval ) );
							} else {
								$price_to_display = wcs_price_string( BOS4W_Cart_Options::bos4w_display_format_the_frequency( $this->ssd_display_bos4w_price( $subscription->get_price_html(), $subscription->get_id() ), $period, $interval ) );
							}
						}
						?>
						<div class="wpr-product-<?php echo absint( $subscription->get_id() ); ?>">
							<div class="wpr-product-image"><?php echo wp_kses_post( $subscription->get_image() ); ?></div>
							<div class="wpr-product-name"><?php echo esc_attr( $subscription->get_name() ); ?></div>
							<div class="wpr-product-price"><?php echo wp_kses_post( $price_to_display ); ?></div>
							<div class="wpr-product-add-button">
								<?php
								if ( $subscription->is_type( 'variable' ) ) {
									$product = wc_get_product( absint( $subscription->get_id() ) );

									/**
									 * Get Available variations
									 *
									 * @since 1.0.0
									 */
									$ajax_threshold       = apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
									$get_variations       = count( $product->get_children() ) <= $ajax_threshold;
									$available_variations = $get_variations ? $product->get_available_variations() : false;
									$attributes           = $product->get_variation_attributes();

									$exclude_variations = array();
									$to_be_removed      = array();
									if ( $available_variations ) {
										foreach ( $available_variations as $key => $the_variable ) {
											if ( ! $the_variable['is_in_stock'] && ! $the_variable['backorders_allowed'] ) {
												$exclude_variations[] = $the_variable['attributes']['attribute_type'];
											}

											if ( in_array( $the_variable['variation_id'], $exclude_ids ) ) {
												$to_be_removed[] = $the_variable['attributes'];
											}
										}

										if ( $discount ) {
											foreach ( $available_variations as $key => $the_variable ) {
												$new_price                                     = $the_variable['display_price'] - ( $the_variable['display_price'] * ( $discount / 100 ) );
												$available_variations[ $key ]['display_price'] = $new_price;
												$the_variable['display_regular_price']         = $new_price;

												$available_variations[ $key ]['price_html'] = wcs_price_string( BOS4W_Cart_Options::bos4w_display_format_the_frequency( $new_price, $period, $interval ) );
											}
										}
									}

									$variations_json = wp_json_encode( $available_variations );
									$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );
									?>
									<?php
									if ( empty( $available_variations ) && false !== $available_variations ) :
										/**
										 * Out of stock message
										 *
										 * @since 1.0.0
										 */
										$out_of_stock = apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'self-service-dashboard-for-woocommerce-subscriptions' ) );
										?>
										<p class="stock out-of-stock"><?php echo esc_html( $out_of_stock ); ?></p>
									<?php else : ?>

										<form style="display: none" class="wpr-add-product-<?php echo absint( $subscription->get_id() ); ?> variations_form cart" method="post" enctype='multipart/form-data' data-subscription-in="<?php echo absint( $subscription->get_id() ); ?>" data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo esc_attr( $variations_attr ); ?>">
											<table class="variations">
												<tbody>
												<?php
												foreach ( $attributes as $attribute_name => $options ) :
													if ( ! empty( $exclude_variations ) ) {
														$options = array_diff( $options, $exclude_variations );
													}

													if ( $to_be_removed ) {
														$options = array_diff( $options, self::array_flatten( $to_be_removed ) );
													}
													?>
													<tr>
														<td class="label">
															<label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>">
																<?php echo esc_attr( wc_attribute_label( $attribute_name ) ); ?>
															</label>
														</td>
														<td class="value">
															<?php
															wc_dropdown_variation_attribute_options(
																array(
																	'options'   => $options,
																	'attribute' => esc_attr( $attribute_name ),
																	'product'   => $product,
																)
															);
															?>
														</td>
													</tr>
												<?php endforeach; ?>
												</tbody>
											</table>
											<div class="single_variation_wrap">

												<div class="woocommerce-variation single_variation" style="">

													<div class="woocommerce-variation-description"></div>

													<div class="woocommerce-variation-price">

														<span class="price"></span>

													</div>

													<div class="woocommerce-variation-availability"></div>

												</div>

											</div>

											<input type="hidden" name="variation_id" class="variation_id" value="">
											<input type="button" class="button wpr-subscription-add-submit" data-id="<?php echo absint( $order_id ); ?>" data-subscription-in="<?php echo absint( $subscription->get_id() ); ?>" value="<?php echo esc_html__( 'Add to subscription', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?>"/>
											<?php
											/**
											 * After add to cart variable form
											 *
											 * @param int $order_id Order ID.
											 * @param int $subscription_id Subscription ID.
											 *
											 * @since 1.0.0
											 */
											do_action( 'sdd_after_variable_product_form', $order_id, $subscription->get_id() );
											?>
											<input type="button" class="button wpr-subscription-cancel-submit" value="<?php echo esc_html__( 'Cancel', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?>"/>
										</form>
										<a href="#" class="wpr-select-add-variable button"><?php echo esc_html__( 'Select option', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></a>
									<?php endif; ?>
									<?php
								} elseif ( $subscription->is_type( 'bundle' ) ) {
									$bundled_items = $subscription->get_bundled_items();
									$form_classes  = array( 'layout_' . $subscription->get_layout(), 'group_mode_' . $subscription->get_group_mode() );

									if ( ! $subscription->is_in_stock() ) {
										$form_classes[] = 'bundle_out_of_stock';
									}

									if ( 'outofstock' === $subscription->get_bundled_items_stock_status() ) {
										$form_classes[] = 'bundle_insufficient_stock';
									}

									if ( ! empty( $bundled_items ) ) {
										/**
										 * Form CSS classes
										 *
										 * @param array $form_classes Form CSS classes.
										 * @param object $subscription Subscription object.
										 *
										 * @since 1.0.0
										 */
										$form_classes = apply_filters( 'woocommerce_bundle_form_classes', $form_classes, $subscription );
										wc_get_template(
											'single-product/add-to-cart/bundle-add.php',
											array(
												'bundled_items'     => $bundled_items,
												'product'           => $subscription,
												'classes'           => implode( ' ', $form_classes ),
												'product_id'        => $subscription->get_id(),
												'order_id'          => $order_id,
												'availability_html' => wc_get_stock_html( $subscription ),
												'bundle_price_data' => $subscription->get_bundle_form_data(),
											),
											false,
											SSD_FUNC_PATH . '/templates/'
										);
									}
									?>
									<a href="#" class="wpr-select-add-bundle button"><?php echo esc_html__( 'Select option', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></a>
									<?php
								} elseif ( $subscription->is_type( 'composite' ) ) {
									$navigation_style           = $subscription->get_composite_layout_style();
									$navigation_style_variation = $subscription->get_composite_layout_style_variation();
									$components                 = $subscription->get_components();

									if ( ! empty( $components ) ) {
										/**
										 * Form CSS classes
										 *
										 * @param array $navigation_style Form CSS classes.
										 * @param object $product Product object.
										 *
										 * @since 1.0.0
										 */
										$classes = apply_filters( 'woocommerce_composite_form_classes', array( $navigation_style, $navigation_style_variation ), $product );

										wc_get_template(
											'single-product/add-to-cart/composite-add.php',
											array(
												'navigation_style' => $navigation_style,
												'classes'          => implode( ' ', $classes ),
												'components'       => $components,
												'product'          => $subscription,
												'product_id'       => $subscription->get_id(),
												'order_id'         => $order_id,
											),
											'',
											SSD_FUNC_PATH . '/templates/'
										);
									}
									?>
									<a href="#" class="wpr-select-add-composite button"><?php echo esc_html__( 'Select option', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></a>
									<?php
								} else {
									?>

									<a href="#" class="wpr-add-simple-product button" data-id="<?php echo absint( $order_id ); ?>" data-subscription-in="<?php echo absint( $subscription->get_id() ); ?>"><?php echo esc_html__( 'Add to subscription', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></a>

									<?php
									/**
									 * After add to cart form
									 *
									 * @param int $order_id Order ID.
									 * @param int $subscription_id Subscription ID.
									 *
									 * @since 1.0.0
									 */
									do_action( 'sdd_after_simple_product_form', $order_id, $subscription->get_id() );
									?>
								<?php } ?>
							</div>
						</div>
						<?php
					}
				} else {
					printf( '<div>%s</div>', esc_html__( 'There are no subscription products available.', 'self-service-dashboard-for-woocommerce-subscriptions' ) );
				}
				?>
			</div>

			<?php
			$result = ob_get_contents();
			ob_end_clean();

			wp_send_json_success(
				array(
					'html' => $result,
				)
			);
		}

		/**
		 * Add new subscription product to order
		 */
		public function wpr_add_simple_product_form() {
			check_ajax_referer( 'wpr-ssd-nonce', 'nonce' );

			$message = esc_html__( 'Product not added.', 'self-service-dashboard-for-woocommerce-subscriptions' );

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

				$interval = $order->get_billing_interval();
				$period   = $order->get_billing_period();
				$discount = $this->ssd_fetch_plan_discount( $interval, $period, $sub_id );

				if ( $discount ) {
					/**
					 * Filter bos_use_regular_price.
					 *
					 * @param bool false
					 *
					 * @since 2.0.2
					 */
					$display_the_price = apply_filters( 'bos_use_regular_price', false );

					$item_price = ! $display_the_price ? $product->get_price() : $product->get_regular_price();

					$discounted_price = $item_price - ( $item_price * ( $discount / 100 ) );

					$product->set_price( $discounted_price );
				}

				$order->add_product( $product, $qty );

				/**
				 * Before calculate totals
				 *
				 * @param object $order Order object.
				 * @param object $product Product object.
				 *
				 * @since 1.0.0
				 */
				do_action( 'ssd_add_simple_product_before_calculate_totals', $order, $product );

				$order->save();
				$order->calculate_totals();

				$add_note = sprintf( 'Customer added "%s".', esc_attr( $product->get_name() ) );
				$order->add_order_note( $add_note );
				ssd_register_note();

				$email_notifications = WC()->mailer()->get_emails();
				// Sending the email.
				$email_notifications['WCS_Email_Completed_Switch_Order']->trigger( $order_id );

				$message = esc_html__( 'Product added to your subscription.', 'self-service-dashboard-for-woocommerce-subscriptions' );
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
		public function wpr_add_variable_product_form() {
			check_ajax_referer( 'wpr-ssd-nonce', 'nonce' );

			if ( ! isset( $_POST['data'] ) ) {
				wp_send_json_error(
					array(
						'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'Invalid data, please try again.', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
					)
				);
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

				if ( ! $variation_id ) {
					$variation_id = self::wpr_get_variation_id( $product, $parameters );
				}

				if ( $variation_id ) {
					$product = new WC_Product_Variation( $variation_id );

					/**
					 * Before calculate totals
					 *
					 * @param object $order Order object.
					 * @param object $product Product object.
					 * @param int $variation_id Product variation id.
					 *
					 * @since 1.0.0
					 */
					do_action( 'ssd_add_variable_product_before_calculate_totals', $order, $product, $variation_id );

					$interval = $order->get_billing_interval();
					$period   = $order->get_billing_period();
					$discount = $this->ssd_fetch_plan_discount( $interval, $period, $sub_id );

					if ( $discount ) {
						/**
						 * Filter bos_use_regular_price.
						 *
						 * @param bool false
						 *
						 * @since 2.0.2
						 */
						$display_the_price = apply_filters( 'bos_use_regular_price', false );

						$item_price = ! $display_the_price ? $product->get_price() : $product->get_regular_price();

						$discounted_price = $item_price - ( $item_price * ( $discount / 100 ) );

						$product->set_price( $discounted_price );
					}

					$order_item_id = $order->add_product( $product, $qty );

					$order->save();
					$order->calculate_totals();

					$add_note = sprintf( 'Customer added "%s".', esc_attr( $product->get_name() ) );
					$order->add_order_note( $add_note );
					ssd_register_note();

					// Remove if added!
					if ( wc_get_order_item_meta( $order_item_id, 'variation_id' ) ) {
						wc_delete_order_item_meta( $order_item_id, 'variation_id' );
					}

					$email_notifications = WC()->mailer()->get_emails();
					// Sending the email.
					$email_notifications['WCS_Email_Completed_Switch_Order']->trigger( $order_id );

					$message = esc_html__( 'Product added to your subscription.', 'self-service-dashboard-for-woocommerce-subscriptions' );
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
		public function wpr_add_bundle_product_form() {
			check_ajax_referer( 'wpr-ssd-nonce', 'nonce' );

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
						$get_entry                                      = explode( '_', $bundle_key );
						$bundle_no                                      = end( $get_entry );
						$posted_configuration[ $bundle_no ]['quantity'] = absint( $parameters[ 'bundle_quantity_' . $bundle_no ] );
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
					$interval          = $order->get_billing_interval();
					$period            = $order->get_billing_period();
					$discount_to_apply = $this->ssd_fetch_plan_discount( $interval, $period, $sub_id );

					$new_container_item = $order->get_item( $result );
					if ( $discount_to_apply ) {
						$new_product = $new_container_item->get_product();

						$bundled_cart_items = wc_pb_get_bundled_order_items( $new_container_item, $order );

						if ( ! empty( $bundled_cart_items ) ) {
							$the_bundle = new WC_Product_Bundle( $new_product );
							foreach ( $bundled_cart_items as $bundled_item_id => $bundled_cart_item ) {
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

									if ( $bundled_item->item_data['discount'] ) {
										$bundle_item_price = WC_PB_Product_Prices::get_discounted_price( $bundled_item_price, $bundled_item->item_data['discount'] );
									} else {
										$bundle_item_price = wc_format_decimal( (float) $bundled_item_price, wc_cp_price_num_decimals() );
									}

									$calculated_price = wc_format_decimal( $bundle_item_price - ( $bundle_item_price * ( (float) wc_format_decimal( $discount_to_apply ) / 100 ) ), wc_get_price_decimals() );

									$bundled_cart_item->set_subtotal( NumberUtil::round( $calculated_price, wc_get_price_decimals() ) );
									$bundled_cart_item->set_total( NumberUtil::round( $calculated_price, wc_get_price_decimals() ) );
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

						$bundle_discount = wc_format_decimal( $bundle_price - ( $bundle_price * ( (float) wc_format_decimal( $discount_to_apply ) / 100 ) ), wc_get_price_decimals() );

						$new_container_item->set_subtotal( NumberUtil::round( $bundle_discount * 1, wc_get_price_decimals() ) );
						$new_container_item->set_total( NumberUtil::round( $bundle_discount * 1, wc_get_price_decimals() ) );

						$bos_data = array(
							'selected_subscription' => $interval . '_' . $period . '_' . $discount_to_apply,
							'discounted_price'      => NumberUtil::round( $bundle_discount * 1, wc_get_price_decimals() ),
						);
						$new_container_item->add_meta_data( 'bos4w_data', $bos_data );

						$new_container_item->calculate_taxes();
						$new_container_item->save();
					}

					$add_note = sprintf( 'Customer added "%s".', esc_attr( $product->get_name() ) );
					$order->add_order_note( $add_note );
					ssd_register_note();

					$order = wc_get_order( $order_id );

					$order->calculate_totals();
					$order->calculate_taxes();
					$order->save();

					$email_notifications = WC()->mailer()->get_emails();
					// Sending the email.
					$email_notifications['WCS_Email_Completed_Switch_Order']->trigger( $order_id );

					$message = esc_html__( 'Product added to your subscription.', 'self-service-dashboard-for-woocommerce-subscriptions' );
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
		public function wpr_add_composite_product_form() {
			check_ajax_referer( 'wpr-ssd-nonce', 'nonce' );

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
					$interval          = $order->get_billing_interval();
					$period            = $order->get_billing_period();
					$discount_to_apply = $this->ssd_fetch_plan_discount( $interval, $period, $sub_id );

					$new_container_item = $order->get_item( $result );
					if ( $discount_to_apply ) {
						$new_product = $new_container_item->get_product();

						$bundled_cart_items = wc_cp_get_composited_order_items( $new_container_item, $order );

						if ( ! empty( $bundled_cart_items ) ) {
							$bundled_data = new WC_Product_Composite( $new_product->get_id() );
							foreach ( $bundled_cart_items as $bundle_id => $bundled_cart_item ) {
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
									$discount     = $bundled_data->get_component_discount( $component_id );

									if ( $discount ) {
										$bundle_item_price = WC_CP_Products::get_discounted_price( $bundled_item_price, $discount );
									} else {
										$bundle_item_price = wc_format_decimal( (float) $bundled_item_price, wc_cp_price_num_decimals() );
									}

									$calculated_price = wc_format_decimal( $bundle_item_price - ( $bundle_item_price * ( (float) wc_format_decimal( $discount_to_apply ) / 100 ) ), wc_get_price_decimals() );

									$bundled_cart_item->set_subtotal( NumberUtil::round( $calculated_price, wc_get_price_decimals() ) );
									$bundled_cart_item->set_total( NumberUtil::round( $calculated_price, wc_get_price_decimals() ) );
									$bundled_cart_item->save();
								}
							}
						}

						$bundle_price = WC_CP_Products::get_product_price(
							$new_product,
							array(
								'price' => ! $display_the_price ? $new_product->get_price() : $new_product->get_regular_price(),
								'calc'  => 'excl_tax',
								'qty'   => 1,
							)
						);

						$bundle_discount = wc_format_decimal( $bundle_price - ( $bundle_price * ( (float) wc_format_decimal( $discount_to_apply ) / 100 ) ), wc_get_price_decimals() );

						$new_container_item->set_subtotal( NumberUtil::round( $bundle_discount, wc_get_price_decimals() ) );
						$new_container_item->set_total( NumberUtil::round( $bundle_discount, wc_get_price_decimals() ) );

						$bos_data = array(
							'selected_subscription' => $interval . '_' . $period . '_' . $discount_to_apply,
							'discounted_price'      => NumberUtil::round( $bundle_discount, wc_get_price_decimals() ),
						);
						$new_container_item->add_meta_data( 'bos4w_data', $bos_data );

						$new_container_item->calculate_taxes();
						$new_container_item->save();
					}

					$add_note = sprintf( 'Customer added "%s".', esc_attr( $product->get_name() ) );
					$order->add_order_note( $add_note );
					ssd_register_note();

					$order = wc_get_order( $order_id );

					$order->calculate_totals();
					$order->calculate_taxes();
					$order->save();

					$email_notifications = WC()->mailer()->get_emails();
					// Sending the email.
					$email_notifications['WCS_Email_Completed_Switch_Order']->trigger( $order_id );

					$message = esc_html__( 'Product added to your subscription.', 'self-service-dashboard-for-woocommerce-subscriptions' );
				}
			}

			wp_send_json_success(
				array(
					'html' => sprintf( '<div class="ssd-display-notification">%s</div>', $message ),
				)
			);
		}

		/**
		 * Pause the subscription
		 */
		public function wpr_pause_subscription_submit() {
			check_ajax_referer( 'wpr-ssd-nonce', 'nonce' );

			if ( ! isset( $_POST['data'] ) ) {
				wp_send_json_error(
					array(
						'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'Invalid data, please try again.', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
					)
				);
			}

			$message = esc_html__( 'Subscription paused.', 'self-service-dashboard-for-woocommerce-subscriptions' );

			$post_parameters = wp_unslash( $_POST );
			parse_str( $post_parameters['data'], $parameters );

			$subscription_id = isset( $parameters['ssd-subscription'] ) ? absint( $parameters['ssd-subscription'] ) : 0;
			$pause_date      = isset( $parameters['wpr-pause-until'] ) ? esc_attr( $parameters['wpr-pause-until'] ) : 0;

			if ( empty( $subscription_id ) ) {
				wp_send_json_error(
					array(
						'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'Subscription ID is invalid.', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
					)
				);
			}

			if ( ! empty( $pause_date ) && ! empty( $subscription_id ) ) {

				$subscription = wcs_get_subscription( $subscription_id );

				if ( empty( $subscription ) ) {
					wp_send_json_error(
						array(
							'html' => sprintf( '<div class="ssd-error-display-notification">%s</div>', esc_html__( 'Subscription is invalid.', 'self-service-dashboard-for-woocommerce-subscriptions' ) ),
						)
					);
				}

				$formatted_pause_date = str_replace( '/', '-', $pause_date );
				$pause_date           = ! strtotime( $formatted_pause_date ) ? $pause_date : $formatted_pause_date;

				$subscription->update_meta_data( 'ssd_pause_subscription_until', strtotime( $pause_date ) );

				$subscription_note = esc_html__( 'Subscription paused until ' ) . esc_attr( $pause_date );
				$subscription->update_status( 'on-hold', $subscription_note );

				/**
				 * Do custom code after subscription it's paused.
				 *
				 * @param int $subscription_id Product object.
				 * @param string $pause_date Pause date.
				 *
				 * @since 1.0.0
				 */
				do_action( 'ssd_pause_subscription', $subscription_id, $pause_date );

				if ( $subscription_id ) {
					/**
					 * Paused subscription notification
					 *
					 * @param int $subscription_id Product object.
					 *
					 * @since 1.0.0
					 */
					do_action( 'paused_subscription_notification', $subscription_id );
				}
			}

			wp_send_json_success(
				array(
					'html' => sprintf( '<div class="ssd-display-notification">%s</div>', $message ),
				)
			);
		}

		/**
		 * Display Add New Product button.
		 *
		 * @param object $subscription Represents the Subscription object.
		 * @param int    $include_item_removal_links Represents the product ID.
		 * @param int    $totals Represents the product ID.
		 * @param int    $include_switch_links Represents the product ID.
		 */
		public function wpr_subscription_totals_table_before( $subscription, $include_item_removal_links, $totals, $include_switch_links ) {
			if ( 'yes' === get_option( 'settings_tabs_hide_add_new_product' ) ) {
				$add_product_text = esc_html__( 'Add new product', 'self-service-dashboard-for-woocommerce-subscriptions' );
				$add_product_link = sprintf( '<a href="#" data-id="%d" class="wpr-add-product button">%s</a>', $subscription->get_id(), $add_product_text );

				echo wp_kses(
				/**
				 * Add product link
				 *
				 * @param string $add_product_link Link
				 * @param object $subscription Subscription object.
				 *
				 * @since 1.0.0
				 */
					apply_filters( 'ssd_add_product_link', $add_product_link, $subscription ),
					array(
						'a' => array(
							'href'    => array(),
							'title'   => array(),
							'class'   => array(),
							'data-id' => array(),
						),
					)
				);
			}
		}

		/**
		 * Display buttons inside subscription order
		 *
		 * @param int    $item_id Represents the Subscription item ID.
		 * @param object $item Represents the Subscription item.
		 * @param object $subscription Represents the Subscription object.
		 */
		public static function display_the_order_subscription_update_buttons( $item_id, $item, $subscription ) {
			if ( wcs_is_order( $subscription ) || 'shop_subscription' !== $subscription->get_type() ) {
				return;
			}

			$product_id = wcs_get_canonical_product_id( $item );
			$product    = wc_get_product( $product_id );

			printf( '<div class="wpr-variable-form-%d"></div>', esc_attr( $item_id ) );

			if ( 'yes' === get_option( 'settings_tabs_hide_quantity_change' ) && is_account_page() ) {
				$switch_text = esc_html__( 'Change quantity', 'self-service-dashboard-for-woocommerce-subscriptions' );
				$switch_link = sprintf( '<a href="#" data-id="%d" data-item-id="%d" data-item-qty="%d" class="wpr-quantity-update change-for-%d button">%s</a>', $subscription->get_id(), $item_id, $item->get_quantity(), $item_id, $switch_text );

				if ( self::can_item_quantity_change_by_user( $item, $subscription ) ) {
					echo wp_kses(
					/**
					 * Link Attr
					 *
					 * @param $switch_link
					 * @param $item_id
					 * @param $item
					 * @param $subscription
					 *
					 * @since 1.0.0
					 */
						apply_filters( 'ssd_subscriptions_quantity_link', $switch_link, $item_id, $item, $subscription ),
						array(
							'a' => array(
								'href'          => array(),
								'title'         => array(),
								'class'         => array(),
								'data-id'       => array(),
								'data-item-id'  => array(),
								'data-item-qty' => array(),
							),
						)
					);
				}
			}

			if ( self::can_item_be_switched_by_user( $item, $subscription ) && is_account_page() ) {
				$switch_subscription_text = esc_html__( 'Switch item', 'self-service-dashboard-for-woocommerce-subscriptions' );
				if ( $product->is_type( array( 'bundle', 'composite' ) ) ) {
					$switch_subscription_text = esc_html__( 'Edit', 'self-service-dashboard-for-woocommerce-subscriptions' );
				}

				/**
				 * Filter Switch Subscription Item output text.
				 *
				 * @param string The button output text.
				 *
				 * @since 1.0.0
				 */
				$switch_subscription_text = esc_html( apply_filters( 'ssd_switch_item_text', $switch_subscription_text ) );
				$switch_subscription_link = sprintf( '<a href="#" data-id="%d" data-item-id="%d" class="wpr-subscription-update button">%s</a>', $subscription->get_id(), $item_id, $switch_subscription_text );

				echo wp_kses(
				/**
				 * Link Attr
				 *
				 * @param $switch_subscription_link
				 * @param $item_id
				 * @param $item
				 * @param $subscription
				 *
				 * @since 1.0.0
				 */
					apply_filters( 'ssd_subscriptions_change_link', $switch_subscription_link, $item_id, $item, $subscription ),
					array(
						'a' => array(
							'href'         => array(),
							'title'        => array(),
							'class'        => array(),
							'data-id'      => array(),
							'data-item-id' => array(),
						),
					)
				);
			}
		}

		/**
		 * Remove the switch button
		 *
		 * @param string $switch_link Button link.
		 * @param int    $item_id Item ID.
		 * @param object $item Item object.
		 * @param object $subscription Subscription object.
		 *
		 * @return mixed|string
		 */
		public function ssd_remove_switch_button( $switch_link, $item_id, $item, $subscription ) {
			if ( function_exists( 'wc_pb_is_bundled_order_item' ) && wc_pb_is_bundled_order_item( $item, $subscription ) ) {
				return '';
			}

			if ( function_exists( 'wc_cp_is_composited_order_item' ) && wc_cp_is_composited_order_item( $item, $subscription ) ) {
				return '';
			}

			return $switch_link;
		}

		/**
		 * Pause Subscription button
		 *
		 * @param array  $actions Represents the action URL.
		 * @param object $subscription Represents the Subscription object.
		 *
		 * @return mixed
		 */
		public function wpr_pause_subscription_action( $actions, $subscription ) {
			if ( $subscription->can_be_updated_to( 'on-hold' ) && 'yes' === get_option( 'settings_tabs_hide_pause_button' ) ) {
				$actions['pause_subscription'] = array(
					'url'  => add_query_arg( array( 'subscription' => $subscription->get_id() ), wc_get_endpoint_url( 'pause-subscription', 'on-hold' ) ),
					'name' => __( 'Pause', 'self-service-dashboard-for-woocommerce-subscriptions' ),
				);
			}

			return $actions;
		}

		/**
		 * Check if the quantity can be updated
		 *
		 * @param object $item Order item info.
		 * @param object $subscription Subscription object info.
		 *
		 * @return bool
		 */
		public static function can_item_quantity_change_by_user( $item, $subscription ) {
			if ( false === ( $item instanceof WC_Order_Item_Product ) ) {
				return false;
			}

			$product_id = wcs_get_canonical_product_id( $item );
			$product    = wc_get_product( $product_id );

			if ( ! $product ) {
				return false;
			}

			$is_not_sign_up_fee = false;
			if ( 0 === WC_Subscriptions_Product::get_sign_up_fee( $product ) ) {
				$is_not_sign_up_fee = true;
			}

			$subscription_o = wc_get_order( $subscription->get_id() );
			if ( $product->is_sold_individually() && wcs_order_contains_product( $subscription_o, $product ) ) {
				$is_not_sign_up_fee = false;
			}

			if ( function_exists( 'wc_cp_is_composited_order_item' ) && wc_cp_is_composited_order_item( $item, $subscription ) ) {
				$is_not_sign_up_fee = false;
			}

			if ( function_exists( 'wc_pb_is_bundled_order_item' ) && wc_pb_is_bundled_order_item( $item, $subscription ) ) {
				$is_not_sign_up_fee = false;
			}

			return $is_not_sign_up_fee;
		}

		/**
		 * Check if user can switch/change subscription
		 *
		 * @param object $item Represents the Subscription item.
		 * @param object $subscription Represents the Subscription object.
		 *
		 * @return bool
		 */
		public static function can_item_be_switched_by_user( $item, $subscription ) {
			if ( false === ( $item instanceof WC_Order_Item_Product ) ) {
				return false;
			}

			if ( 'yes' === get_option( 'settings_tabs_hide_item_switching' ) ) {
				$product_id = wcs_get_canonical_product_id( $item );

				$product = wc_get_product( $product_id );

				if ( empty( $product ) ) {
					$is_product_switchable = false;
				} else {
					if ( $product->is_type( 'subscription_variation' ) || $product->is_type( 'variation' ) || $product->is_type( 'bundle' ) || $product->is_type( 'composite' ) ) {
						$is_product_switchable = true;
					} else {
						$is_product_switchable = false;
					}

					$is_not_sign_up_fee = false;
					if ( 0 === WC_Subscriptions_Product::get_sign_up_fee( $product ) ) {
						$is_not_sign_up_fee = true;
					}
				}

				if ( $subscription->payment_method_supports( 'subscription_amount_changes' ) && $subscription->payment_method_supports( 'subscription_date_changes' ) ) {
					$can_subscription_be_updated = true;
				} else {
					$can_subscription_be_updated = false;
				}

				if ( $is_product_switchable && $can_subscription_be_updated && $is_not_sign_up_fee ) {
					$is_action_allowed = true;
				} else {
					$is_action_allowed = false;
				}

				if ( function_exists( 'wc_cp_is_composited_order_item' ) && wc_cp_is_composited_order_item( $item, $subscription ) ) {
					$is_action_allowed = false;
				}

				if ( function_exists( 'wc_pb_is_bundled_order_item' ) && wc_pb_is_bundled_order_item( $item, $subscription ) ) {
					$is_action_allowed = false;
				}
			} else {
				return false;
			}

			return $is_action_allowed;
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
		 * Add content before the form
		 */
		public function ssd_before_add_to_cart_form_content() {
			?>
			<script type="text/javascript">
				jQuery(document).ready(function ($) {
					$.getScript("<?php echo esc_url_raw( plugins_url() ); ?>/woocommerce/assets/js/frontend/add-to-cart-variation.js?ver=4.8.0");

					<?php if ( function_exists( 'wc_cp_is_composited_order_item' ) ) { ?>
					$.getScript("<?php echo esc_url_raw( plugins_url() ); ?>/woocommerce-composite-products/assets/js/frontend/add-to-cart-composite.js?ver=4.8.0");
					<?php } ?>

					<?php if ( function_exists( 'wc_pb_is_bundled_order_item' ) ) { ?>
					$.getScript("<?php echo esc_url_raw( plugins_url() ); ?>/woocommerce-product-bundles/assets/js/frontend/add-to-cart-bundle.js?ver=4.8.0");
					<?php } ?>
				});
			</script>
			<?php
		}

		/**
		 * Register the Paused Order Email
		 *
		 * @param array $email_classes Woo Classes.
		 *
		 * @return mixed
		 */
		public function ssd_add_paused_woocommerce_email( $email_classes ) {
			include_once SSD_FUNC_PATH . '/class-wc-paused-order-email.php';

			$email_classes['WC_Paused_Order_Email'] = new WC_Paused_Order_Email();

			return $email_classes;
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
		 * Fetch the product discount
		 *
		 * @param string $interval Subscription interval.
		 * @param string $period Subscription period.
		 * @param int    $product_id Product ID.
		 *
		 * @return int|mixed
		 */
		protected function ssd_fetch_plan_discount( $interval, $period, $product_id ) {
			$discount      = 0;
			$product_plans = get_post_meta( $product_id, '_bos4w_saved_subs', true );
			if ( $product_plans && ssd_is_bos_active() ) {
				foreach ( $product_plans as $plan ) {
					if ( $interval === $plan['subscription_period_interval'] && $period === $plan['subscription_period'] ) {
						$discount = $plan['subscription_discount'];
					}
				}
			}

			return $discount;
		}

		/**
		 * Change the html price display
		 *
		 * @param string $price_html Product price html.
		 * @param int    $product_id Product ID.
		 *
		 * @return mixed
		 */
		protected function ssd_display_bos4w_price( $price_html, $product_id ) {
			if ( get_post_meta( $product_id, '_bos4w_saved_subs', true ) && ssd_is_bos_active() ) {
				return preg_replace( '/ - or subscribe and save up to.*/', '', $price_html );
			}

			return $price_html;
		}

		/**
		 * Show or Hide product from "Add new product" option.
		 *
		 * @param object $product - Product/Subscription Object.
		 *
		 * @return bool
		 */
		public function ssd_is_product_visible_to_add( $product ) {
			$is_visible = true;

			if ( ! $product->is_in_stock() ) {
				$is_visible = false;
			}

			if ( 'hidden' === $product->get_catalog_visibility() ) {
				$is_visible = false;
			}

			/**
			 * Is product visible to add
			 *
			 * @param bool $is_visible Is visible.
			 * @param object $product Product object.
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'ssd_is_product_visible_to_add', $is_visible, $product );
		}

		/**
		 * Remove Bundle/Composite items from subscription
		 *
		 * @param object $item Item object data.
		 * @param object $subscription Subscription object data.
		 */
		public function ssd_user_removed_parent_subscription_item( $item, $subscription ) {
			$product_id = wcs_get_canonical_product_id( $item );

			$product = wc_get_product( $product_id );

			if ( $product->is_type( 'bundle' ) ) {
				$bundled_items = wc_pb_get_bundled_order_items( $item, $subscription );

				if ( ! empty( $bundled_items ) ) {
					foreach ( $bundled_items as $bundled_item ) {

						$bundled_item_keys[] = $bundled_item->get_id();

						$bundled_product_id = wcs_get_canonical_product_id( $bundled_item );
						wcs_update_order_item_type( $bundled_item->get_id(), 'line_item_removed', $subscription->get_id() );
						WCS_Download_Handler::revoke_downloadable_file_permission( $bundled_product_id, $subscription->get_id(), $subscription->get_user_id() );
						/* translators: %1$s: Product title, %2$s: Product ID, %2$s: Product Name */
						$subscription->add_order_note( sprintf( _x( '"%1$s" (Product ID: #%2$d) removal triggered by "%3$s" via the My Account page.', 'used in order note', 'woocommerce-all-products-for-subscriptions' ), wcs_get_line_item_name( $bundled_item ), $bundled_product_id, wcs_get_line_item_name( $item ) ) );
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
			}

			if ( $product->is_type( 'composite' ) ) {
				$composite_items = wc_cp_get_composited_order_items( $item, $subscription );

				if ( ! empty( $composite_items ) ) {
					foreach ( $composite_items as $composite_item ) {

						$composite_item_keys[] = $composite_item->get_id();

						$composite_product_id = wcs_get_canonical_product_id( $composite_item );
						wcs_update_order_item_type( $composite_item->get_id(), 'line_item_removed', $subscription->get_id() );
						WCS_Download_Handler::revoke_downloadable_file_permission( $composite_product_id, $subscription->get_id(), $subscription->get_user_id() );
						/* translators: %1$s: Product title, %2$s: Product ID, %2$s: Product Name */
						$subscription->add_order_note( sprintf( _x( '"%1$s" (Product ID: #%2$d) removal triggered by "%3$s" via the My Account page.', 'used in order note', 'woocommerce-all-products-for-subscriptions' ), wcs_get_line_item_name( $composite_item ), $composite_product_id, wcs_get_line_item_name( $item ) ) );
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
		}

		/**
		 * Flatten the array
		 *
		 * @param array $array The array content.
		 *
		 * @return array|false
		 */
		public static function array_flatten( $array ) {
			$return = array();
			array_walk_recursive(
				$array,
				function ( $a ) use ( &$return ) {
					$return[] = $a;
				}
			);

			return $return;
		}
	}
}

new WPR_Update_Subscription();
