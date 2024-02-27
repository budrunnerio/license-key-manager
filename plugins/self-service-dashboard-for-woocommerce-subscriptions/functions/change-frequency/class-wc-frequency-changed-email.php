<?php
/**
 * Custom notification for frequency change
 *
 * @extends \WC_Email
 * @package Subscription Force
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

/**
 * Class WC_Frequency_Changed_Email
 */
class WC_Frequency_Changed_Email extends WC_Email {
	/**
	 * WC_Frequency_Changed_Email constructor.
	 */
	public function __construct() {
		$this->id = 'sf_frequency_changed';

		$this->title = 'Frequency Changed';

		$this->description    = 'Email is sent when a subscription frequency is changed by the user.';
		$this->customer_email = true;
		$this->heading        = __( 'Your subscription frequency has changed', 'self-service-dashboard-for-woocommerce-subscriptions' );
		$this->subject        = __( 'You changed your subscription frequency', 'self-service-dashboard-for-woocommerce-subscriptions' );

		$this->template_html  = 'emails/frequency-changed.php';
		$this->template_plain = 'emails/plain/frequency-changed.php';
		$this->template_base  = SSD_FUNC_PATH . '/change-frequency/templates/';
		$this->placeholders   = array(
			'{frequency}'   => '',
		);

		add_action( 'sf_frequency_changed_email', array( $this, 'trigger' ), 10, 2 );

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
	 * @param int $order_id Order ID.
	 * @param int $frequency Frequency.
	 */
	public function trigger( $order_id, $frequency ) {
		if ( $order_id ) {
			$this->object    = wc_get_order( $order_id );
			$this->recipient = wcs_get_objects_property( $this->object, 'billing_email' );

			$order_date_index = array_search( '{frequency}', $this->find, true );
			if ( false === $order_date_index ) {
				$this->find['frequency']    = '{frequency}';
				$this->replace['frequency'] = $frequency;
			} else {
				$this->replace[ $order_date_index ] = $frequency;
			}
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
