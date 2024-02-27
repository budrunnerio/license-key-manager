<?php
/**
 * Register Session
 *
 * @since 5.2.0
 *
 * @package WooCommerce_Point_Of_Sale/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Session.
 */
class WC_POS_Session extends WC_Data {

	/**
	 * Data array, with defaults.
	 *
	 * @var array
	 */
	protected $data = [
		'name'               => '',
		'slug'               => '',
		'date_created'       => null,
		'date_modified'      => null,
		'date_opened'        => null,
		'date_closed'        => null,
		'locked'             => false,
		'open_first'         => 0,
		'open_last'          => 0,
		'register_id'        => 0,
		'outlet_id'          => 0,
		'opening_note'       => '',
		'closing_note'       => '',
		'opening_cash_total' => 0,
		'counted_totals'     => [],
		'session_data'       => [],
	];

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'pos_session';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'pos_session';

	/**
	 * Cache group.
	 *
	 * @var string
	 */
	protected $cache_group = 'pos_sessions';

	/**
	 * Constructor.
	 *
	 * Loads session data.
	 *
	 * @param mixed $data Session data, object or ID.
	 */
	public function __construct( $data = '' ) {
		parent::__construct( $data );

		// If we already have a session object, read it again.
		if ( $data instanceof WC_POS_Session ) {
			$this->set_id( absint( $data->get_id() ) );
			$this->read_object_from_database();
			return;
		}

		/**
		 * Allow custom session objects to be created on the fly.
		 *
		 * @since 5.0.0
		 */
		$session = apply_filters( 'wc_pos_get_pos_session_data', false, $data, $this );

		if ( $session ) {
			$this->read_manual_session( $data, $session );
			return;
		}

		// Try to load session using slug or ID.
		if ( is_string( $data ) && 'pos_session' === get_post_type( absint( $data ) ) ) {
			$this->set_id( absint( $data ) );
		} elseif ( is_string( $data ) ) {
			$post = get_page_by_path( $data, OBJECT, 'pos_session' );

			if ( $post && isset( $post->ID ) && 'pos_session' === get_post_type( $post->ID ) ) {
				$this->set_id( $post->ID );
			} else {
				$this->set_object_read( true );
			}
		} elseif ( is_int( $data ) && 'pos_session' === get_post_type( $data ) ) {
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
		$this->data_store = WC_Data_Store::load( 'pos_session' );

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
		return 'wc_pos_session_get_';
	}

	/*
	 * Getters
	 *
	 * Methods for getting data from the session object.
	 */

	/**
	 * Get session slug.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_slug( $context = 'view' ) {
		return $this->get_prop( 'slug', $context );
	}

	/**
	 * Get session name.
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
	 * Get the last time the session is opened at.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_opened( $context = 'view' ) {
		return $this->get_prop( 'date_opened', $context );
	}

	/**
	 * Get the last time the session is closed at.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_closed( $context = 'view' ) {
		return $this->get_prop( 'date_closed', $context );
	}

	/**
	 * Whether the session is locked and requires password.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return boolean Whether the session is locked.
	 */
	public function get_locked( $context = 'view' ) {
		return $this->get_prop( 'locked', $context );
	}

	/**
	 * Get the user who first opened the register in this session.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_open_first( $context = 'view' ) {
		return $this->get_prop( 'open_first', $context );
	}

	/**
	 * Get the user who last opened the register in this session.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_open_last( $context = 'view' ) {
		return $this->get_prop( 'open_last', $context );
	}

	/**
	 * Get register ID.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_register_id( $context = 'view' ) {
		return $this->get_prop( 'register_id', $context );
	}

	/**
	 * Get outlet ID.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_outlet_id( $context = 'view' ) {
		return $this->get_prop( 'outlet_id', $context );
	}

	/**
	 * Get opening note.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_opening_note( $context = 'view' ) {
		return $this->get_prop( 'opening_note', $context );
	}

	/**
	 * Get closing note.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_closing_note( $context = 'view' ) {
		return $this->get_prop( 'closing_note', $context );
	}

	/**
	 * Get opening cash total.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return float
	 */
	public function get_opening_cash_total( $context = 'view' ) {
		return $this->get_prop( 'opening_cash_total', $context );
	}

	/**
	 * Get counted totals.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return array
	 */
	public function get_counted_totals( $context = 'view' ) {
		return $this->get_prop( 'counted_totals', $context );
	}

	/**
	 * Get session data.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return array
	 */
	public function get_session_data( $context = 'view' ) {
		return $this->get_prop( 'session_data', $context );
	}

	/*
	 * Setters
	 *
	 * Functions for setting session data. These should not update anything in the
	 * database itself and should only change what is stored in the class
	 * object.
	 */

	/**
	 * Set session name.
	 *
	 * @param string $name Session name.
	 */
	public function set_name( $name ) {
		$this->set_prop( 'name', $name );
	}

	/**
	 * Set session slug.
	 *
	 * @param string $slug Session slug.
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
	 * Set session opening time.
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 */
	public function set_date_opened( $date = null ) {
		$this->set_date_prop( 'date_opened', $date );
	}

	/**
	 * Set session closing time.
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 */
	public function set_date_closed( $date = null ) {
		$this->set_date_prop( 'date_closed', $date );
	}

	/**
	 * Set session's lock status.
	 *
	 * @param boolean $locked Whether the session is locked.
	 */
	public function set_locked( $value ) {
		$this->set_prop( 'locked', $value );
	}

	/**
	 * Set the user who first opened the register in this session.
	 *
	 * @param int $id User ID.
	 */
	public function set_open_first( $id ) {
		$this->set_prop( 'open_first', $id );
	}

	/**
	 * Set the user who last opened the register in this session.
	 *
	 * @param int $id User ID.
	 */
	public function set_open_last( $id ) {
		$this->set_prop( 'open_last', $id );
	}

	/**
	 * Set register ID.
	 *
	 * @param int $id Register ID.
	 */
	public function set_register_id( $id ) {
		$this->set_prop( 'register_id', $id );
	}

	/**
	 * Set outlet ID.
	 *
	 * @param int $id Outlet ID.
	 */
	public function set_outlet_id( $id ) {
		$this->set_prop( 'outlet_id', $id );
	}

	/**
	 * Set opening note.
	 *
	 * @param string $note
	 */
	public function set_opening_note( $note ) {
		$this->set_prop( 'opening_note', $note );
	}

	/**
	 * Set closing note.
	 *
	 * @param string $note
	 */
	public function set_closing_note( $note ) {
		$this->set_prop( 'closing_note', $note );
	}

	/**
	 * Set opening cash total.
	 *
	 * @param float $total
	 */
	public function set_opening_cash_total( $total ) {
		$this->set_prop( 'opening_cash_total', $total );
	}

	/**
	 * Set counted totals.
	 *
	 * @param array $totals
	 */
	public function set_counted_totals( $totals ) {
		$this->set_prop( 'counted_totals', $totals );
	}

	/**
	 * Set session data.
	 *
	 * @param array $data
	 */
	public function set_session_data( $data ) {
		$this->set_prop( 'session_data', $data );
	}

	/*
	 * Other Actions
	 */

	/**
	 * Developers can programmatically return sessions. This function will read those values into our WC_POS_Session class.
	 *
	 * @param string $slug    Session slug.
	 * @param array  $session Array of session properties.
	 */
	public function read_manual_session( $slug, $session ) {
		$this->set_props( $session );
		$this->set_slug( $slug );
		$this->set_id( 0 );
		$this->set_virtual( true );
	}
}
