<?php
/**
 * Receipt Customizer - Custom CSS
 *
 * @var object $receipt_object
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Views
 */
?>
<!-- Custom CSS -->
<li class="customize-control customize-control-textarea">
	<textarea name="custom_css" id="custom_css"><?php echo esc_html( $receipt_object->get_custom_css( 'edit' ) ); ?></textarea>
</li>
