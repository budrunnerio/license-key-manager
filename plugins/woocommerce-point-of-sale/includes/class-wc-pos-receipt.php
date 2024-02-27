<?php
/**
 * Point of Sale Receipt
 *
 * @since 5.0.0
 *
 * @package WooCommerce_Point_Of_Sale/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Receipt.
 */
class WC_POS_Receipt extends WC_Data {

	/**
	 * Data array, with defaults.
	 *
	 * @var array
	 */
	protected $data = [
		'barcode_type'                   => 'code128',
		'cashier_name_format'            => 'display_name',
		'custom_css'                     => '',
		'date_created'                   => null,
		'date_modified'                  => null,
		'footer_text'                    => '',
		'header_text'                    => '',
		'logo'                           => 0,
		'logo_position'                  => 'center',
		'logo_size'                      => 'normal',
		'name'                           => '',
		'num_copies'                     => 1,
		'order_date_format'              => 'jS F Y',
		'order_time_format'              => 'g:i a',
		'outlet_details_position'        => 'center',
		'print_copies'                   => 'num_copies',
		'product_details_layout'         => 'single',
		'show_cashier_name'              => true,
		'show_customer_billing_address'  => false,
		'show_customer_email'            => true,
		'show_customer_name'             => true,
		'show_customer_phone'            => false,
		'show_customer_shipping_address' => false,
		'show_num_items'                 => true,
		'show_order_barcode'             => true,
		'show_order_date'                => true,
		'show_order_status'              => true,
		'show_outlet_address'            => true,
		'show_outlet_contact_details'    => true,
		'show_outlet_name'               => false,
		'show_product_cost'              => true,
		'show_product_discount'          => false,
		'show_original_price'            => true,
		'show_product_image'             => false,
		'show_product_sku'               => true,
		'show_register_name'             => true,
		'show_shop_name'                 => true,
		'show_social_facebook'           => false,
		'show_social_instagram'          => false,
		'show_social_snapchat'           => false,
		'show_social_twitter'            => false,
		'show_tax_number'                => false,
		'show_tax_summary'               => false,
		'show_title'                     => true,
		'show_wifi_details'              => false,
		'slug'                           => '',
		'social_details_position'        => 'header',
		'tax_number_label'               => '',
		'tax_number_position'            => 'center',
		'text_size'                      => 'normal',
		'title_position'                 => 'center',
		'type'                           => 'normal',
		'width'                          => 0,
	];

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'pos_receipt';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'pos_receipt';

	/**
	 * Cache group.
	 *
	 * @var string
	 */
	protected $cache_group = 'pos_receipts';

	/**
	 * Constructor.
	 *
	 * Loads receipt data.
	 *
	 * @param mixed $data Receipt data, object or ID.
	 */
	public function __construct( $data = '' ) {
		parent::__construct( $data );

		// If we already have a receipt object, read it again.
		if ( $data instanceof WC_POS_Receipt ) {
			$this->set_id( absint( $data->get_id() ) );
			$this->read_object_from_database();
			return;
		}

		/**
		 * This filter allows custom receipt objects to be created on the fly.
		 *
		 * @since 5.0.0
		 */
		$receipt = apply_filters( 'wc_pos_get_pos_receipt_data', false, $data, $this );

		if ( $receipt ) {
			$this->read_manual_receipt( $data, $receipt );
			return;
		}

		// Try to load receipt using ID.
		if ( is_int( $data ) && 'pos_receipt' === get_post_type( $data ) ) {
			$this->set_id( $data );
		} else {
			$this->set_object_read( true );
		}

		$this->read_object_from_database();
	}

