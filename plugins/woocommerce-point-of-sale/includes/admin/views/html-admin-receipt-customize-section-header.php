<?php
/**
 * Receipt Customizer - Header Details
 *
 * @var object $receipt_object
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Views
 */
?>
<!-- Outlet Details Position -->
<li class="customize-control customize-control-select">
	<label class="customize-control-title" for="outlet_details_position"><?php esc_html_e( 'Outlet Details Position', 'woocommerce-point-of-sale' ); ?></label>
	<span class="description customize-control-description"><?php esc_html_e( 'Choose the position of the outlet contact details that is printed.', 'woocommerce-point-of-sale' ); ?></span>
	<select id="outlet_details_position" name="outlet_details_position">
		<option value="left" <?php selected( $receipt_object->get_outlet_details_position(), 'left', true ); ?> ><?php esc_html_e( 'Left', 'woocommerce-point-of-sale' ); ?></option>
		<option value="center" <?php selected( $receipt_object->get_outlet_details_position(), 'center', true ); ?>><?php esc_html_e( 'Center', 'woocommerce-point-of-sale' ); ?></option>
		<option value="right" <?php selected( $receipt_object->get_outlet_details_position(), 'right', true ); ?>><?php esc_html_e( 'Right', 'woocommerce-point-of-sale' ); ?></option>
	</select>
</li>

<!-- Show Shop Name -->
<li class="customize-control customize-control-checkbox">
	<span class="customize-inside-control-row">
		<input id="show_shop_name" name="show_shop_name" type="checkbox" value="yes" <?php checked( $receipt_object->get_show_shop_name(), true, true ); ?>>
		<label for="show_shop_name"><?php esc_html_e( 'Shop Name', 'woocommerce-point-of-sale' ); ?></label>
		<span class="description customize-control-description"><?php esc_html_e( 'Print the shop name.', 'woocommerce-point-of-sale' ); ?></span>
	</span>
</li>

<!-- Show Outlet Name -->
<li class="customize-control customize-control-checkbox">
	<span class="customize-inside-control-row">
		<input id="show_outlet_name" name="show_outlet_name" type="checkbox" value="yes" <?php checked( $receipt_object->get_show_outlet_name(), true, true ); ?>>
		<label for="show_outlet_name"><?php esc_html_e( 'Outlet Name', 'woocommerce-point-of-sale' ); ?></label>
		<span class="description customize-control-description"><?php esc_html_e( 'Print the name of the outlet which the order was placed in.', 'woocommerce-point-of-sale' ); ?></span>
	</span>
</li>

<!-- Show Outlet Address -->
<li class="customize-control customize-control-checkbox">
	<span class="customize-inside-control-row">
		<input id="show_outlet_address" name="show_outlet_address" type="checkbox" value="yes" <?php checked( $receipt_object->get_show_outlet_address(), true, true ); ?>>
		<label for="show_outlet_address"><?php esc_html_e( 'Outlet Address', 'woocommerce-point-of-sale' ); ?></label>
		<span class="description customize-control-description"><?php esc_html_e( 'Print the address of the outlet which the order was placed in.', 'woocommerce-point-of-sale' ); ?></span>
	</span>
</li>

<!-- Show Outlet Contact Details -->
<li class="customize-control customize-control-checkbox">
	<span class="customize-inside-control-row">
		<input id="show_outlet_contact_details" name="show_outlet_contact_details" type="checkbox" value="yes" <?php checked( $receipt_object->get_show_outlet_contact_details(), true, true ); ?>>
		<label for="show_outlet_contact_details"><?php esc_html_e( 'Outlet Contact Details', 'woocommerce-point-of-sale' ); ?></label>
		<span class="description customize-control-description"><?php esc_html_e( 'Print the contact details of the outlet which the order was placed in.', 'woocommerce-point-of-sale' ); ?></span>
	</span>
</li>

<!-- Social Details Position -->
<li class="customize-control customize-control-select">
	<label class="customize-control-title" for="social_details_position"><?php esc_html_e( 'Social Details Position', 'woocommerce-point-of-sale' ); ?></label>
	<span class="description customize-control-description"><?php esc_html_e( 'Choose the position of the social details that is printed.', 'woocommerce-point-of-sale' ); ?></span>
	<select id="social_details_position" name="social_details_position">
		<option value="header" <?php selected( $receipt_object->get_social_details_position(), 'header', true ); ?> ><?php esc_html_e( 'Header', 'woocommerce-point-of-sale' ); ?></option>
		<option value="footer" <?php selected( $receipt_object->get_social_details_position(), 'footer', true ); ?>><?php esc_html_e( 'Footer', 'woocommerce-point-of-sale' ); ?></option>
	</select>
