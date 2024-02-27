<?php
/**
 * Receipt Customizer - Logo Details
 *
 * @var object $receipt_object
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Views
 */

$logo     = wp_get_attachment_image_src( (int) $receipt_object->get_logo( 'edit' ), 'full' );
$logo_src = $logo ? $logo[0] : '';
?>
<!-- Shop Logo -->
<li id="customize-control-logo" class="customize-control<?php echo $logo ? ' selected' : ''; ?>">
	<span class="customize-control-title"><?php esc_html_e( 'Shop Logo', 'woocommerce-point-of-sale' ); ?></span>
	<span class="description customize-control-description"><?php esc_html_e( 'Upload a logo representing your shop or business.', 'woocommerce-point-of-sale' ); ?></span>
	<div class="attachment-media-view">
		<button type="button" class="upload button-add-media"><?php esc_html_e( 'Select image', 'woocommerce-point-of-sale' ); ?></button>
		<div class="thumbnail thumbnail-image">
			<img class="attachment-thumb" src="<?php echo esc_url( $logo_src ); ?>" />
			<input type="hidden" name="logo" id="logo" value="<?php echo esc_attr( $receipt_object->get_logo( 'edit' ) ); ?>" />
		</div>
		<div class="actions"><button type="button" class="button remove"><?php esc_html_e( 'Remove', 'woocommerce-point-of-sale' ); ?></button></div>
	</div>
</li>

<!-- Logo Position -->
<li class="customize-control customize-control-select">
	<label class="customize-control-title" for="logo_position"><?php esc_html_e( 'Logo Position', 'woocommerce-point-of-sale' ); ?></label>
	<span class="description customize-control-description"><?php esc_html_e( 'Choose the position of the shop logo that is printed.', 'woocommerce-point-of-sale' ); ?></span>
	<select id="logo_position" name="logo_position">
		<option value="left" <?php selected( $receipt_object->get_logo_position(), 'left', true ); ?> ><?php esc_html_e( 'Left', 'woocommerce-point-of-sale' ); ?></option>
		<option value="center" <?php selected( $receipt_object->get_logo_position(), 'center', true ); ?>><?php esc_html_e( 'Center', 'woocommerce-point-of-sale' ); ?></option>
		<option value="right" <?php selected( $receipt_object->get_logo_position(), 'right', true ); ?>><?php esc_html_e( 'Right', 'woocommerce-point-of-sale' ); ?></option>
	</select>
</li>

<!-- Logo Size -->
<li class="customize-control customize-control-select">
	<label class="customize-control-title" for="logo_size"><?php esc_html_e( 'Logo Size', 'woocommerce-point-of-sale' ); ?></label>
	<span class="description customize-control-description"><?php esc_html_e( 'Choose the size of the shop logo that is printed.', 'woocommerce-point-of-sale' ); ?></span>
	<select id="logo_size" name="logo_size">
		<option value="small" <?php selected( $receipt_object->get_logo_size(), 'small', true ); ?>><?php esc_html_e( 'Small', 'woocommerce-point-of-sale' ); ?></option>
		<option value="normal" <?php selected( $receipt_object->get_logo_size(), 'normal', true ); ?> ><?php esc_html_e( 'Normal', 'woocommerce-point-of-sale' ); ?></option>
		<option value="large" <?php selected( $receipt_object->get_logo_size(), 'large', true ); ?>><?php esc_html_e( 'Large', 'woocommerce-point-of-sale' ); ?></option>
	</select>
</li>
