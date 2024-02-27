<?php
/**
 * Admin Class
 *
 * @package WooCommerce_Point_Of_Sale/Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_POS_Admin', false ) ) {
	return new WC_POS_Admin();
}

/**
 * WC_POS_Admin.
 */
class WC_POS_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'includes' ] );
		add_action( 'admin_footer', 'wc_print_js', 25 );
		add_filter( 'woocommerce_screen_ids', [ $this, 'woocommerce_screen_ids' ], 10, 1 );
		add_filter( 'woocommerce_reports_charts', [ $this, 'pos_reports_charts' ], 20, 1 );
		add_action( 'admin_head', [ $this, 'admin_bar_css' ] );
		add_action( 'wp_head', [ $this, 'admin_bar_css' ] );
		add_filter( 'woocommerce_get_settings_pages', [ $this, 'add_settings_page' ], 10, 1 );

		if ( class_exists( 'SitePress' ) ) {
			$settings = get_option( 'icl_sitepress_settings' );
			if ( 1 === $settings['urls']['directory_for_default_language'] ) {
				add_action( 'generate_rewrite_rules', [ __CLASS__, 'create_rewrite_rules_wpml' ], 9 );
			} else {
				add_filter( 'rewrite_rules_array', [ __CLASS__, 'create_rewrite_rules' ], 11, 1 );
			}
		} else {
			add_filter( 'rewrite_rules_array', [ __CLASS__, 'create_rewrite_rules' ], 11, 1 );
		}
		add_action( 'init', [ __CLASS__, 'on_rewrite_rule' ] );
		add_action( 'wp_loaded', [ __CLASS__, 'flush_rules' ] );
		add_filter( 'query_vars', [ __CLASS__, 'add_query_vars' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( WC_POS_PLUGIN_FILE ), [ __CLASS__, 'plugin_action_links' ] );
		add_filter( 'plugin_row_meta', [ __CLASS__, 'plugin_row_meta' ], 10, 2 );

		add_filter( 'woocommerce_prevent_admin_access', [ __CLASS__, 'prevent_admin_access' ], 10, 2 );

		// Product grids.
		add_filter( 'manage_edit-product_columns', [ $this, 'add_product_grid_column' ], 9999 );
		add_action( 'manage_product_posts_custom_column', [ $this, 'display_product_grid_column' ], 2 );
		add_action( 'admin_footer', [ $this, 'product_grid_bulk_actions' ], 11 );
		add_action( 'load-edit.php', [ $this, 'product_grid_bulk_actions_handler' ] );
		add_action( 'trashed_post', [ $this, 'remove_grid_product_tile' ] );
		add_action( 'deleted_post', [ $this, 'remove_grid_product_tile' ] );
		add_action( 'delete_term', [ $this, 'remove_grid_category_tile' ], 10, 3 );

		if ( 'yes' === get_option( 'wc_pos_visibility', 'no' ) ) {
			add_action( 'post_submitbox_misc_actions', [ $this, 'product_pos_visibility' ] );
		}

		add_action( 'admin_bar_menu', [ $this, 'show_admin_bar_pos_registers' ], 100 );
		add_action( 'woocommerce_admin_field_button', [ $this, 'wc_settings_button_field' ] );
		add_action( 'woocommerce_admin_field_range_slider', [ $this, 'wc_settings_range_slider' ] );
		add_action( 'woocommerce_admin_field_media_upload', [ $this, 'wc_settings_media_upload' ] );

		// Manage outlet stock changes.
		if ( 'yes' === get_option( 'wc_pos_manage_outlet_stock' ) ) {
			add_filter( 'woocommerce_can_restore_order_stock', [ $this, 'increase_outlet_stock_levels' ], 10, 2 );
			add_filter( 'woocommerce_can_reduce_order_stock', [ $this, 'reduce_outlet_stock_levels' ], 10, 2 );
			add_filter( 'woocommerce_can_restock_refunded_items', [ $this, 'restock_refunded_items' ], 10, 3 );
			add_filter( 'woocommerce_prevent_adjust_line_item_product_stock', [ $this, 'prevent_adjust_line_item_product_stock' ], 999, 3 );
		}

		$this->init_users_hooks();
	}

	/**
	 * Add plugin admin screens to the WC screens.
	 *
	 * @param array $screen_ids
	 * @return array
	 */
	public function woocommerce_screen_ids( $screen_ids ) {
		return array_merge( $screen_ids, wc_pos_get_screen_ids() );
	}

	public function product_pos_visibility() {
		global $post;

		if ( 'product' !== $post->post_type ) {
			return;
		}

		$pos_visibility = get_post_meta( $post->ID, '_pos_visibility', true );
		$pos_visibility = $pos_visibility ? $pos_visibility : 'pos_online';

		/**
		 * Visibility options.
		 *
		 * @since 5.0.0
		 */
		$visibility_options = apply_filters(
			'wc_pos_visibility_options',
			[
				'pos_online' => __( 'POS &amp; Online', 'woocommerce-point-of-sale' ),
				'pos'        => __( 'POS Only', 'woocommerce-point-of-sale' ),
				'online'     => __( 'Online Only', 'woocommerce-point-of-sale' ),
			]
		); ?>
		<div class="misc-pub-section" id="pos-visibility">
			<?php esc_html_e( 'POS visibility:', 'woocommerce-point-of-sale' ); ?>
			<strong id="pos-visibility-display">
				<?php echo isset( $visibility_options[ $pos_visibility ] ) ? esc_html( $visibility_options[ $pos_visibility ] ) : esc_html( $pos_visibility ); ?>
			</strong>

			<a href="#pos-visibility" class="edit-pos-visibility hide-if-no-js"><?php esc_html_e( 'Edit', 'woocommerce-point-of-sale' ); ?></a>

			<div id="pos-visibility-select" class="hide-if-js">

				<input type="hidden" name="current_pos_visibility" id="current_visibility" value="<?php echo esc_attr( $pos_visibility ); ?>"/>
				<?php
				foreach ( $visibility_options as $name => $label ) {
					echo '<input type="radio" name="_pos_visibility" id="pos_visibility_' . esc_attr( $name ) . '" value="' . esc_attr( $name ) . '" ' . checked( $pos_visibility, $name, false ) . ' data-label="' . esc_attr( $label ) . '" /> <label for="_visibility_' . esc_attr( $name ) . '" class="selectit">' . esc_html( $label ) . '</label><br />';
				}
				?>
				<p>
					<a href="#pos-visibility" class="save-post-visibility hide-if-no-js button"><?php esc_html_e( 'OK', 'woocommerce-point-of-sale' ); ?></a>
					<a href="#pos-visibility" class="cancel-post-visibility hide-if-no-js"><?php esc_html_e( 'Cancel', 'woocommerce-point-of-sale' ); ?></a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {
		include_once 'class-wc-pos-admin-menus.php';
		include_once 'class-wc-pos-admin-notices.php';
		include_once 'class-wc-pos-admin-orders-page.php';
	}

	public function init_users_hooks() {
		add_action( 'show_user_profile', [ $this, 'add_customer_meta_fields' ] );
		add_action( 'edit_user_profile', [ $this, 'add_customer_meta_fields' ] );

		add_action( 'personal_options_update', [ $this, 'save_customer_meta_fields' ] );
		add_action( 'edit_user_profile_update', [ $this, 'save_customer_meta_fields' ] );
	}

	public function pos_reports_charts( $reports ) {
		$reports['pos'] = [
			'title'   => __( 'POS', 'woocommerce-point-of-sale' ),
			'reports' => [
				'sales_by_register' => [
					'title'       => __( 'Sales by register', 'woocommerce-point-of-sale' ),
					'description' => '',
					'hide_title'  => true,
					'callback'    => [ $this, 'get_report' ],
				],
				'sales_by_outlet'   => [
					'title'       => __( 'Sales by outlet', 'woocommerce-point-of-sale' ),
					'description' => '',
					'hide_title'  => true,
					'callback'    => [ $this, 'get_report' ],
				],
				'sales_by_cashier'  => [
					'title'       => __( 'Sales by cashier', 'woocommerce-point-of-sale' ),
					'description' => '',
					'hide_title'  => true,
					'callback'    => [ $this, 'get_report' ],
				],
			],
		];
		return $reports;
	}

	/**
	 * Get a report from our reports subfolder
	 */
	public static function get_report( $name ) {
		$name  = sanitize_title( str_replace( '_', '-', $name ) );
		$class = 'WC_POS_Report_' . str_replace( '-', '_', $name );

		/**
		 * Admin reports path.
		 *
		 * @since 5.0.0
		 */
		include_once apply_filters( 'wc_pos_admin_reports_path', WC_POS()->plugin_path() . '/includes/reports/class-wc-pos-report-' . $name . '.php', $name, $class );

		if ( ! class_exists( $class ) ) {
			return;
		}

		$report = new $class();
		$report->output_report();
	}

	public static function get_sessions_table() {
		include_once WC_POS_ABSPATH . '/includes/admin/list-tables/class-wc-pos-admin-list-table-sessions.php';
		$table = new WC_POS_Admin_List_Table_Sessions();
	}

	public function admin_bar_css() {
		?>
		<style>
			#wpadminbar #wp-admin-bar-wc_pos_admin_bar_registers .ab-icon::before {
				content: "\f513";
				top: 0;
				font-size: 0.75em;
			}
		</style>
		<?php
	}

	public function add_settings_page( $settings ) {
		$settings[] = include __DIR__ . '/settings/class-wc-pos-admin-settings.php';
		return $settings;
	}

	/**
	 * Show POS fields on edit user pages.
	 *
	 * @param mixed $user User (object) being displayed
	 */
	public function add_customer_meta_fields( $user ) {

		if ( ! current_user_can( 'manage_woocommerce_point_of_sale' ) ) {
			return;
		}

		$show_fields = $this->get_customer_meta_fields();

		foreach ( $show_fields as $fieldset ) :
			?>
			<h3><?php echo esc_html( $fieldset['title'] ); ?></h3>
			<table class="form-table" id="pos_custom_user_fields">
				<?php
				foreach ( $fieldset['fields'] as $key => $field ) :
					?>
					<tr>
						<th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
						</th>
						<td>
							<?php
							if ( isset( $field['type'] ) && 'select' === $field['type'] ) {
								$value_user_meta = (array) get_user_meta( $user->ID, $key, true );
								$multiple        = isset( $field['multiple'] ) && $field['multiple'] ? 'multiple' : '';
								?>
								<select name="<?php echo isset( $field['name'] ) ? esc_attr( $field['name'] ) : esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" <?php echo esc_attr( $multiple ); ?> style="min-width:350px;" class="wc-enhanced-select">
									<?php
									foreach ( $field['options'] as $label_value => $label ) {
										echo '<option value="' . esc_attr( $label_value ) . '" ' . ( ( in_array( $label_value, $value_user_meta ) ) ? 'selected' : '' ) . ' >' . esc_html( $label ) . '</option>';
									}
									?>
								</select>
							<?php } elseif ( 'input' === $field['type'] && 'wc_pos_user_card_number' === $key ) { ?>
								<?php $card = get_user_meta( $user->ID, $key, true ); ?>
								<input type="text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $card ); ?>" <?php echo ! empty( $card ) ? 'disabled' : ''; ?> class="regular-text"/>
								<a id="enable_card" class="button">
									<?php esc_html_e( 'Change Card Number', 'woocommerce-point-of-sale' ); ?>
								</a>
								<?php
							} elseif ( true ) {
								$val = get_user_meta( $user->ID, $key, true );
								?>
								<label for="<?php echo esc_attr( $key ); ?>">
									<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="1" <?php checked( isset( $val ) && 'yes' === $val ); ?>>
									<?php echo isset( $field['desc'] ) ? esc_html( $field['desc'] ) : ''; ?>
								</label>
							<?php } else { ?>
								<input type="text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( get_user_meta( $user->ID, $key, true ) ); ?>" class="regular-text"/><br/>
							<?php } ?>
							<br>
							<span class="description"><?php echo isset( $field['description'] ) ? wp_kses_post( $field['description'] ) : ''; ?></span>
						</td>
					</tr>
					<?php
				endforeach;
				?>
			</table>
			<?php
		endforeach;
	}

	/**
	 * Save Fields on edit user pages
	 *
	 * @param mixed $user_id User ID of the user being saved
	 */
	public function save_customer_meta_fields( $user_id ) {
		check_admin_referer( 'update-user_' . $user_id );

		$save_fields = $this->get_customer_meta_fields();

		foreach ( $save_fields as $fieldset ) {
			foreach ( $fieldset['fields'] as $key => $field ) {
				if ( 'checkbox' === $field['type'] ) {
					update_user_meta( $user_id, $key, isset( $_POST[ $key ] ) ? 'yes' : 'no' );
				} elseif ( 'select' === $field['type'] && isset( $field['multiple'] ) && $field['multiple'] ) {
					update_user_meta( $user_id, $key, isset( $_POST[ $key ] ) ? wc_clean( $_POST[ $key ] ) : [] );
				} else {
					if ( isset( $_POST[ $key ] ) ) {
						update_user_meta( $user_id, $key, wc_clean( $_POST[ $key ] ) );
					}
				}
			}
		}
	}

	/**
	 * Get Fields for the edit user pages.
	 *
	 * @todo rename user meta fields to:
	 *   wc_pos_user_card_number     => wc_pos_card_number
	 *   wc_pos_enable_user_card     => wc_pos_enable_card
	 *   wc_pos_assigned_outlets     => wc_pos_outlets
	 *   wc_pos_enable_tender_orders => wc_pos_can_tender_orders
	 *   wc_pos_enable_discount      => wc_pos_can_discount_orders
	 *
	 * @return array Fields to display which are filtered through wc_pos_customer_meta_fields before being returned
	 */
	public function get_customer_meta_fields() {
		/**
		 * Customer meta fields.
		 *
		 * @since 5.0.0
		 */
		$show_fields = apply_filters(
			'wc_pos_customer_meta_fields',
			[
				'outlet_fields' => [
					'title'  => __( 'Point of Sale', 'woocommerce-point-of-sale' ),
					'fields' => [
						'wc_pos_assigned_outlets' => [
							'label'       => __( 'Assigned Outlets', 'woocommerce-point-of-sale' ),
							'class'       => 'wc-enhanced-select enhanced',
							'type'        => 'select',
							'name'        => 'wc_pos_assigned_outlets[]',
							'multiple'    => true,
							'options'     => wc_pos_get_register_outlet_options(),
							'description' => __( 'Ensure the user is logged out before changing the outlet.', 'woocommerce-point-of-sale' ),
						],
						'wc_pos_enable_discount'  => [
							'label'       => __( 'Discount', 'woocommerce-point-of-sale' ),
							'type'        => 'select',
							'options'     => [
								'yes' => 'Enable',
								'no'  => 'Disable',
							],
							'description' => 'Disable discount ability, user will only be able to enter coupons and add fees.',
						],
					],
				],
			]
		);

		if ( 'yes' === get_option( 'wc_pos_enable_user_card', 'no' ) ) {
			$show_fields['outlet_fields']['fields']['wc_pos_user_card_number'] = [
				'label'       => __( 'Card Number', 'woocommerce-point-of-sale' ),
				'type'        => 'input',
				'description' => 'Enter the number of the card to associate this customer with.',
			];
		}

		if ( 'default' === get_option( 'wc_pos_customer_status_field' ) ) {
			$show_fields['outlet_fields']['fields']['wc_pos_customer_status'] = [
				'label'       => __( 'Customer Status', 'woocommerce-point-of-sale' ),
				'type'        => 'select',
				'options'     => array_merge(
					[ '' => __( 'None', 'woocommerce-point-of-sale' ) ],
					wc_pos_get_default_customer_statuses()
				),
				'description' => 'Select the customer status for this user.',
			];
		}

		$show_fields['outlet_fields']['fields']['wc_pos_enable_tender_orders'] = [
			'label'       => __( 'Tender Orders', 'woocommerce-point-of-sale' ),
			'description' => 'Disable tendering ability, user will only be able to hold orders.',
			'type'        => 'select',
			'options'     => [
				'yes' => 'Enable',
				'no'  => 'Disable',
			],
		];
		return $show_fields;
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @param mixed $links Plugin Action links
	 * @return  array
	 */
	public static function plugin_action_links( $links ) {
		$action_links = [
			'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=point-of-sale' ) . '" title="' . esc_attr( __( 'View Settings', 'woocommerce-point-of-sale' ) ) . '">' . __( 'Settings', 'woocommerce-point-of-sale' ) . '</a>',
		];

		return array_merge( $action_links, $links );
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param mixed $links Plugin Row Meta
	 * @param mixed $file Plugin Base file
	 * @return  array
	 */
	public static function plugin_row_meta( $links, $file ) {
		if ( plugin_basename( WC_POS_PLUGIN_FILE ) === $file ) {
			$row_meta = [
				/**
				 * Docs URL.
				 *
				 * @since 5.0.0
				 */
				'docs'    => '<a href="' . esc_url( apply_filters( 'wc_pos_docs_url', 'https://docs.woocommerce.com/document/point-of-sale/' ) ) . '" title="' . esc_attr( __( 'View Documentation', 'woocommerce-point-of-sale' ) ) . '">' . __( 'Documentation', 'woocommerce-point-of-sale' ) . '</a>',
				/**
				 * Contact URL.
				 *
				 * @since 5.0.0
				 */
				'support' => '<a href="' . esc_url( apply_filters( 'wc_pos_contact_url', 'https://woocommerce.com/my-account/marketplace-ticket-form/' ) ) . '" title="' . esc_attr( __( 'Visit Support', 'woocommerce-point-of-sale' ) ) . '">' . __( 'Support', 'woocommerce-point-of-sale' ) . '</a>',
			];

			return array_merge( $links, $row_meta );
		}

		return (array) $links;
	}

	public static function create_rewrite_rules( $rules ) {
		global $wp_rewrite;
		$newRule  = [ '^point-of-sale/([^/]+)/([^/]+)/?$' => 'index.php?page=wc-pos-register&action=view&outlet=$matches[1]&register=$matches[2]' ];
		$newRules = $newRule + $rules;
		return $newRules;
	}

	public static function create_rewrite_rules_wpml() {
		global $wp_rewrite;
		$newRule = [ '^point-of-sale/([^/]+)/([^/]+)/?$' => 'index.php?page=wc-pos-register&action=view&outlet=$matches[1]&register=$matches[2]' ];

		$wp_rewrite->rules = $newRule + $wp_rewrite->rules;
	}

	public static function on_rewrite_rule() {
		add_rewrite_rule( '^point-of-sale/([^/]+)/([^/]+)/?$', 'index.php?page=wc-pos-register&action=view&outlet=$matches[1]&register=$matches[2]', 'top' );
	}

	public static function flush_rules() {
		$rules = get_option( 'rewrite_rules' );

		if ( ! isset( $rules['^point-of-sale/([^/]+)/([^/]+)/?$'] ) ) {
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
		}
	}

	public static function add_query_vars( $public_query_vars ) {
		$public_query_vars[] = 'page';
		$public_query_vars[] = 'action';
		$public_query_vars[] = 'outlet';
		$public_query_vars[] = 'register';

		return $public_query_vars;
	}

	public static function prevent_admin_access( $prevent_access ) {
		if ( current_user_can( 'view_register' ) ) {
			$prevent_access = false;
		}
		return $prevent_access;
	}

	public function add_product_grid_column( $columns ) {
		$new_columns = [];
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( 'product_tag' === $key ) {
				$new_columns['wc_pos_product_grid'] = __( 'Product Grid', 'woocommerce-point-of-sale' );
			}
		}
		return $new_columns;
	}

	public function display_product_grid_column( $column ) {
		global $post, $woocommerce;

		if ( 'wc_pos_product_grid' === $column ) {
			$product_id = $post->ID;
			$grids      = wc_pos_get_tile_grids( $product_id, 'product', true );
			$links      = [];
			if ( ! empty( $grids ) ) {
				foreach ( $grids as $id => $name ) {
					$url     = admin_url( 'post.php?post=' . $id . '&action=edit' );
					$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
				}
				echo wp_kses_post( implode( ', ', $links ) );
			} else {
				echo '<span class="na">–</span>';
			}
		}
	}

	public function product_grid_bulk_actions() {
		global $post_type;
		if ( 'product' === $post_type ) {
			?>
			<script type="text/javascript">
				jQuery(document).ready(function () {
					<?php
					$grids = get_posts(
						[
							'numberposts' => -1,
							'post_type'   => 'pos_grid',
						]
					);
					if ( ! empty( $grids ) ) {
						foreach ( $grids as $grid ) {
							/* translators: %s grid name */
							$add_to_text = sprintf( __( 'Add to %s', 'woocommerce-point-of-sale' ), $grid->post_title );
							?>
							jQuery('<option>').val('wc_pos_add_to_grid_<?php echo esc_js( $grid->ID ); ?>')
								.text('<?php echo esc_js( $add_to_text ); ?>').appendTo('select[name=action]');
							jQuery('<option>').val('wc_pos_add_to_grid_<?php echo esc_js( $grid->ID ); ?>')
								.text('<?php echo esc_js( $add_to_text ); ?>').appendTo('select[name=action2]');
							<?php
						}
					}
					?>
				});
			</script>
			<?php
		}
	}

	public function product_grid_bulk_actions_handler() {
		if ( ! isset( $_REQUEST['post'] ) ) {
			return;
		}

		$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
		$action        = $wp_list_table->current_action();

		global $wpdb;
		$changed  = 0;
		$post_ids = array_map( 'absint', (array) $_REQUEST['post'] );

		if ( ! strstr( $action, 'wc_pos_add_to_grid_' ) ) {
			return;
		}

		$grid_id       = (int) substr( $action, strlen( 'wc_pos_add_to_grid_' ) );
		$report_action = 'products_added_to_grid';

		foreach ( $post_ids as $post_id ) {
			if ( wc_pos_is_in_grid( $grid_id, $post_id ) ) {
				continue;
			}

			$grid = wc_pos_get_grid( $grid_id );
			$grid->add_tile(
				[
					'type'    => 'product',
					'item_id' => $post_id,
				]
			);
			$grid->save();

			$changed++;
		}

		$sendback = esc_url_raw(
			add_query_arg(
				[
					'post_type'    => 'product',
					$report_action => $changed,
					'ids'          => join( ',', $post_ids ),
				],
				''
			)
		);

		wp_redirect( $sendback );
		exit();
	}

	/**
	 * Removes a product tile from grids if trashed/deleted.
	 *
	 * @param $post_id Post ID.
	 */
	public function remove_grid_product_tile( $post_id ) {
		if ( ! $post_id || 'product' !== get_post_type( $post_id ) ) {
			return;
		}

		$grids = wc_pos_get_tile_grids( $post_id, 'product' );

		foreach ( $grids as $grid_id ) {
			$grid = wc_pos_get_grid( $grid_id );

			if ( $grid ) {
				$tile_id = wc_pos_get_grid_tile_by_item_id( $grid_id, $post_id );

				$grid->delete_tile( $tile_id );
				$grid->save();
			}
		}
	}

	/**
	 * Removes a category tile from grids if deleted.
	 *
	 * @param int $term     Term ID.
	 * @param int $tt_id    Term taxonomy ID.
	 * @param int $taxonomy Taxonomy slug.
	 */
	public function remove_grid_category_tile( $term, $tt_id, $taxonomy ) {
		if ( ! $term || 'product_cat' !== $taxonomy ) {
			return;
		}

		$grids = wc_pos_get_tile_grids( $term, 'product_cat' );

		foreach ( $grids as $grid_id ) {
			$grid    = wc_pos_get_grid( $grid_id );
			$tile_id = wc_pos_get_grid_tile_by_item_id( $grid_id, $term, 'product_cat' );

			$grid->delete_tile( $tile_id );
			$grid->save();
		}
	}

	/**
	 * Show a Point of Sale menu in admin bar.
	 *
	 * @param WP_Admin_Bar $admin_bar
	 */
	public function show_admin_bar_pos_registers( $admin_bar ) {
		if ( ! current_user_can( 'view_register' ) ) {
			return;
		}

		$admin_bar->add_menu(
			[
				'id'    => 'wc_pos_admin_bar_registers',
				'title' => '<span class="ab-icon"></span><span class="ab-label">' . __( 'Point of Sale', 'woocommerce-point-of-sale' ) . '</span>',
				'href'  => admin_url( 'edit.php?post_type=pos_register' ),
				'icon'  => 'asd',
				'meta'  => [
					'title' => __( 'POS', 'woocommerce-point-of-sale' ),
					'icon'  => 'asd',
				],
			]
		);

		$admin_bar->add_menu(
			[
				'id'     => 'wc_pos_admin_bar_all_registers',
				'parent' => 'wc_pos_admin_bar_registers',
				'title'  => __( 'All registers', 'woocommerce-point-of-sale' ),
				'href'   => admin_url( 'edit.php?post_type=pos_register' ),
				'meta'   => [
					'title' => __( 'All registers', 'woocommerce-point-of-sale' ),
				],
			]
		);
	}

	public function wc_settings_button_field( $value ) {
		$option_value      = get_option( $value['id'], $value['default'] );
		$field_description = WC_Admin_Settings::get_field_description( $value );
		$description       = $field_description['description'];
		$tooltip_html      = $field_description['tooltip_html'];
		$custom_attributes = [];

		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}
		$value['class'] = empty( $value['class'] ) ? 'button' : $value['class'];
		?>
		<tr valign="top">
		<th scope="row" class="titledesc">
			<label for=""><?php echo esc_html( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); ?></label>
		</th>
		<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
			<input
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					type="<?php echo esc_attr( $value['type'] ); ?>"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					value="<?php echo esc_attr( $value['button_title'] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
				<?php echo esc_attr( implode( ' ', $custom_attributes ) ); ?>
			/><?php echo esc_html( $value['suffix'] ); ?> <?php echo esc_html( $description ); ?>
		</td>
		</tr>
		<?php
	}

	public function wc_settings_range_slider( $field ) {
		$option_value      = get_option( $field['id'], $field['default'] );
		$field_description = WC_Admin_Settings::get_field_description( $field );
		$description       = $field_description['description'];
		$tooltip_html      = $field_description['tooltip_html'];
		$custom_attributes = [];

		if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) {
			foreach ( $field['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for=""><?php echo esc_html( $field['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $field['type'] ) ); ?>">
				<div class="range_slider_container">
					<span class="range_slider_value"><?php echo esc_attr( $option_value ); ?></span>
					<input
							name="<?php echo esc_attr( $field['id'] ); ?>"
							id="<?php echo esc_attr( $field['id'] ); ?>"
							style="<?php echo esc_attr( $field['css'] ); ?>"
							type="range"
							value="<?php echo esc_attr( $option_value ); ?>"
							class="range_slider <?php echo esc_attr( $field['class'] ); ?>"
							<?php echo esc_attr( implode( ' ', $custom_attributes ) ); ?>
					/><?php echo esc_html( $field['suffix'] ); ?> <?php echo esc_html( $description ); ?>
				</div>
			</td>
		</tr>
		<?php
	}

	public function wc_settings_media_upload( $field ) {
		$option_value      = get_option( $field['id'], $field['default'] );
		$field_description = WC_Admin_Settings::get_field_description( $field );
		$description       = $field_description['description'];
		$tooltip_html      = $field_description['tooltip_html'];
		$custom_attributes = [];
		$thumbnail_src     = wp_get_attachment_image_src( $option_value );
		$thumbnail_src     = $thumbnail_src ? $thumbnail_src[0] : wc_placeholder_img_src();

		if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) {
			foreach ( $field['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for=""><?php echo esc_html( $field['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $field['type'] ) ); ?>" id="<?php echo esc_attr( $field['id'] ); ?>">
				<div class="image-thumbnail">
					<img src="<?php echo esc_url( $thumbnail_src ); ?>" width="60px" height="60px" />
				</div>
				<div style="margin-top: 10px;">
					<input
						type="hidden"
						name="<?php echo esc_attr( $field['id'] ); ?>"
						value="<?php echo esc_attr( $option_value ); ?>"
					/>
					<button type="button" class="upload-image-button button"><?php esc_html_e( 'Upload/Add image', 'woocommerce-point-of-sale' ); ?></button>
					<button type="button" class="remove-image-button button"><?php esc_html_e( 'Remove image', 'woocommerce-point-of-sale' ); ?></button>
				</div>
				<script type="text/javascript">
					( function ( $ ) {
						$( document ).ready( function () {
							var field_selector = '#<?php echo esc_html( $field['id'] ); ?>';
							var input_selector = '[name=<?php echo esc_html( $field['id'] ); ?>]';

							// Only show the "remove image" button when needed
							if ( ! $( input_selector ).val() ) {
								$( field_selector + ' .remove-image-button' ).hide();
							}

							// Uploading files
							var file_frame;

							$( document ).on( 'click', field_selector + ' .upload-image-button', function( event ) {

								event.preventDefault();

								// If the media frame already exists, reopen it.
								if ( file_frame ) {
									file_frame.open();
									return;
								}

								// Create the media frame.
								file_frame = wp.media.frames.downloadable_file = wp.media({
									title: '<?php esc_html_e( 'Choose an image', 'woocommerce-point-of-sale' ); ?>',
									button: {
										text: '<?php esc_html_e( 'Use image', 'woocommerce-point-of-sale' ); ?>'
									},
									multiple: false
								});

								// When an image is selected, run a callback.
								file_frame.on( 'select', function() {
									var attachment           = file_frame.state().get( 'selection' ).first().toJSON();
									var attachment_thumbnail = attachment.sizes.thumbnail || attachment.sizes.full;

									$( input_selector ).val( attachment.id );
									$( field_selector + ' .image-thumbnail' ).find( 'img' ).attr( 'src', attachment_thumbnail.url );
									$( field_selector + ' .remove-image-button' ).show();
								});

								// Finally, open the modal.
								file_frame.open();
							});

							$( document ).on( 'click', '.remove-image-button', function() {
								$( field_selector + ' .image-thumbnail' ).find( 'img' ).attr( 'src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>' );
								$( input_selector ).val( '' );
								$( field_selector + ' .remove-image-button' ).hide();
								return false;
							});
						} );
					} ( jQuery ) );
				</script>
			</td>
		</tr>
		<?php
	}

	public function increase_outlet_stock_levels( $increase, $order ) {
		$outlet_id = $this->get_outlet_id_to_manage_stock( $order );

		if ( ! $outlet_id || ! $this->is_order_created_via_pos( $order ) ) {
			return $increase;
		}

		$changes = [];

		foreach ( $order->get_items() as $item ) {
			if ( ! $item->is_type( 'line_item' ) ) {
				continue;
			}

			// Only increase stock once for each item.
			$product            = $item->get_product();
			$item_stock_reduced = absint( $item->get_meta( '_reduced_stock', true ) );

			if ( ! $item_stock_reduced || ! $product || ! $product->managing_stock() ) {
				continue;
			}

			$item_name        = $product->get_formatted_name();
			$new_outlet_stock = wc_pos_update_product_outlet_stock( $product, [ $outlet_id => $item_stock_reduced ], 'increase' );

			if ( false === $new_outlet_stock || is_wp_error( $new_outlet_stock ) ) {
				/* translators: %s item name. */
				$order->add_order_note( sprintf( __( 'Unable to restore stock for item %s.', 'woocommerce-point-of-sale' ), $item_name ) );
				continue;
			}

			$item->delete_meta_data( '_reduced_stock' );
			$item->save();

			$changes[] = $item_name . ' ' . ( $new_outlet_stock[ $outlet_id ] - $item_stock_reduced ) . '&rarr;' . $new_outlet_stock[ $outlet_id ];
		}

		if ( $changes ) {
			$order->add_order_note( __( 'Stock levels increased:', 'woocommerce-point-of-sale' ) . ' ' . implode( ', ', $changes ) );
		}

		// Don't increase stock levels. We have already taken care of that.
		return false;
	}

	public function reduce_outlet_stock_levels( $reduce, $order ) {
		$outlet_id = $this->get_outlet_id_to_manage_stock( $order );

		if ( ! $outlet_id || ! $this->is_order_created_via_pos( $order ) ) {
			return $reduce;
		}

		$changes = [];

		foreach ( $order->get_items() as $item ) {
			if ( ! $item->is_type( 'line_item' ) ) {
				continue;
			}

			// Only reduce stock once for each item.
			$product            = $item->get_product();
			$item_stock_reduced = $item->get_meta( '_reduced_stock', true );

			if ( $item_stock_reduced || ! $product || ! $product->managing_stock() ) {
				continue;
			}

			/**
			 * This filter is documented in WC core.
			 *
			 * @since 6.0.0
			 */
			$qty              = apply_filters( 'woocommerce_order_item_quantity', $item->get_quantity(), $order, $item );
			$item_name        = $product->get_formatted_name();
			$new_outlet_stock = wc_pos_update_product_outlet_stock( $product, [ $outlet_id => $qty ], 'decrease' );

			if ( false === $new_outlet_stock || is_wp_error( $new_outlet_stock ) ) {
				/* translators: %s item name. */
				$order->add_order_note( sprintf( __( 'Unable to reduce stock for item %s.', 'woocommerce-point-of-sale' ), $item_name ) );
				continue;
			}

			$item->add_meta_data( '_reduced_stock', $qty, true );
			$item->save();

			$changes[] = [
				'product' => $product,
				'from'    => $new_outlet_stock[ $outlet_id ] + $qty,
				'to'      => $new_outlet_stock[ $outlet_id ],
			];
		}

		wc_trigger_stock_change_notifications( $order, $changes );

		// Don't reduce stock levels. We have already taken care of that.
		return false;
	}

	public function restock_refunded_items( $restock, $order, $refunded_line_items ) {
		$outlet_id = $this->get_outlet_id_to_manage_stock( $order );

		if ( ! $outlet_id || ! $this->is_order_created_via_pos( $order ) ) {
			return $restock;
		}

		$line_items = $order->get_items();

		foreach ( $line_items as $item_id => $item ) {
			if ( ! isset( $refunded_line_items[ $item_id ], $refunded_line_items[ $item_id ]['qty'] ) ) {
				continue;
			}

			$product                = $item->get_product();
			$item_stock_reduced     = $item->get_meta( '_reduced_stock', true );
			$restock_refunded_items = (int) $item->get_meta( '_restock_refunded_items', true );
			$qty_to_refund          = $refunded_line_items[ $item_id ]['qty'];

			if ( ! $item_stock_reduced || ! $qty_to_refund || ! $product || ! $product->managing_stock() ) {
				continue;
			}

			$old_outlet_stock = wc_pos_get_product_outlet_stock( $product->get_id() );
			$old_stock        = isset( $old_outlet_stock[ $outlet_id ] ) ? $old_outlet_stock[ $outlet_id ] : 0;

			$new_outlet_stock = wc_pos_update_product_outlet_stock( $product, [ $outlet_id => $old_stock + $qty_to_refund ], 'update' );
			$new_stock        = $new_outlet_stock[ $outlet_id ];

			// Update _reduced_stock meta to track changes.
			$item_stock_reduced = $item_stock_reduced - $qty_to_refund;

			$item->update_meta_data( '_reduced_stock', $item_stock_reduced );
			$item->update_meta_data( '_restock_refunded_items', $qty_to_refund + $restock_refunded_items );

			/* translators: 1: product ID 2: old stock level 3: new stock level */
			$restock_note = sprintf( __( 'Item #%1$s stock increased from %2$s to %3$s.', 'woocommerce-point-of-sale' ), $product->get_id(), $old_stock, $new_stock );

			/**
			* This filter is documented in WC core.
			*
			* @since 6.0.0
			*/
			$restock_note = apply_filters( 'woocommerce_refund_restock_note', $restock_note, $old_stock, $new_stock, $order, $product );

			$order->add_order_note( $restock_note );

			$item->save();
		}

		// Don't restock the items. We have already taken care of that.
		return false;
	}

	/**
	 * We are not interested in adjusting line item product stock if outlet stock management is enabled.
	 *
	 * @param boolean       $prevent
	 * @param WC_Order_Item $item
	 * @param int           $item_quantity
	 */
	public function prevent_adjust_line_item_product_stock( $prevent, $item, $item_quantity ) {
		$order     = $item->get_order();
		$outlet_id = $this->get_outlet_id_to_manage_stock( $order );

		if ( ! $outlet_id || ! $this->is_order_created_via_pos( $order ) ) {
			return $prevent;
		}

		return true;
	}

	/**
	 * Checks if the order was created via POS.
	 *
	 * @param WC_Order $order
	 * @return boolean
	 */
	private function is_order_created_via_pos( $order ) {
		$created_via = $order->get_created_via();
		$register_id = $order->get_meta( 'wc_pos_register_id' );

		// Register ID is a second check if _created_via has not yet changed from 'rest-api' to
		// 'pos'.
		return 'pos' === $created_via || ( 'rest-api' === $created_via && ! empty( $register_id ) );
	}

	/**
	 * Returns outlet ID to manage stock for the given order.
	 *
	 * @param WC_Oorder $order
	 * @return int
	 */
	private function get_outlet_id_to_manage_stock( $order ) {
		$outlet_id = wc_pos_get_order_outlet_id( $order );

		if ( $outlet_id && (int) get_option( 'wc_pos_default_outlet', 0 ) !== $outlet_id ) {
			return $outlet_id;
		}

		return 0;
	}
}

return new WC_POS_Admin();
