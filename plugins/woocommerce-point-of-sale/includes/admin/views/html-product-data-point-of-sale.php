<?php
/**
 * Product Point of Sale Data Panel
 *
 * @var int $thepostid
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Views
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="point_of_sale_product_data" class="panel woocommerce_options_panel">
	<div class="options_group">
		<?php
			woocommerce_wp_checkbox(
				[
					'id'          => 'unit_of_measurement',
					'value'       => get_post_meta( $thepostid, 'unit_of_measurement', true ),
					'label'       => __( 'Unit of Measurement', 'woocommerce-point-of-sale' ),
					'description' => __( 'Change the unit of measurement of stock values.', 'woocommerce-point-of-sale' ),
				]
			);

			woocommerce_wp_checkbox(
				[
					'id'          => 'uom_override_quantity',
					'value'       => get_post_meta( $thepostid, 'uom_override_quantity', true ),
					'label'       => __( 'Override Quantity', 'woocommerce-point-of-sale' ),
					'description' => __( 'Check this to override quantity when scanning price embedded barcodes.', 'woocommerce-point-of-sale' ),
				]
			);

			woocommerce_wp_select(
				[
					'id'          => 'uom_unit',
					'value'       => get_post_meta( $thepostid, 'uom_unit', true ),
					'label'       => __( 'Unit', 'woocommerce-point-of-sale' ),
					'description' => __( 'Select unit of measurement.', 'woocommerce-point-of-sale' ),
					'desc_tip'    => true,
					'options'     => [
						'kg'    => 'kg',
						'g'     => 'g',
						'lbs'   => 'lbs',
						'oz'    => 'oz',
						'km'    => 'km',
						'm'     => 'm',
						'cm'    => 'cm',
						'mm'    => 'mm',
						'in'    => 'in',
						'ft'    => 'ft',
						'yd'    => 'yd',
						'mi'    => 'mi (mile)',
						'ha'    => 'ha (hectare)',
						'sq-km' => 'sq km',
						'sq-m'  => 'sq m',
						'sq-cm' => 'sq cm',
						'sq-mm' => 'sq mm',
						'acs'   => 'acs (acre)',
						'sq-mi' => 'sq mi',
						'sq-yd' => 'sq yd',
						'sq-ft' => 'sq ft',
						'sq-in' => 'sq in',
						'cu-m'  => 'cu m',
						'l'     => 'l',
						'ml'    => 'ml',
						'gal'   => 'gal',
						'qt'    => 'qt',
						'pt'    => 'pt',
						'cup'   => 'ft',
						'yd'    => 'yd',
					],
				]
			);

			$starting_value = get_post_meta( $thepostid, 'uom_starting_value', true );
			$starting_value = empty( $starting_value ) ? '0.25' : $starting_value;
			woocommerce_wp_text_input(
				[
					'id'                => 'uom_starting_value',
					'label'             => __( 'Starting Value', 'woocommerce-point-of-sale' ),
					'description'       => __( 'Select the starting value used for pre defined suggestions.', 'woocommerce-point-of-sale' ),
					'desc_tip'          => true,
					'type'              => 'number',
					'value'             => $starting_value,
					'custom_attributes' => [
						'size' => '6',
						'step' => '0.01',
						'min'  => '0',
						'max'  => '10',
					],
				]
			);

			$increments = get_post_meta( $thepostid, 'uom_increments', true );
			$increments = empty( $increments ) ? $starting_value : $increments;
			woocommerce_wp_text_input(
				[
					'id'                => 'uom_increments',
					'label'             => __( 'Increments', 'woocommerce-point-of-sale' ),
					'description'       => __( 'Set the increment value.', 'woocommerce-point-of-sale' ),
					'desc_tip'          => true,
					'type'              => 'number',
					'value'             => $increments,
					'custom_attributes' => [
						'step' => '0.01',
					],
				]
			);

			$suggestions       = get_post_meta( $thepostid, 'uom_suggestions', true );
			$suggestions_value = get_post_meta( $thepostid, 'uom_suggestions_value', true );
			?>
			<p class="form-field uom_suggestions_field">
				<label for="uom_suggestions"><?php esc_html_e( 'Suggestions', 'woocommerce-point-of-sale' ); ?></label>
				<span class="wrap">
					<select id="uom_suggestions" name="uom_suggestions" class="select">
						<option value="increments" <?php selected( $suggestions, 'increments', true ); ?>><?php esc_html_e( 'Increments of', 'woocommerce-point-of-sale' ); ?></option>
						<option value="multipliers" <?php selected( $suggestions, 'multipliers', true ); ?>><?php esc_html_e( 'Multiplied by', 'woocommerce-point-of-sale' ); ?></option>
					</select>
					<select id="uom_suggestions_value" name="uom_suggestions_value" class="select last">
						<option value="1" <?php selected( $suggestions_value, '1', true ); ?>><?php esc_attr_e( '1', 'woocommerce-point-of-sale' ); ?></option>
						<option value="2" <?php selected( $suggestions_value, '2', true ); ?>><?php esc_attr_e( '2', 'woocommerce-point-of-sale' ); ?></option>
						<option value="3" <?php selected( $suggestions_value, '3', true ); ?>><?php esc_attr_e( '3', 'woocommerce-point-of-sale' ); ?></option>
						<option value="4" <?php selected( $suggestions_value, '4', true ); ?>><?php esc_attr_e( '4', 'woocommerce-point-of-sale' ); ?></option>
						<option value="5" <?php selected( $suggestions_value, '5', true ); ?>><?php esc_attr_e( '5', 'woocommerce-point-of-sale' ); ?></option>
						<option value="6" <?php selected( $suggestions_value, '6', true ); ?>><?php esc_attr_e( '6', 'woocommerce-point-of-sale' ); ?></option>
						<option value="7" <?php selected( $suggestions_value, '7', true ); ?>><?php esc_attr_e( '7', 'woocommerce-point-of-sale' ); ?></option>
						<option value="8" <?php selected( $suggestions_value, '8', true ); ?>><?php esc_attr_e( '8', 'woocommerce-point-of-sale' ); ?></option>
						<option value="9" <?php selected( $suggestions_value, '9', true ); ?>><?php esc_attr_e( '9', 'woocommerce-point-of-sale' ); ?></option>
						<option value="10" <?php selected( $suggestions_value, '10', true ); ?>><?php esc_attr_e( '10', 'woocommerce-point-of-sale' ); ?></option>
					</select>
				</span>
				<?php echo wc_help_tip( __( 'Define the way the next suggestions are calculated.', 'woocommerce-point-of-sale' ) ); ?>
			</p>
	</div>

	<?php
	/**
	 * Hook: woocommerce_product_options_point_of_sale_product_data
	 *
	 * @since 5.0.0
	 */
	do_action( 'woocommerce_product_options_point_of_sale_product_data' );
	?>
</div>
