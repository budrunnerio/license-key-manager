<?php
/**
 * Composite Product template
 *
 * @package Self-service Dashboard for WooCommerce Subscriptions
 *
 * @since    2.4.0
 * @version  3.12.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<form method="post" enctype="multipart/form-data" class="cart cart_group composite_form cp-no-js wpr-add-composite-<?php echo absint( $product_id ); ?> <?php echo esc_attr( $classes ); ?>">
	<?php

	$loop  = 0;
	$steps = count( $components );

	/**
	 * Woocommerce_composite_before_components hook
	 *
	 * @hooked wc_cp_before_components - 10
	 *
	 * @since    2.4.0
	 */
	do_action( 'woocommerce_composite_before_components', $components, $product );

	foreach ( $components as $component_id => $component ) {

		$loop ++;

		$tmpl_args = array(
			'product'           => $product,
			'component_id'      => $component_id,
			'component'         => $component,
			'component_data'    => $component->get_data(),
			'component_classes' => $component->get_classes(),
			'step'              => $loop,
			'steps'             => $steps,
		);

		if ( 'single' === $navigation_style ) {
			wc_get_template( 'single-product/component-single-page.php', $tmpl_args, '', WC_CP()->plugin_path() . '/templates/' );
		} elseif ( 'progressive' === $navigation_style ) {
			wc_get_template( 'single-product/component-single-page-progressive.php', $tmpl_args, '', WC_CP()->plugin_path() . '/templates/' );
		} else {
			wc_get_template( 'single-product/component-multi-page.php', $tmpl_args, '', WC_CP()->plugin_path() . '/templates/' );
		}
	}
	?>
	<div id="composite_data_<?php echo absint( $product_id ); ?>" class="cart composite_data <?php echo isset( $_REQUEST['add-to-cart'] ) ? 'composite_added_to_cart' : ''; ?>" data-item_id="review" data-composite_settings="<?php echo esc_attr( json_encode( $product->add_to_cart_form_settings() ) ); ?>" data-nav_title="<?php echo esc_attr( __( 'Review and Purchase', 'woocommerce-composite-products' ) ); ?>" data-scenario_data="<?php echo esc_attr( json_encode( $product->get_current_scenario_data() ) ); ?>" data-price_data="<?php echo esc_attr( json_encode( $product->get_composite_price_data() ) ); ?>" data-container_id="<?php echo absint( $product_id ); ?>" style="display:none;">
		<?php
		/**
		 * 'woocommerce_composite_button_behaviour' filter.
		 *
		 * @since 8.3.4
		 */
		$comp_style = apply_filters( 'woocommerce_composite_button_behaviour', 'new', $product ) === 'new' ? '' : 'display:none';
		?>
		<div class="composite_wrap" style="<?php echo esc_attr( $comp_style ); ?>">
			<div class="composite_price"></div>
			<?php
			/**
			 * 'woocommerce_composite_after_composite_price' action.
			 *
			 * @since 8.3.4
			 */
			do_action( 'woocommerce_composite_after_composite_price' );
			?>
			<div class="composite_message" style="display:none;">
				<ul class="msg woocommerce-info"></ul>
			</div>
			<div class="composite_availability">
				<?php echo wp_kses_post( $availability_html ); ?>
			</div>

		</div>
		<?php
		/**
		 * 'woocommerce_after_add_to_cart_button' action.
		 *
		 * @since 8.3.4
		 */
		do_action( 'woocommerce_after_add_to_cart_button' );
		?>
	</div>
	<input type="button" data-id="<?php echo absint( $order_id ); ?>" data-item-id="<?php echo isset( $item_id ) ? absint( $item_id ) : 0; ?>" class="wpr-subscription-composite-update-submit" value="<?php echo esc_html__( 'Save', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?>"/>
	<input type="button" class="wpr-subscription-cancel-submit" value="<?php echo esc_html__( 'Cancel', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?>"/>
</form>
