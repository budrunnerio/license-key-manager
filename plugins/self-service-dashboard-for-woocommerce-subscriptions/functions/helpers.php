<?php
/**
 * Helpers
 *
 * @package Self-service Dashboard for WooCommerce Subscriptions
 * @since 1.0.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

add_action( 'admin_enqueue_scripts', 'ssd_admin_scripts' );
/**
 * Register admin scripts
 */
function ssd_admin_scripts() {
	$plugin_data = get_plugin_data( SFORCE_PLUGIN_FILE );
	wp_enqueue_script( 'wpr_sd_admin', SSD_FUNC_URL . 'assets/js/admin.js', array( 'jquery' ), $plugin_data['Version'], true );
	wp_enqueue_style( 'ssd-admin', SSD_FUNC_URL . 'assets/css/ssd-admin.css', array(), $plugin_data['Version'] );
	wp_enqueue_style( 'fontawesome', 'https://use.fontawesome.com/releases/v6.0.0/css/all.css', '', $plugin_data['Version'] );
	wp_localize_script(
		'wpr_sd_admin',
		'wpr_sd_ajs',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'wpr-ssd-a-nonce' ),
		)
	);
}

/**
 * Register Order Note increment
 */
function ssd_register_note() {
	$note_no = get_option( 'ssd_note_no' );
	if ( $note_no ) {
		update_option( 'ssd_note_no', absint( $note_no ) + 1 );
	} else {
		update_option( 'ssd_note_no', 1 );
	}
}

if ( defined( 'SSD_IS_PLUGIN' ) && SSD_IS_PLUGIN ) {
	add_action( 'admin_notices', 'ssd_display_review_message' );
	/**
	 * Display the Reviews message
	 */
	function ssd_display_review_message() {
		$activation_date = get_option( 'ssd_activation_date' );
		$current_date    = time();
		if ( $activation_date ) {
			$in_7_days = strtotime( '+7 days', $activation_date );
			$in_30_days = strtotime( '+30 days', $activation_date );
		}

		$hide_notice = get_option( 'ssd_notice_dismiss' );

		if ( ! $hide_notice && ( $activation_date && $current_date >= $in_30_days ) ) {
			$head    = esc_html__( 'Enjoying the Self-service Dashboard?', 'self-service-dashboard-for-woocommerce-subscriptions' );
			$message = esc_html__( 'If you\'re happy with our plugin please help us grow by leaving a 5-star review on the WooCommerce page. If you think it needs work, please help us make it better.', 'self-service-dashboard-for-woocommerce-subscriptions' );

			$class = 'notice notice-info is-dismissible ssd-notice-wrap';

			printf(
				'<div data-dismissible="ssd-notice-review" class="%1$s"><h2>%2$s</h2><p>%3$s</p><p><a href="%4$s" target="_blank" class="button button-primary ssd-dismiss-notice"><i class="fa-solid fa-star"></i> <i class="fa-solid fa-star"></i> <i class="fa-solid fa-star"></i> <i class="fa-solid fa-star"></i> <i class="fa-solid fa-star"></i> %5$s</a> &nbsp; <a href="%6$s" target="_blank" class="button ssd-dismiss-notice">%7$s</a></p></div>',
				esc_attr( $class ),
				esc_html( $head ),
				esc_html( $message ),
				esc_url( 'https://woocommerce.com/products/self-service-dashboard-for-woocommerce-subscriptions/' ),
				esc_html__( 'Rate us', 'self-service-dashboard-for-woocommerce-subscriptions' ),
				esc_url( 'https://woocommerce.com/feature-requests/self-service-dashboard-for-woocommerce-subscriptions/' ),
				esc_html__( 'Submit feature request', 'self-service-dashboard-for-woocommerce-subscriptions' )
			);
		}

		$hide_upsell_notice = get_option( 'ssd_upsell_notice_dismiss' );

		if ( ! class_exists( 'BOS4W_Front_End' ) && ! $hide_upsell_notice && ( $activation_date && $current_date >= $in_30_days ) ) {
			$head = esc_html__( 'Enhance your Subscriptions website with these useful plugins', 'bos4w' );

			$message = '<div class="ssd-message-wrap">';
			$message .= '<div>';
			$message .= '<img src="' . SSD_FUNC_URL . 'assets/images/SF Logo.png" height="50" />';
			$message .= '</div>';
			$message .= '<div>';
			$message .= '<ul>';
			$message .= sprintf( '<li><a href="%s" target="_blank">%s</a> %s</li>', esc_url( 'https://bit.ly/cross-ssd-to-bos' ), esc_html__( 'Buy Once or Subscribe for WooCommerce Subscriptions', 'bos4w' ), esc_html__( ' - Make all your existing products available for purchase on a subscription using this powerful extension for WooCommerce Subscriptions.', 'bos4w' ) );
			$message .= '</ul>';
			$message .= '</div>';
			$message .= '</div>';

			$class = 'notice notice-info is-dismissible ssd-notice-upsell-wrap';

			printf(
				'<div data-dismissible="ssd-notice-addons" class="%1$s"><h2>%2$s</h2>%3$s</div>',
				esc_attr( $class ),
				esc_html( $head ),
				wp_kses( $message, ssd_allowed_notice_tags() )
			);
		}
	}
}

