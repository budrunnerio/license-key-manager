<?php
/**
 * Receipt Customizer - Preview
 *
 * @var object $receipt_object
 * @var string $logo_src
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Views
 */

$get_current_user = wp_get_current_user();
$default_register = wc_pos_get_register( absint( get_option( 'wc_pos_default_register' ) ) );
$default_outlet   = wc_pos_get_outlet( absint( get_option( 'wc_pos_default_outlet' ) ) );
$address          = WC()->countries->get_formatted_address(
	[
		'address_1' => $default_outlet->get_address_1(),
		'address_2' => $default_outlet->get_address_2(),
		'city'      => $default_outlet->get_city(),
		'postcode'  => $default_outlet->get_postcode(),
		'state'     => empty( $default_outlet->get_state() ) ? $default_outlet->get_state() : '',
		'country'   => $default_outlet->get_country(),
	]
);
$social_accounts  = $default_outlet->get_social_accounts();
?>
<div id="receipt-preview">
	<div class="title"><?php esc_html_e( 'Receipt', 'woocommerce-point-of-sale' ); ?></div>
	<div class="logo"><img src="<?php echo esc_url( $logo_src ); ?>" /></div>

	<div class="outlet-details">
		<div class="shop-name"><?php echo esc_html( bloginfo( 'name' ) ); ?></div>
		<div class="outlet-name"><?php esc_html_e( 'Outlet Name', 'woocommerce-point-of-sale' ); ?></div>
		<div class="outlet-address">
			<?php echo wp_kses_post( $address ); ?>
		</div>
		<div class="outlet-contact-details">
			<div><?php esc_html_e( 'Phone:', 'woocommerce-point-of-sale' ); ?> <?php echo ! empty( $default_outlet->get_phone() ) ? esc_html( $default_outlet->get_phone() ) : esc_html_x( '0161 154 6783', 'Receipt preview phone example', 'woocommerce-point-of-sale' ); ?></div>
			<div><?php esc_html_e( 'Fax:', 'woocommerce-point-of-sale' ); ?> <?php echo ! empty( $default_outlet->get_fax() ) ? esc_html( $default_outlet->get_fax() ) : esc_html_x( '0161 154 6784', 'Receipt preview fax example', 'woocommerce-point-of-sale' ); ?></div>
			<div><?php esc_html_e( 'Email:', 'woocommerce-point-of-sale' ); ?> <?php echo ! empty( $default_outlet->get_email() ) ? esc_html( $default_outlet->get_email() ) : esc_html( bloginfo( 'admin_email' ) ); ?></div>
			<div><?php esc_html_e( 'Website:', 'woocommerce-point-of-sale' ); ?> <?php echo ! empty( $default_outlet->get_website() ) ? esc_html( $default_outlet->get_website() ) : esc_url( bloginfo( 'url' ) ); ?></div>
		</div>
		<div class="wifi-details">
			<span><?php esc_html_e( 'Wi-Fi Network:', 'woocommerce-point-of-sale' ); ?> <?php esc_html( $default_outlet->get_wifi_network() ); ?></span><br />
			<span><?php esc_html_e( 'Wi-Fi Password:', 'woocommerce-point-of-sale' ); ?> <?php esc_html( $default_outlet->get_wifi_password() ); ?></span>
		</div>
		<div class="social-details">
			<div class="social-twitter"><?php esc_html_e( 'Twitter:', 'woocommerce-point-of-sale' ); ?> <?php echo isset( $social_accounts['twitter'] ) ? esc_html( $social_accounts['twitter'] ) : esc_html_x( 'OutletTW', 'Receipt Twitter account example', 'woocommerce-point-of-sale' ); ?></div>
			<div class="social-facebook"><?php esc_html_e( 'Facebook:', 'woocommerce-point-of-sale' ); ?> <?php echo isset( $social_accounts['facebook'] ) ? esc_html( $social_accounts['facebook'] ) : esc_html_x( 'OutletFB', 'Receipt Facebook account example', 'woocommerce-point-of-sale' ); ?></div>
			<div class="social-instagram"><?php esc_html_e( 'Instagram:', 'woocommerce-point-of-sale' ); ?> <?php echo isset( $social_accounts['instagram'] ) ? esc_html( $social_accounts['instagram'] ) : esc_html_x( 'OutletIN', 'Receipt Instagram account example', 'woocommerce-point-of-sale' ); ?></div>
			<div class="social-snapchat"><?php esc_html_e( 'Snapchat:', 'woocommerce-point-of-sale' ); ?> <?php echo isset( $social_accounts['snapchat'] ) ? esc_html( $social_accounts['snapchat'] ) : esc_html_x( 'OutletSC', 'Receipt Snapchat account example', 'woocommerce-point-of-sale' ); ?></div>
		</div>
		<div class="tax-number">
			<span class="tax-number-label"></span> <?php echo esc_html( get_option( 'wc_pos_tax_number', '' ) ); ?>
		</div>
	</div>

	<div class="header-text">
		<?php echo esc_html( $receipt_object->get_header_text( 'edit' ) ); ?>
	</div>

	<table class="order-details">
		<tbody>
			<tr class="order-number">
				<th><?php esc_html_e( 'Order Number', 'woocommerce-point-of-sale' ); ?></th>
				<td><?php echo esc_html_x( 'WC1234AE', 'Receipt preview order number example', 'woocommerce-point-of-sale' ); ?></td>
			</tr>
			<tr class="order-status">
				<th><?php esc_html_e( 'Order Status', 'woocommerce-point-of-sale' ); ?></th>
				<td><?php echo esc_html_x( 'Completed', 'Receipt preview order status example', 'woocommerce-point-of-sale' ); ?></td>
			</tr>
			<tr class="order-date">
				<th><?php esc_html_e( 'Date', 'woocommerce-point-of-sale' ); ?></th>
				<td>
					<span class="date"><?php echo esc_html( date_i18n( $receipt_object->get_order_date_format( 'edit' ), time() ) ); ?></span>
					<span class="at"> <?php echo esc_html_x( 'at', 'At time', 'woocommerce-point-of-sale' ); ?> </span>
					<span class="time"><?php echo esc_html( date_i18n( $receipt_object->get_order_time_format( 'edit' ), time() ) ); ?></span>
				</td>
			</tr>
			<tr class="customer-name">
				<th><?php esc_html_e( 'Customer', 'woocommerce-point-of-sale' ); ?></th>
				<td><?php echo esc_html_x( 'John Doe', 'Receipt preview customer example', 'woocommerce-point-of-sale' ); ?></td>
			</tr>
			<tr class="customer-email">
				<th><?php esc_html_e( 'Email', 'woocommerce-point-of-sale' ); ?></th>
				<td><?php echo esc_html_x( 'mail@example.com', 'Receipt preview email example', 'woocommerce-point-of-sale' ); ?></td>
			</tr>
			<tr class="customer-phone">
				<th><?php esc_html_e( 'Phone', 'woocommerce-point-of-sale' ); ?></th>
				<td><?php echo ! empty( $default_outlet->get_phone() ) ? esc_html( $default_outlet->get_phone() ) : esc_html_x( '0161 154 6783', 'Receipt preview phone example', 'woocommerce-point-of-sale' ); ?></td>
			</tr>
			<tr class="customer-shipping-address">
				<th><?php esc_html_e( 'Shipping', 'woocommerce-point-of-sale' ); ?></th>
				<td><?php echo wp_kses_post( $address ); ?></td>
			</tr>
			<tr class="customer-billing-address">
				<th><?php esc_html_e( 'Billing', 'woocommerce-point-of-sale' ); ?></th>
				<td><?php echo wp_kses_post( $address ); ?></td>
			</tr>
			<tr class="cashier-name" data-user_nicename="<?php echo esc_attr( $get_current_user->user_nicename ); ?>" data-display_name="<?php echo esc_attr( $get_current_user->display_name ); ?>" data-user_login="<?php echo esc_attr( $get_current_user->user_login ); ?>">
				<th><?php esc_html_e( 'Served by', 'woocommerce-point-of-sale' ); ?></th>
				<td>
					<span class="cashier"><?php echo esc_html( $get_current_user->{ $receipt_object->get_cashier_name_format( 'edit' ) } ); ?></span>
					<span class="register-name"> <?php echo esc_html_x( 'on Default Register', 'Receipt preview register name example', 'woocommerce-point-of-sale' ); ?> </span>
				</td>
			</tr>
			<tr class="order-notes">
				<th><?php echo esc_html_e( 'Order Notes', 'woocommerce-point-of-sale' ); ?></th>
				<td><?php echo esc_html_e( 'Please deliver this tomorrow at 12pm.', 'woocommerce-point-of-sale' ); ?></td>
			</tr>
			<tr class="dining-option">
				<th><?php echo esc_html_e( 'Dining Option', 'woocommerce-point-of-sale' ); ?></th>
				<td><?php echo esc_html_e( 'Take Away', 'woocommerce-point-of-sale' ); ?></td>
			</tr>
		</tbody>
	</table>

	<table class="product-details">
		<thead class="product-details-layout-single">
			<tr>
				<th class="qty"><?php esc_html_e( 'Qty', 'woocommerce-point-of-sale' ); ?></th>
				<th class="product"><?php esc_html_e( 'Product', 'woocommerce-point-of-sale' ); ?></th>
				<th class="image">&nbsp;</th>
				<th class="cost"><?php esc_html_e( 'Price', 'woocommerce-point-of-sale' ); ?></th>
				<th class="total"><?php esc_html_e( 'Total', 'woocommerce-point-of-sale' ); ?></th>
			</tr>
		</thead>
		<thead class="product-details-layout-multiple">
			<tr>
				<th class="item" colspan="3"><?php esc_html_e( 'Item', 'woocommerce-point-of-sale' ); ?></th>
				<th class="total"><?php esc_html_e( 'Total', 'woocommerce-point-of-sale' ); ?></th>
			</tr>
		</thead>
		<tbody class="product-details-layout-single">
			<tr>
				<td class="qty"><?php echo esc_html_x( '2', 'Receipt preview quantity example', 'woocommerce-point-of-sale' ); ?></td>
				<td class="product">
					<strong><?php esc_attr_e( 'Mobile Phone', 'woocommerce-point-of-sale' ); ?></strong>
					<small class="receipt-product-sku" class="receipt-product-sku"><?php echo esc_html_x( 'SKU: PRDCT123', 'Receipt preview SKU example', 'woocommerce-point-of-sale' ); ?></small>
					<small class="receipt-product-image"><?php echo esc_html_x( 'Size: 32GB', 'Receipt preview product size example', 'woocommerce-point-of-sale' ); ?></small>
					<small class="receipt-product-image"><?php echo esc_html_x( 'Color: Silver', 'Receipt preview product color example', 'woocommerce-point-of-sale' ); ?></small>
				</td>
				<td class="image receipt-product-image"><?php echo wp_kses_post( wc_placeholder_img( 'thumbnail' ) ); ?></td>
				<td class="cost receipt-product-cost">
					<div class="line-through-if-discount-enabled"><?php echo esc_html_x( '£59.00', 'Receipt preview product cost example', 'woocommerce-point-of-sale' ); ?></div>
					<small class="receipt-original-price" style="display:inline-block;">(<?php esc_html_e( 'Originally', 'woocommerce-point-of-sale' ); ?> <?php echo esc_html_x( '£79.00', 'Receipt preview original price example', 'woocommerce-point-of-sale' ); ?>)</small>
					<div class="show-if-discount-enabled"><?php echo esc_html_x( '£55.00', 'Receipt preview product discounted cost example', 'woocommerce-point-of-sale' ); ?></div>
				</td>
				<td class="total">
					<div class="line-through-if-discount-enabled"><?php echo esc_html_x( '£118.00', 'Receipt preview product total example', 'woocommerce-point-of-sale' ); ?></div>
					<div class="show-if-discount-enabled"><?php echo esc_html_x( '£110.00', 'Receipt preview product discounted total example', 'woocommerce-point-of-sale' ); ?></div>
				</td>
			</tr>
		</tbody>
		<tbody class="product-details-layout-multiple">
			<tr>
				<td class="product" colspan="3">
					<strong><?php esc_html_e( 'Mobile Phone', 'woocommerce-point-of-sale' ); ?></strong>
					<span class="receipt-product-sku"> – <?php echo esc_html_x( 'SKU: PRDCT123', 'Receipt preview SKU example', 'woocommerce-point-of-sale' ); ?></span><br>
					<span class="size"><?php echo esc_html_x( 'Size: 32GB', 'Receipt preview product size example', 'woocommerce-point-of-sale' ); ?></span><br>
					<span class="color"><?php echo esc_html_x( 'Color: Silver', 'Receipt preview product color example', 'woocommerce-point-of-sale' ); ?></span><br>
					<span><?php esc_html_e( 'Qty:', 'woocommerce-point-of-sale' ); ?> <?php echo esc_html_x( '2', 'Receipt preview quantity example', 'woocommerce-point-of-sale' ); ?></span>
					<span class="cost receipt-product-cost"></span>
					<span> &times; </span>
					<span class="line-through-if-discount-enabled"><?php echo esc_html_x( '£59.00', 'Receipt preview product cost example', 'woocommerce-point-of-sale' ); ?></span>
					<small class="receipt-original-price" style="display:inline-block;">(<?php esc_html_e( 'Originally', 'woocommerce-point-of-sale' ); ?> <?php echo esc_html_x( '£79.00', 'Receipt preview original price example', 'woocommerce-point-of-sale' ); ?>)</small>
					<span class="show-if-discount-enabled"><?php echo esc_html_x( '£55.00', 'Receipt preview product discounted cost example', 'woocommerce-point-of-sale' ); ?></span>
					</span>
				</td>
				<td class="total">
					<div class="line-through-if-discount-enabled"><?php echo esc_html_x( '£118.00', 'Receipt preview product total example', 'woocommerce-point-of-sale' ); ?></div>
					<div class="show-if-discount-enabled"><?php echo esc_html_x( '£110.00', 'Receipt preview product discounted total example', 'woocommerce-point-of-sale' ); ?></div>
				</td>
			</tr>
		</tbody>
		<tfoot>
			<tr>
				<th scope="row" colspan="3"><?php echo esc_html_e( 'Subtotal', 'woocommerce-point-of-sale' ); ?></th>
				<td>
					<div class="line-through-if-discount-enabled"><?php echo esc_html_x( '£118.00', 'Receipt preview subtotal example', 'woocommerce-point-of-sale' ); ?></div>
					<div class="show-if-discount-enabled"><?php echo esc_html_x( '£110.00', 'Receipt preview discounted subtotal example', 'woocommerce-point-of-sale' ); ?></div>
				</td>
			</tr>
			<tr class="tax">
				<th scope="row" colspan="3"><?php echo esc_html_e( 'Tax', 'woocommerce-point-of-sale' ); ?></th>
				<td>
					<div class="line-through-if-discount-enabled"><?php echo esc_html_x( '£23.60', 'Receipt preview tax example', 'woocommerce-point-of-sale' ); ?></div>
					<div class="show-if-discount-enabled"><?php echo esc_html_x( '£22.00', 'Receipt preview discounted tax example', 'woocommerce-point-of-sale' ); ?></div>
				</td>
			</tr>
			<tr>
				<th scope="row" colspan="3"><?php echo esc_html_e( 'Payment Type Sales', 'woocommerce-point-of-sale' ); ?></th>
				<td>
					<div class="line-through-if-discount-enabled"><?php echo esc_html_x( '£141.60', 'Receipt preview sales example', 'woocommerce-point-of-sale' ); ?></div>
					<div class="show-if-discount-enabled"><?php echo esc_html_x( '£132.00', 'Receipt preview discounted sales example', 'woocommerce-point-of-sale' ); ?></div>
				</td>
			</tr>
			<tr>
				<th scope="row" colspan="3"><?php echo esc_html_e( 'Total', 'woocommerce-point-of-sale' ); ?></th>
				<td><?php echo esc_html_x( '£141.60', 'Receipt preview total example', 'woocommerce-point-of-sale' ); ?></td>
			</tr>
			<tr>
				<th scope="row" colspan="3"><?php echo esc_html_e( 'Change', 'woocommerce-point-of-sale' ); ?></th>
				<td><?php echo esc_html_x( '£0.00', 'Receipt preview change example', 'woocommerce-point-of-sale' ); ?></td>
			</tr>
			<tr class="no-items">
				<th scope="row" colspan="3"><?php echo esc_html_e( 'Number of Items', 'woocommerce-point-of-sale' ); ?></th>
				<td><?php echo esc_html_x( '2', 'Receipt preview number of items example', 'woocommerce-point-of-sale' ); ?></td>
			</tr>
		<tfoot>
	</table>

	<table class="tax-summary">
		<thead>
			<tr>
				<th class="tax-name"><?php echo esc_html_e( 'Tax Name', 'woocommerce-point-of-sale' ); ?></th>
				<th class="tax-rate"><?php echo esc_html_e( 'Tax Rate', 'woocommerce-point-of-sale' ); ?></th>
				<th class="tax"><?php echo esc_html_e( 'Tax', 'woocommerce-point-of-sale' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>&nbsp;</td>
				<td><?php echo esc_html_x( 'GB-VAT-20', 'Receipt preview tax name example', 'woocommerce-point-of-sale' ); ?></td>
				<td><?php echo esc_html_x( '20.00', 'Receipt preview tax rate example', 'woocommerce-point-of-sale' ); ?></td>
				<td><?php echo esc_html_x( '£23.60', 'Receipt preview tax example', 'woocommerce-point-of-sale' ); ?></td>
			</tr>
		</tbody>
	</table>

	<div class="outlet-details outlet-details-footer"></div>

	<div class="order-barcode">
		<img />
	</div>

	<div class="footer-text">
		<?php echo esc_html( $receipt_object->get_footer_text( 'edit' ) ); ?>
	</div>
</div>
<style type="text/less" class="custom-css"></style>
<style type="text/css">
	.order-status {
		display: table-row;
		line-height: inherit;
		color: inherit;
		background: inherit;
		border-radius: inherit;
		border-bottom: inherit;
		margin: inherit;
		cursor: inherit;
		white-space: inherit;
		max-width: inherit;
	}
</style>
<script>
	(function($) {
		$(function() {
			var barcodeCanvas = document.createElement('canvas');
			bwipjs.toCanvas(barcodeCanvas, {
				bcid: '<?php echo esc_html( ! empty( $receipt_object->get_barcode_type( 'edit' ) ) ? $receipt_object->get_barcode_type( 'edit' ) : 'code128' ); ?>',
				text: 'WC1234AE',
				scale: 2,
				includetext: true,
				textxalign:  'center',
			});
			jQuery( '.order-barcode img' ).attr( 'src', barcodeCanvas.toDataURL('image/png') );
		});
	})(jQuery);
</script>
