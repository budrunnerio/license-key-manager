<?php
/**
 * Orders Page
 *
 * @package WooCommerce_Point_Of_Sale/Classes/Admin
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_POS_Admin_Orders_Page', false ) ) {
	return new WC_POS_Admin_Orders_Page();
}

/**
 * WC_POS_Admin_Orders_Page.
 */
class WC_POS_Admin_Orders_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_admin_order_buyer_name', [ $this, 'update_order_buyer_name' ], 10, 2 );

		// CPT orders.
		add_action( 'manage_shop_order_posts_custom_column', [ $this, 'display_order_type_column_cpt' ], 10, 2 );
		add_filter( 'manage_edit-shop_order_columns', [ $this, 'add_order_type_column' ], 9999 );

		// HPOS orders.
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', [ $this, 'display_order_type_column_hpos' ], 10, 2 );
		add_filter( 'manage_woocommerce_page_wc-orders_columns', [ $this, 'add_order_type_column' ], 9999 );

		add_action( 'restrict_manage_posts', [ $this, 'restrict_manage_orders' ] );
		add_action( 'woocommerce_order_list_table_restrict_manage_orders', [ $this, 'restrict_manage_orders' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_pos_print_metabox' ] );
	}

	/**
	 * Change guest order buyer's name.
	 *
	 * @param string   $buyer Buyer name.
	 * @param WC_Order $order Order object.
	 *
	 * @return string Updated buyer's name.
	 */
	public function update_order_buyer_name( $buyer, $order ) {
		if ( 'pos' === $order->get_data()['created_via'] && ! $buyer ) {
			$buyer = __( 'Walk-in Customer', 'woocommerce-point-of-sale' );
		}

		return $buyer;
	}

	/**
	 * Displays order type in orders table.
	 *
	 * @param string $created_via Order's created via value.
	 * @return void
	 */
	private function echo_order_type( $created_via ) {
		if ( 'admin' === $created_via ) {
			/* translators: %1$s opening span tag %2$s closing span tag */
			$order_type = sprintf( _x( '%1$sManual%2$s', 'Manual Order Icon', 'woocommerce-point-of-sale' ), '<span class="order-type-admin tips" data-tip="', '"><span>' );
		} elseif ( 'rest-api' === $created_via ) {
			/* translators: %1$s opening span tag %2$s closing span tag */
			$order_type = sprintf( _x( '%1$sAPI%2$s', 'API Order Icon', 'woocommerce-point-of-sale' ), '<span class="order-type-rest-api tips" data-tip="', '"><span>' );
		} elseif ( 'subscription' === $created_via ) {
			/* translators: %1$s opening span tag %2$s closing span tag */
			$order_type = sprintf( _x( '%1$sSubscription%2$s', 'Subscription Order Icon', 'woocommerce-point-of-sale' ), '<span class="order-type-subscription tips" data-tip="', '"><span>' );
		} elseif ( 'pos' === $created_via ) {
			/* translators: %1$s opening span tag %2$s closing span tag */
			$order_type = sprintf( _x( '%1$sPOS%2$s', 'POS Order Icon', 'woocommerce-point-of-sale' ), '<span class="order-type-pos tips" data-tip="', '"><span>' );
		} else {
			/* translators: %1$s opening span tag %2$s closing span tag */
			$order_type = sprintf( _x( '%1$sWebsite%2$s', 'Website Order Icon', 'woocommerce-point-of-sale' ), '<span class="order-type-checkout tips" data-tip="', '"><span>' );
		}

		echo wp_kses_post( $order_type );
	}

	/**
	 * Displays order type column for CPT.
	 *
	 * @param string $column
	 * @param int    $post_id
	 */
	public function display_order_type_column_cpt( $column, $post_id ) {
		if ( 'wc_pos_order_type' === $column ) {
			$order = wc_get_order( $post_id );

			if ( $order ) {
				$this->echo_order_type( $order->get_created_via() );
			}
		}
	}

	/**
	 * Displays order type column for HPOS.
	 *
	 * @param string   $column Column ID.
	 * @param WC_Order $order  The order object.
	 */
	public function display_order_type_column_hpos( $column, $order ) {
		if ( 'wc_pos_order_type' === $column ) {
			$this->echo_order_type( $order->get_created_via() );
		}
	}

	/**
	 * Adds an order type column to the orders listing table.
	 *
	 * @param array $columns
	 * @return array
	 */
	public function add_order_type_column( $columns ) {
		$new_columns = [];

		foreach ( $columns as $key => $value ) {
			if ( 'order_number' === $key ) {
				$new_columns['wc_pos_order_type'] = __( '<span class="order-type tips" data-tip="Order Type">Order Type</span>', 'woocommerce-point-of-sale' );
			}

			$new_columns[ $key ] = $value;
		}

		return $new_columns;
	}

	private function is_orders_page() {
		$screen         = get_current_screen();
		$screen_id      = $screen ? $screen->id : '';
		$allowed_screen = [ 'edit-shop_order' ];

		if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			array_push( $allowed_screen, Automattic\WooCommerce\Utilities\OrderUtil::get_order_admin_screen() );
		}

		if ( in_array( $screen_id, $allowed_screen, true ) ) {
			return true;
		}

		return false;
	}

	public function restrict_manage_orders() {
		if ( ! $this->is_orders_page() ) {
			return;
		}

		$req_type = isset( $_REQUEST['_created_via'] ) ? wc_clean( wp_unslash( $_REQUEST['_created_via'] ) ) : '';
		$req_reg  = isset( $_REQUEST['_register_id'] ) ? wc_clean( wp_unslash( $_REQUEST['_register_id'] ) ) : '';
		$req_out  = isset( $_REQUEST['_outlet_id'] ) ? wc_clean( wp_unslash( $_REQUEST['_outlet_id'] ) ) : '';
		?>
		<select name='_created_via' id='dropdown__created_via'>
			<option value=""><?php esc_attr_e( 'All types', 'woocommerce-point-of-sale' ); ?></option>
			<option value="online" <?php selected( $req_type, 'online', true ); ?> ><?php esc_html_e( 'Online', 'woocommerce-point-of-sale' ); ?></option>
			<option value="pos" <?php selected( $req_type, 'pos', true ); ?> ><?php esc_html_e( 'POS', 'woocommerce-point-of-sale' ); ?></option>
		</select>
		<?php
		$filters = get_option( 'wc_pos_order_filters', [ 'registers' ] );

		if ( ! $filters || ! is_array( $filters ) ) {
			return;
		}

		if ( in_array( 'registers', $filters, true ) ) {
			$registers = get_posts(
				[
					'numberposts' => -1,
					'post_type'   => 'pos_register',
				]
			);

			if ( $registers ) {
				?>
		<select name='_register_id' id='_register_id'>
			<option value=""><?php esc_html_e( 'All registers', 'woocommerce-point-of-sale' ); ?></option>
				<?php
				foreach ( $registers as $register ) {
					echo '<option value="' . esc_attr( $register->ID ) . '" ' . selected( $req_reg, $register->ID, false ) . ' >' . esc_html( $register->post_title ) . '</option>';
				}
				?>
		</select>
				<?php
			}
		}
		if ( in_array( 'outlets', $filters, true ) ) {
			$outlets = get_posts(
				[
					'numberposts' => - 1,
					'post_type'   => 'pos_outlet',
				]
			);
			if ( $outlets ) {
				?>
		<select name='_outlet_id' id='_outlet_id'>
		<option value=""><?php esc_html_e( 'All outlets', 'woocommerce-point-of-sale' ); ?></option>
				<?php
				foreach ( $outlets as $outlet ) {
					echo '<option value="' . esc_attr( $outlet->ID ) . '" ' . selected( $req_out, $outlet->ID, false ) . ' >' . esc_html( $outlet->post_title ) . '</option>';
				}
				?>
		</select>
				<?php
			}
		}
	}

	public function add_pos_print_metabox() {
		if ( ! $this->is_orders_page() ) {
			return;
		}

		global $post_id;

		$q_id  = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$id    = $post_id ?? $q_id;
		$id    = absint( $id );
		$order = wc_get_order( $id );

		if ( ! $order || 'pos' !== $order->get_created_via() ) {
			return;
		}

		$screen = wc_pos_custom_orders_table_usage_is_enabled() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';

		add_meta_box(
			'wc-pos-print',
			__( 'Point of Sale', 'woocommerce-point-of-sale' ),
			[ $this, 'pos_print_output' ],
			$screen,
			'side'
		);
	}

	public function pos_print_output() {
		global $post_id;

		$q_id  = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$id    = $post_id ?? $q_id;
		$order = wc_get_order( $id );

		$register     = wc_pos_get_register( absint( $order->get_meta( 'wc_pos_register_id' ) ) );
		$cashier      = $order->get_meta( 'wc_pos_served_by_name' );
		$amount       = floatval( $order->get_meta( 'wc_pos_amount_pay' ) );
		$change       = floatval( $order->get_meta( 'wc_pos_amount_change' ) );
		$order_number = $order->get_meta( 'wc_pos_prefix_suffix_order_number' ); // @todo fix me.
		$signature    = $order->get_meta( 'wc_pos_signature' );
		?>
		<?php if ( $register ) : ?>
		<div class="register_meta_data">
			<?php esc_html_e( 'Register:', 'woocommerce-point-of-sale' ); ?> <span><strong><?php echo esc_html( $register->get_name() ); ?></strong></span>
		</div>
		<?php endif; ?>
		<div class="register_meta_data">
			<?php esc_html_e( 'Cashier:', 'woocommerce-point-of-sale' ); ?> <span><strong><?php echo esc_html( $cashier ); ?></strong></span>
		</div>
		<div class="register_meta_data">
			<?php esc_html_e( 'Total:', 'woocommerce-point-of-sale' ); ?> <span><strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong></span>
		</div>
		<?php if ( $amount > 0 ) : ?>
		<div class="register_meta_data">
			<?php esc_html_e( 'Tendered:', 'woocommerce-point-of-sale' ); ?> <span><strong><?php echo wp_kses_post( wc_price( $amount ) ); ?></strong></span>
		</div>
		<?php endif; ?>
		<?php if ( $change > 0 ) : ?>
		<div class="register_meta_data">
			<?php esc_html_e( 'Change:', 'woocommerce-point-of-sale' ); ?> <span><strong><?php echo wp_kses_post( wc_price( $change ) ); ?></strong></span>
		</div>
		<?php endif; ?>
		<?php if ( $change < 0 ) : ?>
		<div class="register_meta_data amount_due">
			<?php esc_html_e( 'Amount Due:', 'woocommerce-point-of-sale' ); ?> <span><strong><?php echo wp_kses_post( wc_price( $change ) ); ?></strong></span>
		</div>
		<?php endif; ?>
		<?php if ( $order_number ) : ?>
		<div class="register_meta_data">
			<?php esc_html_e( 'Order #:', 'woocommerce-point-of-sale' ); ?> <span><strong><?php echo esc_html( $order_number ); ?></strong></span>
		</div>
		<?php endif; ?>

		<?php
		$coupons = wc_get_order( $id )->get_items( 'coupon' );
		$reason  = '';
		foreach ( $coupons as $coupon ) {
			// @todo fix me
			// Reasons no longer used. Coupon code is the reason.
			if ( 0 === strpos( $coupon->get_code(), 'pos_discount' ) ) {
				$reason = wc_get_order_item_meta( $coupon->get_id(), 'reason', true );
				break;
			}
		}
		?>
		<?php if ( ! empty( $reason ) ) : ?>
		<div class="register_meta_data">
			<?php esc_html_e( 'Discount Reason:', 'woocommerce-point-of-sale' ); ?> <span><strong><?php echo esc_html( $reason ); ?></strong></span>
		</div>
		<?php endif; ?>
		<?php if ( $signature ) : ?>
			<?php add_thickbox(); ?>
			<div id="signature-content" style="display:none;">
				<img
					src="data:image/png;base64,<?php echo esc_attr( str_replace( 'data:image/png;base64,', '', $signature ) ); ?>"
					alt="signature"
					id="signature-img"
					style="max-height:90%;"
				/>
			</div>
			<a href="#TB_inline?width=300&height=300&inlineId=signature-content" id="preview-signature" class="thickbox button button-block"><?php esc_html_e( 'Signature', 'woocommerce-point-of-sale' ); ?></a>
		<?php endif; ?>
		<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?pos_print_receipt=true&print_from_wc=true&order_id=' . $id ), 'print-receipt' ) ); ?>" target="_blank" id="view-post-receipt" class="button button-block">
			<?php esc_html_e( 'View Receipt', 'woocommerce-point-of-sale' ); ?>
		</a>
		<button id="print-post-receipt" class="button button-primary button-block"><?php esc_html_e( 'Print Receipt', 'woocommerce-point-of-sale' ); ?></button>
		<?php
	}
}

return new WC_POS_Admin_Orders_Page();
