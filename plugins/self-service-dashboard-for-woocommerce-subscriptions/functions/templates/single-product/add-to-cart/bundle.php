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
/** WC Core action. */
$bundle_sell_ids = WC_PB_BS_Product::get_bundle_sell_ids( $product );

if ( ! empty( $bundle_sell_ids ) ) {

	$bundle = WC_PB_BS_Product::get_bundle( $bundle_sell_ids, $product );

	if ( ! $bundle->get_bundled_items() ) {
		return;
	}

	// Syncing at this point will prevent infinite loops in some edge cases.
	$bundle->sync();

	if ( false === wp_style_is( 'wc-bundle-css', 'enqueued' ) ) {
		wp_enqueue_style( 'wc-bundle-css' );
	}

	if ( false === wp_script_is( 'wc-add-to-cart-bundle', 'enqueued' ) ) {
		wp_enqueue_script( 'wc-add-to-cart-bundle' );
	}

	/*
	 * Show Bundle-Sells section title.
	 */
	$bundle_sells_title = WC_PB_BS_Product::get_bundle_sells_title( $product );

	if ( $bundle_sells_title ) {

		$bundle_sells_title_proc = do_shortcode( wp_kses( $bundle_sells_title, WC_PB_Helpers::get_allowed_html( 'inline' ) ) );

		wc_get_template(
			'single-product/bundle-sells-section-title.php',
			array(
				'wrap'  => $bundle_sells_title_proc === $bundle_sells_title,
				'title' => $bundle_sells_title_proc === $bundle_sells_title ? $bundle_sells_title_proc : wpautop( $bundle_sells_title_proc ),
			),
			false,
			WC_PB()->plugin_path() . '/includes/modules/bundle-sells/templates/'
		);
	}
	/**
	 * Before bundle items display
	 *
	 * @param $bundle
	 *
	 * @since 1.0.0
	 */
	do_action( 'woocommerce_before_bundled_items', $bundle );

	/*
	 * Show Bundle-Sells.
	 */
	?>
	<div class="bundle_form bundle_sells_form">
		<?php

		foreach ( $bundle->get_bundled_items() as $bundled_item ) {
			WC_PB_BS_Display::apply_bundled_item_template_overrides();
			/**
			 * Bundle items display
			 *
			 * @param $bundled_item
			 * @param $bundle
			 *
			 * @since 1.0.0
			 */
			do_action( 'woocommerce_bundled_item_details', $bundled_item, $bundle );
			WC_PB_BS_Display::reset_bundled_item_template_overrides();
		}

		?>
		<div class="bundle_data bundle_data_<?php echo absint( $bundle->get_id() ); ?>" data-bundle_form_data="<?php echo esc_attr( json_encode( $bundle->get_bundle_form_data() ) ); ?>" data-bundle_id="<?php echo absint( $bundle->get_id() ); ?>">
			<div class="bundle_wrap">
				<div class="bundle_error" style="display:none">
					<div class="woocommerce-info">
						<ul class="msg"></ul>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
	/**
	 * After bundle items display
	 *
	 * @param $bundle
	 *
	 * @since 1.0.0
	 */
	do_action( 'woocommerce_after_bundled_items', $bundle );
}
?>

<form method="post" enctype="multipart/form-data" class="cart cart_group bundle_form ssd_bundle_form wpr-add-bundle-<?php echo absint( $product_id ); ?> <?php echo esc_attr( $classes ); ?>">
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
	<input type="button" data-id="<?php echo absint( $order_id ); ?>" data-item-id="<?php echo isset( $item_id ) ? absint( $item_id ) : 0; ?>" class="wpr-subscription-bundle-update-submit" value="<?php echo esc_html__( 'Save', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?>"/>
	<input type="button" class="wpr-subscription-cancel-submit" value="<?php echo esc_html__( 'Cancel', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?>"/>
</form><?php
/**
 * WC Core action.
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_after_add_to_cart_form' );
?>
