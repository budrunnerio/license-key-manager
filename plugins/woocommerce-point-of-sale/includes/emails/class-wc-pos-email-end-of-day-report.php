<?php
/**
 * Class WC_POS_Email_End_Of_Day_Report file.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/Emails
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_POS_Email_End_Of_Day_Report', false ) ) {
	return new WC_POS_Email_End_Of_Day_Report();
}

/**
 * End of Day Email.
 *
 * An email sent to chosen recipient(s) when a register is closed.
 */
class WC_POS_Email_End_Of_Day_Report extends WC_Email {

	/**
	 * Register object.
	 *
	 * @var WC_POS_Register
	 */
	public $register = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id            = 'wc_pos_end_of_day_report';
		$this->title         = __( 'POS End of Day Report', 'woocommerce-point-of-sale' );
		$this->description   = __( 'POS End of day reports are sent to chosen recipient(s) when a register is closed.', 'woocommerce-point-of-sale' );
		$this->template_html = 'emails/pos-end-of-day-report.php';
		$this->placeholders  = [
			'{register_name}' => '',
		];

		// This action is required to be available during ajax requests. The easiest way to
		// accomplish this is to re-use the transactional email logic, hence the `_notification`
		// suffix is added.
		add_action( 'wc_pos_end_of_day_report_notification', [ $this, 'trigger' ], 10, 2 );

		// Call parent constructor.
		parent::__construct();

		// Other settings.
		$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );

		// Templates path.
		$this->template_base = WC_POS_ABSPATH . '/templates/';
	}

	/**
	 * Get email subject.
	 *
	 * @since 5.2.0
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'End of Day Report', 'woocommerce-point-of-sale' );
	}

	/**
	 * Get email heading.
	 *
	 * @since 5.2.0
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'End of Day Report', 'woocommerce-point-of-sale' );
	}

	/**
	 * Trigger the sending of this email.
	 *
	 * @param int                  $session_id The session ID.
	 * @param WC_POS_Session|false $session    Session object.
	 */
	public function trigger( $session_id, $session = false ) {
		$this->setup_locale();

		if ( ! $session ) {
			$session = wc_pos_get_session( $session_id );
		}

		if ( $session_id && is_a( $session, 'WC_POS_Session' ) ) {
			$register = wc_pos_get_register( $session->get_register_id() );

			$this->object   = $session;
			$this->register = $register;
		}

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	/**
	 * Get content html.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			[
				'session'            => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $this,
			],
			'',
			$this->template_base
		);
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			[
				'session'            => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => true,
				'email'              => $this,
			],
			'',
			$this->template_base
		);
	}

	/**
	 * Default content to show below main email content.
	 *
	 * @since 5.2.0
	 * @return string
	 */
	public function get_default_additional_content() {
		return __( 'Congratulations on the sales', 'woocommerce-point-of-sale' );
	}

	/**
	 * Return content from the additional_content field.
	 *
	 * Displayed above the footer.
	 *
	 * @return string
	 */
	public function get_additional_content() {
		$content = $this->get_option( 'additional_content', '' );

		/**
		 * Email's additional content.
		 *
		 * @since 4.0.0
		 */
		return apply_filters( 'woocommerce_email_additional_content_' . $this->id, $this->format_string( $content ), $this->object, $this );
	}

	/**
	 * Initialise settings form fields.
	 */
	public function init_form_fields() {
		/* translators: %s: list of placeholders */
		$placeholder_text  = sprintf( __( 'Available placeholders: %s', 'woocommerce-point-of-sale' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' );
		$this->form_fields = [
			'enabled'            => [
				'title'   => __( 'Enable/Disable', 'woocommerce-point-of-sale' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'woocommerce-point-of-sale' ),
				'default' => 'yes',
			],
			'recipient'          => [
				'title'       => __( 'Recipient(s)', 'woocommerce-point-of-sale' ),
				'type'        => 'text',
				/* translators: %s: WP admin email */
				'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'woocommerce-point-of-sale' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
				'placeholder' => '',
				'default'     => '',
				'desc_tip'    => true,
			],
			'subject'            => [
				'title'       => __( 'Subject', 'woocommerce-point-of-sale' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => $placeholder_text,
				'placeholder' => $this->get_default_subject(),
				'default'     => '',
			],
			'heading'            => [
				'title'       => __( 'Email heading', 'woocommerce-point-of-sale' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => $placeholder_text,
				'placeholder' => $this->get_default_heading(),
				'default'     => '',
			],
			'additional_content' => [
				'title'       => __( 'Additional content', 'woocommerce-point-of-sale' ),
				'description' => __( 'Text to appear below the main email content.', 'woocommerce-point-of-sale' ) . ' ' . $placeholder_text,
				'css'         => 'width:400px; height: 75px;',
				'placeholder' => __( 'N/A', 'woocommerce-point-of-sale' ),
				'type'        => 'textarea',
				'default'     => $this->get_default_additional_content(),
				'desc_tip'    => true,
			],
			'email_type'         => [
				'title'       => __( 'Email type', 'woocommerce-point-of-sale' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'woocommerce-point-of-sale' ),
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_email_type_options(),
				'desc_tip'    => true,
			],
		];
	}
}

return new WC_POS_Email_End_Of_Day_Report();
