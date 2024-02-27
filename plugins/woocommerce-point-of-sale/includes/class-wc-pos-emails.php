<?php
/**
 * Emails Controller
 *
 * Handles the sending of emails and email templates.
 *
 * @package WooCommerce_Point_Of_Sale/Classes
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_POS_Emails', false ) ) {
	return new WC_POS_Emails();
}

/**
 * WC_POS_Emails class.
 */
class WC_POS_Emails {

	/**
	 * Email actions.
	 *
	 * @var array Actions list.
	 */
	public $actions;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_email_classes', [ $this, 'email_classes' ] );
		add_filter( 'woocommerce_email_actions', [ $this, 'email_actions' ] );

		// Disable WC core email notificaitons.
		add_filter( 'woocommerce_email_enabled_new_order', [ $this, 'disable_order_email_notifications' ], 10, 3 );
		add_filter( 'woocommerce_email_enabled_customer_completed_order', [ $this, 'disable_order_email_notifications' ], 10, 3 );
		add_filter( 'woocommerce_email_enabled_customer_processing_order', [ $this, 'disable_order_email_notifications' ], 10, 3 );
		add_filter( 'woocommerce_email_enabled_customer_on_hold_order', [ $this, 'disable_order_email_notifications' ], 10, 3 );
		add_filter( 'woocommerce_email_enabled_customer_invoice', [ $this, 'disable_order_email_notifications' ], 10, 3 );
	}

	/**
	 * Disables core WC customer email notifications.
	 *
	 * @param bool     $enabled Enabled.
	 * @param WC_Order $order   Order object.
	 * @param WC_Email $email  Email object.
	 *
	 * @since 6.0.0
	 *
	 * @return boolean
	 */
	public function disable_order_email_notifications( $enabled, $order, $email ) {
		$order_emails = [
			'new_order',
			'customer_invoice',
		];

		if ( wc_pos_is_pos_referer() ) {
			array_push(
				$order_emails,
				[
					'customer_completed_order',
					'customer_processing_order',
					'customer_on_hold_order',
				]
			);

			return in_array( $email->id, $order_emails, true );
		}

		if ( in_array( $email->id, $order_emails, true ) && is_a( $order, 'WC_Order' ) && 'pos' === $order->get_created_via() ) {
			return false;
		}

		return $enabled;
	}

	/**
	 * Registers the new email classes.
	 *
	 * @since 5.2.0
	 *
	 * @param $emails array Email classes.
	 * @return array The updated email classes array.
	 */
	public function email_classes( $emails ) {
		$emails['WC_POS_Email_New_Order']         = include 'emails/class-wc-pos-email-new-order.php';
		$emails['WC_POS_Email_Customer_Invoice']  = include 'emails/class-wc-pos-email-customer-invoice.php';
		$emails['WC_POS_Email_End_Of_Day_Report'] = include 'emails/class-wc-pos-email-end-of-day-report.php';

		return $emails;
	}

	public function email_actions( $actions ) {
		// End of day report action is added here to be available during Ajax requests.
		$actions[] = 'wc_pos_end_of_day_report';

		return $actions;
	}
}

return new WC_POS_Emails();
