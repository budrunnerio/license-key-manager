<?php
/**
 * Available terminals template.
 *
 * @var array $terminals Available terminals.
 */
?>
<h3 class="wc-settings-sub-title">
	<?php esc_html_e( 'Available Terminals', 'woocommerce-point-of-sale' ); ?>
</h3>
<table class="wc_status_table widefat">
	<thead>
		<tr>
			<td><?php esc_html_e( 'Label', 'woocommerce-point-of-sale' ); ?></td>
			<td><?php esc_html_e( 'ID', 'woocommerce-point-of-sale' ); ?></td>
			<td><?php esc_html_e( 'Device', 'woocommerce-point-of-sale' ); ?></td>
			<td><?php esc_html_e( 'Serial Number', 'woocommerce-point-of-sale' ); ?></td>
			<td><?php esc_html_e( 'IP Address', 'woocommerce-point-of-sale' ); ?></td>
			<td><?php esc_html_e( 'Location', 'woocommerce-point-of-sale' ); ?></td>
			<td><?php esc_html_e( 'Status', 'woocommerce-point-of-sale' ); ?></td>
			<td><?php esc_html_e( 'Mode', 'woocommerce-point-of-sale' ); ?></td>
		</tr>
	</thead>
	<tbody>
	<?php if ( count( $terminals ) ) : ?>
		<?php foreach ( $terminals as $terminal ) : ?>
		<tr>
			<td><?php echo esc_html( $terminal->label ); ?></td>
			<td><?php echo esc_html( $terminal->id ); ?></td>
			<td><?php echo esc_html( $terminal->device_type ); ?></td>
			<td><?php echo esc_html( $terminal->serial_number ); ?></td>
			<td><?php echo esc_html( $terminal->ip_address ); ?></td>
			<td><?php echo esc_html( $terminal->location ); ?></td>
			<td><?php echo esc_html( $terminal->status ); ?></td>
			<td><?php $terminal->livemode ? esc_html_e( 'Live', 'woocommerce-point-of-sale' ) : esc_html_e( 'Test', 'woocommerce-point-of-sale' ); ?></td>
		</tr>
		<?php endforeach; ?>
	<?php else : ?>
		<tr>
			<td colspan="8"><?php esc_html_e( 'No terminals found.', 'woocommerce-point-of-sale' ); ?></td>
		</tr>
	<?php endif; ?>
	</tbody>
</table>

