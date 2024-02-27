<?php
/**
 * Cash payment gateway
 *
 * @package WooCommerce_Point_Of_Sale/Gateways
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Gateway_Cash
 */
#[AllowDynamicProperties]
class WC_POS_Gateway_Cash extends WC_Payment_Gateway {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// General properties.
		$this->id = 'pos_cash';
		/**
		 * The Cash icon.
		 *
		 * @since 5.0.0
		 */
		$this->icon               = apply_filters( 'wc_pos_cash_icon', '' );
		$this->method_title       = __( 'Cash', 'woocommerce-point-of-sale' );
		$this->method_description = __( 'Have your customers pay with cash (or by other means).', 'woocommerce-point-of-sale' );
		$this->has_fields         = false;

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Get settings.
		$this->title              = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
		$this->instructions       = $this->get_option( 'instructions' );
		$this->enable_for_methods = $this->get_option( 'enable_for_methods', [] );
		$this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes';
		$this->supports           = [ 'products', 'woocommerce-point-of-sale' ];

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );

		// Customer Emails.
		add_action( 'woocommerce_email_before_order_table', [ $this, 'email_instructions' ], 10, 3 );
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {

		$options    = [];
		$data_store = WC_Data_Store::load( 'shipping-zone' );
		$raw_zones  = $data_store->get_zones();

		foreach ( $raw_zones as $raw_zone ) {
			$zones[] = new WC_Shipping_Zone( $raw_zone );
		}

		$zones[] = new WC_Shipping_Zone( 0 );

		foreach ( WC()->shipping()->load_shipping_methods() as $method ) {

			$options[ $method->get_method_title() ] = [];

			// Translators: %1$s shipping method name.
			$options[ $method->get_method_title() ][ $method->id ] = sprintf( __( 'Any &quot;%1$s&quot; method', 'woocommerce-point-of-sale' ), $method->get_method_title() );

			foreach ( $zones as $zone ) {

				$shipping_method_instances = $zone->get_shipping_methods();

				foreach ( $shipping_method_instances as $shipping_method_instance_id => $shipping_method_instance ) {

					if ( $shipping_method_instance->id !== $method->id ) {
						continue;
					}

					$option_id = $shipping_method_instance->get_rate_id();

					// Translators: %1$s shipping method title, %2$s shipping method id.
					$option_instance_title = sprintf( __( '%1$s (#%2$s)', 'woocommerce-point-of-sale' ), $shipping_method_instance->get_title(), $shipping_method_instance_id );

					// Translators: %1$s zone name, %2$s shipping method instance name.
					$option_title = sprintf( __( '%1$s &ndash; %2$s', 'woocommerce-point-of-sale' ), $zone->get_id() ? $zone->get_zone_name() : __( 'Other locations', 'woocommerce-point-of-sale' ), $option_instance_title );

					$options[ $method->get_method_title() ][ $option_id ] = $option_title;
				}
			}
		}

		$this->form_fields = [
			'enabled'            => [
				'title'       => __( 'Enable/Disable', 'woocommerce-point-of-sale' ),
				'label'       => __( 'Enable Cash', 'woocommerce-point-of-sale' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			],
			'title'              => [
				'title'       => __( 'Title', 'woocommerce-point-of-sale' ),
				'type'        => 'text',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce-point-of-sale' ),
				'default'     => __( 'Cash', 'woocommerce-point-of-sale' ),
				'desc_tip'    => true,
			],
			'description'        => [
				'title'       => __( 'Description', 'woocommerce-point-of-sale' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your website.', 'woocommerce-point-of-sale' ),
				'default'     => __( 'Pay with cash.', 'woocommerce-point-of-sale' ),
				'desc_tip'    => true,
			],
			'instructions'       => [
				'title'       => __( 'Instructions', 'woocommerce-point-of-sale' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page.', 'woocommerce-point-of-sale' ),
				'default'     => __( 'Thank you for your purchase.', 'woocommerce-point-of-sale' ),
				'desc_tip'    => true,
			],
			'enable_for_methods' => [
				'title'             => __( 'Enable for shipping methods', 'woocommerce-point-of-sale' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 400px;',
				'default'           => '',
				'description'       => __( 'If the payment method is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'woocommerce-point-of-sale' ),
				'options'           => $options,
				'desc_tip'          => true,
				'custom_attributes' => [
					'data-placeholder' => __( 'Select shipping methods', 'woocommerce-point-of-sale' ),
				],
			],
			'enable_for_virtual' => [
				'title'   => __( 'Accept for virtual orders', 'woocommerce-point-of-sale' ),
				'label'   => __( 'Accept only if the order is virtual', 'woocommerce-point-of-sale' ),
				'type'    => 'checkbox',
				'default' => 'yes',
			],
		];
	}

	/**
	 * Check if the gateway is available.
	 *
	 * @return bool
	 */
	public function is_available() {
		$order          = null;
		$needs_shipping = false;

		if ( ! function_exists( 'wc_pos_is_register_page' ) || ! wc_pos_is_register_page() || is_checkout() ) {
			return false;
		}

		// Test if shipping is needed first.
		if ( WC()->cart && WC()->cart->needs_shipping() ) {
			$needs_shipping = true;
		} elseif ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
			$order_id = absint( get_query_var( 'order-pay' ) );
			$order    = wc_get_order( $order_id );

			// Test if order needs shipping.
			if ( 0 < count( $order->get_items() ) ) {
				foreach ( $order->get_items() as $item ) {
					$_product = $item->get_product();
					if ( $_product && $_product->needs_shipping() ) {
						$needs_shipping = true;
						break;
					}
				}
			}
		}

		/**
		 * This filter is documented in WC core.
		 *
		 * @since 5.0.0
		 */
		$needs_shipping = apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );

		// Virtual order, with virtual disabled.
		if ( ! $this->enable_for_virtual && ! $needs_shipping ) {
			return false;
		}

		// Only apply if all packages are being shipped via chosen method, or order is virtual.
		if ( ! empty( $this->enable_for_methods ) && $needs_shipping ) {
			$order_shipping_items            = is_object( $order ) ? $order->get_shipping_methods() : false;
			$chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );

			if ( $order_shipping_items ) {
				$canonical_rate_ids = $this->get_canonical_order_shipping_item_rate_ids( $order_shipping_items );
			} else {
				$canonical_rate_ids = $this->get_canonical_package_rate_ids( $chosen_shipping_methods_session );
			}

			if ( ! count( $this->get_matching_rates( $canonical_rate_ids ) ) ) {
				return false;
			}
		}

		return parent::is_available();
	}

	/**
	 * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
	 *
	 * @param  array $order_shipping_items  Array of WC_Order_Item_Shipping objects.
	 * @return array $canonical_rate_ids    Rate IDs in a canonical format.
	 */
	private function get_canonical_order_shipping_item_rate_ids( $order_shipping_items ) {

		$canonical_rate_ids = [];

		foreach ( $order_shipping_items as $order_shipping_item ) {
			$canonical_rate_ids[] = $order_shipping_item->get_method_id() . ':' . $order_shipping_item->get_instance_id();
		}

		return $canonical_rate_ids;
	}

	/**
	 * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
	 *
	 * @param  array $chosen_package_rate_ids Rate IDs as generated by shipping methods. Can be anything if a shipping method doesn't honor WC conventions.
	 * @return array $canonical_rate_ids  Rate IDs in a canonical format.
	 */
	private function get_canonical_package_rate_ids( $chosen_package_rate_ids ) {

		$shipping_packages  = WC()->shipping()->get_packages();
		$canonical_rate_ids = [];

		if ( ! empty( $chosen_package_rate_ids ) && is_array( $chosen_package_rate_ids ) ) {
			foreach ( $chosen_package_rate_ids as $package_key => $chosen_package_rate_id ) {
				if ( ! empty( $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ] ) ) {
					$chosen_rate          = $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ];
					$canonical_rate_ids[] = $chosen_rate->get_method_id() . ':' . $chosen_rate->get_instance_id();
				}
			}
		}

		return $canonical_rate_ids;
	}

	/**
	 * Indicates whether a rate exists in an array of canonically-formatted rate IDs that activates this gateway.
	 *
	 * @param array $rate_ids Rate ids to check.
	 * @return boolean
	 */
	private function get_matching_rates( $rate_ids ) {
		// First, match entries in 'method_id:instance_id' format. Then, match entries in 'method_id' format by stripping off the instance ID from the candidates.
		return array_unique( array_merge( array_intersect( $this->enable_for_methods, $rate_ids ), array_intersect( $this->enable_for_methods, array_unique( array_map( 'wc_get_string_before_colon', $rate_ids ) ) ) ) );
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order->get_total() > 0 ) {
			// Mark as processing or on-hold (payment won't be taken until delivery).
			$order->update_status(
				/**
				 * This filter is documented in WC core.
				 *
				 * @since 5.0.0
				 */
				apply_filters( 'woocommerce_pos_cash_process_payment_order_status', $order->has_downloadable_item() ? 'on-hold' : 'processing', $order ),
				__( 'Payment to be made upon delivery.', 'woocommerce-point-of-sale' )
			);
		} else {
			$order->payment_complete();
		}
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin  Sent to admin.
	 * @param bool     $plain_text Email format: plain text or HTML.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
		}
	}
}
