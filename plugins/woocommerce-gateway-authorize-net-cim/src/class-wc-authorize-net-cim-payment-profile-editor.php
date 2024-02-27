<?php
/**
 * WooCommerce Authorize.Net Gateway
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Authorize.Net Gateway to newer
 * versions in the future. If you wish to customize WooCommerce Authorize.Net Gateway for your
 * needs please refer to http://docs.woocommerce.com/document/authorize-net-cim/
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2023, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_12_0 as Framework;

/**
 * The token editor.
 *
 * @since 2.2.0
 */
class WC_Authorize_Net_CIM_Payment_Profile_Editor extends Framework\SV_WC_Payment_Gateway_Admin_Payment_Token_Editor {


	/**
	 * Build the token when saving the editor.
	 *
	 * @since 2.2.0
	 * @param int $user_id the user ID
	 * @param string $token_id the token ID
	 * @param array $data the token data
	 * @return \WC_Authorize_Net_CIM_Payment_Profile the payment profile object
	 */
	protected function build_token( $user_id, $token_id, $data ) {

		$user = get_userdata( $user_id );

		$data['customer_profile_id'] = $this->get_gateway()->get_customer_id( $user_id );

		$data['billing'] = array(
			'first_name' => $user->billing_first_name,
			'last_name'  => $user->billing_last_name,
			'company'    => $user->billing_company,
			'address'    => trim( $user->billing_address_1 . ' ' . $user->billing_address_2 ),
			'city'       => $user->billing_city,
			'state'      => $user->billing_state,
			'postcode'   => $user->billing_postcode,
			'country'    => $user->billing_country,
			'phone'      => $user->billing_phone,
		);

		return parent::build_token( $user_id, $token_id, $data );
	}


	/**
	 * Get the editor fields.
	 *
	 * @since 2.2.0
	 * @return array
	 */
	protected function get_fields( $type = '' ) {

		$fields = parent::get_fields();

		$fields['id']['label'] = __( 'Profile ID', 'woocommerce-gateway-authorize-net-cim' );

		$fields['last_four']['editable'] = false;

		return $fields;
	}


}
