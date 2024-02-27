<?php
/**
 * Paymentsense for POS.
 *
 * @package WooCommerce_Point_Of_Sale/Gateways
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Paymentsense.
 */
class WC_POS_Paymentsense {

	/**
	 * Init.
	 */
	public static function init() {
		self::includes();

		add_filter( 'wc_pos_app_data', [ __CLASS__, 'params' ] );
		add_filter( 'wc_pos_register_options_tabs', [ __CLASS__, 'register_options_tabs' ] );
		add_action( 'wc_pos_register_options_panels', [ __CLASS__, 'register_options_panels' ], 10, 2 );
		add_action( 'wc_pos_register_options_save', [ __CLASS__, 'save_register_data' ], 10, 2 );
		add_filter( 'wc_pos_register_data', [ __CLASS__, 'add_register_data' ] );
	}

	/**
	 * Includes.
	 */
	public static function includes() {
		include_once 'includes/class-wc-pos-gateway-paymentsense-api.php';
		include_once 'includes/class-wc-pos-gateway-paymentsense.php';
	}

	/**
	 * Add gateway params.
	 *
	 * @param array $params
	 * @return array
	 */
	public static function params( $params ) {
		$paymentsense_data = get_option( 'woocommerce_pos_paymentsense_settings', [] );

		$params['pos']['paymentsense_host_address']      = isset( $paymentsense_data['host_address'] ) ? set_url_scheme( esc_url( $paymentsense_data['host_address'] ), 'https' ) : '';
		$params['pos']['paymentsense_api_key']           = isset( $paymentsense_data['api_key'] ) ? $paymentsense_data['api_key'] : '';
		$params['pos']['paymentsense_installer_id']      = isset( $paymentsense_data['installer_id'] ) ? $paymentsense_data['installer_id'] : '';
		$params['pos']['paymentsense_software_house_id'] = isset( $paymentsense_data['software_house_id'] ) ? $paymentsense_data['software_house_id'] : '';

		return $params;
	}

	/**
	 * Add Paymentsense tab to the register data meta box.
	 *
	 * @param array $tabs
	 * @return array
	 */
	public static function register_options_tabs( $tabs ) {
		$paymentsense_data = get_option( 'woocommerce_pos_paymentsense_settings', [] );

		if ( isset( $paymentsense_data['enabled'] ) && 'yes' === $paymentsense_data['enabled'] ) {
			$tabs['paymentsense'] = [
				'label'  => __( 'Paymentsense', 'woocommerce-point-of-sale' ),
				'target' => 'paymentsense_register_options',
				'class'  => '',
			];
		}

		return $tabs;
	}

	/**
	 * Display the Paymentsense tab content.
	 *
	 * @param int             $thepostid
	 * @param WC_POS_Register $register
	 */
	public static function register_options_panels( $thepostid, $register_object ) {
		include_once 'includes/views/html-admin-register-options-paymentsense.php';
	}

	/**
	 * On save register data.
	 *
	 * @param int             $post_id
	 * @param WC_POS_Register $register
	 */
	public static function save_register_data( $post_id, $register ) {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'update-post_' . $post_id ) ) {
			wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'woocommerce-point-of-sale' ) );
		}

		$terminal = ! empty( $_POST['paymentsense_terminal'] ) ? wc_clean( wp_unslash( $_POST['paymentsense_terminal'] ) ) : 'none';
		update_post_meta( $post_id, 'paymentsense_terminal', $terminal );
	}

	/**
	 * Add Paymentsense data to register data.
	 *
	 * @param array $register_data
	 * @return array
	 */
	public static function add_register_data( $register_data ) {
		$register_data['paymentsense_terminal'] = get_post_meta( $register_data['id'], 'paymentsense_terminal', true );

		return $register_data;
	}

}

WC_POS_Paymentsense::init();
