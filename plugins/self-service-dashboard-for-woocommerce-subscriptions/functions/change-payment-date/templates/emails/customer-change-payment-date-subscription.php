<?php
/**
 * Customer Next Payment change email
 *
 * @package Self-service Dashboard for WooCommerce Subscriptions
 * @since 0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Woocommerce_email_header
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_email_header', $email_heading, $email );
$format_date = strtotime( $order->get_date( 'next_payment', 'site' ) );
?>

<?php /* translators: %s: Customer first name */ ?>
	<p><?php printf( esc_html__( 'Hi %s,', 'self-service-dashboard-for-woocommerce-subscriptions' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
	<p><?php /* translators: Notice HTML */ printf( esc_html__( 'You have successfully changed your subscription payment date to  %s.', 'self-service-dashboard-for-woocommerce-subscriptions' ), esc_html( date_i18n( wc_date_format(), $format_date ) ) ); ?></p>

<?php
/**
 * Woocommerce_subscriptions_email_order_details
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_subscriptions_email_order_details', $order, $sent_to_admin, $plain_text, $email );
/**
 * Woocommerce_email_order_meta
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
?>

	<h2><?php echo esc_html__( 'Subscription details', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></h2>

<?php
foreach ( $subscriptions as $subscription ) {
	/**
	 * Woocommerce_subscriptions_email_order_details
	 *
	 * @since 1.0.0
	 */
	do_action( 'woocommerce_subscriptions_email_order_details', $subscription, $sent_to_admin, $plain_text, $email );
}
/**
 * Woocommerce_email_customer_details
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

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

