<?php
/**
 * Class WC_POS_Email_New_Order file.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/Emails
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_POS_Email_New_Order', false ) ) {
	return new WC_POS_Email_New_Order();
}

/**
 * New Point of Sale Order Email.
 *
 * An email sent to the admin when a new Point of Sale order is received.
 */
class WC_POS_Email_New_Order extends WC_Email {

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->id           = 'wc_pos_new_order';
		$this->title        = __( 'POS New Order', 'woocommerce-point-of-sale' );
		$this->description  = __( 'New order emails are sent to chosen recipient(s) when a new Point of Sale order is received.', 'woocommerce-point-of-sale' );
		$this->placeholders = [
			'{order_date}'   => '',
			'{order_number}' => '',
		];

		// Use core templates.
		$this->template_html  = 'emails/admin-new-order.php';
		$this->template_plain = 'emails/plain/admin-new-order.php';

		// Triggers for this email.
		add_action( 'wc_pos_order_paid', [ $this, 'trigger' ], 10, 1 );
		add_action( 'wc_pos_order_held', [ $this, 'trigger' ], 10, 1 );

		// Call parent constructor.
		parent::__construct();

		// Other settings.
		$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
	}

	/**
	 * Trigger the sending of this email.
	 *
	 * @param int $order_id The order ID.
	 * @return void
	 */
	public function trigger( $order_id ) {
		$this->setup_locale();

		$order = wc_get_order( $order_id );

		if ( ! is_a( $order, 'WC_Order' ) || 'pos' !== $order->get_created_via() ) {
			return;
		}

		$this->object                         = $order;
		$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
		$this->placeholders['{order_number}'] = $this->object->get_order_number();

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	/**
	 * Get email subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( '[{site_title}]: New POS order #{order_number}', 'woocommerce-point-of-sale' );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'New POS Order: #{order_number}', 'woocommerce-point-of-sale' );
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
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => true,
				'plain_text'         => false,
				'email'              => $this,
			]
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
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => true,
				'plain_text'         => true,
				'email'              => $this,
			]
		);
	}

	/**
	 * Default content to show below main email content.
	 *
	 * @return string
	 */
	public function get_default_additional_content() {
		return __( 'Congratulations on the sale!', 'woocommerce-point-of-sale' );
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
	 *
	 * @return void
	 */
	public function init_form_fields() {
		/* translators: %s: list of placeholders */
		$placeholder_text = sprintf( __( 'Available placeholders: %s', 'woocommerce-point-of-sale' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' );

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
				'css'         => 'width:400px;height:75px;',
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

return new WC_POS_Email_New_Order();
