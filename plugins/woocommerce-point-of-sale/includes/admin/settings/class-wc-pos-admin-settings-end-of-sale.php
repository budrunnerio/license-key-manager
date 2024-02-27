<?php
/**
 * Settings > Point of Sale > End_Of_Sale.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/Admin/Settings
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Admin_Settings_End_Of_Sale.
 */
class WC_POS_Admin_Settings_End_Of_Sale {

	/**
	 * Returns section settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$order_statuses = wc_pos_get_order_statuses_no_prefix();

		/**
		 * The end of sale settings section.
		 *
		 * @since 6.0.0
		 */
		return apply_filters(
			'wc_pos_settings_end_of_sale_section',
			[
				/*
				 * End Of Sale options.
				 */
				[
					'title' => __( 'End of Sale Options', 'woocommerce-point-of-sale' ),
					'type'  => 'title',
					'desc'  => __( 'The following options affect the End of Sale options in POS.', 'woocommerce-point-of-sale' ),
					'id'    => 'end_of_sale_options',
				],
				[
					'title'    => __( 'Tender Suggestions', 'woocommerce-point-of-sale' ),
					'desc'     => __( 'Hide cash tender suggestions', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Check this to hide the suggested cash tender amounts.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_hide_tender_suggestions',
					'default'  => 'no',
					'type'     => 'checkbox',
				],
				[
					'title'    => __( 'Signature Capture', 'woocommerce-point-of-sale' ),
					'desc'     => __( 'Enable signature capture', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Presents a modal window to capture the signature of user or customer.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_signature',
					'default'  => 'no',
					'type'     => 'checkbox',
				],
				[
					'title'    => __( 'Signature Required', 'woocommerce-point-of-sale' ),
					'desc'     => __( 'Enforce capturing of signature', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Allows you to force user to enter signature before proceeding with register commands.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_signature_required',
					'class'    => 'pos_signature',
					'default'  => 'no',
					'type'     => 'checkbox',
				],
				[
					'title'    => __( 'Signature Commands', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Choose which commands would you like the signature panel to be shown for.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_signature_required_on',
					'class'    => 'wc-enhanced-select pos_signature',
					'default'  => 'pay',
					'type'     => 'multiselect',
					'options'  => [
						'pay'  => __( 'Pay', 'woocommerce-point-of-sale' ),
						'save' => __( 'Hold', 'woocommerce-point-of-sale' ),
					],
				],
				[
					'type' => 'sectionend',
					'id'   => 'end_of_sale_options',
				],

				/*
				 * Order status options.
				 */
				[
					'title' => __( 'Order Status', 'woocommerce-point-of-sale' ),
					'desc'  => __( 'The following options affect the status of the orders placed through the register.', 'woocommerce-point-of-sale' ),
					'type'  => 'title',
					'id'    => 'order_status_options',
				],
				[
					'name'     => __( 'Status Selection', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_disable_status_selection',
					'type'     => 'checkbox',
					'desc'     => __( 'Disable order status selector at the End of Sale', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Check this box to show only the default set status for fulfilling and holding orders; the order status will still be displayed.', 'woocommerce-point-of-sale' ),
					'default'  => 'no',
				],
				[
					'type' => 'sectionend',
					'id'   => 'order_status_options',
				],

				/*
				 * Pay options.
				 */
				[
					'title' => __( 'Pay', 'woocommerce-point-of-sale' ),
					'desc'  => __( 'The following options affect the statuses shown when paying or fulfilling an order through POS.', 'woocommerce-point-of-sale' ),
					'type'  => 'title',
					'id'    => 'pay_options',
				],
				[
					'name'     => __( 'Default Status', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Select the default status of completed orders when using the register.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_fulfilled_order_default_status',
					'class'    => 'wc-enhanced-select',
					'type'     => 'select',
					/**
					 * Fulfilled order statuses
					 *
					 * @since 5.0.0
					 */
					'options'  => apply_filters( 'wc_pos_fulfilled_order_statuses', $order_statuses ),
					'default'  => 'processing',
				],
				[
					'name'     => __( 'Alternative Status', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Select the alternative status of completed orders when using the register.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_fulfilled_order_alternative_status',
					'class'    => 'wc-enhanced-select',
					'type'     => 'select',
					/**
					 * Fulfilled order statuses.
					 *
					 * @since 5.0.0
					 */
					'options'  => apply_filters( 'wc_pos_fulfilled_order_statuses', $order_statuses ),
					'default'  => 'completed',
				],
				[
					'type' => 'sectionend',
					'id'   => 'pay_options',
				],

				/*
				 * Hold options.
				 */
				[
					'title' => __( 'Hold', 'woocommerce-point-of-sale' ),
					'desc'  => __( 'The following options affect the statuses shown when saving or parking an order through POS.', 'woocommerce-point-of-sale' ),
					'type'  => 'title',
					'id'    => 'hold_options',
				],
				[
					'name'     => __( 'Default Status', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Select the default status of saved orders when using the register.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_parked_order_default_status',
					'class'    => 'wc-enhanced-select',
					'type'     => 'select',
					/**
					 * Parked order statuses.
					 *
					 * @since 5.0.0
					 */
					'options'  => apply_filters( 'wc_pos_parked_order_statuses', $order_statuses ),
					'default'  => 'on-hold',
				],
				[
					'name'     => __( 'Alternative Status', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Select the alternative status of saved orders when using the register.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_parked_order_alternative_status',
					'class'    => 'wc-enhanced-select',
					'type'     => 'select',
					/**
					 * Parked order statuses.
					 *
					 * @since 5.0.0
					 */
					'options'  => apply_filters( 'wc_pos_parked_order_statuses', $order_statuses ),
					'default'  => 'pending',
				],
				[
					'type' => 'sectionend',
					'id'   => 'hold_options',
				],
			]
		);
	}

	/**
	 * Filter the list of options for the wc_pos_fulfilled_order_statuses option.
	 *
	 * @since 5.0.0
	 *
	 * @param array $statuses Order statuses.
	 * @return array
	 */
	public static function fulfilled_order_statuses( $statuses ) {
		unset( $statuses['on-hold'] );
		unset( $statuses['pending'] );
		unset( $statuses['cancelled'] );
		unset( $statuses['refunded'] );
		unset( $statuses['failed'] );

		return $statuses;
	}

	/**
	 * Filter the list of options for the wc_pos_parked_order_status option.
	 *
	 * @since 5.0.0
	 *
	 * @param array $statuses Order statuses.
	 * @return array
	 */
	public static function parked_order_statuses( $statuses ) {
		$remove = array_unique(
			array_merge(
				[
					'cancelled',
					'refunded',
					'failed',
					'completed',
					'processing',
				],
				wc_get_is_paid_statuses()
			)
		);

		foreach ( $remove as $status ) {
			unset( $statuses[ $status ] );
		}

		return $statuses;
	}
}