	/**
	 * If the object has an ID, read using the data store.
	 */
	protected function read_object_from_database() {
		$this->data_store = WC_Data_Store::load( 'pos_receipt' );

		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @return string
	 */
	protected function get_hook_prefix() {
		return 'wc_pos_receipt_get_';
	}

	/*
	 * Getters
	 *
	 * Methods for getting data from the receipt object.
	 */

	/**
	 * Get receipt slug.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_slug( $context = 'view' ) {
		return $this->get_prop( 'slug', $context );
	}

	/**
	 * Get receipt name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return $this->get_prop( 'name', $context );
	}

	/**
	 * Get date_created
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Get date_modified
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Whether to print the title on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_title( $context = 'view' ) {
		return $this->get_prop( 'show_title', $context );
	}

	/**
	 * Get title position.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_title_position( $context = 'view' ) {
		return $this->get_prop( 'title_position', $context );
	}

	/**
	 * Get the number of copies to be printed.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_num_copies( $context = 'view' ) {
		return $this->get_prop( 'num_copies', $context );
	}

	/**
	 * How the number of copies should be determined.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_print_copies( $context = 'view' ) {
		return $this->get_prop( 'print_copies', $context );
	}

	/**
	 * Get receipt width in mm.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_width( $context = 'view' ) {
		return $this->get_prop( 'width', $context );
	}

	/**
	 * Get receipt type.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_type( $context = 'view' ) {
		return $this->get_prop( 'type', $context );
	}

	/**
	 * Get receipt logo attachment ID.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int Attachment ID.
	 */
	public function get_logo( $context = 'view' ) {
		return $this->get_prop( 'logo', $context );
	}

	/**
	 * Get receipt logo position.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_logo_position( $context = 'view' ) {
		return $this->get_prop( 'logo_position', $context );
	}

	/**
	 * Get receipt logo size.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_logo_size( $context = 'view' ) {
		return $this->get_prop( 'logo_size', $context );
	}

	/**
	 * Get outlet details position in the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_outlet_details_position( $context = 'view' ) {
		return $this->get_prop( 'outlet_details_position', $context );
	}

	/**
	 * Whether to print the shop name on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_shop_name( $context = 'view' ) {
		return $this->get_prop( 'show_shop_name', $context );
	}

	/**
	 * Whether to print the outlet name on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_outlet_name( $context = 'view' ) {
		return $this->get_prop( 'show_outlet_name', $context );
	}

	/**
	 * Whether to print the outlet address on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_outlet_address( $context = 'view' ) {
		return $this->get_prop( 'show_outlet_address', $context );
	}

	/**
	 * Whether to print the outlet contact details on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_outlet_contact_details( $context = 'view' ) {
		return $this->get_prop( 'show_outlet_contact_details', $context );
	}

	/**
	 * Get social details position in the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_social_details_position( $context = 'view' ) {
		return $this->get_prop( 'social_details_position', $context );
	}

	/**
	 * Whether to print the Twitter account on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_social_twitter( $context = 'view' ) {
		return $this->get_prop( 'show_social_twitter', $context );
	}

	/**
	 * Whether to print the Facebook account on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_social_facebook( $context = 'view' ) {
		return $this->get_prop( 'show_social_facebook', $context );
	}

	/**
	 * Whether to print the Instagram account on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_social_instagram( $context = 'view' ) {
		return $this->get_prop( 'show_social_instagram', $context );
	}

	/**
	 * Whether to print the Snapchat account on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_social_snapchat( $context = 'view' ) {
		return $this->get_prop( 'show_social_snapchat', $context );
	}

	/**
	 * Whether to print the Wi-Fi details on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_wifi_details( $context = 'view' ) {
		return $this->get_prop( 'show_wifi_details', $context );
	}

	/**
	 * Whether to print the tax number on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_tax_number( $context = 'view' ) {
		return $this->get_prop( 'show_tax_number', $context );
	}

	/**
	 * Get tax number label.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_tax_number_label( $context = 'view' ) {
		return $this->get_prop( 'tax_number_label', $context );
	}

	/**
	 * Get tax number position.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_tax_number_position( $context = 'view' ) {
		return $this->get_prop( 'tax_number_position', $context );
	}

	/**
	 * Whether to print the order status on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_order_status( $context = 'view' ) {
		return $this->get_prop( 'show_order_status', $context );
	}

	/**
	 * Whether to print the order date on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_order_date( $context = 'view' ) {
		return $this->get_prop( 'show_order_date', $context );
	}

	/**
	 * Get order date format.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_order_date_format( $context = 'view' ) {
		return $this->get_prop( 'order_date_format', $context );
	}

	/**
	 * Get order time format.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_order_time_format( $context = 'view' ) {
		return $this->get_prop( 'order_time_format', $context );
	}

	/**
	 * Whether to print the customer name on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_customer_name( $context = 'view' ) {
		return $this->get_prop( 'show_customer_name', $context );
	}

	/**
	 * Whether to print the customer email on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_customer_email( $context = 'view' ) {
		return $this->get_prop( 'show_customer_email', $context );
	}

	/**
	 * Whether to print the customer phone on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_customer_phone( $context = 'view' ) {
		return $this->get_prop( 'show_customer_phone', $context );
	}

	/**
	 * Whether to print the customer shipping address on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_customer_shipping_address( $context = 'view' ) {
		return $this->get_prop( 'show_customer_shipping_address', $context );
	}

	/**
	 * Whether to print the customer billing address on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_customer_billing_address( $context = 'view' ) {
		return $this->get_prop( 'show_customer_billing_address', $context );
	}

	/**
	 * Whether to print the cashier name on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_cashier_name( $context = 'view' ) {
		return $this->get_prop( 'show_cashier_name', $context );
	}

	/**
	 * Whether to print the register name on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_register_name( $context = 'view' ) {
		return $this->get_prop( 'show_register_name', $context );
	}

	/**
	 * Get cashier name format.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_cashier_name_format( $context = 'view' ) {
		return $this->get_prop( 'cashier_name_format', $context );
	}

	/**
	 * Get product details layout.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_product_details_layout( $context = 'view' ) {
		return $this->get_prop( 'product_details_layout', $context );
	}

	/**
	 * Whether to print the product image on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_product_image( $context = 'view' ) {
		return $this->get_prop( 'show_product_image', $context );
	}

	/**
	 * Whether to print the product SKU on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_product_sku( $context = 'view' ) {
		return $this->get_prop( 'show_product_sku', $context );
	}

	/**
	 * Whether to print the product cost on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_product_cost( $context = 'view' ) {
		return $this->get_prop( 'show_product_cost', $context );
	}

	/**
	 * Whether to print the product discount on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_product_discount( $context = 'view' ) {
		return $this->get_prop( 'show_product_discount', $context );
	}

	/**
	 * Whether to print the original item price before discount.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_original_price( $context = 'view' ) {
		return $this->get_prop( 'show_original_price', $context );
	}

	/**
	 * Whether to print the total number of order items on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_num_items( $context = 'view' ) {
		return $this->get_prop( 'show_num_items', $context );
	}

	/**
	 * Whether to print a tax summary on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_tax_summary( $context = 'view' ) {
		return $this->get_prop( 'show_tax_summary', $context );
	}

	/**
	 * Whether to print order barcode on the receipt.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_show_order_barcode( $context = 'view' ) {
		return $this->get_prop( 'show_order_barcode', $context );
	}

	/**
	 * Get barcode type.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_barcode_type( $context = 'view' ) {
		return $this->get_prop( 'barcode_type', $context );
	}

	/**
	 * Get receipt text size.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_text_size( $context = 'view' ) {
		return $this->get_prop( 'text_size', $context );
	}

	/**
	 * Get receipt header text.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_header_text( $context = 'view' ) {
		return $this->get_prop( 'header_text', $context );
	}

	/**
	 * Get receipt footer text.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_footer_text( $context = 'view' ) {
		return $this->get_prop( 'footer_text', $context );
	}

	/**
	 * Get receipt custom CSS code.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_custom_css( $context = 'view' ) {
		return $this->get_prop( 'custom_css', $context );
	}

	/*
	 * Setters
	 *
	 * Functions for setting receipt data. These should not update anything in the
	 * database itself and should only change what is stored in the class
	 * object.
	 */

	/**
	 * Set receipt name.
	 *
	 * @param string $name Receipt name.
	 */
	public function set_name( $name ) {
		$this->set_prop( 'name', $name );
	}

	/**
	 * Set receipt slug.
	 *
	 * @param string $slug Receipt slug.
	 */
	public function set_slug( $slug ) {
		$this->set_prop( 'slug', $slug );
	}

	/**
	 * Set date_created
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 */
	public function set_date_created( $date ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set date_modified
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 */
	public function set_date_modified( $date ) {
		$this->set_date_prop( 'date_modified', $date );
	}

	/**
	 * If to print the title on the receipt.
	 *
	 * @param bool $show Whether to print the title.
	 */
	public function set_show_title( $show ) {
		$this->set_prop( 'show_title', $show );
	}

	/**
	 * Set receipt title position.
	 *
	 * @param string $title_position Title position.
	 */
	public function set_title_position( $position ) {
		$this->set_prop( 'title_position', $position );
	}

	/**
	 * Set the number of copies to be printed.
	 *
	 * @param int $num_copies Number of copies.
	 */
	public function set_num_copies( $copies ) {
		$this->set_prop( 'num_copies', $copies );
	}

	/**
	 * Set how the number of copies should be determined.
	 *
	 * @param string $value How to calculate the number of copies.
	 */
	public function set_print_copies( $value ) {
		$this->set_prop( 'print_copies', $value );
	}

	/**
	 * Set receipt width.
	 *
	 * @param int $width Receipt width in mm or 0 for dynamic width.
	 */
	public function set_width( $width ) {
		$this->set_prop( 'width', $width );
	}

	/**
	 * Set receipt type.
	 *
	 * @param int $type Receipt type.
	 */
	public function set_type( $type ) {
		$this->set_prop( 'type', $type );
	}

	/**
	 * Set receipt logo.
	 *
	 * @param int $attachment Attachment ID.
	 */
	public function set_logo( $attachment ) {
		$this->set_prop( 'logo', $attachment );
	}

	/**
	 * Set receipt logo position.
	 *
	 * @param string $position Logo position.
	 */
	public function set_logo_position( $position ) {
		$this->set_prop( 'logo_position', $position );
	}

	/**
	 * Set receipt logo size.
	 *
	 * @param string $size Logo size.
	 */
	public function set_logo_size( $size ) {
		$this->set_prop( 'logo_size', $size );
	}

	/**
	 * Set outlet details position.
	 *
	 * @param string $position Position.
	 */
	public function set_outlet_details_position( $position ) {
		$this->set_prop( 'outlet_details_position', $position );
	}

	/**
	 * If to print the shop name on the receipt.
	 *
	 * @param bool $show Whether to print the shop name.
	 */
	public function set_show_shop_name( $show ) {
		$this->set_prop( 'show_shop_name', $show );
	}

	/**
	 * If to print the outlet name on the receipt.
	 *
	 * @param bool $show Whether to print the outlet name.
	 */
	public function set_show_outlet_name( $show ) {
		$this->set_prop( 'show_outlet_name', $show );
	}

	/**
	 * If to print the outlet address on the receipt.
	 *
	 * @param bool $show Whether to print the outlet address.
	 */
	public function set_show_outlet_address( $show ) {
		$this->set_prop( 'show_outlet_address', $show );
	}

	/**
	 * If to print the outlet contact details on the receipt.
	 *
	 * @param bool $show Whether to print the outlet contact details.
	 */
	public function set_show_outlet_contact_details( $show ) {
		$this->set_prop( 'show_outlet_contact_details', $show );
	}

	/**
	 * Set social details position.
	 *
	 * @param string $position Position.
	 */
	public function set_social_details_position( $position ) {
		$this->set_prop( 'social_details_position', $position );
	}

	/**
	 * If to print the Twitter account on the receipt.
	 *
	 * @param bool $show Whether to print the Twitter account.
	 */
	public function set_show_social_twitter( $show ) {
		$this->set_prop( 'show_social_twitter', $show );
	}

	/**
	 * If to print the Facebook account on the receipt.
	 *
	 * @param bool $show Whether to print the Facebook account.
	 */
	public function set_show_social_facebook( $show ) {
		$this->set_prop( 'show_social_facebook', $show );
	}

	/**
	 * If to print the Instagram account on the receipt.
	 *
	 * @param bool $show Whether to print the Instagram account.
	 */
	public function set_show_social_instagram( $show ) {
		$this->set_prop( 'show_social_instagram', $show );
	}

	/**
	 * If to print the Snapchat account on the receipt.
	 *
	 * @param bool $show Whether to print the Snapchat account.
	 */
	public function set_show_social_snapchat( $show ) {
		$this->set_prop( 'show_social_snapchat', $show );
	}

	/**
	 * If to print the Wi-Fi details on the receipt.
	 *
	 * @param bool $show Whether to print the Wi-Fi details.
	 */
	public function set_show_wifi_details( $show ) {
		$this->set_prop( 'show_wifi_details', $show );
	}

	/**
	 * If to print the tax number on the receipt.
	 *
	 * @param bool $show Whether to print the tax number.
	 */
	public function set_show_tax_number( $show ) {
		$this->set_prop( 'show_tax_number', $show );
	}

	/**
	 * Set tax number label.
	 *
	 * @param string $label Label.
	 */
	public function set_tax_number_label( $label ) {
		$this->set_prop( 'tax_number_label', $label );
	}

	/**
	 * Set tax number position.
	 *
	 * @param string $position Position.
	 */
	public function set_tax_number_position( $position ) {
		$this->set_prop( 'tax_number_position', $position );
	}

	/**
	 * If to print the order status on the receipt.
	 *
	 * @param bool $show Whether to print the order status.
	 */
	public function set_show_order_status( $show ) {
		$this->set_prop( 'show_order_status', $show );
	}

	/**
	 * If to print the order date on the receipt.
	 *
	 * @param bool $show Whether to print the order date.
	 */
	public function set_show_order_date( $show ) {
		$this->set_prop( 'show_order_date', $show );
	}

	/**
	 * Set order date format.
	 *
	 * @param string $format Date format.
	 */
	public function set_order_date_format( $format ) {
		$this->set_prop( 'order_date_format', $format );
	}

	/**
	 * Set order time format.
	 *
	 * @param string $format Time format.
	 */
	public function set_order_time_format( $format ) {
		$this->set_prop( 'order_time_format', $format );
	}

	/**
	 * If to print the customer name on the receipt.
	 *
	 * @param bool $show Whether to print the customer name.
	 */
	public function set_show_customer_name( $show ) {
		$this->set_prop( 'show_customer_name', $show );
	}

	/**
	 * If to print the customer email on the receipt.
	 *
	 * @param bool $show Whether to print the customer email.
	 */
	public function set_show_customer_email( $show ) {
		$this->set_prop( 'show_customer_email', $show );
	}

	/**
	 * If to print the customer phone on the receipt.
	 *
	 * @param bool $show Whether to print the customer phone.
	 */
	public function set_show_customer_phone( $show ) {
		$this->set_prop( 'show_customer_phone', $show );
	}

	/**
	 * If to print the customer shipping address on the receipt.
	 *
	 * @param bool $show Whether to print the customer shipping address.
	 */
	public function set_show_customer_shipping_address( $show ) {
		$this->set_prop( 'show_customer_shipping_address', $show );
	}

	/**
	 * If to print the customer billing address on the receipt.
	 *
	 * @param bool $show Whether to print the customer shipping address.
	 */
	public function set_show_customer_billing_address( $show ) {
		$this->set_prop( 'show_customer_billing_address', $show );
	}

	/**
	 * If to print the cashier name on the receipt.
	 *
	 * @param bool $show Whether to print the cashier name.
	 */
	public function set_show_cashier_name( $show ) {
		$this->set_prop( 'show_cashier_name', $show );
	}

	/**
	 * If to print the register name on the receipt.
	 *
	 * @param bool $show Whether to print the register name.
	 */
	public function set_show_register_name( $show ) {
		$this->set_prop( 'show_register_name', $show );
	}

	/**
	 * Set cashier name format.
	 *
	 * @param string $format Name format.
	 */
	public function set_cashier_name_format( $format ) {
		$this->set_prop( 'cashier_name_format', $format );
	}

	/**
	 * Set product details layout.
	 *
	 * @param string $layout Layout.
	 */
	public function set_product_details_layout( $format ) {
		$this->set_prop( 'product_details_layout', $format );
	}

	/**
	 * If to print the product image on the receipt.
	 *
	 * @param bool $show Whether to print the product image.
	 */
	public function set_show_product_image( $show ) {
		$this->set_prop( 'show_product_image', $show );
	}

	/**
	 * If to print the product SKU on the receipt.
	 *
	 * @param bool $show Whether to print the product SKU.
	 */
	public function set_show_product_sku( $show ) {
		$this->set_prop( 'show_product_sku', $show );
	}

	/**
	 * If to print the product cost on the receipt.
	 *
	 * @param bool $show Whether to print the product cost.
	 */
	public function set_show_product_cost( $show ) {
		$this->set_prop( 'show_product_cost', $show );
	}

	/**
	 * If to print the product discount on the receipt.
	 *
	 * @param bool $show Whether to print the product discount.
	 */
	public function set_show_product_discount( $show ) {
		$this->set_prop( 'show_product_discount', $show );
	}

	/**
	 * If to print the original item price before discount.
	 *
	 * @param bool $show Whether to print the product discount.
	 */
	public function set_show_original_price( $show ) {
		$this->set_prop( 'show_original_price', $show );
	}

	/**
	 * If to print the total number of order items on the receipt.
	 *
	 * @param bool $show Whether to print the number of items.
	 */
	public function set_show_num_items( $show ) {
		$this->set_prop( 'show_num_items', $show );
	}

	/**
	 * If to print a tax summary on the receipt.
	 *
	 * @param bool $show Whether to print a tax summary.
	 */
	public function set_show_tax_summary( $show ) {
		$this->set_prop( 'show_tax_summary', $show );
	}

	/**
	 * If to print the order barcode on the receipt.
	 *
	 * @param bool $show Whether to print the order barcode.
	 */
	public function set_show_order_barcode( $show ) {
		$this->set_prop( 'show_order_barcode', $show );
	}

	/**
	 * Set barcode type.
	 *
	 * @param string $type Type.
	 */
	public function set_barcode_type( $type ) {
		$this->set_prop( 'barcode_type', $type );
	}

	/**
	 * Set receipt text size.
	 *
	 * @param string $size Text size.
	 */
	public function set_text_size( $size ) {
		$this->set_prop( 'text_size', $size );
	}

	/**
	 * Set receipt header text.
	 *
	 * @param string $text Header text.
	 */
	public function set_header_text( $text ) {
		$this->set_prop( 'header_text', $text );
	}

	/**
	 * Set receipt footer text.
	 *
	 * @param string $text Footer text.
	 */
	public function set_footer_text( $text ) {
		$this->set_prop( 'footer_text', $text );
	}

	/**
	 * Set custom CSS for the receipt.
	 *
	 * @param string $css Custom CSS code.
	 */
	public function set_custom_css( $css ) {
		$this->set_prop( 'custom_css', $css );
	}

	/*
	 * Other Actions
	 */

	/**
	 * Developers can programmatically return receipts. This function will read those values into our WC_POS_Receipt class.
	 *
	 * @param string $slug    Receipt slug.
	 * @param array  $receipt Array of receipt properties.
	 */
	public function read_manual_receipt( $slug, $receipt ) {
		$this->set_props( $receipt );
		$this->set_slug( $slug );
		$this->set_id( 0 );
		$this->set_virtual( true );
	}
}
