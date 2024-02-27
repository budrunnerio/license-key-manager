<?php
/**
 * Register Paymentsense options panel.
 *
 * @var WC_POS_Register $register_object
 *
 * @package WooCommerce_Point_Of_Sale/Gateways
 */

defined( 'ABSPATH' ) || exit;

$terminals         = [];
$paymentsense_api  = new WC_POS_Gateway_Paymentsense_API();
$pac_terminals     = $paymentsense_api->pac_terminals_response();
$terminals['none'] = __( 'None', 'woocommerce-point-of-sale' );

if ( isset( $pac_terminals['terminals'] ) && count( $pac_terminals['terminals'] ) ) {
	foreach ( $pac_terminals['terminals'] as $terminal ) {
		$terminals[ $terminal['tid'] ] = $terminal['tid'];
	}
}
?>
<div id="paymentsense_register_options" class="panel woocommerce_options_panel">
	<?php
	woocommerce_wp_select(
		[
			'id'          => 'paymentsense_terminal',
			'value'       => $register_object->get_meta( 'paymentsense_terminal', true ),
			'label'       => __( 'Terminal', 'woocommerce-point-of-sale' ),
			'options'     => $terminals,
			'desc_tip'    => true,
			'description' => __( 'Select the EMV terminal you want to use for this register.', 'woocommerce-point-of-sale' ),
		]
	);
	?>

	<p class="form-field">
		<label for="paymentsense_eod_report"><?php esc_html_e( 'EOD Report', 'woocommerce-point-of-sale' ); ?></label>
		<button class="button" type="button" id="paymentsense_eod_report"><?php esc_html_e( 'Print EOD', 'woocommerce-point-of-sale' ); ?></button>
		<span class="description"><?php esc_html_e( 'This will print a total of all EMV sales done for this terminal', 'woocommerce-point-of-sale' ); ?></span>
	</p>
</div>
