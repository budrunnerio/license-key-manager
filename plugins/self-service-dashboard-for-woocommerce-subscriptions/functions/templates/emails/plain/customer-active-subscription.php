<?php
/**
 * Customer active subscription change email (plain text)
 *
 * @package Self-service Dashboard for WooCommerce Subscriptions
 * @since 0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo esc_attr( $email_heading ) . "\n\n";

/* translators: %s: Customer first name */
echo sprintf( esc_html__( 'Hi %s,', 'woocommerce-subscriptions' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";
esc_html_e( 'Your subscription it\'s now active. Your new order and subscription details are shown below for your reference:', 'self-service-dashboard-for-woocommerce-subscriptions' );

echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
/**
 * Email details
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_subscriptions_email_order_details', $order, $sent_to_admin, $plain_text, $email );

// translators: placeholder is order's view url.
echo "\n" . sprintf( esc_html__( 'View your order: %s', 'self-service-dashboard-for-woocommerce-subscriptions' ), esc_url( $order->get_view_order_url() ) );

echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";

/**
 * Order meta data
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

foreach ( $subscriptions as $subscription ) {
	/**
	 * Subscription order details
	 *
	 * @hooked WC_Emails::order_meta() Shows order meta data.
	 * @since 2.5.0
	 */
	do_action( 'woocommerce_subscriptions_email_order_details', $subscription, $sent_to_admin, $plain_text, $email );

	// translators: placeholder is subscription's view url.
	echo "\n" . sprintf( esc_html__( 'View your subscription: %s', 'self-service-dashboard-for-woocommerce-subscriptions' ), esc_url( $order->get_view_order_url() ) );
}
echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
/**
 * Customer order details
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 * @since 2.5.0
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
 * Email footer
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 *
 * @since 2.5.0
 */
echo esc_attr( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
