<?php
/**
 * Customer active subscription change email
 *
 * @package Self-service Dashboard for WooCommerce Subscriptions
 * @since 0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Email header
 *
 * @hooked WC_Emails::email_header() Output the email header
 *
 * @since 2.5.0
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer first name */ ?>
	<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce-subscriptions' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
	<p><?php esc_html_e( 'Your subscription it\'s now active. Your order and subscription details are shown below for your reference:', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></p>

<?php
/**
 * Email details
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_subscriptions_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Order meta data
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
?>

	<h2><?php echo esc_html__( 'Subscription details', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?></h2>

<?php
foreach ( $subscriptions as $subscription ) {
	/**
	 * Subscription order details
	 *
	 * @hooked WC_Emails::order_meta() Shows order meta data.
	 * @since 2.5.0
	 */
	do_action( 'woocommerce_subscriptions_email_order_details', $subscription, $sent_to_admin, $plain_text, $email );
}
/**
 * Customer order details
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
/**
 * Email footer
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 *
 * @since 2.5.0
 */
do_action( 'woocommerce_email_footer', $email );
