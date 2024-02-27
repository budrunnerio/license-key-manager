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

namespace SkyVerge\WooCommerce\Authorize_Net\CIM;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_12_0 as Framework;

/**
 * The plugin lifecycle handler.
 *
 * @since 2.10.2
 */
class Lifecycle extends Framework\Plugin\Lifecycle {


	/**
	 * Lifecycle constructor.
	 *
	 * @param \WC_Authorize_Net_CIM $plugin plugin instance
	 */
	public function __construct( \WC_Authorize_Net_CIM $plugin ) {

		// known upgrade versions
		$this->upgrade_versions = [
			'2.0.0',
		];

		parent::__construct( $plugin );
	}


	/**
	 * Adds the action & filter hooks.
	 *
	 * @since 3.0.0
	 */
	protected function add_hooks() {

		parent::add_hooks();

		// deactivate legacy plugins
		add_action( 'admin_init', function() {

			$legacy_plugins = [
				'woocommerce-gateway-authorize-net-aim',
				'woocommerce-gateway-authorize-net-sim',
				'woocommerce-gateway-authorize-net-dpm',
			];

			foreach ( $legacy_plugins as $slug ) {

				if ( is_plugin_active( "{$slug}/{$slug}.php" ) ) {
					deactivate_plugins( "{$slug}/{$slug}.php" );
				}
			}
		} );
	}


