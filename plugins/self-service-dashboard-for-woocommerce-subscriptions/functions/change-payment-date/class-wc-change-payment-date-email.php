<?php
/**
 * Change payment date email
 *
 * @package SForce for WooCommerce Subscriptions
 * @since 0.1
 * @extends \WC_Email
 */

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

/**
 * Class WC_Change_Payment_Date_Email
 */
class WC_Change_Payment_Date_Email extends WC_Email {
	/**
	 * WC_Change_Payment_Date_Email constructor.
	 */
	public function __construct() {
		$this->id = 'customer_change_payment_date_subscription';

		$this->title = 'Change Payment Date';

		$this->description    = 'Change Payment Date Subscription Notification emails are sent when a subscription get\'t paid earlier.';
		$this->customer_email = true;
		$this->heading        = __( 'You changed your subscription date', 'self-service-dashboard-for-woocommerce-subscriptions' );
		$this->subject        = __( 'You changed your subscription date', 'self-service-dashboard-for-woocommerce-subscriptions' );

		$this->template_html  = 'emails/customer-change-payment-date-subscription.php';
		$this->template_plain = 'emails/plain/customer-change-payment-date-subscription.php';
		$this->template_base  = SSD_FUNC_PATH . '/change-payment-date/templates/';
		$this->placeholders   = array(
			'{next_payment_date}' => '',
		);

		add_action( 'change_payment_date_subscription_notification', array( $this, 'trigger' ) );

		WC_Email::__construct();
	}

	/**
	 * Get the default e-mail subject.
	 *
	 * @return string
	 * @since 2.5.3
	 */
	public function get_default_subject() {
		return $this->subject;
	}

	/**
	 * Get the default e-mail heading.
	 *
	 * @return string
	 * @since 2.5.3
	 */
	public function get_default_heading() {
		return $this->heading;
	}

	/**
	 * Trigger function.
	 *
	 * @param int    $order_id Represents the Order ID.
	 * @param object $order Represents the Order object.
	 */
	public function trigger( $order_id, $order = null ) {
		if ( $order_id ) {
			$this->object = new WC_Subscription( $order_id );

			$order_id = $this->object->get_parent_id();
			$order    = wc_get_order( $order_id );
			if ( $order->get_billing_email() ) {
				$this->recipient = $order->get_billing_email();
			} else {
				$this->recipient = $this->object->get_billing_email();
			}

			$format_date = strtotime( $this->object->get_date( 'next_payment', 'site' ) );

			$order_date_index = array_search( '{next_payment_date}', $this->find, true );
			if ( false === $order_date_index ) {
				$this->find['next_payment_date']    = '{next_payment_date}';
				$this->replace['next_payment_date'] = esc_attr( date_i18n( wc_date_format(), $format_date ) );
			} else {
				$this->replace[ $order_date_index ] = esc_attr( date_i18n( wc_date_format(), $format_date ) );
			}

			$this->subscriptions = wcs_get_subscriptions_for_switch_order( $this->object );
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * Get content html function.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'order'              => $this->object,
				'subscriptions'      => $this->subscriptions,
				'email_heading'      => $this->get_heading(),
				'additional_content' => is_callable( array( $this, 'get_additional_content' ) ) ? $this->get_additional_content() : '', // WC 3.7 introduced an additional content field for all emails.
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Get content plain function.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'order'              => $this->object,
				'subscriptions'      => $this->subscriptions,
				'email_heading'      => $this->get_heading(),
				'additional_content' => is_callable( array( $this, 'get_additional_content' ) ) ? $this->get_additional_content() : '', // WC 3.7 introduced an additional content field for all emails.
				'sent_to_admin'      => false,
				'plain_text'         => true,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}
}

