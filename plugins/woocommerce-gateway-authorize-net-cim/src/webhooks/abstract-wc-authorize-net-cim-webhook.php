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
 * The Authorize.Net base webhook response handler.
 *
 * @since 2.8.0
 */
abstract class WC_Authorize_Net_CIM_Webhook {


	/** the updated action slug */
	const ACTION_UPDATED = 'updated';

	/** the deleted action slug */
	const ACTION_DELETED = 'deleted';


	/** @var string API resource entity name */
	protected $entity_name;

	/** @var \WC_Authorize_Net_CIM the plugin instance */
	protected $plugin;

	/** @var WC_Gateway_Authorize_Net_CIM[] */
	private $gateways;


	/**
	 * Constructs the class.
	 *
	 * @since 2.8.0
	 *
	 * @param \WC_Authorize_Net_CIM $plugin plugin instance
	 */
	public function __construct( \WC_Authorize_Net_CIM $plugin ) {

		$this->plugin = $plugin;
	}


	/**
	 * Processes the webhook payload data.
	 *
	 * @since 2.8.0
	 *
	 * @param string $action action that triggered the webhook
	 * @param object $data payload data
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function process( $action, $data ) {

		if ( $this->get_entity_name() && ( empty( $data->entityName ) || $this->get_entity_name() !== $data->entityName ) ) {
			throw new Framework\SV_WC_API_Exception( 'Invalid entity name.' );
		}

		switch ( $action ) {
			case self::ACTION_UPDATED: $this->update_entity( $data ); break;
			case self::ACTION_DELETED: $this->delete_entity( $data ); break;
		}
	}


	/**
	 * Triggers when an API entity has been updated.
	 *
	 * @since 2.8.0
	 *
	 * @param object $data payload data
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	abstract protected function update_entity( $data );


	/**
	 * Triggers when an API entity has been deleted.
	 *
	 * @since 2.8.0
	 *
	 * @param object $data payload data
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	abstract protected function delete_entity( $data );


	/**
	 * Gets the API resource entity name.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	protected function get_entity_name() {

		return $this->entity_name;
	}


	/**
	 * Gets the plugin instance.
	 *
	 * @since 2.8.0
	 *
	 * @return \WC_Authorize_Net_CIM
	 */
	protected function get_plugin() {

		return $this->plugin;
	}


	/**
	 * Gets the gateways configured with unique API credentials.
	 *
	 * @since 3.0.6
	 *
	 * @return \WC_Gateway_Authorize_Net_CIM[]
	 */
	protected function get_gateways() {

		if ( empty( $this->gateways ) ) {
			$this->gateways = $this->get_plugin()->get_gateways();
		}

		return $this->gateways;
	}


}
