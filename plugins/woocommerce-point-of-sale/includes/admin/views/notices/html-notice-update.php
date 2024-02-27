<?php
/**
 * Admin view: Notice - Update.
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Views
 */

defined( 'ABSPATH' ) || exit;
?>
<?php if ( ! WC_POS_Admin_Notices::has_notice( 'install' ) ) : ?>
<div id="message" class="updated">
	<p><?php echo wp_kses_post( __( '<strong>WooCommerce Point of Sale data update</strong> &#8211; We need to update your database to the latest version', 'woocommerce-point-of-sale' ) ); ?></p>
	<p class="submit"><a href="<?php echo esc_url( add_query_arg( 'do_update_wc_pos', 'true', admin_url( 'admin.php?page=wc-settings&tab=point-of-sale' ) ) ); ?>" class="wc-update-now button-primary"><?php esc_html_e( 'Run the updater', 'woocommerce-point-of-sale' ); ?></a></p>
</div>
<script type="text/javascript">
	jQuery( '.wc-update-now' ).click( 'click', function() {
		return window.confirm( '<?php echo esc_js( __( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'woocommerce-point-of-sale' ) ); ?>' );
	});
</script>
<?php endif; ?>
