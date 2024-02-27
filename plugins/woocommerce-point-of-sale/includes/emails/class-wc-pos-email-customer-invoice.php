<?php
/**
 * Class WC_POS_Email_Customer_Invoice file.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/Emails
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_POS_Email_Customer_Invoice', false ) ) {
	return new WC_POS_Email_Customer_Invoice();
}

/**
 * Customer Invoice.
 *
 * An email sent to the customer via admin.
 *
 * @since       6.0.0
 * @extends     WC_Email
 */
class WC_POS_Email_Customer_Invoice extends WC_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'wc_pos_customer_invoice';
		$this->customer_email = true;
		$this->title          = __( 'POS Customer Invoice', 'woocommerce-point-of-sale' );
		$this->description    = __( 'Customer invoice emails are sent to POS customers containing their order information and payment links.', 'woocommerce-point-of-sale' );
		$this->placeholders   = [
			'{order_date}'   => '',
			'{order_number}' => '',
		];

		// Use core templates.
		$this->template_html  = 'emails/customer-invoice.php';
		$this->template_plain = 'emails/plain/customer-invoice.php';

		// Triggers for this email.
		add_action( 'wc_pos_order_paid', [ $this, 'trigger' ], 10, 1 );
		add_action( 'wc_pos_order_held', [ $this, 'trigger' ], 10, 1 );

		// Call parent constructor.
		parent::__construct();
	}

	/**
	 * Get email subject.
	 *
	 * @since  6.0.0
	 *
	 * @param bool $paid Whether the order has been paid or not.
	 *
	 * @return string
	 */
	public function get_default_subject( $paid = false ) {
		if ( $paid ) {
			return __( 'Invoice for order #{order_number} on {site_title}', 'woocommerce-point-of-sale' );
		} else {
			return __( 'Your latest {site_title} invoice', 'woocommerce-point-of-sale' );
		}
	}

	/**
	 * Get email heading.
	 *
	 * @since  6.0.0
	 *
	 * @param bool $paid Whether the order has been paid or not.
	 *
	 * @return string
	 */
	public function get_default_heading( $paid = false ) {
		if ( $paid ) {
			return __( 'Invoice for order #{order_number}', 'woocommerce-point-of-sale' );
		} else {
			return __( 'Your invoice for order #{order_number}', 'woocommerce-point-of-sale' );
		}
	}

	/**
	 * Get email recipient.
	 *
	 * @since 6.0.0
	 *
	 * @return string
	 */
	public function get_recipient() {
		return $this->object ? $this->object->get_meta( '_wc_pos_notification_email_address' ) : '';
	}

	/**
	 * Get email subject.
	 *
	 * @since 6.0.0
	 *
	 * @return string
	 */
	public function get_subject() {
		if ( $this->object->has_status( [ 'completed', 'processing' ] ) ) {
			$subject = $this->get_option( 'subject_paid', $this->get_default_subject( true ) );

			/**
			 * This filter is documented in WC core.
			 *
			 * @since 6.0.0
			 */
			return apply_filters( 'woocommerce_email_subject_customer_invoice_paid', $this->format_string( $subject ), $this->object, $this );
		}

		$subject = $this->get_option( 'subject', $this->get_default_subject() );

		/**
		 * This filter is documented in WC core.
		 *
		 * @since 6.0.0
		 */
		return apply_filters( 'woocommerce_email_subject_customer_invoice', $this->format_string( $subject ), $this->object, $this );
	}

	/**
	 * Get email heading.
	 *
	 * @since 6.0.0
	 *
	 * @return string
	 */
	public function get_heading() {
		if ( $this->object->has_status( wc_get_is_paid_statuses() ) ) {
			$heading = $this->get_option( 'heading_paid', $this->get_default_heading( true ) );

			/**
			 * This filter is documented in WC core.
			 *
			 * @since 6.0.0
			 */
			return apply_filters( 'woocommerce_email_heading_customer_invoice_paid', $this->format_string( $heading ), $this->object, $this );
		}

		$heading = $this->get_option( 'heading', $this->get_default_heading() );

		/**
		 * This filter is documented in WC core.
		 *
		 * @since 6.0.0
		 */
		return apply_filters( 'woocommerce_email_heading_customer_invoice', $this->format_string( $heading ), $this->object, $this );
	}

	/**
	 * Default content to show below main email content.
	 *
	 * @since 6.0.0
	 *
	 * @return string
	 */
	public function get_default_additional_content() {
		return __( 'Thanks for using {site_url}!', 'woocommerce-point-of-sale' );
	}

	/**
	 * Trigger the sending of this email.
	 *
	 * @since 6.0.0
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
		$this->recipient                      = $this->object->get_billing_email();
		$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
		$this->placeholders['{order_number}'] = $this->object->get_order_number();

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	/**
	 * Get content html.
	 *
	 * @since 6.0.0
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
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $this,
			]
		);
	}

	/**
	 * Get content plain.
	 *
	 * @since 6.0.0
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			[
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => true,
				'email'              => $this,
			]
		);
	}

	/**
	 * Initialise settings form fields.
	 *
	 * @since 6.0.0
	 */
	public function init_form_fields() {
		/* translators: %s: list of placeholders */
		$placeholder_text  = sprintf( __( 'Available placeholders: %s', 'woocommerce-point-of-sale' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );
		$this->form_fields = [
			'enabled'            => [
				'title'   => __( 'Enable/Disable', 'woocommerce-point-of-sale' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'woocommerce-point-of-sale' ),
				'default' => 'yes',
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
			'subject_paid'       => [
				'title'       => __( 'Subject (paid)', 'woocommerce-point-of-sale' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => $placeholder_text,
				'placeholder' => $this->get_default_subject( true ),
				'default'     => '',
			],
			'heading_paid'       => [
				'title'       => __( 'Email heading (paid)', 'woocommerce-point-of-sale' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => $placeholder_text,
				'placeholder' => $this->get_default_heading( true ),
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

return new WC_POS_Email_Customer_Invoice();
