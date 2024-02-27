<?php
/**
 * Change frequency row
 *
 * @package Subscription Force
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$sforce_change_frequency = new Change_Frequency();

// Get data.
$subscription = get_query_var( 'subscription' );
$_frequencies = $sforce_change_frequency->get_available_frequencies();

if ( ! $subscription ) {
	return;
}
?>
<tr id="sf_change_frequency_subscription_row">
	<td> <?php esc_html_e( 'Subscription frequency', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></td>
	<td>
		<div class="view">
			<div class="notification" style="border: 1px solid rgba(0, 124, 75, 1); padding: 5px 10px;
			margin: 5px 0; border-radius: 5px; color: rgba(0, 124, 75, 1); display: none;"></div>
			<span>
				<?php echo esc_html( ucfirst( wcs_get_subscription_period_interval_strings( $subscription->get_billing_interval() ) ) ); ?>
				<?php echo esc_html( ucfirst( $subscription->get_billing_period() ) ); ?>
			</span>
			<?php if ( count( $_frequencies ) ) : ?>
				<a id="sf_change_frequency_edit_button" class="button" style="margin-left: 5px;"><?php esc_html_e( 'Change frequency', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></a>
			<?php endif; ?>
		</div>
		<div class="edit" style="display: none;">
			<script type="text/javascript">
				jQuery(document).ready(function () {
					jQuery('#sf_subscription_frequency').on('change', function() {
						jQuery('button[name="sf_change_frequency_save"]').attr('disabled',false);
					});
				});
			</script>
			<select name="sf_subscription_frequency" id="sf_subscription_frequency" data-subscription_id="<?php echo esc_attr( $subscription->get_id() ); ?>">
				<?php if ( count( $_frequencies ) ) : ?>
					<?php foreach ( $_frequencies as $freq_id => $freq ) : ?>

						<option
								value="<?php echo esc_attr( $freq_id ); ?>"
							<?php echo ( $freq_id == $subscription->get_billing_period() . '_' . $subscription->get_billing_interval() ? 'selected' : '' ); ?>
						>

							<?php echo esc_html( ucfirst( $freq->interval_text ) ); ?> <?php echo esc_html( ucfirst( $freq->period_text ) ); ?>
						</option>

					<?php endforeach; ?>
				<?php endif; ?>
			</select>
			<button name="sf_change_frequency_save" disabled="disabled" class="button" type="submit" value="Save"><?php esc_html_e( 'Save', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></button>
			<a id="sf_change_frequency_cancel" class="button" style="margin-left: 5px;"><?php esc_html_e( 'Cancel', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></a>
		</div>
	</td>
</tr>

