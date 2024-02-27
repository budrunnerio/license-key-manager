<?php
/**
 * Change Subscription Payment Date
 *
 * @package SForce
 * @since 0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

if ( ! class_exists( 'Change_Payment_Date' ) ) {
	/**
	 * Class Change_Payment_Date
	 */
	class Change_Payment_Date {
		/**
		 * Change_Payment_Date constructor.
		 */
		public function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'sf_enqueue_scripts' ) );
			add_action( 'wp_ajax_sf_change_payment_date_subscription', array( $this, 'sf_change_payment_date_subscription_form' ) );
			add_action( 'wp_ajax_sf_change_date_subscription_submit', array( $this, 'sf_change_date_subscription_submit' ) );
			add_filter( 'woocommerce_subscription_date_to_display', array( $this, 'sf_display_button' ), 10, 3 );
			add_filter( 'esc_html', array( $this, 'sf_esc_html' ), 10, 2 );
			add_filter( 'woocommerce_email_classes', array( $this, 'sf_add_active_woocommerce_email' ), 10, 1 );
			add_filter( 'settings_tabs_ssd_settings', array( $this, 'sf_add_ssd_settings_option' ), 10, 1 );
		}

		/**
		 * Add the option in SSD admin settings tab
		 *
		 * @param array $settings Settings array data.
		 *
		 * @return mixed
		 */
		public function sf_add_ssd_settings_option( $settings ) {
			$change_date['hide_change_payment_date'] = array(
				'name' => __( 'Allow payment date change', 'self-service-dashboard-for-woocommerce-subscriptions' ),
				'type' => 'checkbox',
				'desc' => __( 'When checked, the [Change payment date] button will be displayed on the My Subscription page; otherwise, it will be hidden.', 'self-service-dashboard-for-woocommerce-subscriptions' ),
				'id'   => 'settings_tabs_hide_change_payment_date',
			);

			return ssd_array_insert_after( $settings, 'hide_item_switching', $change_date );
		}

		/**
		 * Enqueue scripts
		 */
		public function sf_enqueue_scripts() {
			$plugin_data = get_plugin_data( __FILE__ );
			wp_enqueue_script( 'sf_sf_frontend', SSD_FUNC_URL . '/change-payment-date/assets/js/front-end.js', array( 'jquery' ), $plugin_data['Version'], true );

			wp_localize_script(
				'sf_sf_frontend',
				'sf_sf_js',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'sf-sf-nonce' ),
				)
			);

			wp_register_style( 'jquery-ui-style', WC()->plugin_url() . '/assets/css/jquery-ui/jquery-ui.min.css', array(), '4.8.0' );
			wp_enqueue_style( 'jquery-ui-style' );
			wp_enqueue_script( 'jquery-ui-datepicker' );
		}

		/**
		 * Display the Change payment date field
		 */
		public function sf_change_payment_date_subscription_form() {
			check_ajax_referer( 'sf-sf-nonce', 'nonce' );
			ob_start();
			$subscription_id = isset( $_POST['subscription_id'] ) ? absint( $_POST['subscription_id'] ) : 0;
			$result          = '';
			if ( $subscription_id > 0 ) {
				$subscription = wcs_get_subscription( $subscription_id );

				$default_date_format = 'yy-mm-dd';
				/**
				 * Date format filter.
				 *
				 * @param string $default_date_format Date format.
				 *
				 * @since 1.0.0
				 */
				$date_format = apply_filters( 'ssd_custom_date_format', $default_date_format );

				/**
				 * Max Date filter
				 *
				 * @param int Number of days
				 *
				 * @since 2.1.2
				 */
				$max_date = apply_filters( 'ssd_calendar_number_of_days', 30 );
				?>
				<div id="sf-change-date-subscription">
					<script type="text/javascript">
						jQuery(document).ready(function () {
							var enableDisableSubmitBtn = function(){
								var startVal = jQuery('#sf-datepicker').val().trim();
								var disableBtn =  startVal.length === 0;
								jQuery('#sf-subscription-change-date-submit').attr('disabled',disableBtn);
							}

							jQuery('#sf-datepicker').datepicker({
								dateFormat: '<?php echo esc_html( $date_format ); ?>',
								minDate: '1',
								maxDate: '<?php echo absint( $max_date ); ?>',
								onSelect: function(selected) {
									jQuery('#sf-subscription-change-date-submit').datepicker('option','<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( 'tomorrow' ) ) ); ?>', selected);
									enableDisableSubmitBtn();
								}
							});
						});
					</script>
					<form method="post" id="sf-change-date-form">
						<input type="text" id="sf-datepicker" readonly placeholder="<?php echo esc_html__( 'Select a date', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?>" class="input-text datepicker" name="sf-next-date"/>
						<input type="hidden" name="sf-subscription" value="<?php echo esc_attr( $subscription_id ); ?>"/>
						<input type="button" id="sf-subscription-change-date-submit" disabled="disabled" value="<?php echo esc_html__( 'Save', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?>"/>
						<input type="button" id="sf-subscription-change-date-cancel" value="<?php echo esc_html__( 'Cancel', 'self-service-dashboard-for-woocommerce-subscriptions' ); ?>"/>
					</form>
				</div>
				<?php
				$result = ob_get_contents();
				ob_end_clean();
			}

			wp_send_json_success(
				array(
					'html' => $result,
				)
			);
		}

		/**
		 * Change next payment date for the subscription
		 */
		public function sf_change_date_subscription_submit() {
			check_ajax_referer( 'sf-sf-nonce', 'nonce' );

			if ( ! isset( $_POST['data'] ) ) {
				wp_die( esc_html__( 'Invalid data, please try again.', 'self-service-dashboard-for-woocommerce-subscriptions' ) );
			}

			$message = esc_html__( 'Payment date changed.', 'self-service-dashboard-for-woocommerce-subscriptions' );

			$post_parameters = wp_unslash( $_POST );
			parse_str( $post_parameters['data'], $parameters );

			$subscription_id = isset( $parameters['sf-subscription'] ) ? absint( $parameters['sf-subscription'] ) : 0;
			$next_date       = isset( $parameters['sf-next-date'] ) ? esc_attr( $parameters['sf-next-date'] ) : 0;

			if ( empty( $subscription_id ) ) {
				wp_die( esc_html__( 'Subscription ID is invalid.', 'self-service-dashboard-for-woocommerce-subscriptions' ) );
			}

			if ( ! empty( $next_date ) && ! empty( $subscription_id ) ) {
				$subscription = wcs_get_subscription( $subscription_id );

				if ( empty( $subscription ) ) {
					wp_die( esc_html__( 'Subscription is invalid.', 'self-service-dashboard-for-woocommerce-subscriptions' ) );
				}

				$current_next_date     = $subscription->get_date( 'next_payment', 'site' );

				$formatted_pause_date = str_replace( '/', '-', $next_date );
				$next_date            = ! strtotime( $formatted_pause_date ) ? $next_date : $formatted_pause_date;

				$dates['next_payment'] = gmdate( 'Y-m-d H:i:s', strtotime( $next_date ) );
				$subscription->update_dates( $dates );
				$format_date = strtotime( $current_next_date );

				$subscription_note = esc_html__( 'Customer changed Payment date from ' ) . esc_attr( date_i18n( wc_date_format(), $format_date ) );
				$subscription_note .= esc_html__( ' to ' ) . esc_attr( date_i18n( wc_date_format(), strtotime( $next_date ) ) );
				$subscription->add_order_note( $subscription_note );

				$mailer = WC()->mailer();
				/**
				 * Change_payment_date_subscription_notification
				 *
				 * @since 1.0.0
				 */
				do_action( 'change_payment_date_subscription_notification', $subscription_id );
				/**
				 * Sf_change_next_payment_date_for_subscription
				 *
				 * @since 1.0.0
				 */
				do_action( 'sf_change_next_payment_date_for_subscription', $subscription_id, $next_date );
			}

			wp_send_json_success(
				array(
					'html' => sprintf( '<div class="ssd-display-notification">%s</div>', $message ),
				)
			);
		}

		/**
		 * Display the button
		 *
		 * @param string $date_to_display Date to display.
		 * @param string $date_type Display type.
		 * @param object $subscription Subscription object.
		 *
		 * @return mixed|string
		 */
		public function sf_display_button( $date_to_display, $date_type, $subscription ) {
			if ( is_admin() ) {
				return $date_to_display;
			}

			$get_the_date = $subscription->get_meta( 'ssd_pause_subscription_until' );
			if ( $get_the_date && $subscription->has_status( 'on-hold' ) ) {
				return $date_to_display;
			}

			global $wp;

			if ( 'next_payment' === $date_type && $subscription->payment_method_supports( 'subscription_date_changes' ) && array_key_exists( 'view-subscription', $wp->query_vars ) ) {
				$next_payment_date_text = esc_html__( 'Change payment date', 'self-service-dashboard-for-woocommerce-subscriptions' );
				/**
				 * Sf_payment_date_text
				 *
				 * @since 1.0.0
				 */
				$next_payment_date_text = esc_html( apply_filters( 'sf_payment_date_text', $next_payment_date_text ) );
				$next_payment_date_link = sprintf( ' <a href="#" data-id="%d" class="sf-change-payment-date button">%s</a>', $subscription->get_id(), $next_payment_date_text );

				if ( 'yes' === get_option( 'settings_tabs_hide_change_payment_date' ) ) {
					/**
					 * Sf_change_next_payment_date
					 *
					 * @since 1.0.0
					 */
					$date_to_display .= apply_filters( 'sf_change_next_payment_date', $next_payment_date_link, $subscription );
				}
			}

			return $date_to_display;
		}

		/**
		 * Add exception for the button
		 *
		 * @param string $safe_text The string escaped.
		 * @param string $text The original text.
		 *
		 * @return mixed
		 */
		public function sf_esc_html( $safe_text, $text ) {
			if ( is_string( $text ) && strpos( $text, 'sf-change-payment-date' ) !== false ) {
				return $text;
			}

			return $safe_text;
		}

		/**
		 * Change Payment Date Email
		 *
		 * @param array $email_classes Woo Classes.
		 *
		 * @return mixed
		 */
		public function sf_add_active_woocommerce_email( $email_classes ) {
			require_once( 'class-wc-change-payment-date-email.php' );

			$email_classes['WC_Change_Payment_Date_Email'] = new WC_Change_Payment_Date_Email();

			return $email_classes;
		}
	}
}

new Change_Payment_Date();