/**
 * Allowed tags for notice
 *
 * @return array[][]
 */
function ssd_allowed_notice_tags() {
	return array(
		'a' => array(
			'class' => array(),
			'href'  => array(),
			'rel'   => array(),
			'title' => array(),
			'target' => array(),
		),
		'div' => array(
			'class' => array(),
			'title' => array(),
			'style' => array(),
		),
		'img' => array(
			'alt'    => array(),
			'class'  => array(),
			'height' => array(),
			'src'    => array(),
			'width'  => array(),
		),
		'li' => array(
			'class' => array(),
		),
		'ul' => array(
			'class' => array(),
		),
	);
}

add_action( 'wp_ajax_ssd_dismiss_notice', 'ssd_do_dismiss' );
/**
 * Do the notice dismiss
 */
function ssd_do_dismiss() {
	check_ajax_referer( 'wpr-ssd-a-nonce', 'nonce' );

	update_option( 'ssd_notice_dismiss', 1 );
}

/**
 * Check if BOS plugin is active
 *
 * @return bool
 */
function ssd_is_bos_active() {
	return class_exists( 'BOS4W\Load_Packages' );
}

add_filter( 'wcs_can_item_be_removed', 'ssd_can_be_removed', 10, 3 );
/**
 * Can the item be removed
 *
 * @param bool   $remove Can it be removed.
 * @param object $item Item object.
 * @param object $subscription Subscription object.
 *
 * @return mixed
 */
function ssd_can_be_removed( $remove, $item, $subscription ) {
	if ( function_exists( 'wc_cp_is_composited_order_item' ) && wc_cp_is_composited_order_item( $item, $subscription ) ) {
		$remove = false;
	}

	if ( function_exists( 'wc_pb_is_bundled_order_item' ) && wc_pb_is_bundled_order_item( $item, $subscription ) ) {
		$remove = false;
	}

	$counter = 0;
	foreach ( $subscription->get_items() as $item_id => $item_obj ) {
		$counter ++;
		if ( function_exists( 'wc_cp_is_composited_order_item' ) && wc_cp_is_composited_order_item( $item_obj, $subscription ) ) {
			$counter --;
		}

		if ( function_exists( 'wc_pb_is_bundled_order_item' ) && wc_pb_is_bundled_order_item( $item_obj, $subscription ) ) {
			$counter --;
		}
	}

	if ( $counter < 2 ) {
		$remove = false;
	}

	return $remove;
}

/**
 * Insert array after array key
 *
 * @param array  $array Array.
 * @param string $key Array key.
 * @param array  $new New array.
 *
 * @return array
 */
function ssd_array_insert_after( array $array, $key, array $new ) {
	$keys  = array_keys( $array );
	$index = array_search( $key, $keys );
	$pos   = false === $index ? count( $array ) : $index + 1;

	return array_merge( array_slice( $array, 0, $pos ), $new, array_slice( $array, $pos ) );
}

/**
 * Display message
 */
add_filter(
	'settings_tabs_ssd_settings',
	function ( $settings ) {
		if ( defined( 'SSD_IS_PLUGIN' ) && SSD_IS_PLUGIN ) {
			$message_to_display = sprintf(
			/* translators: %1$s documentaion and %2$s hook list */
				wp_kses( 'For more information about our plugin please see the <a href="%1$s" target="_blank">Documentation</a>, including the <a href="%2$s" target="_blank">Hooks and Filters</a>.', ssd_allowed_notice_tags() ),
				esc_url( 'https://woocommerce.com/document/self-service-dashboard-for-woocommerce-subscriptions/' ),
				esc_url( 'https://woocommerce.com/document/self-service-dashboard-for-woocommerce-subscriptions-hooks/' )
			);
		} else {
			$message_to_display = sprintf(
			/* translators: %1$s documentaion and %2$s hook list */
				wp_kses( 'For more information about our plugin please see the <a href="%1$s" target="_blank">Documentation</a>, including the <a href="%2$s" target="_blank">Hooks and Filters</a>.', ssd_allowed_notice_tags() ),
				esc_url( 'https://help.subscriptionforce.com/' ),
				esc_url( 'https://help.subscriptionforce.com/article/16-hooks' )
			);
		}

		$settings['section_documentation_title']       = array(
			'name' => __( 'Documentation & Filters', 'self-service-dashboard-for-woocommerce-subscriptions' ),
			'type' => 'title',
			'desc' => '<h3>' . $message_to_display . '</h3>',
			'id'   => 'settings_tabs_ssd_settings_tab_section_title',
		);

		return $settings;
	},
	50,
	1
);

/**
 * Compatible with HPOS
 */
add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', 'self-service-dashboard-for-woocommerce-subscriptions/self-service-dashboard-for-woocommerce-subscriptions.php', true );
		}
	}
);
