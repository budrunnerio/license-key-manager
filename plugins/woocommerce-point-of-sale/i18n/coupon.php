<?php
/**
 * POS Coupons
 *
 * Returns an array of strings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

return [
	100 => __( 'Coupon is not valid.', 'woocommerce-point-of-sale' ),
	/* translators: %s coupon code */
	101 => __( 'Sorry, it seems the coupon "%s" is invalid - it has now been removed from your order.', 'woocommerce-point-of-sale' ),
	/* translators: %s coupon code */
	102 => __( 'Sorry, it seems the coupon "%s" is not yours - it has now been removed from your order.', 'woocommerce-point-of-sale' ),
	103 => __( 'Coupon code already applied!', 'woocommerce-point-of-sale' ),
	/* translators: %s coupon code */
	104 => __( 'Sorry, coupon "%s" has already been applied and cannot be used in conjunction with other coupons.', 'woocommerce-point-of-sale' ),
	/* translators: %s coupon code */
	105 => __( 'Coupon "%s" does not exist!', 'woocommerce-point-of-sale' ),
	106 => __( 'Coupon usage limit has been reached.', 'woocommerce-point-of-sale' ),
	107 => __( 'This coupon has expired.', 'woocommerce-point-of-sale' ),
	/* translators: %s minimum spend */
	108 => __( 'The minimum spend for this coupon is %s.', 'woocommerce-point-of-sale' ),
	109 => __( 'Sorry, this coupon is not applicable to your cart contents.', 'woocommerce-point-of-sale' ),
	110 => __( 'Sorry, this coupon is not valid for sale items.', 'woocommerce-point-of-sale' ),
	111 => __( 'Please enter a coupon code.', 'woocommerce-point-of-sale' ),
	/* translators: %s maximum spend */
	112 => __( 'The maximum spend for this coupon is %s.', 'woocommerce-point-of-sale' ),
	/* translators: %s products */
	113 => __( 'Sorry, this coupon is not applicable to the products: %s.', 'woocommerce-point-of-sale' ),
	/* translators: %s categories */
	114 => __( 'Sorry, this coupon is not applicable to the categories: %s.', 'woocommerce-point-of-sale' ),
	200 => __( 'Coupon code applied successfully.', 'woocommerce-point-of-sale' ),
	201 => __( 'Coupon code removed successfully.', 'woocommerce-point-of-sale' ),
	202 => __( 'Discount added successfully.', 'woocommerce-point-of-sale' ),
	203 => __( 'Discount updated successfully.', 'woocommerce-point-of-sale' ),
];
