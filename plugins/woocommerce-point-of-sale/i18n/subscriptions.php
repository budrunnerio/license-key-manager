<?php
/**
 * POS Subscriptions Addon
 *
 * Returns an array of strings.
 */

defined( 'ABSPATH' ) || exit;

return [
	0  => __( 'A subscription renewal has been removed from your cart. Multiple subscriptions can not be purchased at the same time.', 'woocommerce-point-of-sale' ),
	1  => __( 'A subscription has been removed from your cart. Due to payment gateway restrictions, different subscription products can not be purchased at the same time.', 'woocommerce-point-of-sale' ),
	2  => __( 'A subscription has been removed from your cart. Products and subscriptions can not be purchased at the same time.', 'woocommerce-point-of-sale' ),
	/* translators: %1$s number %2$s number */
	3  => __( '%1$s every %2$s', 'woocommerce-point-of-sale' ),
	/* translators: %1$s number %2$s number %3$s day */
	4  => __( '%1$s every %2$s on %3$s', 'woocommerce-point-of-sale' ),
	/* translators: %s number */
	5  => __( '%s on the last day of each month', 'woocommerce-point-of-sale' ),
	/* translators: %1$s number %2$s nth */
	6  => __( '%1$s on the %2$s of each month', 'woocommerce-point-of-sale' ),
	/* translators: %1$s number %2$s number */
	7  => __( '%1$s on the last day of every %2$s month', 'woocommerce-point-of-sale' ),
	/* translators: %1$s number %2$s nth %1$s number */
	8  => __( '%1$s on the %2$s day of every %3$s month', 'woocommerce-point-of-sale' ),
	/* translators: %1$s number %2$s number %3$s number */
	9  => __( '%1$s on %2$s %3$s each year', 'woocommerce-point-of-sale' ),
	/* translators: %1$s number %2$s number %3$s number %4$s nth */
	10 => __( '%1$s on %2$s %3$s every %4$s year', 'woocommerce-point-of-sale' ),
	11 => [
		__( 'day', 'woocommerce-point-of-sale' ),
		/* translators: number of days %s */
		__( '%s days', 'woocommerce-point-of-sale' ),
		__( 'week', 'woocommerce-point-of-sale' ),
		/* translators: %s number of weeks*/
		__( '%s weeks', 'woocommerce-point-of-sale' ),
		__( 'month', 'woocommerce-point-of-sale' ),
		/* translators: %s number of months */
		__( '%s months', 'woocommerce-point-of-sale' ),
		__( 'year', 'woocommerce-point-of-sale' ),
		/* translators: %s number of years */
		__( '%s years', 'woocommerce-point-of-sale' ),
	],
	12 => [
		/* translators: %s nth */
		__( '%sth', 'woocommerce-point-of-sale' ),
		/* translators: %s nst */
		__( '%sst', 'woocommerce-point-of-sale' ),
		/* translators: %s nnd */
		__( '%snd', 'woocommerce-point-of-sale' ),
		/* translators: %s nrd */
		__( '%srd', 'woocommerce-point-of-sale' ),
	],
	13 => [
		/* translators: %1$s number %2$s number */
		__( '%1$s / %2$s', 'woocommerce-point-of-sale' ),
		/* translators: %1$s number %2$s number */
		__( ' %1$s every %2$s', 'woocommerce-point-of-sale' ),
	],
	/* translators: %1$s number %2$s number  */
	14 => __( '%1$s for %2$s', 'woocommerce-point-of-sale' ),
	/* translators: %1$s number %2$s number */
	15 => __( '%1$s with %2$s free trial', 'woocommerce-point-of-sale' ),
	16 => [
		/* translators: %s something */
		__( '%s day', 'woocommerce-point-of-sale' ),
		/* translators: %s something */
		__( 'a %s-day', 'woocommerce-point-of-sale' ),
		/* translators: %s something */
		__( '%s week', 'woocommerce-point-of-sale' ),
		/* translators: %s something */
		__( 'a %s-week', 'woocommerce-point-of-sale' ),
		/* translators: %s something */
		__( '%s month', 'woocommerce-point-of-sale' ),
		/* translators: %s something */
		__( 'a %s-month', 'woocommerce-point-of-sale' ),
		/* translators: %s something */
		__( '%s year', 'woocommerce-point-of-sale' ),
		/* translators: %s something */
		__( 'a %s-year', 'woocommerce-point-of-sale' ),
	],
	/* translators: %1$s something %2$s something */
	17 => __( '%1$s and a %2$s sign-up fee', 'woocommerce-point-of-sale' ),
];