	/**
	 * Upgrades to v2.0.0
	 *
	 * The routine itself has existed since v2.0.0 - this method is new due to the new framework upgrade handling.
	 *
	 * @since 3.0.0
	 */
	protected function upgrade_to_2_0_0() {
		global $wpdb;

		$using_hpos = Framework\SV_WC_Plugin_Compatibility::is_hpos_enabled();

		/** Upgrade settings */

		$old_cc_settings        = get_option( 'woocommerce_authorize_net_cim_settings' );
		$old_echeck_settings    = get_option( 'woocommerce_authorize_net_cim_echeck_settings' );

		if ( $old_cc_settings ) {

			// prior to 2.0.0, there was no settings for tokenization (always on) and enable_customer_decline_messages.
			// eCheck settings were inherited from the credit card gateway by default

			// credit card
			$new_cc_settings = array(
				'enabled'                          => ( isset( $old_cc_settings['enabled'] ) && 'yes' === $old_cc_settings['enabled'] ) ? 'yes' : 'no',
				'title'                            => ( ! empty( $old_cc_settings['title'] ) ) ? $old_cc_settings['title'] : 'Credit Card',
				'description'                      => ( ! empty( $old_cc_settings['description'] ) ) ? $old_cc_settings['description'] : 'Pay securely using your credit card.',
				'enable_csc'                       => ( isset( $old_cc_settings['require_cvv'] ) && 'yes' === $old_cc_settings['require_cvv'] ) ? 'yes' : 'no',
				'transaction_type'                 => ( isset( $old_cc_settings['transaction_type'] ) && 'auth_capture' === $old_cc_settings['transaction_type'] ) ? 'charge' : 'authorization',
				'card_types'                       => ( ! empty( $old_cc_settings['card_types'] ) ) ? $old_cc_settings['card_types'] : array( 'VISA', 'MC', 'AMEX', 'DISC' ),
				'tokenization'                     => 'yes',
				'environment'                      => ( isset( $old_cc_settings['test_mode'] ) && 'yes' === $old_cc_settings['test_mode'] ) ? 'test' : 'production',
				'inherit_settings'                 => 'no',
				'api_login_id'                     => ( ! empty( $old_cc_settings['api_login_id'] ) ) ? $old_cc_settings['api_login_id'] : '',
				'api_transaction_key'              => ( ! empty( $old_cc_settings['api_transaction_key'] ) ) ? $old_cc_settings['api_transaction_key'] : '',
				'test_api_login_id'                => ( ! empty( $old_cc_settings['test_api_login_id'] ) ) ? $old_cc_settings['test_api_login_id'] : '',
				'test_api_transaction_key'         => ( ! empty( $old_cc_settings['test_api_transaction_key'] ) ) ? $old_cc_settings['test_api_transaction_key'] : '',
				'enable_customer_decline_messages' => 'no',
				'debug_mode'                       => ( ! empty( $old_cc_settings['debug_mode'] ) ) ? $old_cc_settings['debug_mode'] : 'off',
			);

			// eCheck
			$new_echeck_settings = array(
				'enabled'                          => ( isset( $old_echeck_settings['enabled'] ) && 'yes' === $old_echeck_settings['enabled'] ) ? 'yes' : 'no',
				'title'                            => ( ! empty( $old_echeck_settings['title'] ) ) ? $old_echeck_settings['title'] : 'eCheck',
				'description'                      => ( ! empty( $old_echeck_settings['description'] ) ) ? $old_echeck_settings['description'] : 'Pay securely using your checking account.',
				'tokenization'                     => 'yes',
				'environment'                      => $new_cc_settings['environment'],
				'inherit_settings'                 => 'yes',
				'api_login_id'                     => '',
				'api_transaction_key'              => '',
				'test_api_login_id'                => '',
				'test_api_transaction_key'         => '',
				'enable_customer_decline_messages' => 'no',
				'debug_mode'                       => $new_cc_settings['debug_mode'],
			);

			// save new settings, remove old ones
			update_option( 'woocommerce_authorize_net_cim_credit_card_settings', $new_cc_settings );
			update_option( 'woocommerce_authorize_net_cim_echeck_settings', $new_echeck_settings );
			delete_option( 'woocommerce_authorize_net_cim_settings' );

			$this->get_plugin()->log( 'Settings upgraded.' );
		}


		/** Update meta key for customer profile ID and shipping profile ID */

		// old key: _wc_authorize_net_cim_profile_id
		// new key: wc_authorize_net_cim_customer_profile_id
		// note that we don't know on a per-user basis what environment the customer ID was set in, so we assume production, just to be safe
		$rows = $wpdb->update( $wpdb->usermeta, array( 'meta_key' => 'wc_authorize_net_cim_customer_profile_id' ), array( 'meta_key' => '_wc_authorize_net_cim_profile_id' ) );

		$this->get_plugin()->log( sprintf( '%d users updated for customer profile ID.', $rows ) );

		// old key: _wc_authorize_net_cim_shipping_profile_id
		// new key: wc_authorize_net_cim_shipping_address_id
		$rows = $wpdb->update( $wpdb->usermeta, array( 'meta_key' => 'wc_authorize_net_cim_shipping_address_id' ), array( 'meta_key' => '_wc_authorize_net_cim_shipping_profile_id' ) );

		$this->get_plugin()->log( sprintf( '%d users updated for shipping address ID', $rows ) );


		/** Update meta values for order payment method & recurring payment method */

		// meta key: _payment_method
		// old value: authorize_net_cim
		// new value: authorize_net_cim_credit_card
		// note that the eCheck method has not changed from 1.x to 2.x
		$rows = $wpdb->update( $wpdb->postmeta, array( 'meta_value' => 'authorize_net_cim_credit_card' ), array( 'meta_key' => '_payment_method', 'meta_value' => 'authorize_net_cim' ) );

		$this->get_plugin()->log( sprintf( '%d orders updated for payment method meta', $rows ) );

		if ( $using_hpos ) {

			$rows = $wpdb->update( $wpdb->prefix . 'wc_orders_meta', [ 'meta_value' => 'authorize_net_cim_credit_card' ], [ 'meta_key' => '_payment_method', 'meta_value' => 'authorize_net_cim' ] );

			$this->get_plugin()->log( sprintf( '%d orders updated for payment method meta (HPOS)', $rows ) );
		}

		// meta key: _recurring_payment_method
		// old value: authorize_net_cim
		// new value: authorize_net_cim_credit_card
		$rows = $wpdb->update( $wpdb->postmeta, array( 'meta_value' => 'authorize_net_cim_credit_card' ), array( 'meta_key' => '_recurring_payment_method', 'meta_value' => 'authorize_net_cim' ) );

		$this->get_plugin()->log( sprintf( '%d orders updated for recurring payment method meta', $rows ) );

		if ( $using_hpos ) {

			$rows = $wpdb->update( $wpdb->prefix . 'wc_orders_meta', [ 'meta_value' => 'authorize_net_cim_credit_card' ], [ 'meta_key' => '_recurring_payment_method', 'meta_value' => 'authorize_net_cim' ] );

			$this->get_plugin()->log( sprintf( '%d orders updated for recurring payment method meta (HPOS)', $rows ) );
		}


		/** Convert payment profiles stored in legacy format to framework payment token format */

		$this->get_plugin()->log( 'Starting payment profile upgrade.' );

		$user_ids = $wpdb->get_col( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '_wc_authorize_net_cim_payment_profiles'" );

		if ( $user_ids ) {

			// iterate through each user with a payment profile
			foreach ( $user_ids as $user_id ) {

				$customer_profile_id = get_user_meta( $user_id, 'wc_authorize_net_cim_customer_profile_id', true );

				$payment_profiles = get_user_meta( $user_id, '_wc_authorize_net_cim_payment_profiles', true );

				$cc_tokens = $echeck_tokens = array();

				// iterate through each payment profile
				foreach ( $payment_profiles as $profile_id => $profile ) {

					// bail if corrupted
					if ( ! $profile_id || empty( $profile['type'] ) ) {
						continue;
					}

					// parse expiry date
					if ( ! empty( $profile['exp_date'] ) && Framework\SV_WC_Helper::str_exists( $profile['exp_date'], '/' ) ) {
						list( $exp_month, $exp_year ) = explode( '/', $profile['exp_date'] );
					} else {
						$exp_month = $exp_year = '';
					}

					if ( 'Bank Account' === $profile['type'] ) {

						// eCheck tokens
						$echeck_tokens[ $profile_id ] = array(
							'type'                => 'echeck',
							'last_four'           => ! empty( $profile['last_four'] ) ? $profile['last_four'] : '',
							'customer_profile_id' => $customer_profile_id,
							'billing_hash'        => '',
							'payment_hash'        => '',
							'default'             => ( ! empty( $profile['active'] ) && $profile['active'] ),
							'exp_month'           => $exp_month,
							'exp_year'            => $exp_year,
						);

					} else {

						// parse card type
						switch ( $profile['type'] ) {
							case 'Visa':             $card_type = 'visa';   break;
							case 'American Express': $card_type = 'amex';   break;
							case 'MasterCard':       $card_type = 'mc';     break;
							case 'Discover':         $card_type = 'disc';   break;
							case 'Diners Club':      $card_type = 'diners'; break;
							case 'JCB':              $card_type = 'jcb';    break;
							default:                 $card_type = '';
						}

						// credit card tokens
						$cc_tokens[ $profile_id ] = array(
							'type'                => 'credit_card',
							'last_four'           => ! empty( $profile['last_four'] ) ? $profile['last_four'] : '',
							'customer_profile_id' => $customer_profile_id,
							'billing_hash'        => '',
							'payment_hash'        => '',
							'default'             => ( ! empty( $profile['active'] ) && $profile['active'] ),
							'card_type'           => $card_type,
							'exp_month'           => $exp_month,
							'exp_year'            => $exp_year,
						);
					}
				}

				// update credit card tokens
				if ( ! empty( $cc_tokens ) ) {
					update_user_meta( $user_id, '_wc_authorize_net_cim_credit_card_payment_tokens', $cc_tokens );
				}

				// update eCheck tokens
				if ( ! empty( $echeck_tokens ) ) {
					update_user_meta( $user_id, '_wc_authorize_net_cim_echeck_payment_tokens', $echeck_tokens );
				}

				// save the legacy payment profiles in case we need them later
				update_user_meta( $user_id, '_wc_authorize_net_cim_legacy_tokens', $payment_profiles );
				delete_user_meta( $user_id, '_wc_authorize_net_cim_payment_profiles' );

				$this->get_plugin()->log( sprintf( 'Converted payment profile for user ID: %s', $user_id) ) ;
			}
		}

		$this->get_plugin()->log( 'Completed payment profile upgrade.' );
	}


	/**
	 * Performs installation tasks.
	 *
	 * @since 3.0.0
	 */
	protected function install() {

		$migrate_from     = get_option( 'wc_authorize_net_migrate_from', '' );
		$migration_method = "migrate_from_{$migrate_from}";

		// if migrating directly
		if ( is_callable( [ $this, $migration_method ] ) ) {

			// overwrite existing (probably stale) CIM settings
			$this->$migration_method( true );

			delete_option( 'wc_authorize_net_migrate_from' );

		// otherwise, check for installed settings and migrate
		} elseif ( $this->is_aim_installed() ) {

			$this->migrate_from_aim();

		} elseif ( $this->is_sim_installed() ) {

			$this->migrate_from_sim();

		} elseif ( $this->is_dpm_installed() ) {

			$this->migrate_from_dpm();
		}
	}


	/** AIM migration methods *****************************************************************************************/


	/**
	 * Determines if AIM is installed.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	private function is_aim_installed() {

		return ( get_option( 'wc_authorize_net_aim_version', false ) );
	}


	/**
	 * Migrates setting & transaction data from AIM.
	 *
	 * @since 3.0.0-dev
	 *
	 * @param bool $overwrite_existing whether to force overwrite existing CIM settings
	 */
	private function migrate_from_aim( $overwrite_existing = false ) {

		// always force the AIM settings to the inline forms, since that's what existed previously
		$cc_settings     = [ 'form_type' => \WC_Gateway_Authorize_Net_CIM::FEATURE_PAYMENT_FORM_INLINE ];
		$echeck_settings = [ 'form_type' => \WC_Gateway_Authorize_Net_CIM::FEATURE_PAYMENT_FORM_INLINE ];

		// migrate the credit card gateway data
		$this->get_plugin()->log( 'Migrating AIM credit card data' );

		$this->migrate_credit_card_gateway( 'authorize_net_aim', $cc_settings, $overwrite_existing );

		$this->get_plugin()->log( 'Migrated AIM credit card data' );

		// migrate the eCheck gateway data
		$this->get_plugin()->log( 'Migrating AIM eCheck data' );

		$this->migrate_echeck_gateway( 'authorize_net_aim_echeck', $echeck_settings, $overwrite_existing );

		$this->get_plugin()->log( 'Migrated AIM eCheck data' );

		$emulation_enabled = get_option( 'wc_authorize_net_aim_emulation_enabled', false );

		// TODO remove this by March 2022 or by version 4.0.0 {FN 2021-03-24}
		update_option( 'wc_authorize_net_emulation_enabled', $emulation_enabled ? 'yes' : 'no' );

		// flag a successful migration so we can display a notice
		update_option( 'wc_' . $this->get_plugin()->get_id() . '_migrated_from_legacy', 'aim' );

		// store the event for debugging
		$this->add_migrate_event( 'authorize_net_aim', get_option( 'wc_authorize_net_aim_version', '' ) );
	}


	/** SIM migration methods *****************************************************************************************/


	/**
	 * Determines if SIM is installed.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	private function is_sim_installed() {

		return ( get_option( 'wc_authorize_net_sim_version', false ) );
	}


	/**
	 * Migrates setting & transaction data from SIM.
	 *
	 * @since 3.0.0-dev
	 *
	 * @param bool $overwrite_existing whether to force overwrite existing CIM settings
	 */
	private function migrate_from_sim( $overwrite_existing = false ) {

		// always force the SIM settings to the hosted forms, since that's what existed previously
		$cc_settings     = [ 'form_type' => \WC_Gateway_Authorize_Net_CIM::FEATURE_PAYMENT_FORM_LIGHTBOX ];
		$echeck_settings = [ 'form_type' => \WC_Gateway_Authorize_Net_CIM::FEATURE_PAYMENT_FORM_HOSTED ];

		// if the legacy SIM gateway was active
		if ( 'sim' === get_option( 'wc_authorize_net_sim_active_integration' ) ) {

			// migrate the legacy credit card gateway data
			$this->get_plugin()->log( 'Migrating SIM credit card data' );

			$this->migrate_credit_card_gateway( 'authorize_net_sim_credit_card', $cc_settings, $overwrite_existing );

			$this->get_plugin()->log( 'Migrated SIM credit card data' );

			// migrate the legacy eCheck gateway data
			$this->get_plugin()->log( 'Migrating SIM eCheck data' );

			$this->migrate_echeck_gateway( 'authorize_net_sim_echeck', $echeck_settings, $overwrite_existing );

			$this->get_plugin()->log( 'Migrated SIM eCheck data' );

		// otherwise, migrate accept hosted
		} else {

			// migrate the Accept Hosted credit card gateway data
			$this->get_plugin()->log( 'Migrating Accept Hosted credit card data' );

			$this->migrate_credit_card_gateway( 'authorize_net_accept_hosted_credit_card', $cc_settings, $overwrite_existing );

			$this->get_plugin()->log( 'Migrated Accept Hosted credit card data' );

			// migrate the Accept Hosted eCheck gateway data
			$this->get_plugin()->log( 'Migrating Accept Hosted eCheck data' );

			$this->migrate_echeck_gateway( 'authorize_net_accept_hosted_echeck', $echeck_settings, $overwrite_existing );

			$this->get_plugin()->log( 'Migrated Accept Hosted eCheck data' );
		}

		// flag a successful migration so we can display a notice
		update_option( 'wc_' . $this->get_plugin()->get_id() . '_migrated_from_legacy', 'sim' );

		// store the event for debugging
		$this->add_migrate_event( 'authorize_net_sim', get_option( 'wc_authorize_net_sim_version', '' ) );
	}


	/**
	 * Migrates data from a credit card gateway.
	 *
	 * This copies settings, as well as order transaction meta.
	 *
	 * @since 3.0.0
	 *
	 * @param string $old_gateway_id gateway ID migrating from
	 * @param array $force_settings settings to override to a specific value
	 * @param bool $overwrite_existing whether to force overwrite existing CIM settings
	 */
	private function migrate_credit_card_gateway( $old_gateway_id, array $force_settings = [], $overwrite_existing = false ) {

		// don't overwrite existing CIM settings
		if ( ! $overwrite_existing && ! empty( $this->get_credit_card_settings() ) ) {

			$this->get_plugin()->log( 'Existing CIM credit card settings found. Skipping settings migration.' );

		} else {

			$settings = get_option( "woocommerce_{$old_gateway_id}_settings" );

			if ( ! empty( $settings ) && is_array( $settings ) ) {

				$settings = array_merge( $settings, $force_settings );

				update_option( 'woocommerce_authorize_net_cim_credit_card_settings', $settings );
			}
		}

		$migrated = $this->migrate_transaction_data( $old_gateway_id, 'authorize_net_cim_credit_card', [
			'card_type',
			'card_expiry_date',
			'charge_captured',
			'capture_total',
			'capture_trans_id',
			'authorization_code',
			'authorization_amount',
			'void_amount',
			'void_trans_id',
		] );

		$migrated_wpdb = absint( $migrated['wpdb'] ?? 0 );
		$migrated_hpos = absint( $migrated['hpos'] ?? 0 );

		if ( $migrated_wpdb > 0 ) {
			$this->get_plugin()->log( "{$migrated_wpdb} credit card order meta rows migrated" );
		}

		if ( $migrated_hpos > 0 ) {
			$this->get_plugin()->log( "{$migrated_hpos} credit card order meta rows migrated (HPOS)" );
		}
	}


	/**
	 * Migrates data from a eCheck gateway.
	 *
	 * This copies settings, as well as order transaction meta.
	 *
	 * @since 3.0.0
	 *
	 * @param string $old_gateway_id gateway ID migrating from
	 * @param array $force_settings settings to override to a specific value
	 * @param bool $overwrite_existing whether to force overwrite existing CIM settings
	 */
	private function migrate_echeck_gateway( $old_gateway_id, array $force_settings = [], $overwrite_existing = false ) {

		// don't overwrite existing CIM settings
		if ( ! $overwrite_existing && ! empty( $this->get_echeck_settings() ) ) {

			$this->get_plugin()->log( 'Existing CIM eCheck settings found. Skipping settings migration.' );

		} else {

			$settings = get_option( "woocommerce_{$old_gateway_id}_settings" );

			if ( ! empty( $settings ) && is_array( $settings ) ) {

				$settings = array_merge( $settings, $force_settings );

				update_option( 'woocommerce_authorize_net_cim_echeck_settings', $settings );
			}
		}

		$migrated = $this->migrate_transaction_data( $old_gateway_id, 'authorize_net_cim_echeck', [
			'account_type',
		] );

		$migrated_wpdb = absint( $migrated['wpdb'] ?? 0 );
		$migrated_hpos = absint( $migrated['hpos'] ?? 0 );

		if ( $migrated_wpdb > 0 ) {
			$this->get_plugin()->log( "{$migrated_wpdb} eCheck order meta rows migrated" );
		}

		if ( $migrated_hpos > 0 ) {
			$this->get_plugin()->log( "{$migrated_hpos} eCheck order meta rows migrated (HPOS)" );
		}
	}


	/**
	 * Migrates SkyVerge plugin framework transaction data between gateways.
	 *
	 * @since 3.0.0
	 *
	 * @param string $old_gateway_id migrating from gateway ID
	 * @param string $new_gateway_id migrating to gateway ID
	 * @param array $gateway_keys extra gateway-specific meta keys to migrate, without the ID prefix
	 * @return array<string, int>
	 */
	private function migrate_transaction_data( $old_gateway_id, $new_gateway_id, array $gateway_keys = []  ) : array {
		global $wpdb;

		$using_hpos = Framework\SV_WC_Plugin_Compatibility::is_hpos_enabled();

		$meta_keys = array_merge( [
			'trans_id',
			'trans_date',
			'account_four',
			'environment',
			'retry_count',
		], $gateway_keys );

		$rows_migrated = $rows_migrated_hpos = 0;

		foreach ( $meta_keys as $key ) {

			if ( $updated = $wpdb->update( $wpdb->postmeta, array( 'meta_key' => "_wc_{$new_gateway_id}_{$key}" ), array( 'meta_key' => "_wc_{$old_gateway_id}_{$key}" ) ) ) {
				$rows_migrated += $updated;
			}

			if ( $using_hpos ) {
				if ( $updated_hpos = $wpdb->update( $wpdb->prefix . 'wc_orders_meta', [ 'meta_key' => "_wc_{$new_gateway_id}_{$key}" ], [ 'meta_key' => "_wc_{$old_gateway_id}_{$key}" ] ) ) {
					$rows_migrated_hpos += $updated_hpos;
				}
			}
		}

		// update the WC core payment method meta
		if ( $updated = $wpdb->update( $wpdb->postmeta, array( 'meta_value' => $new_gateway_id ), array( 'meta_key' => '_payment_method', 'meta_value' => $old_gateway_id ) ) ) {
			$rows_migrated += $updated;
		}

		if ( $using_hpos ) {
			if ( $updated_hpos = $wpdb->update( $wpdb->prefix . 'wc_orders_meta', [ 'meta_value' => $new_gateway_id ], [ 'meta_key' => '_payment_method', 'meta_value' => $old_gateway_id ] ) ) {
				$rows_migrated_hpos += $updated_hpos;
			}
		}

		return [
			'wpdb' => $rows_migrated,
			'hpos' => $rows_migrated_hpos,
		];
	}


	/** DPM migration methods *****************************************************************************************/


	/**
	 * Determines if DPM is installed.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	private function is_dpm_installed() {

		$settings = get_option( 'woocommerce_authorize_net_dpm_settings', [] );

		return is_array( $settings ) && ! empty( $settings );
	}


	/**
	 * Migrates setting & transaction data from DPM.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $overwrite_existing whether to force overwrite existing CIM settings
	 */
	private function migrate_from_dpm( $overwrite_existing = false ) {
		global $wpdb;

		$using_hpos = Framework\SV_WC_Plugin_Compatibility::is_hpos_enabled();

		$this->get_plugin()->log( 'Migrating DPM credit card data' );

		// don't overwrite existing CIM settings
		if ( ! $overwrite_existing && ! empty( $this->get_credit_card_settings() ) ) {

			$this->get_plugin()->log( 'Existing CIM credit card settings found. Skipping settings migration.' );

		} else {

			$settings = get_option( 'woocommerce_authorize_net_dpm_settings', [] );

			if ( is_array( $settings ) && ! empty( $settings ) ) {

				// convert DPM settings to ours
				$new_settings = [
					'enabled'     => ! empty( $settings['enabled'] )     ? $settings['enabled']     : 'yes',
					'title'       => ! empty( $settings['title'] )       ? $settings['title']       : '',
					'description' => ! empty( $settings['description'] ) ? $settings['description'] : '',
					'card_types'  => [ 'VISA', 'MC', 'AMEX', 'DISC', 'DINERS', 'JCB' ],

					'environment'         => ! empty( $settings['testmode'] )  && 'yes' === $settings['testmode']  ? \WC_Gateway_Authorize_Net_CIM::ENVIRONMENT_TEST               : \WC_Gateway_Authorize_Net_CIM::ENVIRONMENT_PRODUCTION,
					'transaction_type'    => ! empty( $settings['auth_only'] ) && 'yes' === $settings['auth_only'] ? \WC_Gateway_Authorize_Net_CIM::TRANSACTION_TYPE_AUTHORIZATION : \WC_Gateway_Authorize_Net_CIM::TRANSACTION_TYPE_CHARGE,
					'enable_paid_capture' => 'yes',
					'debug_mode'          => ! empty( $settings['debug'] )     && 'yes' === $settings['debug']     ? \WC_Gateway_Authorize_Net_CIM::DEBUG_MODE_LOG                 : \WC_Gateway_Authorize_Net_CIM::DEBUG_MODE_OFF,
					'form_type'           => \WC_Gateway_Authorize_Net_CIM::FEATURE_PAYMENT_FORM_LIGHTBOX,
				];

				$login_id        = ! empty( $settings['api_login'] )       ? $settings['api_login']       : '';
				$transaction_key = ! empty( $settings['transaction_key'] ) ? $settings['transaction_key'] : '';
				$signature_key   = ! empty( $settings['signature_key'] )   ? $settings['signature_key'] : '';

				// set the API credentials based on the environment
				if ( \WC_Gateway_Authorize_Net_CIM::ENVIRONMENT_TEST === $new_settings['environment'] ) {
					$new_settings['test_api_login_id']        = $login_id;
					$new_settings['test_api_transaction_key'] = $transaction_key;
					$new_settings['test_api_signature_key']   = $signature_key;
				} else {
					$new_settings['api_login_id']        = $login_id;
					$new_settings['api_transaction_key'] = $transaction_key;
					$new_settings['api_signature_key']   = $signature_key;
				}

				update_option( 'woocommerce_authorize_net_cim_credit_card_settings', $new_settings );
			}
		}

		// this is the only order meta DPM sets
		$wpdb->update( $wpdb->postmeta, array( 'meta_key' => '_wc_authorize_net_cim_credit_card_charge_captured' ), array( 'meta_key' => '_authorize_net_dpm_captured' ) );

		if ( $using_hpos ) {
			$wpdb->update( $wpdb->prefix . 'wc_orders_meta', [ 'meta_key' => '_wc_authorize_net_cim_credit_card_charge_captured' ], [ 'meta_key' => '_authorize_net_dpm_captured' ] );
		}

		// update the WC core payment method meta
		$wpdb->update( $wpdb->postmeta, array( 'meta_value' => 'authorize_net_cim_credit_card' ), array( 'meta_key' => '_payment_method', 'meta_value' => 'authorize_net_dpm' ) );

		if ( $using_hpos ) {
			$wpdb->update( $wpdb->prefix . 'wc_orders_meta', [ 'meta_value' => 'authorize_net_cim_credit_card' ], [ 'meta_key' => '_payment_method', 'meta_value' => 'authorize_net_dpm' ] );
		}

		$this->get_plugin()->log( 'Migrated DPM credit card data' );

		// flag a successful migration so we can display a notice
		update_option( 'wc_' . $this->get_plugin()->get_id() . '_migrated_from_legacy', 'dpm' );

		// store the event for debugging
		$this->add_migrate_event( 'authorize_net_dpm' );
	}


	/**
	 * Gets the stored credit card settings.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	private function get_credit_card_settings() {

		$settings = get_option( 'woocommerce_authorize_net_cim_credit_card_settings', [] );

		return is_array( $settings ) ? $settings : [];
	}


	/**
	 * Gets the stored echeck settings.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	private function get_echeck_settings() {

		$settings = get_option( 'woocommerce_authorize_net_cim_echeck_settings', [] );

		return is_array( $settings ) ? $settings : [];
	}


}
