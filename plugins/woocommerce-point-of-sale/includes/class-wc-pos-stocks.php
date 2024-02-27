<?php
/**
 * Stock Controller
 *
 * @package WooCommerce_Point_Of_Sale/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Stocks Class
 */
class WC_POS_Stocks {

	/**
	 * The single instance of the class.
	 *
	 * @var WC_POS_Stocks
	 */
	protected static $_instance = null;

	/**
	 * Main WC_POS_Stocks Instance.
	 *
	 * Ensures only one instance of WC_POS_Stocks is loaded or can be loaded.
	 *
	 * @return WC_POS_Stocks Main instance.
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

	public function display_single_stocks_page() {
		?>
		<style>
			.filter-items label,
			.filter-items input {
				margin-right: 10px;
			}
			.filter-items{
				padding: 12px 0;
			}
		</style>
		<div class="wrap">
			<h2><?php esc_html_e( 'Stock Controller', 'woocommerce-point-of-sale' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Manage your stock instantly using the barcode scanner. Simply scan the SKU and the product will load below with the option to enter updated stock value.', 'woocommerce-point-of-sale' ); ?></p>
			<div id="lost-connection-notice" class="error hidden"></div>
			<div id="wc_pos_stock_controller">
				<div class="wp-filter">
					<div class="filter-items">
						<form action="" method="post" id="put_wc_pos_barcode">
							<label for="product_barcode"><?php esc_html_e( 'SKU', 'woocommerce-point-of-sale' ); ?></label>
							<input type="text" id="product_barcode" name="product_barcode" value="" minlength="3" >
							<input type="submit" value="<?php esc_attr_e( 'Find', 'woocommerce-point-of-sale' ); ?>" class="button button-primary button-large" id="find_product_by_barcode">
						</form>
					</div>
				</div>
				<div id="message" style="display: none">
				</div>
				<div id="poststuff_stock" style="display: none">
					<input type="hidden" name="id" id="product_id" value="">
					<table class="wp-list-table widefat striped posts" id="barcode_options_table">
						<thead>
							<tr>
								<th scope="col" id="thumb" class="manage-column column-thumb"><span class="stock_page_image tips" data-tip="<?php esc_attr_e( 'Image', 'woocommerce-point-of-sale' ); ?>"></span></th>
								<th scope="col" id="name" class="manage-column column-name"><?php esc_html_e( 'Name', 'woocommerce-point-of-sale' ); ?></th>
								<th scope="col" id="is_in_stock" class="manage-column column-is_in_stock"><?php esc_html_e( 'Stock', 'woocommerce-point-of-sale' ); ?></th>
								<th scope="col" id="price" class="manage-column column-price"><?php esc_html_e( 'Price', 'woocommerce-point-of-sale' ); ?></th>
								<th scope="col" id="stock_val" class="manage-column column-price"><?php esc_html_e( 'Update Stock', 'woocommerce-point-of-sale' ); ?></th>
								<th scope="col" id="increase" class="manage-column column-price"><?php esc_html_e( 'Action', 'woocommerce-point-of-sale' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr id="" class="iedit author-self level-0 post-99 type-product status-publish has-post-thumbnail hentry product_cat-music product_cat-singles">
								<td class="thumb column-thumb" data-colname="Image">
									<div id="product_image"></div>
								</td>
								<th id="name" class="column-name name" data-colname="Name" scope="col">
									<span id="product_name"></span><br>
									<small id="product_sku"></small>
								</th>
								<td class="stock column-stock" data-colname="Stock">
									<div id="product_stock"></div>
								</td>
								<td class="price column-price" data-colname="Price">
									<div id="product_price"></div>
								</td>
								<td class="price column-price" data-colname="Stock Value">
									<input type="number" id="stock_value" name="stock_value" min="1">
								</td>
								<td class="price column-price" data-colname="Increase" id="actions">
									<input type="submit" value="<?php esc_attr_e( 'Increase', 'woocommerce-point-of-sale' ); ?>" name="increase" class="page-title-action" id="increase_stock">
									<input type="submit" value="<?php esc_attr_e( 'Decrease', 'woocommerce-point-of-sale' ); ?>" name="decrease" class="page-title-action" id="decrease_stock">
									<input type="submit" value="<?php esc_attr_e( 'Replace', 'woocommerce-point-of-sale' ); ?>" name="replace" class="page-title-action" id="replace_stock">
								</td>
								<td class="price column-price" id="actions_fallback">
									<?php esc_html_e( 'Stock management is not enabled', 'woocommerce-point-of-sale' ); ?>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="clear"></div>
			</div>
		</div>
		<script>
			jQuery(document).ready(function($){
				$(document).anysearch({
					searchSlider: false,
					isBarcode: function(barcode) {
						filter_product(barcode);
					},
					searchFunc: function(search) {
						filter_product(search);
					},
				});
				$("#message").fadeOut();
				$("#poststuff_stock").fadeOut();
				$("#put_wc_pos_barcode").on('submit', function(e) {
					if( '' !== $('#product_barcode').val() ) {
						$("#message").fadeOut();
						var barcode = $('#product_barcode').val();
						filter_product(barcode);
					}
					return false;
				});

				$("#increase_stock, #replace_stock, #decrease_stock").on('click', function(e) {
					e.preventDefault();
					var id = Number.parseInt($('#product_id').val());
					var operation = $(this).attr('name');
					var value = Number.parseInt($('#stock_value').val());

					$("#message").fadeOut();
					$('#wc_pos_stock_controller').block({
						message: null,
						overlayCSS: {
							background: '#fff',
							opacity: 0.6
						}
						});

					data = {
						action: 'wc_pos_change_stock',
						security: '<?php echo esc_html( wp_create_nonce( 'change-stock' ) ); ?>',
						id: id,
						value: Number.parseInt(value),
						operation: operation
					};
					$.ajax({
						type: 'post',
						dataType: 'json',
						url: ajaxurl,
						data: data,
						success: function (data, textStatus, XMLHttpRequest) {
							if ( 'success' === data.status && data.response ) {
								update_sku_controller_table (data.response);
							}
						},
						error: function (MLHttpRequest, textStatus, errorThrown) {
						},
						complete : function (argument) {
							$('#wc_pos_stock_controller').unblock();
						}
					});
				});

				function filter_product(barcode) {
					$('#wc_pos_stock_controller').block({
							message: null,
							overlayCSS: {
								background: '#fff',
								opacity: 0.6
							}
							});
					data = {
						action: 'wc_pos_filter_product_barcode',
						security: '<?php echo esc_html( wp_create_nonce( 'filter-product' ) ); ?>',
						barcode: barcode
					};
					$.ajax({
						type: 'post',
						dataType: 'json',
						url: ajaxurl,
						data: data,
						success: function (data, textStatus, XMLHttpRequest) {
							if ( 'success' === data.status && data.response ) {
								update_sku_controller_table (data.response);
							} else {
								$("#message").html("Product wasn't found.");
								$("#message").fadeIn();
								$("#poststuff_stock").fadeOut();
							}
						},
						error: function (MLHttpRequest, textStatus, errorThrown) {
						}, 
						complete : function (argument) {
							$('#wc_pos_stock_controller').unblock();
						}
					});
					$('#product_barcode').val('');
				}

				function update_sku_controller_table (product) {
					$("#product_id").val(product.id);
					$("#product_name").html(product.name);
					$("#product_sku").html(product.sku);
					$("#product_image").html(product.image);
					$("#product_price").html(product.price);
					$("#product_stock").html(product.stock_status);
					$('#stock_value').val('');
					$("#poststuff_stock").fadeIn();

					if (product.manage_stock) {
						$("#actions").show();
						$("#actions_fallback").hide();
						$("#stock_value").prop('disabled', false);
					} else {
						$("#actions").hide();
						$("#actions_fallback").show();
						$("#stock_value").prop('disabled', true);
					}
				}

			});
		</script>
		<?php
	}

	public function display_messages() {
		$i = 0;
		if ( isset( $_GET['message'] ) && ! empty( $_GET['message'] ) ) {
			$i = wc_clean( wp_unslash( $_GET['message'] ) );
		}
		$messages = [
			0 => '', // Unused. Messages start at index 1.
			1 => '<div id="message" class="updated"><p>' . esc_html__( 'Barcode Template created.', 'woocommerce-point-of-sale' ) . '</p></div>',
			2 => '<div id="message" class="updated"><p>' . esc_html__( 'Barcode Template updated.', 'woocommerce-point-of-sale' ) . '</p></div>',
		];
		return $messages[ $i ];
	}

	public function save_stocks() {}
}
