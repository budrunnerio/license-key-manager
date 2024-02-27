<?php
/**
 * Outlet stock management options.
 *
 * @var WP_Post    $post
 * @var int        $thepostid
 * @var WC_Product $product_object
 */

$outlet_stock = wc_pos_get_product_outlet_stock( $thepostid );
?>
<p class="form-field show_if_simple show_if_variable">
	<label for="wc_pos_managed_outlets"><?php esc_html_e( 'Outlets', 'woocommerce-point-of-sale' ); ?></label>
	<select class="wc-enhanced-select" multiple="multiple" style="width:50%;" id="wc_pos_managed_outlets" name="wc_pos_managed_outlets[]" data-placeholder="<?php esc_attr_e( 'Search for outlets&hellip;', 'woocommerce-point-of-sale' ); ?>">
		<?php
		$outlets = wc_pos_get_outlets( true );
		krsort( $outlets );

		$selected_ids = array_keys( $outlet_stock );

		foreach ( $outlets as $outlet ) {
			$selected = selected( in_array( $outlet->get_id(), $selected_ids, true ), true, false );
			echo '<option value="' . esc_attr( $outlet->get_id() ) . '"' . esc_attr( $selected ) . '>' . esc_html( wp_strip_all_tags( $outlet->get_name() ) ) . '</option>';
		}
		?>
	</select><?php echo wc_help_tip( __( 'Select the outlets where the inventory of this product is located.', 'woocommerce-point-of-sale' ) ); ?>
</p>

<div class="show_if_simple show_if_variable" id="wc_pos_outlet_stock_container">
	<?php
	foreach ( $outlet_stock as $outlet_id => $stock_quantity ) {
		$outlet = wc_pos_get_outlet( $outlet_id );

		if ( $outlet && is_a( $outlet, 'WC_POS_Outlet' ) ) {
			$outlet_name = $outlet->get_name();

			woocommerce_wp_text_input(
				[
					'id'                => 'wc_pos_outlet_stock_' . $outlet_id,
					'name'              => 'wc_pos_outlet_stock_' . $outlet_id,
					'value'             => $stock_quantity,
					'label'             => __( 'Stock for ', 'woocommerce-point-of-sale' ) . $outlet_name,
					'type'              => 'number',
					'custom_attributes' => [
						'data-outlet-id' => $outlet_id,
						'step'           => 'any',
					],
				]
			);
		}
	}
	?>
</div>
