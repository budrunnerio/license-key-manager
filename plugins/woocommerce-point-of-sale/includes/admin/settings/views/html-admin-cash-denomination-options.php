<?php
/**
 * Register Settings - Denomination Options
 *
 * @var array $denominations
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Settings/Views
 */
?>
<table class="wc-pos-register-denomination-options widefat" id="wc-pos-register-denomination-options">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Value', 'woocommerce-point-of-sale' ); ?></th>
			<th><?php esc_html_e( 'Type', 'woocommerce-point-of-sale' ); ?></th>
			<th><?php esc_html_e( 'Color', 'woocommerce-point-of-sale' ); ?></th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $denominations as $index => $denomination ) : ?>
			<?php
			// Starting from 1 because having an index of 0 breaks the checked attribute.
			$index++;
			?>
		<tr class="denomination-row" data-index="<?php echo esc_attr( $index ); ?>">
			<td class="denomination-value">
				<input type="number" step="0.01" name="<?php echo 'wc_pos_cash_denominations[' . esc_attr( $index ) . '][value]'; ?>" value="<?php echo esc_attr( $denomination['value'] ); ?>" />
			</td>
			<td class="denomination-type">
				<input type="radio" id="<?php echo 'type_note_' . esc_attr( $index ); ?>" name="<?php echo 'wc_pos_cash_denominations[' . esc_attr( $index ) . '][type]'; ?>" value="note" <?php checked( $denomination['type'], 'note', true ); ?> />
				<label for="<?php echo 'type_note_' . esc_attr( $index ); ?>"><?php esc_html_e( 'Note', 'woocommerce-point-of-sale' ); ?></label>
				<input type="radio" id="<?php echo 'type_coin_' . esc_attr( $index ); ?>" name="<?php echo 'wc_pos_cash_denominations[' . esc_attr( $index ) . '][type]'; ?>" value="coin" <?php checked( $denomination['type'], 'coin', true ); ?> />
				<label for="<?php echo 'type_coin_' . esc_attr( $index ); ?>"><?php esc_html_e( 'Coin', 'woocommerce-point-of-sale' ); ?></label>
			</td>
			<td class="denomination-color">
				<input type="text" class="color-pick" name="<?php echo 'wc_pos_cash_denominations[' . esc_attr( $index ) . '][color]'; ?>" value="<?php echo esc_attr( $denomination['color'] ); ?>" />
			</td>
			<td>
				<button class="button button-secondary remove-denomination"><?php esc_html_e( 'Remove', 'woocommerce-point-of-sale' ); ?></button>
			</td>
		</tr>
		<?php endforeach; ?>
		<?php if ( ! count( $denominations ) ) : ?>
		<tr class="no-denominations">
			<td colspan="4"><?php esc_html_e( 'No denominations have been added yet.', 'woocommerce-point-of-sale' ); ?></td>
		</tr>
		<?php endif; ?>
	</tbody>
	<tfoot>
		<tr class="actions">
			<td colspan="4">
				<button class="button button-primary add-denomination"><?php esc_html_e( 'Add Denomination', 'woocommerce-point-of-sale' ); ?></button>
			</td>
		</tr>
	</tfoot>
</table>
