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
 * Authorize.Net Transaction Response Class
 *
 * Parses XML received from CIM Profile Transaction requests, the general response body looks like:
 *
 * <?xml version="1.0" encoding="utf-8"?>
 * 	<createTransactionResponse xmlns:xsi="https://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="https://www.w3.org/2001/XMLSchema" xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
 * 		<refId>123456</refId>
 * 		<messages>
 * 			<resultCode>Ok</resultCode>
 * 			<message>
 * 				<code>I00001</code>
 * 				<text>Successful.</text>
 * 			</message>
 * 		</messages>
 * 		<transactionResponse>
 * 			<responseCode>1</responseCode>
 * 			<authCode>UGELQC</authCode>
 * 			<avsResultCode>E</avsResultCode>
 * 			<cavvResultCode />
 * 			<transId>2148061808</transId>
 * 			<refTransID />
 * 			<transHash>0B428D8A928AAC61121AF2F6EAC5FF3F</transHash>
 * 			<accountNumber>XXXX0015</accountNumber>
 * 			<accountType>Mastercard</accountType>
 * 			<message>
 * 				<code>1</code>
 * 				<description>This transaction has been approved.</description>
 * 			</message>
 * 			<userFields>
 * 				<userField>
 * 					<name>MerchantDefinedFieldName1</name>
 * 					<value>MerchantDefinedFieldValue1</value>
 * 				</userField>
 * 				<userField>
 * 					<name>favorite_color</name>
 * 					<value>lavender</value>
 * 				</userField>
 * 			</userFields>
 * 			<networkTransId>123456789NNNH</networkTransId>
 * 		</transactionResponse>
 * 	</createTransactionResponse>
 *
 * @link http://developer.authorize.net/api/reference/#payment-transactions-charge-a-credit-card
 *
 * @since 2.0.0
 * @see Framework\SV_WC_Payment_Gateway_API_Response
 */
class WC_Authorize_Net_CIM_API_Profile_Transaction_Response extends WC_Authorize_Net_CIM_API_Non_Profile_Transaction_Response{}
