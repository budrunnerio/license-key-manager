<?php
/**
 * Custom Paused Subscription
 *
 * @package Self-service Dashboard for WooCommerce Subscriptions
 * @since 0.1
 * @extends \WC_Email
 */

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

/**
 * Class WC_Paused_Order_Email
 */
class WC_Paused_Order_Email extends WC_Email {
	/**
	 * WC_Paused_Order_Email constructor.
	 */
	public function __construct() {
		$this->id = 'customer_paused_subscription';

		$this->title = 'Paused Subscription';

		$this->description    = 'Paused Subscription Notification emails are sent when a subscription has been paused.';
		$this->customer_email = true;
		$this->heading        = __( 'Your subscription has been paused until {paused_date}', 'self-service-dashboard-for-woocommerce-subscriptions' );
		$this->subject        = __( 'Your {blogname} subscription has been paused until {paused_date}', 'self-service-dashboard-for-woocommerce-subscriptions' );

		$this->template_html  = 'emails/customer-paused-subscription.php';
		$this->template_plain = 'emails/plain/customer-paused-subscription.php';
		$this->template_base  = SSD_FUNC_PATH . 'templates/';
		$this->placeholders   = array(
			'{order_date}'   => '',
			'{order_number}' => '',
			'{paused_date}'  => '',
		);

		add_action( 'paused_subscription_notification', array( $this, 'trigger' ) );

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
			$date_format = wc_date_format();

			$this->object    = wc_get_order( $order_id );
			$this->recipient = wcs_get_objects_property( $this->object, 'billing_email' );

			$order_date_index = array_search( '{order_date}', $this->find, true );
			if ( false === $order_date_index ) {
				$this->find['order_date']    = '{order_date}';
				$this->replace['order_date'] = wcs_format_datetime( wcs_get_objects_property( $this->object, 'date_created' ) );
			} else {
				$this->replace[ $order_date_index ] = wcs_format_datetime( wcs_get_objects_property( $this->object, 'date_created' ) );
			}

			$order_paused_index  = array_search( '{paused_date}', $this->find, true );
			$subscription_paused = $this->object->get_meta( 'ssd_pause_subscription_until' );
			if ( false === $order_paused_index ) {
				$this->find['paused_date']    = '{paused_date}';
				$this->replace['paused_date'] = esc_html( date_i18n( $date_format, $subscription_paused ) );
			} else {
				$this->replace[ $order_paused_index ] = esc_html( date_i18n( $date_format, $subscription_paused ) );
			}

			$order_number_index = array_search( '{order_number}', $this->find, true );
			if ( false === $order_number_index ) {
				$this->find['order_number']    = '{order_number}';
				$this->replace['order_number'] = $this->object->get_order_number();
			} else {
				$this->replace[ $order_number_index ] = $this->object->get_order_number();
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
