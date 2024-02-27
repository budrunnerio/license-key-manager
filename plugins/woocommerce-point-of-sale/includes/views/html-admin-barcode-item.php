<?php
/**
 * Admin Barcode Item
 *
 * @package WooCommerce_Point_Of_Sale/Viwes
 */

defined( 'ABSPATH' ) || exit;

$product_link = $_product ? admin_url( 'post.php?post=' . absint( $_product->get_id() ) . '&action=edit' ) : '';

/**
 * This filter is documented in WC core.
 *
 * @since 4.0.0
 */
$thumbnail = $_product ? apply_filters( 'woocommerce_admin_order_item_thumbnail', $_product->get_image( 'thumbnail', [ 'title' => '' ], false ), 0, null ) : '';
$item_id   = $_product->get_id();
$parent_id = $_product->is_type( 'variation' ) ? $_product->get_parent_id() : $_product->get_id();
?>
<tr class="item <?php echo esc_attr( $class ); ?> item_<?php echo esc_attr( $item_id ); ?>" data-prid="<?php echo esc_attr( $item_id ); ?>" data-parentid="<?php echo esc_attr( $parent_id ); ?>" >
	<td class="thumb">
		<div><div class="wc-order-item-thumbnail"><?php echo wp_kses_post( $thumbnail ); ?></div>
	</td>
	<td class="name">
		<?php
			echo $product_link ? '<a href="' . esc_url( $product_link ) . '" class="wc-order-item-name">' . esc_html( $_product->get_title() ) . '</a>' : '<div class="class="wc-order-item-name"">' . esc_html( $_product->get_title() ) . '</div>';

			$sku = '';
		if ( $_product && $_product->get_sku() ) {
			$sku = esc_html( $_product->get_sku() );
				echo '<div class="wc-order-item-sku sku_text"><strong>' . esc_html( $_product->get_sku() ) . '</strong></div>';
		} else {
			echo '<div class="wrong_sku sku_text"></div>';
		}

		if ( $_product->is_type( 'variation' ) ) {
			echo '<div class="wc-order-item-variation"><strong>' . esc_html__( 'Variation ID:', 'woocommerce-point-of-sale' ) . '</strong> ';
			echo esc_html( $_product->get_id() );
			echo '</div>';

			$variation = $_product->get_attributes();
			if ( is_array( $variation ) ) {
				echo '<div class="view"><table cellspacing="0" class="display_meta"><tbody>';

				foreach ( $variation as $name => $value ) {
					if ( ! $value ) {
						continue;
					}

					// If this is a term slug, get the term's nice name
					if ( taxonomy_exists( esc_attr( str_replace( 'attribute_', '', $name ) ) ) ) {
						$t = get_term_by( 'slug', $value, esc_attr( str_replace( 'attribute_', '', $name ) ) );
						if ( ! is_wp_error( $t ) && ! empty( $t->name ) ) {
							$value = $t->name;
						}
					} else {
						$value = ucwords( str_replace( '-', ' ', $value ) );
					}

					echo '<tr><th>' . esc_html( wc_attribute_label( str_replace( 'attribute_', '', $name ) ) ) . ':</th><td>' . esc_html( rawurldecode( $value ) ) . '</td></tr>';
				}

				echo '</tbody></table></div>';
			}
		}
		?>
	</td>
	<td class="item_cost" width="1%">
		<div class="view product_price">
			<?php echo wp_kses_post( wc_price( $_product->get_price() ) ); ?>
		</div>
	</td>
	<td class="quantity" width="1%">
		<div class="view">
			<?php
				echo '<small class="times">&times;</small> <span>1</span>';
			?>
		</div>
		<div class="edit" style="display: none;">
			<?php $item_qty = 1; ?>
			<input type="number" step="
			<?php
			/**
			 * This filter is documented in WC core.
			 *
			 * @since 4.0.0
			 */
			echo esc_attr( apply_filters( 'woocommerce_quantity_input_step', '1', $_product ) );
			?>
			" min="1" autocomplete="off" placeholder="1" value="1" size="4" class="quantity" />
		</div>
	</td>
	<td class="line_barcode">
		<div class="barcode_border">
			<?php
			$text        = ! empty( $_product->get_sku() ) ? $_product->get_sku() : $_product->get_id();
			$barcode_url = WC_POS()->barcode_url() . '&text=' . $text;
			?>
			<img src="<?php echo esc_url( $barcode_url ); ?>&font_size=12" alt="" data-barcode_url="<?php echo esc_attr( $barcode_url ); ?>">
			<div class="barcode_text"></div>
		</div>
	</td>
	<td class="wc-order-edit-line-item" width="1%">
		<div class="wc-order-edit-line-item-actions">
			<a class="edit-order-item tips" href="#" data-tip="<?php esc_attr_e( 'Edit item', 'woocommerce-point-of-sale' ); ?>"></a>
			<a class="delete-order-item tips" href="#" data-tip="<?php esc_attr_e( 'Delete item', 'woocommerce-point-of-sale' ); ?>"></a>
		</div>
	</th>
</tr>
