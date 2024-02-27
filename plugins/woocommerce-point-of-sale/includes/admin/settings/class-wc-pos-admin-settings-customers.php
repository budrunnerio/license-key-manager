<?php
/**
 * Settings > Point of Sale > Customers.
 *
 * @package WooCommerce_Point_Of_Sale/Classes/Admin/Settings
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Admin_Settings_Customers.
 */
class WC_POS_Admin_Settings_Customers {

	/**
	 * Returns section settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$customer_status_field_options = [
			'off'     => __( 'Off', 'woocommerce-point-of-sale' ),
			'default' => __( 'Default', 'woocommerce-point-of-sale' ),
		];

		if ( function_exists( 'acf_get_field_groups' ) && function_exists( 'acf_get_fields' ) ) {

			$acf_field_groups     = acf_get_field_groups();
			$acf_user_form_fields = [];

			foreach ( $acf_field_groups as $acf_field_group ) {
				foreach ( $acf_field_group['location'] as $group_locations ) {
					foreach ( $group_locations as $rule ) {
						if ( 'user_form' === $rule['param'] && '==' === $rule['operator'] && 'all' === $rule['value'] ) {
							$acf_user_form_fields = array_merge(
								$acf_user_form_fields,
								acf_get_fields( $acf_field_group )
							);
						}
					}
				}
			}

			$acf_user_form_fields = array_filter(
				$acf_user_form_fields,
				function ( $field ) {
					return in_array(
						$field['type'],
						[
							'button_group',
							'radio',
							'select',
						]
					);
				}
			);

			$acf_user_form_fields = array_map(
				function ( $field ) {
					return [
						'name'  => 'acf_' . $field['name'],
						'label' => $field['label'],
					];
				},
				$acf_user_form_fields
			);

			$acf_user_form_fields = array_combine(
				array_column( $acf_user_form_fields, 'name' ),
				array_column( $acf_user_form_fields, 'label' )
			);

			$customer_status_field_options = array_merge(
				$customer_status_field_options,
				$acf_user_form_fields
			);
		}

		/**
		 * The customers settings section.
		 *
		 * @since 6.0.0
		 */
		return apply_filters(
			'wc_pos_settings_customers_section',
			[
				/*
				 * Customer options.
				 */
				[
					'title' => __( 'Customer Options', 'woocommerce-point-of-sale' ),
					'desc'  => __( 'The following options affect the account creation process when creating customers.', 'woocommerce-point-of-sale' ),
					'type'  => 'title',
					'id'    => 'customer_options',
				],
				[
					'title'    => __( 'Default Country', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Sets the default country for shipping and customer accounts.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_default_country',
					'css'      => 'min-width:350px;',
					'default'  => 'GB',
					'type'     => 'single_select_country',
				],
				[
					'name'     => __( 'Guest Checkout', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_guest_checkout',
					'type'     => 'checkbox',
					'desc'     => __( 'Enable guest checkout', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Allows register cashiers to process and fulfil an order without choosing a customer.', 'woocommerce-point-of-sale' ),
					'default'  => 'yes',
					'autoload' => true,
				],
				[
					'title'    => __( 'Customer Cards', 'woocommerce-point-of-sale' ),
					'desc'     => __( 'Enable customer cards', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Allow the ability to scan customers cards to load account details.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_enable_user_card',
					'default'  => 'no',
					'type'     => 'checkbox',
				],
				[
					'title'    => __( 'Customer Status', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Select the customer status field.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_customer_status_field',
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:350px;',
					'default'  => 'off',
					'type'     => 'select',
					'options'  => $customer_status_field_options,
				],
				[
					'type' => 'sectionend',
					'id'   => 'customer_options',
				],
				[
					'type' => 'sectionend',
					'id'   => 'customers_options',
				],

				/*
				 * Customer fields options.
				 */
				[
					'title' => __( 'Customer Fields', 'woocommerce-point-of-sale' ),
					'desc'  => __( 'The following options affect the fields presented when creating a customer.', 'woocommerce-point-of-sale' ),
					'type'  => 'title',
					'id'    => 'customer_fields_options',
				],
				[
					'name'     => __( 'Required Fields', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_customer_create_required_fields',
					'type'     => 'multiselect',
					'class'    => 'wc-enhanced-select-required-fields',
					'desc_tip' => __( 'Select the fields that are required when creating a customer through the register.', 'woocommerce-point-of-sale' ),
					'options'  => [
						'billing_first_name'  => __( 'Billing First Name', 'woocommerce-point-of-sale' ),
						'billing_last_name'   => __( 'Billing Last Name', 'woocommerce-point-of-sale' ),
						'billing_email'       => __( 'Billing Email', 'woocommerce-point-of-sale' ),
						'billing_company'     => __( 'Billing Company', 'woocommerce-point-of-sale' ),
						'billing_address_1'   => __( 'Billing Address 1', 'woocommerce-point-of-sale' ),
						'billing_address_2'   => __( 'Billing Address 2', 'woocommerce-point-of-sale' ),
						'billing_city'        => __( 'Billing City', 'woocommerce-point-of-sale' ),
						'billing_state'       => __( 'Billing State', 'woocommerce-point-of-sale' ),
						'billing_postcode'    => __( 'Billing Postcode', 'woocommerce-point-of-sale' ),
						'billing_country'     => __( 'Billing Country', 'woocommerce-point-of-sale' ),
						'billing_phone'       => __( 'Billing Phone', 'woocommerce-point-of-sale' ),
						'shipping_first_name' => __( 'Shipping First Name', 'woocommerce-point-of-sale' ),
						'shipping_last_name'  => __( 'Shipping Last Name', 'woocommerce-point-of-sale' ),
						'shipping_company'    => __( 'Shipping Company', 'woocommerce-point-of-sale' ),
						'shipping_address_1'  => __( 'Shipping Address 1', 'woocommerce-point-of-sale' ),
						'shipping_address_2'  => __( 'Shipping Address 2', 'woocommerce-point-of-sale' ),
						'shipping_city'       => __( 'Shipping City', 'woocommerce-point-of-sale' ),
						'shipping_state'      => __( 'Shipping State', 'woocommerce-point-of-sale' ),
						'shipping_postcode'   => __( 'Shipping Postcode', 'woocommerce-point-of-sale' ),
						'shipping_country'    => __( 'Shipping Country', 'woocommerce-point-of-sale' ),
					],
					'default'  => [
						'billing_first_name',
						'billing_last_name',
						'billing_email',
						'billing_address_1',
						'billing_city',
						'billing_state',
						'billing_postcode',
						'billing_country',
						'billing_phone',
					],
				],
				[
					'title'    => __( 'Custom Checkout Fields', 'woocommerce-point-of-sale' ),
					'desc'     => __( 'Display custom checkout fields', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Check this box to display the custom billing/shipping fields.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_custom_checkout_fields',
					'default'  => 'no',
					'type'     => 'checkbox',
				],
				[
					'name'     => __( 'Optional Fields', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_hide_not_required_fields',
					'type'     => 'checkbox',
					'desc'     => __( 'Hide optional fields when adding customer', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Optional fields will not be shown to make capturing of customer data easier for the cashier.', 'woocommerce-point-of-sale' ),
					'default'  => 'no',
					'autoload' => true,
				],
				[
					'title'    => __( 'Save Customer', 'woocommerce-point-of-sale' ),
					'desc'     => __( 'Toggle save customer by default', 'woocommerce-point-of-sale' ),
					'desc_tip' => __( 'Check this to turn on the Save Customer toggle by default.', 'woocommerce-point-of-sale' ),
					'id'       => 'wc_pos_save_customer_default',
					'default'  => 'no',
					'type'     => 'checkbox',
				],
				[
					'type' => 'sectionend',
					'id'   => 'customer_fields_options',
				],
			]
		);
	}
}
