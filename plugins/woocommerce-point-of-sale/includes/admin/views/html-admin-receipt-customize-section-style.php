<?php
/**
 * Receipt Customizer - Style Details
 *
 * @var object $receipt_object
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Views
 */
?>
<!-- Text Size -->
<li class="customize-control customize-control-select">
	<label class="customize-control-title" for="text_size"><?php esc_html_e( 'Text Size', 'woocommerce-point-of-sale' ); ?></label>
	<span class="description customize-control-description"><?php esc_html_e( 'Select the size of the text that is printed.', 'woocommerce-point-of-sale' ); ?></span>
	<select id="text_size" name="text_size">
		<option value="tiny" <?php selected( $receipt_object->get_text_size(), 'tiny', true ); ?>><?php esc_html_e( 'Tiny', 'woocommerce-point-of-sale' ); ?></option>
		<option value="small" <?php selected( $receipt_object->get_text_size(), 'small', true ); ?> ><?php esc_html_e( 'Small', 'woocommerce-point-of-sale' ); ?></option>
		<option value="normal" <?php selected( $receipt_object->get_text_size(), 'normal', true ); ?>><?php esc_html_e( 'Normal', 'woocommerce-point-of-sale' ); ?></option>
		<option value="large" <?php selected( $receipt_object->get_text_size(), 'large', true ); ?>><?php esc_html_e( 'Large', 'woocommerce-point-of-sale' ); ?></option>
	</select>
</li>
