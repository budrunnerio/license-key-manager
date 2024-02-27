<?php
/**
 * Receipt Customizer - Product Details
 *
 * @var object $receipt_object
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Views
 */
?>
<!-- Product Details Layout -->
<li class="customize-control customize-control-select">
	<label class="customize-control-title" for="product_details_layout"><?php esc_html_e( 'Product Details Layout', 'woocommerce-point-of-sale' ); ?></label>
	<span class="description customize-control-description"><?php esc_html_e( 'Select how to display the product item details.', 'woocommerce-point-of-sale' ); ?></span>
	<select id="product_details_layout" name="product_details_layout">
		<option value="single" <?php selected( $receipt_object->get_product_details_layout(), 'single', true ); ?> ><?php esc_html_e( 'Single Line', 'woocommerce-point-of-sale' ); ?></option>
		<option value="multiple" <?php selected( $receipt_object->get_product_details_layout(), 'multiple', true ); ?>><?php esc_html_e( 'Multiple Lines', 'woocommerce-point-of-sale' ); ?></option>
	</select>
</li>

<!-- Show Product Image -->
<li class="customize-control customize-control-checkbox">
	<span class="customize-inside-control-row">
		<input id="show_product_image" name="show_product_image" type="checkbox" value="yes" <?php checked( $receipt_object->get_show_product_image(), true, true ); ?>>
		<label for="show_product_image"><?php esc_html_e( 'Product Image', 'woocommerce-point-of-sale' ); ?></label>
		<span class="description customize-control-description"><?php esc_html_e( 'Print the product image.', 'woocommerce-point-of-sale' ); ?></span>
	</span>
</li>

<!-- Show Product SKU -->
<li class="customize-control customize-control-checkbox">
	<span class="customize-inside-control-row">
		<input id="show_product_sku" name="show_product_sku" type="checkbox" value="yes" <?php checked( $receipt_object->get_show_product_sku(), true, true ); ?>>
		<label for="show_product_sku"><?php esc_html_e( 'Product SKU', 'woocommerce-point-of-sale' ); ?></label>
		<span class="description customize-control-description"><?php esc_html_e( 'Print the product SKU.', 'woocommerce-point-of-sale' ); ?></span>
	</span>
</li>

<!-- Show Product Cost -->
<li class="customize-control customize-control-checkbox">
	<span class="customize-inside-control-row">
		<input id="show_product_cost" name="show_product_cost" type="checkbox" value="yes" <?php checked( $receipt_object->get_show_product_cost(), true, true ); ?>>
		<label for="show_product_cost"><?php esc_html_e( 'Product Price', 'woocommerce-point-of-sale' ); ?></label>
		<span class="description customize-control-description"><?php esc_html_e( 'Print the product cost.', 'woocommerce-point-of-sale' ); ?></span>
	</span>
</li>

<!-- Show Product Discount -->
<li class="customize-control customize-control-checkbox">
	<span class="customize-inside-control-row">
		<input id="show_product_discount" name="show_product_discount" type="checkbox" value="yes" <?php checked( $receipt_object->get_show_product_discount(), true, true ); ?>>
		<label for="show_product_discount"><?php esc_html_e( 'Product Discount', 'woocommerce-point-of-sale' ); ?></label>
		<span class="description customize-control-description"><?php esc_html_e( 'Print the discount.', 'woocommerce-point-of-sale' ); ?></span>
	</span>
</li>

<!-- Show Originl Price -->
<li class="customize-control customize-control-checkbox">
	<span class="customize-inside-control-row">
		<input id="show_original_price" name="show_original_price" type="checkbox" value="yes" <?php checked( $receipt_object->get_show_original_price(), true, true ); ?>>
		<label for="show_original_price"><?php esc_html_e( 'Original Price', 'woocommerce-point-of-sale' ); ?></label>
		<span class="description customize-control-description"><?php esc_html_e( 'Print the original item price before discount.', 'woocommerce-point-of-sale' ); ?></span>
	</span>
</li>

<!-- Show Number of Items -->
<li class="customize-control customize-control-checkbox">
	<span class="customize-inside-control-row">
		<input id="show_num_items" name="show_num_items" type="checkbox" value="yes" <?php checked( $receipt_object->get_show_num_items(), true, true ); ?>>
		<label for="show_num_items"><?php esc_html_e( 'Number of Items', 'woocommerce-point-of-sale' ); ?></label>
		<span class="description customize-control-description"><?php esc_html_e( 'Print the total number of items.', 'woocommerce-point-of-sale' ); ?></span>
	</span>
</li>

<!-- Show Tax Summary -->
<li class="customize-control customize-control-checkbox">
	<span class="customize-inside-control-row">
		<input id="show_tax_summary" name="show_tax_summary" type="checkbox" value="yes" <?php checked( $receipt_object->get_show_tax_summary(), true, true ); ?>>
		<label for="show_tax_summary"><?php esc_html_e( 'Tax Summary', 'woocommerce-point-of-sale' ); ?></label>
		<span class="description customize-control-description"><?php esc_html_e( 'Print tax summary.', 'woocommerce-point-of-sale' ); ?></span>
	</span>
</li>

<!-- Order Barcode -->
<li class="customize-control customize-control-checkbox">
	<span class="customize-inside-control-row">
		<input id="show_order_barcode" name="show_order_barcode" type="checkbox" value="yes" <?php checked( $receipt_object->get_show_order_barcode(), true, true ); ?>>
		<label for="show_order_barcode"><?php esc_html_e( 'Order Barcode', 'woocommerce-point-of-sale' ); ?></label>
		<span class="description customize-control-description"><?php esc_html_e( 'Print the order barcode.', 'woocommerce-point-of-sale' ); ?></span>
	</span>
</li>

<!-- Barcode Type -->
<li class="customize-control customize-control-select">
	<label class="customize-control-title" for="barcode_type"><?php esc_html_e( 'Barcode type', 'woocommerce-point-of-sale' ); ?></label>
	<span class="description customize-control-description"><?php esc_html_e( 'Select the barcode type.', 'woocommerce-point-of-sale' ); ?></span>
	<select id="barcode_type" name="barcode_type">
		<option value="code128" <?php selected( $receipt_object->get_barcode_type(), 'code128', true ); ?> ><?php esc_html_e( 'Code 128', 'woocommerce-point-of-sale' ); ?></option>
		<option value="qrcode" <?php selected( $receipt_object->get_barcode_type(), 'qrcode', true ); ?>><?php esc_html_e( 'Quick Response (QR)', 'woocommerce-point-of-sale' ); ?></option>
	</select>
</li>
