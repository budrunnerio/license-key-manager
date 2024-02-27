<?php
/**
 * Stock Functions
 *
 * @since 6.0.0
 *
 * @package WooCommerce_Point_Of_Sale/Functions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns the outlet stock amounts for a given product.
 *
 * This function filters out the stock values of non-existing outlets.
 *
 * @since 6.0.0
 *
 * @param int $product_id Product ID.
 * @return array
 */
function wc_pos_get_product_outlet_stock( $product_id ) {
	$_wc_pos_outlet_stock = get_post_meta( $product_id, '_wc_pos_outlet_stock', true );

	$outlet_stock = [];

	if ( $_wc_pos_outlet_stock ) {
		$_wc_pos_outlet_stock = maybe_unserialize( $_wc_pos_outlet_stock );
		$_wc_pos_outlet_stock = $_wc_pos_outlet_stock && is_array( $_wc_pos_outlet_stock ) ? $_wc_pos_outlet_stock : [];

		foreach ( $_wc_pos_outlet_stock as $outlet_id => $stock_quantity ) {
			$outlet = wc_pos_get_outlet( (int) $outlet_id );

			if ( $outlet && is_a( $outlet, 'WC_POS_Outlet' ) ) {
				$outlet_stock[ (int) $outlet_id ] = (float) $stock_quantity;
			}
		}
	}

	return $outlet_stock;
}

/**
 * Returns the outlet stock data of a given product and outlet.
 *
 * @since 6.0.0
 *
 * @param int $product_id Product ID.
 * @param int $outlet_id  Outlet ID.
 *
 * @return array
 */
function wc_pos_get_outlet_stock_data( $product_id, $outlet_id ) {
	$stock_quantity = null;
	$stock_status   = 'instock';

	$product = wc_get_product( $product_id );

	if ( $product ) {
		$stock_status = $product->get_stock_status();

		if ( $product->get_manage_stock() ) {
			$outlet_stock = wc_pos_get_product_outlet_stock( $product_id );

			$stock_quantity = isset( $outlet_stock[ $outlet_id ] ) ? $outlet_stock[ $outlet_id ] : 0;
			$stock_status   = $stock_quantity <= 0 ? 'outofstock' : 'instock';
		}
	}

	return [
		'outlet_stock_quantity' => $stock_quantity,
		'outlet_stock_status'   => $stock_status,
	];
}

/**
 * Update a product's outlet stock amounts.
 *
 * @since 6.0.0
 * @see wc_update_product_stock() in WC core.
 * @todo maybe use direct DB queries rather than update_post_meta to avoid possible issues.
 *
 * @param int|WC_Product $product      Product ID or product instance.
 * @param array          $outlet_stock Stock quantities per outlet.
 * @param string         $operation    Type of operation, allows 'set', 'update', 'increase' and 'decrease'.
 * @param bool           $updating     If true, the product object won't be saved here as it will be updated later.
 *
 * @return bool|array|null
 */
function wc_pos_update_product_outlet_stock( &$product, $outlet_stock = [], $operation = 'set', $updating = false ) {
	if ( ! is_a( $product, 'WC_Product' ) ) {
		$product = wc_get_product( $product );
	}

	if ( ! $product ) {
		return false;
	}

	if ( $product->managing_stock() ) {
		// Some products (variations) can have their stock managed by their parent. Get the correct object to be updated here.
		$product_id_with_stock = $product->get_stock_managed_by_id();
		$product_with_stock    = $product_id_with_stock !== $product->get_id() ? wc_get_product( $product_id_with_stock ) : $product;
		$data_store            = WC_Data_Store::load( 'product' );

		$current_outlet_stock = wc_pos_get_product_outlet_stock( $product_id_with_stock );
		$new_outlet_stock     = [];

		if ( 'set' === $operation || 'update' === $operation ) {
			// Keep the existing outlet stocks.
			if ( 'update' === $operation ) {
				$new_outlet_stock = $current_outlet_stock;
			}

			foreach ( $outlet_stock as $outlet_id => $stock_quantity ) {
				$new_outlet_stock[ $outlet_id ] = wc_stock_amount( $stock_quantity );
			}
		} else {
			// We can only increase/decrease the existing values. If the outlet stock value is not
			// already set (managed), then it cannot be updated (increased/decreased). Only the
			// managed outlets have stock values.
			foreach ( $current_outlet_stock as $outlet_id => $stock_quantity ) {
				$new_stock_quantity = $stock_quantity;

				if ( isset( $outlet_stock[ $outlet_id ] ) ) {
					if ( 'increase' === $operation ) {
						$new_stock_quantity = $stock_quantity + $outlet_stock[ $outlet_id ];
					} else {
						$new_stock_quantity = $stock_quantity - $outlet_stock[ $outlet_id ];
					}
				}

				$new_outlet_stock[ $outlet_id ] = wc_stock_amount( $new_stock_quantity );
			}
		}

		krsort( $new_outlet_stock );

		// Update the database.
		if ( update_post_meta( $product_id_with_stock, '_wc_pos_outlet_stock', $new_outlet_stock ) ) {
			$data_store->read_meta_data( true );

			if ( ! $updating ) {
				$product_with_stock->save();
			}

			return wc_pos_get_product_outlet_stock( $product_id_with_stock );
		}

		return false;
	} else {
		update_post_meta( $product->get_id(), '_wc_pos_outlet_stock', [] );
	}

	return wc_pos_get_product_outlet_stock( $product->get_id() );
}
