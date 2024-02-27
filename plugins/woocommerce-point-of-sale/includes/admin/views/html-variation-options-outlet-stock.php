<?php
/**
 * Outlet stock managment options for variations.
 *
 * @var int     $loop           Variation index.
 * @var array   $variation_data Variation data.
 * @var WP_Post $variation      Post object.
 */

/**
 * Variation ID.
 *
 * @var int
 */
$variation_id = $variation->ID;

/**
 * Variation object.
 *
 * @var WC_Product_Variation
 */
$variation_object = wc_get_product_object( 'variation', $variation_id );

$outlet_stock = wc_pos_get_product_outlet_stock( $variation_id );
?>
<p class="form-field form-row form-row-full show_if_variation_manage_stock">
	<label for="<?php echo esc_attr( "wc_pos_variation_managed_outlets_${loop}" ); ?>">
		<?php esc_html_e( 'Outlets', 'woocommerce-point-of-sale' ); ?>
	</label><?php echo wc_help_tip( __( 'Select the outlets where the inventory of this product is located.', 'woocommerce-point-of-sale' ) ); ?>
	<select data-variation-index="<?php echo esc_attr( $loop ); ?>" class="wc-enhanced-select wc_pos_variation_managed_outlets" multiple="multiple" style="width:100%;" id="<?php echo esc_attr( "wc_pos_variation_managed_outlets_{$loop}" ); ?>" name="<?php echo esc_attr( "wc_pos_variation_managed_outlets[$loop][]" ); ?>" data-placeholder="<?php esc_attr_e( 'Search for outlets&hellip;', 'woocommerce-point-of-sale' ); ?>">
		<?php
		$outlets = wc_pos_get_outlets( true );
		krsort( $outlets );

		$selected_ids = array_keys( $outlet_stock );

		foreach ( $outlets as $outlet ) {
			$selected = selected( in_array( $outlet->get_id(), $selected_ids, true ), true, false );
			echo '<option value="' . esc_attr( $outlet->get_id() ) . '"' . esc_attr( $selected ) . '>' . esc_html( wp_strip_all_tags( $outlet->get_name() ) ) . '</option>';
		}
		?>
	</select>
</p>

<div class="show_if_variation_manage_stock" id="<?php echo esc_attr( "wc_pos_variation_outlet_stock_container_${loop}" ); ?>">
	<?php
	$index = 0;
	foreach ( $outlet_stock as $outlet_id => $stock_quantity ) {
		$outlet = wc_pos_get_outlet( $outlet_id );

		if ( $outlet && is_a( $outlet, 'WC_POS_Outlet' ) ) {
			$outlet_name = $outlet->get_name();

			woocommerce_wp_text_input(
				[
					'id'                => 'wc_pos_variation_outlet_stock_' . $outlet_id . "_{$loop}",
					'name'              => 'wc_pos_variation_outlet_stock_' . $outlet_id . "[{$loop}]",
					'value'             => $stock_quantity,
					'label'             => __( 'Stock for ', 'woocommerce-point-of-sale' ) . $outlet_name,
					'type'              => 'number',
					'custom_attributes' => [
						'data-outlet-id' => $outlet_id,
						'step'           => 'any',
					],
					'wrapper_class'     => 'form-row ' . ( 0 === $index % 2 ? 'form-row-first' : 'form-row-last' ),
				]
			);

			$index++;
		}
	}
	?>
</div>
 
