<?php
/**
 * Customer Next Payment change email (plain text)
 *
 * @package Self-service Dashboard for WooCommerce Subscriptions
 * @since 0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$format_date = strtotime( $order->get_date( 'next_payment', 'site' ) );

echo esc_attr( $email_heading ) . "\n\n";

/* translators: %s: Customer first name */

echo sprintf( esc_html__( 'Hi %s,', 'self-service-dashboard-for-woocommerce-subscriptions' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";
/* translators: Notice HTML */
echo sprintf( esc_html__( 'You have successfully changed your subscription payment date to  %s.', 'self-service-dashboard-for-woocommerce-subscriptions' ), esc_html( date_i18n( wc_date_format(), $format_date ) ) );

echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
/**
 * Woocommerce_subscriptions_email_order_details
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_subscriptions_email_order_details', $order, $sent_to_admin, $plain_text, $email );

// translators: placeholder is order's view url.
echo "\n" . sprintf( esc_html__( 'View your order: %s', 'self-service-dashboard-for-woocommerce-subscriptions' ), esc_url( $order->get_view_order_url() ) );

echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
/**
 * Woocommerce_email_order_meta
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

foreach ( $subscriptions as $subscription ) {
	/**
	 * Woocommerce_subscriptions_email_order_details
	 *
	 * @since 1.0.0
	 */
	do_action( 'woocommerce_subscriptions_email_order_details', $subscription, $sent_to_admin, $plain_text, $email );

	// translators: placeholder is subscription's view url.
	echo "\n" . sprintf( esc_html__( 'View your subscription: %s', 'self-service-dashboard-for-woocommerce-subscriptions' ), esc_url( $order->get_view_order_url() ) );
}
echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
/**
 * Woocommerce_email_customer_details
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
}
/**
 * Woocommerce_email_footer_text
 *
 * @since 1.0.0
 */
echo esc_attr( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

