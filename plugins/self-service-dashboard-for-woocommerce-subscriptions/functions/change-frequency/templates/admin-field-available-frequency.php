<?php
/**
 * Admin field available frequency
 *
 * @package Subscription Force
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$sforce_change_frequency = new Change_Frequency();

$periods = wcs_get_subscription_period_strings();
$intervals = wcs_get_subscription_period_interval_strings();

$_frequencies = $sforce_change_frequency->get_available_frequencies();
?>

<tbody id="_frequency_row">
<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="sf_smart_shipping_min_order"><?php esc_html_e( 'Frequency', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></label>
	</th>
	<td class="forminp forminp-number">
		<table id="sf_admin_field_available_frequencies">
			<tr>
				<td class="interval">
					<select name="available_frequency_interval" id="available_frequency_interval">
						<?php foreach ( $intervals as $interval => $label ) : ?>
							<option value="<?php echo esc_attr( $interval ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
				<td class="period">
					<select name="available_frequency_period" id="available_frequency_period">
						<?php foreach ( $periods as $period => $label ) : ?>
							<option value="<?php echo esc_attr( $period ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
				<td class="add">
					<button name="add_available_frequency" class="button-primary" type="submit" value="Add"><?php esc_html_e( 'Add', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></button>
				</td>
				<span class="frequency_notice notice" style="padding: 5px; display: none;"><?php esc_html_e( 'This frequency is already saved', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></span>
			</tr>
		</table>
	</td>
</tr>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="sf_admin_field_user_frequencies"><?php esc_html_e( 'Defined available frequencies', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></label>
	</th>
	<td class="forminp forminp-number">
		<table id="sf_admin_field_user_frequencies">
			<?php if ( count( $_frequencies ) ) : ?>
				<?php foreach ( $_frequencies as $freq_id => $freq ) : ?>
					<tr data-id="<?php echo esc_attr( $freq_id ); ?>">
						<td class="frequency"><?php echo esc_html( $freq->interval_text ); ?> <?php echo esc_html( $freq->period_text ); ?></td>
						<td class="delete">
							<button data-id="<?php echo esc_attr( $freq_id ); ?>" name="delete_user_frequency" class="button-primary" type="submit" value="Delete"><?php esc_html_e( 'Delete', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></button>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</table>
	</td>
</tr>
</tbody>