</li>

<!-- Show Twitter -->
<li class="customize-control customize-control-checkbox">
	<span class="customize-inside-control-row">
		<input id="show_social_twitter" name="show_social_twitter" type="checkbox" value="yes" <?php checked( $receipt_object->get_show_social_twitter(), true, true ); ?>>
		<label for="show_social_twitter"><?php esc_html_e( 'Twitter', 'woocommerce-point-of-sale' ); ?></label>
	</span>
</li>

<!-- Show Facebook -->
<li class="customize-control customize-control-checkbox">
	<span class="customize-inside-control-row">
		<input id="show_social_facebook" name="show_social_facebook" type="checkbox" value="yes" <?php checked( $receipt_object->get_show_social_facebook(), true, true ); ?>>
		<label for="show_social_facebook"><?php esc_html_e( 'Facebook', 'woocommerce-point-of-sale' ); ?></label>
	</span>
</li>

<!-- Show Instagram -->
<li class="customize-control customize-control-checkbox">
	<span class="customize-inside-control-row">
		<input id="show_social_instagram" name="show_social_instagram" type="checkbox" value="yes" <?php checked( $receipt_object->get_show_social_instagram(), true, true ); ?>>
		<label for="show_social_instagram"><?php esc_html_e( 'Instagram', 'woocommerce-point-of-sale' ); ?></label>
	</span>
</li>

<!-- Show Snapchat -->
<li class="customize-control customize-control-checkbox">
	<span class="customize-inside-control-row">
		<input id="show_social_snapchat" name="show_social_snapchat" type="checkbox" value="yes" <?php checked( $receipt_object->get_show_social_snapchat(), true, true ); ?>>
		<label for="show_social_snapchat"><?php esc_html_e( 'Snapchat', 'woocommerce-point-of-sale' ); ?></label>
	</span>
</li>

<!-- Show Wi-Fi Details -->
<li class="customize-control customize-control-checkbox">
	<span class="customize-inside-control-row">
		<input id="show_wifi_details" name="show_wifi_details" type="checkbox" value="yes" <?php checked( $receipt_object->get_show_wifi_details(), true, true ); ?>>
		<label for="show_wifi_details"><?php esc_html_e( 'Wi-Fi Details', 'woocommerce-point-of-sale' ); ?></label>
		<span class="description customize-control-description"><?php esc_html_e( 'Print the SSID and passphrase of the outlet that the order was placed in. This is set in the outlet settings.', 'woocommerce-point-of-sale' ); ?></span>
	</span>
</li>

<!-- Show Tax Number -->
<li class="customize-control customize-control-checkbox">
	<span class="customize-inside-control-row">
		<input id="show_tax_number" name="show_tax_number" type="checkbox" value="yes" <?php checked( $receipt_object->get_show_tax_number(), true, true ); ?>>
		<label for="show_tax_number"><?php esc_html_e( 'Tax Number', 'woocommerce-point-of-sale' ); ?></label>
		<span class="description customize-control-description"><?php esc_html_e( 'Print the tax number of the shop. This is set under Point of Sale > Settings > General.', 'woocommerce-point-of-sale' ); ?></span>
		<input type="text" id="tax_number_label" name="tax_number_label" value="<?php echo esc_attr( $receipt_object->get_tax_number_label() ); ?>" />
	</span>
</li>

<!-- Tax Number Position -->
<li class="customize-control customize-control-select">
	<label class="customize-control-title" for="outlet_details_position"><?php esc_html_e( 'Tax Number Position', 'woocommerce-point-of-sale' ); ?></label>
	<span class="description customize-control-description"><?php esc_html_e( 'Choose the position of the tax number that is printed.', 'woocommerce-point-of-sale' ); ?></span>
	<select id="tax_number_position" name="tax_number_position">
		<option value="left" <?php selected( $receipt_object->get_tax_number_position(), 'left', true ); ?> ><?php esc_html_e( 'Left', 'woocommerce-point-of-sale' ); ?></option>
		<option value="center" <?php selected( $receipt_object->get_tax_number_position(), 'center', true ); ?>><?php esc_html_e( 'Center', 'woocommerce-point-of-sale' ); ?></option>
		<option value="right" <?php selected( $receipt_object->get_tax_number_position(), 'right', true ); ?>><?php esc_html_e( 'Right', 'woocommerce-point-of-sale' ); ?></option>
	</select>
</li>
