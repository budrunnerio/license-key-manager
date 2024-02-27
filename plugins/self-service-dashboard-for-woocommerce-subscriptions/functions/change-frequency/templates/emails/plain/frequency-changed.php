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
/**
 * Woocommerce_email_header
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php
/* translators: Notice HTML */
echo sprintf( esc_html__( 'Hi %s,', 'self-service-dashboard-for-woocommerce-subscriptions' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";
/* translators: Notice HTML */
echo sprintf( esc_html__( 'You have successfully changed your subscription frequency to Every %1$s, for subscription #%2$s.', 'self-service-dashboard-for-woocommerce-subscriptions' ), esc_html( wcs_get_subscription_period_strings( wcs_get_objects_property( $order, 'billing_interval' ), wcs_get_objects_property( $order, 'billing_period' ) ) ), absint( $order->get_id() ) );
?>

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
