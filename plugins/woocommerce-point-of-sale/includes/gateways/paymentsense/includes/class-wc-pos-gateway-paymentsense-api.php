<?php
/**
 * Paymentsense API
 *
 * @package WooCommerce_Point_Of_Sale/Gateways
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Gateway_Paymentsense_API.
 */
class WC_POS_Gateway_Paymentsense_API {

	public $base_url;
	public $settings;
	public $username;
	public $key;
	public $installer_id;
	public $software_house_id;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_settings();

		$this->base_url          = $this->settings['host_address'];
		$this->key               = $this->settings['api_key'];
		$this->installer_id      = $this->settings['installer_id'];
		$this->software_house_id = $this->settings['software_house_id'];
		$this->username          = 'user';
	}

	/**
	 * Returns API URL.
	 *
	 * @return string
	 */
	protected function get_base_url() {
		$url = esc_url( $this->base_url );
		if ( empty( $url ) ) {
			return '';
		}

		$parsed = parse_url( $url );

		if ( 'http' === $parsed['scheme'] ) {
			$url = str_replace( 'http', 'https', $url );
		}

		return trailingslashit( $url );
	}

	/**
	 * Init settings.
	 *
	 * @return array
	 */
	protected function init_settings() {
		$this->settings = get_option(
			'woocommerce_pos_paymentsense_settings',
			[
				'host_address'      => '',
				'api_key'           => '',
				'installer_id'      => '',
				'software_house_id' => '',
			]
		);

		return $this->settings;
	}

	/**
	 * Returns request headers.
	 *
	 * @return array
	 */
	protected function get_headers() {
		return [
			'Content-Type'      => 'application/json',
			'Accept'            => 'application/connect.v2+json',
			'Authorization'     => 'Basic ' . base64_encode( $this->username . ':' . $this->key ),
			'Software-House-Id' => $this->software_house_id,
			'Installer-Id'      => $this->installer_id,
		];
	}

	/**
	 * Make a request.
	 *
	 * @param array $data
	 * @return array|WP_Error
	 */
	protected function request( $url, $data = [] ) {
		$data['headers'] = $this->get_headers();
		return wp_remote_request( $url, $data );
	}

	/**
	 * PAC terminals.
	 *
	 * @param int $single
	 * @return array|WP_Error
	 */
	public function pac_terminals( $single = 0 ) {
		$url = $this->get_base_url() . 'pac/terminals';
		if ( ! empty( $single ) ) {
			$url .= '/' . $single;
		}

		error_log( json_encode( $this->request( $url, [] ) ) );

		return $this->request( $url, [] );
	}

	public function pac_terminals_response( $single = 0 ) {
		$response = $this->pac_terminals( $single );
		if ( $response instanceof WP_Error ) {
			return [];
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * PAC transactions.
	 *
	 * @param string $tid
	 * @param string $single
	 * @param array  $data
	 *
	 * @return array|WP_Error
	 */
	public function pac_transactions( $tid = '0', $single = '', $data = [] ) {
		$url = $this->get_base_url() . "pac/terminals/$tid/transactions";
		if ( ! empty( $single ) ) {
			$url .= '/' . $single;
		}

		return $this->request( $url, $data );
	}

	/**
	 * PAC reports.
	 *
	 * @param string $tid
	 * @param int    $single
	 * @param array  $data
	 *
	 * @return array|WP_Error
	 */
	public function pac_reports( $tid = '0', $single = 0, $data = [] ) {
		$url = $this->get_base_url() . "pac/terminals/$tid/reports";
		if ( ! empty( $single ) ) {
			$url .= '/' . $single;
		}

		return $this->request( $url, $data );
	}

	/**
	 * Returns first error message.
	 */
	public function get_first_error_message( $response ) {
		$message = __( 'An error occurred', 'woocommerce-point-of-sale' );

		if ( ! isset( $response['userMessage'] ) ) {
			return $message;
		}

		$message = $response['userMessage'];

		return $message;
	}
}
