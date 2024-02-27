<?php
/**
 * Frequency changed email template
 *
 * @package Subscription Force
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$billing_interval = wcs_get_objects_property( $order, 'billing_interval' );
if ( ! $billing_interval ) {
	$billing_interval = $order->get_meta( '_billing_interval' );
}

$billing_period = wcs_get_objects_property( $order, 'billing_period' );
if ( ! $billing_period ) {
	$billing_period = $order->get_meta( '_billing_period' );
}
/**
 * Woocommerce_email_header
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<?php /* translators: %s: Customer first name */ ?>
	<p><?php printf( esc_html__( 'Hi %s,', 'self-service-dashboard-for-woocommerce-subscriptions' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
	<p><?php /* translators: Notice HTML */ printf( esc_html__( 'You have successfully changed your subscription frequency to Every %1$s, for subscription #%2$s.', 'self-service-dashboard-for-woocommerce-subscriptions' ), esc_html( wcs_get_subscription_period_strings( $billing_interval, $billing_period ) ), absint( $order->get_id() ) ); ?></p>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
/**
 * Woocommerce_email_footer
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_email_footer', $email );
