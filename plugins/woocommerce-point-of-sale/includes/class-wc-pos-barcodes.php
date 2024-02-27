<?php
/**
 * Barcodes Page
 *
 * @package WooCommerce_Point_Of_Sale/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Barcodes Class
 */
class WC_POS_Barcodes {

	/**
	 * The single instance of the class.
	 *
	 * @var WC_POS_Barcodes
	 */
	protected static $_instance = null;

	/**
	 * Main WC_POS_Barcodes Instance.
	 *
	 * Ensures only one instance of WC_POS_Barcodes is loaded or can be loaded.
	 *
	 * @return WC_POS_Barcodes Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {}

	public function display_single_barcode_page() {
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Barcode Label Printing', 'woocommerce-point-of-sale' ); ?></h2>
			<p><?php esc_html_e( 'Barcode labels for your store can be printed here. To change the the fields to print in the label, you can check the boxes for the labels to print in the panel to the right.', 'woocommerce-point-of-sale' ); ?></p>
			
			<div id="lost-connection-notice" class="error hidden">
				<p><span class="spinner"></span> <?php echo wp_kses_post( __( '<strong>Connection lost.</strong> Saving has been disabled until you&#8217;re reconnected.', 'woocommerce-point-of-sale' ) ); ?>
				<span class="hide-if-no-sessionstorage"><?php esc_html_e( 'We&#8217;re backing up this post in your browser, just in case.', 'woocommerce-point-of-sale' ); ?></span>
				</p>
			</div>
			<form action="" method="post" id="edit_wc_pos_barcode" onsubmit="return false;">
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<div id="postbox-container-2" class="postbox-container">
							<div class="postbox products_list">
								<div class="inside">
									<?php include_once 'views/html-admin-barcode-options.php'; ?>
								</div>
							</div>
						</div>
						<div id="postbox-container-1" class="postbox-container">
							<div class="postbox ">
								<h3 class="hndle">
									<label ><?php esc_html_e( 'Print Settings', 'woocommerce-point-of-sale' ); ?></label>
								</h3>
								<div class="inside" id="barcode_print_settings">
									<div>
										<label for="number_of_labels"><?php esc_html_e( 'Number of Labels', 'woocommerce-point-of-sale' ); ?></label>
										<input type="number" step="1" name="number_of_labels" id="number_of_labels">
									</div>
									<div>
										<label for="label_type"><?php esc_html_e( 'Label Type', 'woocommerce-point-of-sale' ); ?></label>
										<select id="label_type" name="label_type" class="wc-enhanced-select" style="max-width:100%;">
											<optgroup label="<?php esc_attr_e( 'Continuous', 'woocommerce-point-of-sale' ); ?>">
											<option value="continuous_feed"><?php esc_html_e( 'Continuous Feed', 'woocommerce-point-of-sale' ); ?></option>
											<option value="continuous_feed_custom"><?php esc_html_e( 'Continuous Feed Custom', 'woocommerce-point-of-sale' ); ?></option>
											<option value="con_4_3"><?php esc_html_e( 'Continuous Feed (40mm x 30mm)', 'woocommerce-point-of-sale' ); ?></option>
											<option value="con_4_2"><?php esc_html_e( 'Continuous Feed (40mm x 20mm)', 'woocommerce-point-of-sale' ); ?></option>
											<option value="con_25_54"><?php esc_html_e( 'Continuous Feed (25mm x 54mm)', 'woocommerce-point-of-sale' ); ?></option>
											</optgroup>
											<optgroup label="<?php esc_attr_e( 'A4', 'woocommerce-point-of-sale' ); ?>">
											<option value="a4"><?php esc_html_e( '2 x 7', 'woocommerce-point-of-sale' ); ?></option>
											<option value="a4_30"><?php esc_html_e( '3 x 7', 'woocommerce-point-of-sale' ); ?></option>
											<option value="a4_27"><?php esc_html_e( '3 x 9', 'woocommerce-point-of-sale' ); ?></option>
											</optgroup>
											<optgroup label="<?php esc_html_e( 'Letter', 'woocommerce-point-of-sale' ); ?>">
											<option value="letter"><?php esc_html_e( '4 x 5', 'woocommerce-point-of-sale' ); ?></option>
											<option value="per_sheet_30"><?php esc_html_e( '3 x 10', 'woocommerce-point-of-sale' ); ?></option>
											<option value="per_sheet_80"><?php esc_html_e( '4 x 20', 'woocommerce-point-of-sale' ); ?></option>
											</optgroup>
											<optgroup label="<?php esc_attr_e( 'Other', 'woocommerce-point-of-sale' ); ?>">
											<option value="jew_50_10"><?php esc_html_e( 'Jewellery Tag (50mm x 10mm)', 'woocommerce-point-of-sale' ); ?></option>
											</optgroup>
										</select>
									</div>
									<div id="continous_feed_custom_options">
										<div>
											<label for="label_type"><?php esc_html_e( 'Label Margin Unit', 'woocommerce-point-of-sale' ); ?></label>
											<select id="label_margin_unit" name="label_margin_unit" class="wc-enhanced-select">
												<option value="in"><?php esc_html_e( 'Inches (in)', 'woocommerce-point-of-sale' ); ?></option>
												<option value="mm"><?php esc_html_e( 'Millimeters (mm)', 'woocommerce-point-of-sale' ); ?></option>
											</select>
										</div>
										<div class="label_margin_options">
											<div class="label_margin_option">
												<label for="label_margin_top">
													<span><?php esc_html_e( 'Top', 'woocommerce-point-of-sale' ); ?></span>
													<input type="number" value="0" step="1" name="label_margin_top" id="label_margin_top">
												</label>
											</div>
											<div class="label_margin_option">
												<label for="label_margin_bottom">
													<span><?php esc_html_e( 'Bottom', 'woocommerce-point-of-sale' ); ?></span>
													<input type="number" value="0" step="1" name="label_margin_bottom" id="label_margin_bottom">
												</label>
											</div>
											<div class="label_margin_option">
												<label for="label_margin_left">
													<span><?php esc_html_e( 'Left', 'woocommerce-point-of-sale' ); ?></span>
													<input type="number" value="0" step="1" name="label_margin_left" id="label_margin_left">
												</label>
											</div>
											<div class="label_margin_option">
												<label for="label_margin_right">
													<span><?php esc_html_e( 'Right', 'woocommerce-point-of-sale' ); ?></span>
													<input type="number" value="0" step="1" name="label_margin_right" id="label_margin_right">
												</label>
											</div>
										</div>
									</div>
									<div>
										<label for="label_fields"><?php esc_html_e( 'Product Fields', 'woocommerce-point-of-sale' ); ?></label>
										<div><label><input type="checkbox" name="fields_print" class="fields_to_print" id="field_barcode" checked="checked"><?php esc_html_e( 'Barcode', 'woocommerce-point-of-sale' ); ?></label></div>
										<div><label><input type="checkbox" name="fields_print" class="fields_to_print" id="field_sku" checked="checked"><?php esc_html_e( 'SKU', 'woocommerce-point-of-sale' ); ?></label></div>
										<div><label><input type="checkbox" name="fields_print" class="fields_to_print" id="field_sku_label"><?php esc_html_e( 'SKU Label', 'woocommerce-point-of-sale' ); ?></label></div>
										<div><label><input type="checkbox" name="fields_print" class="fields_to_print" id="field_name" checked="checked"><?php esc_html_e( 'Product Name', 'woocommerce-point-of-sale' ); ?></label></div>
										<div><label><input type="checkbox" name="fields_print" class="fields_to_print" id="field_price" checked="checked"><?php esc_html_e( 'Price', 'woocommerce-point-of-sale' ); ?></label></div>
										<div><label><input type="checkbox" name="fields_print" class="fields_to_print" id="field_meta_value"><?php esc_html_e( 'Variation', 'woocommerce-point-of-sale' ); ?></label></div>
										<div><label><input type="checkbox" name="fields_print" class="fields_to_print" id="field_meta_title"><?php esc_html_e( 'Variation Label', 'woocommerce-point-of-sale' ); ?></label></div>
									</div>
									<div>
										<p class="description" style="margin-top: 1em;"><?php esc_html_e( 'Note: set your paper size to the corresponding template size. Printing margins should be set to none to ensure accurate printing.', 'woocommerce-point-of-sale' ); ?></p>
									</div>
									
								</div>
								<div id="major-publishing-actions">
									<div id="publishing-action">
										<span class="spinner"></span>
										<input type="button" value="Print" class="button button-primary button-large" id="print_barcode">
									</div>
									<div class="clear"></div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="clear"></div>
			</form>
		</div>
		<?php
	}
}
