<?php
/**
 * Point of Sale Register
 *
 * @since 5.0.0
 *
 * @package WooCommerce_Point_Of_Sale/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Register.
 */
class WC_POS_Register extends WC_Data {

	/**
	 * Data array, with defaults.
	 *
	 * @var array
	 */
	protected $data = [
		'name'            => '',
		'slug'            => '',
		'date_created'    => null,
		'date_modified'   => null,
		'date_opened'     => null,
		'date_closed'     => null,
		'open_first'      => 0,
		'open_last'       => 0,
		'current_session' => 0,
		'grid'            => 0,
		'receipt'         => 0,
		'grid_layout'     => 'rectangular',
		'prefix'          => '',
		'suffix'          => '',
		'outlet'          => 0,
		'customer'        => 0,
		'cash_management' => false,
		'dining_option'   => 'none',
		'default_mode'    => 'search',
		'change_user'     => false,
		'email_receipt'   => 'no',
		'print_receipt'   => false,
		'gift_receipt'    => false,
		'note_request'    => 'none',
	];

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'pos_register';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'pos_register';

	/**
	 * Cache group.
	 *
	 * @var string
	 */
	protected $cache_group = 'pos_registers';

	/**
	 * Constructor.
	 *
	 * Loads register data.
	 *
	 * @param mixed $data Register data, object or ID.
	 */
	public function __construct( $data = '' ) {
		parent::__construct( $data );

		// If we already have a register object, read it again.
		if ( $data instanceof WC_POS_Register ) {
			$this->set_id( absint( $data->get_id() ) );
			$this->read_object_from_database();
			return;
		}

		/**
		 * Allow custom register objects to be created on the fly.
		 *
		 * @since 5.0.0
		 * @param mixed           $data Register data.
		 * @param WC_POS_Register $this Class instance.
		 */
		$register = apply_filters( 'wc_pos_get_pos_register_data', false, $data, $this );

		if ( $register ) {
			$this->read_manual_register( $data, $register );
			return;
		}

		// Try to load register using slug or ID.
		if ( is_string( $data ) && 'pos_register' === get_post_type( absint( $data ) ) ) {
			$this->set_id( absint( $data ) );
		} elseif ( is_string( $data ) ) {
			$post = get_page_by_path( $data, OBJECT, 'pos_register' );

			if ( $post && isset( $post->ID ) && 'pos_register' === get_post_type( $post->ID ) ) {
				$this->set_id( $post->ID );
			} else {
				$this->set_object_read( true );
			}
		} elseif ( is_int( $data ) && 'pos_register' === get_post_type( $data ) ) {
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
		$this->data_store = WC_Data_Store::load( 'pos_register' );

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
		return 'wc_pos_register_get_';
	}

	/*
	 * Getters
	 *
	 * Methods for getting data from the register object.
	 */

	/**
	 * Get register slug.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_slug( $context = 'view' ) {
		return $this->get_prop( 'slug', $context );
	}

	/**
	 * Get register name.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return $this->get_prop( 'name', $context );
	}

	/**
	 * Get date_created.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Get date_modified.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Get the last time the register is opened at.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_opened( $context = 'view' ) {
		return $this->get_prop( 'date_opened', $context );
	}

	/**
	 * Get the last time the register is closed at.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_closed( $context = 'view' ) {
		return $this->get_prop( 'date_closed', $context );
	}

	/**
	 * Get the user who first opened the register.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int User ID.
	 */
	public function get_open_first( $context = 'view' ) {
		return $this->get_prop( 'open_first', $context );
	}

	/**
	 * Get the last user to open the register.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int User ID.
	 */
	public function get_open_last( $context = 'view' ) {
		return $this->get_prop( 'open_last', $context );
	}

	/**
	 * Get the ID of the current session.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int Post ID.
	 */
	public function get_current_session( $context = 'view' ) {
		return $this->get_prop( 'current_session', $context );
	}

	/**
	 * Get register grid.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_grid( $context = 'view' ) {
		return $this->get_prop( 'grid', $context );
	}

	/**
	 * Get register receipt.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_receipt( $context = 'view' ) {
		return $this->get_prop( 'receipt', $context );
	}

	/**
	 * Get grid layout.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_grid_layout( $context = 'view' ) {
		return $this->get_prop( 'grid_layout', $context );
	}

	/**
	 * Get order prefix.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_prefix( $context = 'view' ) {
		return $this->get_prop( 'prefix', $context );
	}

	/**
	 * Get order suffix.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_suffix( $context = 'view' ) {
		return $this->get_prop( 'suffix', $context );
	}

	/**
	 * Get register outlet.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_outlet( $context = 'view' ) {
		return $this->get_prop( 'outlet', $context );
	}

	/**
	 * Get register customer.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_customer( $context = 'view' ) {
		return $this->get_prop( 'customer', $context );
	}

	/**
	 * If this register enables cash management or not.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_cash_management( $context = 'view' ) {
		return $this->get_prop( 'cash_management', $context );
	}

	/**
	 * Get dining option.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_dining_option( $context = 'view' ) {
		return $this->get_prop( 'dining_option', $context );
	}

	/**
	 * Get default mode.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_default_mode( $context = 'view' ) {
		return $this->get_prop( 'default_mode', $context );
	}

	/**
	 * Whether user to be changed at end of sale.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_change_user( $context = 'view' ) {
		return $this->get_prop( 'change_user', $context );
	}

	/**
	 * When to email receipt at end of sale.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_email_receipt( $context = 'view' ) {
		return $this->get_prop( 'email_receipt', $context );
	}

	/**
	 * Whether to print receipt at end of sale.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_print_receipt( $context = 'view' ) {
		return $this->get_prop( 'print_receipt', $context );
	}

	/**
	 * Whether to print gift receipt at end of sale.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_gift_receipt( $context = 'view' ) {
		return $this->get_prop( 'gift_receipt', $context );
	}

	/**
	 * When to add a note at end of sale.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_note_request( $context = 'view' ) {
		return $this->get_prop( 'note_request', $context );
	}

	/*
	 * Setters
	 *
	 * Functions for setting register data. These should not update anything in the
	 * database itself and should only change what is stored in the class
	 * object.
	 */

	/**
	 * Set register name.
	 *
	 * @param string $name Register name.
	 */
	public function set_name( $name ) {
		$this->set_prop( 'name', $name );
	}

	/**
	 * Set register slug.
	 *
	 * @param string $slug Register slug.
	 */
	public function set_slug( $slug ) {
		$this->set_prop( 'slug', $slug );
	}

	/**
	 * Set date_created.
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 */
	public function set_date_created( $date ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set date_modified.
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 */
	public function set_date_modified( $date ) {
		$this->set_date_prop( 'date_modified', $date );
	}

	/**
	 * Set register opening time.
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 */
	public function set_date_opened( $date = null ) {
		$this->set_date_prop( 'date_opened', $date );
	}

	/**
	 * Set register closing time.
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 */
	public function set_date_closed( $date = null ) {
		$this->set_date_prop( 'date_closed', $date );
	}

	/**
	 * Set open first.
	 *
	 * @param int $id User ID.
	 */
	public function set_open_first( $id ) {
		$this->set_prop( 'open_first', $id );
	}

	/**
	 * Set open last.
	 *
	 * @param int $id User ID.
	 */
	public function set_open_last( $id ) {
		$this->set_prop( 'open_last', $id );
	}

	/**
	 * Set current session.
	 *
	 * @param int $id Post ID.
	 */
	public function set_current_session( $id ) {
		$this->set_prop( 'current_session', $id );
	}

	/**
	 * Set register grid.
	 *
	 * @param int $id Grid ID.
	 */
	public function set_grid( $id ) {
		$this->set_prop( 'grid', $id );
	}

	/**
	 * Set register receipt.
	 *
	 * @param int $id Receipt ID.
	 */
	public function set_receipt( $id ) {
		$this->set_prop( 'receipt', $id );
	}

	/**
	 * Set grid layout.
	 *
	 * @param string $layout Grid layout.
	 */
	public function set_grid_layout( $layout ) {
		$this->set_prop( 'grid_layout', $layout );
	}

	/**
	 * Set register prefix.
	 *
	 * @param string $prefix Register prefix.
	 */
	public function set_prefix( $prefix ) {
		$this->set_prop( 'prefix', $prefix );
	}

	/**
	 * Set register suffix.
	 *
	 * @param string $suffix Register suffix.
	 */
	public function set_suffix( $suffix ) {
		$this->set_prop( 'suffix', $suffix );
	}

	/**
	 * Set register outlet.
	 *
	 * @param int $id Outlet ID.
	 */
	public function set_outlet( $id ) {
		$this->set_prop( 'outlet', $id );
	}

	/**
	 * Set the default customer of this register.
	 *
	 * @param int $id Customer ID.
	 */
	public function set_customer( $id ) {
		$this->set_prop( 'customer', $id );
	}

	/**
	 * Set if this register enables cash management or not.
	 *
	 * @param bool $cash_management Whether to enable cash management.
	 */
	public function set_cash_management( $cash_management ) {
		$this->set_prop( 'cash_management', (bool) $cash_management );
	}

	/**
	 * Set dining option.
	 *
	 * @param string $dining_option Dining option.
	 */
	public function set_dining_option( $dining_option ) {
		$this->set_prop( 'dining_option', $dining_option );
	}

	/**
	 * Set default mode.
	 *
	 * @param string $mode Default mode.
	 */
	public function set_default_mode( $mode ) {
		$this->set_prop( 'default_mode', $mode );
	}

	/**
	 * Set if user should be changed at end of sale.
	 *
	 * @param bool $change_user Whether to enable cash management.
	 */
	public function set_change_user( $change_user ) {
		$this->set_prop( 'change_user', (bool) $change_user );
	}

	/**
	 * Set when to email receipt.
	 *
	 * @param string $email_receipt Dining option.
	 */
	public function set_email_receipt( $email_receipt ) {
		$this->set_prop( 'email_receipt', $email_receipt );
	}

	/**
	 * Set whether to print receipt at end of sale.
	 *
	 * @param bool $print_receipt Whether to print receipt.
	 */
	public function set_print_receipt( $print_receipt ) {
		$this->set_prop( 'print_receipt', (bool) $print_receipt );
	}

	/**
	 * Set whether to print gift receipt at end of sale.
	 *
	 * @param bool $gift_receipt Whether to print gift receipt.
	 */
	public function set_gift_receipt( $gift_receipt ) {
		$this->set_prop( 'gift_receipt', (bool) $gift_receipt );
	}

	/**
	 * Set when to add note at end of sale.
	 *
	 * @param string $note_request When to add note.
	 */
	public function set_note_request( $note_request ) {
		$this->set_prop( 'note_request', $note_request );
	}

	/*
	 * Other Actions
	 */

	/**
	 * Developers can programmatically return registers. This function will read those values into our WC_POS_Register class.
	 *
	 * @param string $slug     Register slug.
	 * @param array  $register Array of register properties.
	 */
	public function read_manual_register( $slug, $register ) {
		$this->set_props( $register );
		$this->set_slug( $slug );
		$this->set_id( 0 );
		$this->set_virtual( true );
	}
}
