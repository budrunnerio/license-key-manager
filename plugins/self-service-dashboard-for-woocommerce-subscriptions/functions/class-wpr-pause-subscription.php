<?php
/**
 * Pause Subscription
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

if ( ! class_exists( 'WPR_Pause_Subscription' ) ) {
	/**
	 * Class WPR_Pause_Subscription
	 */
	class WPR_Pause_Subscription {
		/**
		 * WPR_Pause_Subscription constructor.
		 */
		public function __construct() {
			add_action( 'wp', array( $this, 'ssd_activate_cron' ) );
			add_action( 'ssd_subscription_paused', array( $this, 'ssd_reactivate_subscription' ) );
			add_action( 'woocommerce_subscription_details_table', array( $this, 'wpr_subscription_details_template_loaded' ), 10, 1 );
			add_action( 'woocommerce_customer_changed_subscription_to_active', array( $this, 'ssd_reactivate_subscription_manually' ), 10, 1 );
			add_action( 'woocommerce_subscription_status_active', array( $this, 'ssd_reactivate_subscription_manually' ), 10, 1 );
			add_action( 'woocommerce_customer_changed_subscription_to_active', array( $this, 'ssd_reactivate_subscription_active_manually' ), 10, 1 );
			add_filter( 'woocommerce_email_classes', array( $this, 'ssd_add_active_woocommerce_email' ), 10, 1 );
			add_shortcode( 'ecommtools_get_renewal_date', array( $this, 'ssd_display_paused_date' ) );
		}

		/**
		 * Register the Active Order Email
		 *
		 * @param array $email_classes Woo Classes.
		 *
		 * @return mixed
		 */
		public function ssd_add_active_woocommerce_email( $email_classes ) {
			require_once( SSD_FUNC_PATH . '/class-wc-active-order-email.php' );

			$email_classes['WC_Active_Order_Email'] = new WC_Active_Order_Email();

			return $email_classes;
		}

		/**
		 * Display the paused message
		 *
		 * @param object $subscription Represent Subscription Object.
		 */
		public function wpr_subscription_details_template_loaded( $subscription ) {
			if ( 'on-hold' === $subscription->get_status() ) {
				$get_the_date = $subscription->get_meta( 'ssd_pause_subscription_until' );

				if ( ! $get_the_date ) {
					return;
				}

				add_filter(
					'woocommerce_subscription_status_name',
					function ( $status_name, $status ) use ( $get_the_date ) {
						$to_date = date_i18n( wc_date_format(), $get_the_date );

						return sprintf( '%s %s', esc_html__( 'On hold until', 'self-service-dashboard-for-woocommerce-subscriptions' ), esc_attr( $to_date ) );
					},
					10,
					2
				);
			}
		}

		/**
		 * Triggered on manually reactivate the subscription
		 *
		 * @param object $subscription Represent Subscription Object.
		 */
		public function ssd_reactivate_subscription_manually( $subscription ) {
			$subscription = wcs_get_subscription( $subscription->get_id() );
			$subscription->delete_meta_data( 'ssd_pause_subscription_until' );
			$subscription->save();
		}

		/**
		 * Triggered on manually reactivate the subscription
		 *
		 * @param object $subscription Represent Subscription Object.
		 */
		public function ssd_reactivate_subscription_active_manually( $subscription ) {
			WC()->mailer();
			if ( $subscription->get_id() ) {
				/**
				 * Active subscription notification
				 *
				 * @param int $order_id Order ID
				 *
				 * @since 1.0.0
				 */
				do_action( 'active_subscription_notification', $subscription->get_id() );
			}
		}

		/**
		 * Create the cron event
		 */
		public function ssd_activate_cron() {
			if ( ! wp_next_scheduled( 'ssd_subscription_paused' ) ) {
				wp_schedule_event( current_time( 'timestamp' ), 'daily', 'ssd_subscription_paused' );
			}
		}

		/**
		 * Reactivate subscription on wp-cron trigger
		 */
		public function ssd_reactivate_subscription() {
			$timestamp    = strtotime( gmdate( 'Y-m-d', time() ) . '00:00:00' );
			$begin_of_day = strtotime( 'today', $timestamp );
			$end_of_day   = strtotime( 'tomorrow', $begin_of_day ) - 1;

			$args = array(
				'status'    => array( 'on-hold' ),
				'limit' => - 1,
				'meta_query'     => array(
					array(
						'key'     => 'ssd_pause_subscription_until',
						'value'   => $end_of_day,
						'compare' => '<=',
					),
				),
			);

			$paused_subscriptions = wcs_get_subscriptions( $args );

			if ( $paused_subscriptions ) {
				WC()->mailer();
				foreach ( $paused_subscriptions as $paused_subscription ) {
					$subscription = wcs_get_subscription( $paused_subscription->ID );

					if ( empty( $subscription ) ) {
						wp_die( esc_html__( 'Subscription is invalid.', 'self-service-dashboard-for-woocommerce-subscriptions' ) );
					}

					// Extra check, just in case.
					$get_the_date = $subscription->get_meta( 'ssd_pause_subscription_until' );
					if ( ! $get_the_date ) {
						continue;
					}
					$subscription = wcs_get_subscription( $subscription->get_id() );
					$subscription->delete_meta_data( 'ssd_pause_subscription_until' );
					$subscription->save();

					if ( $subscription->has_status( 'active' ) ) {
						continue;
					}

					$subscription->update_status( 'active' );

					if ( $subscription->get_id() ) {
						/**
						 * Active subscription notification
						 *
						 * @param int $order_id Order ID
						 *
						 * @since 1.0.0
						 */
						do_action( 'active_subscription_notification', $subscription->get_id() );
					}
				}
			}

			/**
			 * Remove older metas
			 */
			$this->ssd_remove_pause_meta_on_active();
		}

		/**
		 * Display the Paused until date
		 *
		 * @param array $atts Attributes.
		 *
		 * @return mixed|void
		 */
		public function ssd_display_paused_date( $atts ) {
			$atts = shortcode_atts(
				array(
					'subscription_id' => '',
				),
				$atts,
				'woocommerce_email_classes'
			);

			$subscription_id = get_query_var( 'view-subscription' );
			if ( $atts['subscription_id'] ) {
				$subscription_id = $atts['subscription_id'];
			}

			if ( ! $subscription_id ) {
				return;
			}

			$subscription = wcs_get_subscription( $subscription_id );

			if ( ! $subscription->has_status( 'on-hold' ) ) {
				return;
			}

			$subscription_paused = $subscription->get_meta( 'ssd_pause_subscription_until' );
			/**
			 * Paused date
			 *
			 * @param string $subscription_paused Date paused.
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'ssd_paused_date_format', date_i18n( wc_date_format(), $subscription_paused ) );
		}

		/**
		 * Remove older active pause meta
		 */
		public function ssd_remove_pause_meta_on_active() {
			$timestamp    = strtotime( gmdate( 'Y-m-d', time() ) . '00:00:00' );
			$begin_of_day = strtotime( 'today', $timestamp );
			$end_of_day   = strtotime( 'tomorrow', $begin_of_day ) - 1;

			$args = array(
				'status'    => array( 'active' ),
				'limit' => - 1,
				'meta_query'     => array(
					array(
						'key'     => 'ssd_pause_subscription_until',
						'value'   => $end_of_day,
						'compare' => '<=',
					),
				),
			);

			$paused_subscriptions = wcs_get_subscriptions( $args );

			if ( $paused_subscriptions ) {
				foreach ( $paused_subscriptions as $subscription ) {
					$subscription->delete_meta_data( 'ssd_pause_subscription_until' );
					$subscription->save();
				}
			}
		}
	}
}
new WPR_Pause_Subscription();
