<?php
/**
 * Admin view: Notice - Install.
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Views
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="message" class="updated woocommerce-message">
	<a class="woocommerce-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-pos-hide-notice', 'install' ), 'wc_pos_hide_notices_nonce', '_wc_pos_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'woocommerce-point-of-sale' ); ?></a>

	<p><?php echo wp_kses_post( __( '<strong>Welcome to WooCommerce Point of Sale</strong> &#8211; You&lsquo;re almost ready to start selling :)', 'woocommerce-point-of-sale' ) ); ?></p>
	<p class="submit">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=point-of-sale' ) ); ?>" class="button-primary"><?php esc_html_e( 'Settings', 'woocommerce-point-of-sale' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=pos_register' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Registers', 'woocommerce-point-of-sale' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=pos_outlet' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Outlets', 'woocommerce-point-of-sale' ); ?></a>
	</p>
</div>
