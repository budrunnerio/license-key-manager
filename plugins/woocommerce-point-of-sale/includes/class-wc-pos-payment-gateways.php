<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WC_POS_Payment_Gateways {

	public static function init() {
		add_action( 'pos_admin_enqueue_scripts', [ __CLASS__, 'admin_enqueue_scripts' ] );
		add_action( 'option_woocommerce_securesubmit_settings', [ __CLASS__, 'woocommerce_securesubmit_settings' ], 100, 1 );
	}

	public static function woocommerce_securesubmit_settings( $value ) {
		if ( wc_pos_is_register_page() ) {
			$value['use_iframes'] = 'no';
		}
		return $value;
	}

	public static function pos_enqueue_scripts( $sctipts ) {
		if ( class_exists( 'WooCommerceSecureSubmitGateway' ) ) {
			$sctipts['WooCommerceSecureSubmitGateway'] = WC_POS()->plugin_url() . '/assets/js/register/subscriptions.js';
		}
		return $sctipts;
	}
	public static function admin_enqueue_scripts( $sctipts ) {
		if ( class_exists( 'WC_Gateway_SecureSubmit' ) ) {
			$ss = new WC_Gateway_SecureSubmit();
			$ss->payment_scripts();
		}
	}
}

WC_POS_Payment_Gateways::init();
