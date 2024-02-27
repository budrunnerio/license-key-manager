<?php
/**
 * Advanced Settings - Database Options
 *
 * @todo Please clean up the mess here.
 * @package WooCommerce_Point_Of_Sale/Admin/Settings/Views
 */
global $wpdb;

$force_updates = [
	'5.0.0' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.0.0.php',
	'5.1.3' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.1.3.php',
	'5.2.0' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.2.0.php',
	'5.2.2' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.2.2.php',
	'5.2.4' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.2.4.php',
	'5.2.5' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.2.5.php',
	'5.2.7' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.2.7.php',
	'5.2.8' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.2.8.php',
	'5.2.9' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.2.9.php',
	'5.3.0' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.3.0.php',
	'5.3.2' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.3.2.php',
	'5.3.3' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.3.3.php',
	'5.3.4' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.3.4.php',
	'5.3.5' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.3.5.php',
	'5.3.7' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.3.7.php',
	'5.3.6' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.3.6.php',
	'5.5.0' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.5.0.php',
	'5.5.2' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.5.2.php',
	'5.5.4' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-5.5.4.php',
	'6.0.0' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-6.0.0.php',
	'6.1.0' => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-6.1.0.php',
	'next'  => WC_POS_ABSPATH . '/includes/updates/wc_pos-update-next.php',
];

if ( ! empty( $_POST['wc_pos_force_update_database'] ) ) {
	check_admin_referer( 'woocommerce-settings' );

	$last_update['date'] = gmdate( 'Y-m-d H:i' );
	foreach ( $force_updates as $version => $update ) {
		include $update;
		$last_update['version'] = $version;
	}

	WC_POS_Install::update_pos_version( $last_update['version'] );
	update_option( 'wc_pos_last_force_db_update', $last_update );
}

if ( ! empty( $_POST['wc_pos_reset_settings'] ) ) {
	check_admin_referer( 'woocommerce-settings' );

	$wpdb->query(
		"DELETE FROM {$wpdb->options}
		WHERE option_name LIKE 'wc\_pos\_%'
		AND option_name NOT IN (
			'wc_pos_db_version',
			'wc_pos_last_force_db_update',
			'wc_pos_admin_notices',
			'wc_pos_custom_product_id',
			'wc_pos_default_outlet',
			'wc_pos_default_receipt',
			'wc_pos_default_register',
			'wc_pos_meta_box_errors',
			'wc_pos_force_refresh_db'
		);"
	);
}

$last_update = get_option( 'wc_pos_last_force_db_update', '' );
$last_update = empty( $last_update ) ? [ 'date' => '' ] : $last_update;
?>
<table class="form-table">
	<tbody>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<?php esc_html_e( 'Database Version', 'woocommerce-point-of-sale' ); ?>
			</th>
			<td class="forminp">
				<span><?php echo esc_html( get_option( 'wc_pos_db_version' ) ); ?></span>
			</td>
		</tr>

		<tr valign="top">
			<th scope="row" class="titledesc">
				<?php esc_html_e( 'Last Force Update', 'woocommerce-point-of-sale' ); ?>
			</th>
			<td class="forminp">
				<span><?php echo empty( $last_update['date'] ) ? esc_html__( 'Database has never been force updated.', 'woocommerce-point-of-sale' ) : esc_html( $last_update['date'] ); ?></span>
			</td>
		</tr>

		<tr valign="top">
			<th scope="row" class="titledesc">
				<?php esc_html_e( 'Update Database', 'woocommerce-point-of-sale' ); ?>
			</th>
			<td class="forminp">
				<button name="wc_pos_force_update_database" type="submit" class="button" value="1"><?php esc_html_e( 'Force Update', 'woocommerce-point-of-sale' ); ?></button>
				<p class="description">
					<?php esc_html_e( 'Use with caution. This tool will update the database to the latest version - useful when settings are not being applied as per configured in settings, registers, receipts and outlets.', 'woocommerce-point-of-sale' ); ?>
				</p>
			</td>
		</tr>

		<tr valign="top">
			<th scope="row" class="titledesc">
				<?php esc_html_e( 'Reset Settings', 'woocommerce-point-of-sale' ); ?>
			</th>
			<td class="forminp">
				<input id="wc_pos_reset_settings" name="wc_pos_reset_settings" type="submit" class="button" value="<?php esc_attr_e( 'Reset Settings', 'woocommerce-point-of-sale' ); ?>">
				<p class="description">
					<?php esc_html_e( 'Reset all plugin settings.', 'woocommerce-point-of-sale' ); ?>
				</p>
			</td>
		</tr>
	</tbody>
</table>
