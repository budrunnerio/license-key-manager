<?php
/**
 * Product Bundle single-product template
 *
 * @package Self-service Dashboard for WooCommerce Subscriptions
 * @version 5.5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<form style="display: none" method="post" enctype="multipart/form-data" class="cart cart_group bundle_form ssd_bundle_form wpr-add-bundle-<?php echo absint( $product_id ); ?> <?php echo esc_attr( $classes ); ?>">
	<?php
	/**
	 * 'woocommerce_before_bundled_items' action.
	 *
	 * @param WC_Product_Bundle $product
	 *
	 * @since 1.0.0
	 */
	do_action( 'woocommerce_before_bundled_items', $product );

	foreach ( $bundled_items as $bundled_item ) {

		/**
		 * 'woocommerce_bundled_item_details' action.
		 *
		 * @hooked wc_pb_template_bundled_item_details_wrapper_open  -   0
		 * @hooked wc_pb_template_bundled_item_thumbnail             -   5
		 * @hooked wc_pb_template_bundled_item_details_open          -  10
		 * @hooked wc_pb_template_bundled_item_title                 -  15
		 * @hooked wc_pb_template_bundled_item_description           -  20
		 * @hooked wc_pb_template_bundled_item_product_details       -  25
		 * @hooked wc_pb_template_bundled_item_details_close         -  30
		 * @hooked wc_pb_template_bundled_item_details_wrapper_close - 100
		 *
		 * @since 1.0.0
		 */
		do_action( 'woocommerce_bundled_item_details', $bundled_item, $product );
	}

	/**
	 * 'woocommerce_after_bundled_items' action.
	 *
	 * @param WC_Product_Bundle $product
	 *
	 * @since 1.0.0
	 */
	do_action( 'woocommerce_after_bundled_items', $product );

	/**
	 * 'woocommerce_bundles_add_to_cart_wrap' action.
	 *
	 * @param WC_Product_Bundle $product
	 *
	 * @since  5.5.0
	 */
	$is_purchasable     = $product->is_purchasable();
	$purchasable_notice = __( 'This product is currently unavailable.', 'woocommerce-product-bundles' );

	if ( ! $is_purchasable && current_user_can( 'manage_woocommerce' ) ) {

		$purchasable_notice_reason = '';

		// Give store owners a reason.
		if ( defined( 'WC_PB_UPDATING' ) ) {
			/* translators: Ticket form URL  */
			$purchasable_notice_reason .= sprintf( __( 'The Product Bundles database is updating in the background. During this time, all bundles on your site will be unavailable. If this message persists, please <a href="%s" target="_blank">get in touch</a> with our support team. Note: This message is visible to store managers only.', 'woocommerce-product-bundles' ), WC_PB()->get_resource_url( 'ticket-form' ) );
		} elseif ( false === $product->contains( 'priced_individually' ) && '' === $product->get_price() ) {
			/* translators: %1$s: Product title %, %2$s: Pricing options doc URL */
			$purchasable_notice_reason .= sprintf( __( '&quot;%1$s&quot; is not purchasable just yet. But, fear not &ndash; setting up <a href="%2$s" target="_blank">pricing options</a> only takes a minute! <ul class="pb_notice_list"><li>To give &quot;%1$s&quot; a static base price, navigate to <strong>Product Data > General</strong> and fill in the <strong>Regular Price</strong> field.</li><li>To preserve the prices and taxes of individual bundled products, go to <strong>Product Data > Bundled Products</strong> and enable <strong>Priced Individually</strong> for each bundled product whose price must be preserved.</li></ul>Note: This message is visible to store managers only.', 'woocommerce-product-bundles' ), $product->get_title(), WC_PB()->get_resource_url( 'pricing-options' ) );
		} elseif ( $product->contains( 'non_purchasable' ) ) {
			$purchasable_notice_reason .= __( 'Please make sure that all products contained in this bundle have a price. WooCommerce does not allow products with a blank price to be purchased. Note: This message is visible to store managers only.', 'woocommerce-product-bundles' );
		} elseif ( $product->contains( 'subscriptions' ) && class_exists( 'WC_Subscriptions_Admin' ) && 'yes' !== get_option( WC_Subscriptions_Admin::$option_prefix . '_multiple_purchase', 'no' ) ) {
			$purchasable_notice_reason .= __( 'Please enable <strong>Mixed Checkout</strong> under <strong>WooCommerce > Settings > Subscriptions</strong>. Bundles that contain subscription-type products cannot be purchased when <strong>Mixed Checkout</strong> is disabled. Note: This message is visible to store managers only.', 'woocommerce-product-bundles' );
		}

		if ( $purchasable_notice_reason ) {
			$purchasable_notice .= '<span class="purchasable_notice_reason">' . $purchasable_notice_reason . '</span>';
		}
	}

	$form_data = $product->get_bundle_form_data();

	wc_get_template(
		'single-product/add-to-cart/bundle-add-to-cart-wrap.php',
		array(
			'is_purchasable'     => $is_purchasable,
			'purchasable_notice' => $purchasable_notice,
			'availability_html'  => wc_get_stock_html( $product ),
			'bundle_form_data'   => $form_data,
			'product'            => $product,
			'product_id'         => $product->get_id(),
			'bundle_price_data'  => $form_data,
		),
		false,
		SSD_FUNC_PATH . '/templates/'
	);
	?>
	<input type="button" data-id="<?php echo absint( $order_id ); ?>" data-subscription-in="<?php echo absint( $product_id ); ?>" class="wpr-subscription-bundle-add-submit" value="<?php echo esc_html__( 'Add to subscription', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?>"/>
	<input type="button" class="wpr-subscription-cancel-submit" value="<?php echo esc_html__( 'Cancel', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?>"/>
	<?php
	/**
	 * After add to cart bundle form
	 *
	 * @param int $order_id Order ID.
	 * @param int $product_id Subscription ID.
	 *
	 * @since 1.0.0
	 */
	do_action( 'sdd_after_bundle_product_form', $order_id, $product_id );
	?>
</form><?php
/**
 * WC Core action.
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_after_add_to_cart_form' );
?>
