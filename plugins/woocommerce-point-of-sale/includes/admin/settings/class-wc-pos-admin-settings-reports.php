<?php
/**
 * Settings > Point of Sale > Reports.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/Admin/Settings
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Admin_Settings_Reports.
 */
class WC_POS_Admin_Settings_Reports {

	/**
	 * Returns section settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$order_statuses = wc_pos_get_order_statuses_no_prefix();

		/**
		 * The reports settings section.
		 *
		 * @since 6.0.0
		 */
		return apply_filters(
			'wc_pos_settings_reports_section',
			[
				/*
				 * Report options.
				 */
				[
					'title' => __( 'Report Options', 'woocommerce-point-of-sale' ),
					'desc'  => __( 'The following options affect the reports that are displayed when closing the register.', 'woocommerce-point-of-sale' ),
					'type'  => 'title',
					'id'    => 'report_options',
				],
				[
					'title'    => __( 'Closing Reports', 'woocommerce-point-of-sale' ),
					'desc'     => __( 'Display end of day report when closing register', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'End of day report displayed with total sales when register closes.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_display_end_of_day_report',
					'default'  => 'no',
					'type'     => 'checkbox',
				],
				[
					'title'             => __( 'Report Orders', 'woocommerce-point-of-sale' ),
					'desc_tip'          => __( 'Select which order statuses to include in the final counts displayed in the end of day report.', 'woocommerce-point-of-sale' ),
					'id'                => 'wc_pos_end_of_day_order_statuses',
					'class'             => 'wc-enhanced-select',
					'type'              => 'multiselect',
					'custom_attributes' => [ 'required' => 'required' ],
					'default'           => 'processing',
					'options'           => $order_statuses,
				],
				[
					'type' => 'sectionend',
					'id'   => 'report_options',
				],
				[
					'type' => 'sectionend',
					'id'   => 'reports_options',
				],

				/**
				 *  End of Day email options.
				 */
				[
					'title' => __( 'End of Day Email', 'woocommerce-point-of-sale' ),
					/* translators: %1$s opening anchor tag %2$s closing anchor tag */
					'desc'  => sprintf( __( 'The end of day email notification can be customized in %1$sWooCommerce &gt; Emails%2$s.', 'woocommerce-point-of-sale' ), '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=email&section=wc_pos_email_end_of_day_report' ) . '">', '</a>' ),
					'type'  => 'title',
					'id'    => 'end_of_day_email_options',
				],
				[
					'type' => 'sectionend',
					'id'   => 'end_of_day_email_options',
				],
			]
		);
	}
}
