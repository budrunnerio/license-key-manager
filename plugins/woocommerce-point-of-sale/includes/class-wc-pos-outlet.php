<?php
/**
 * Point of Sale Outlet
 *
 * @since 5.0.0
 *
 * @package WooCommerce_Point_Of_Sale/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_POS_Outlet.
 */
class WC_POS_Outlet extends WC_Data {

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
		'address_1'       => '',
		'address_2'       => '',
		'city'            => '',
		'postcode'        => '',
		'country'         => '',
		'state'           => '',
		'email'           => '',
		'phone'           => '',
		'fax'             => '',
		'website'         => '',
		'wifi_network'    => '',
		'wifi_password'   => '',
		'social_accounts' => [],
	];

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'pos_outlet';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'pos_outlet';

	/**
	 * Cache group.
	 *
	 * @var string
	 */
	protected $cache_group = 'pos_outlets';

	/**
	 * Constructor.
	 *
	 * Loads outlet data.
	 *
	 * @param mixed $data Outlet data, object or ID.
	 */
	public function __construct( $data = '' ) {
		parent::__construct( $data );

		// If we already have a outlet object, read it again.
		if ( $data instanceof WC_POS_Outlet ) {
			$this->set_id( absint( $data->get_id() ) );
			$this->read_object_from_database();
			return;
		}

		/**
		 * Allow custom outlet objects to be created on the fly.
		 *
		 * @since 5.0.0
		 * @param mixed         $data Outlet data.
		 * @param WC_POS_Outlet $this Class instance.
		 */
		$outlet = apply_filters( 'wc_pos_get_pos_outlet_data', false, $data, $this );

		if ( $outlet ) {
			$this->read_manual_outlet( $data, $outlet );
			return;
		}

		// Try to load outlet using slug or ID.
		if ( is_string( $data ) && 'pos_outlet' === get_post_type( absint( $data ) ) ) {
			$this->set_id( absint( $data ) );
		} elseif ( is_string( $data ) ) {
			$post = get_page_by_path( $data, OBJECT, 'pos_outlet' );

			if ( $post && isset( $post->ID ) && 'pos_outlet' === get_post_type( $post->ID ) ) {
				$this->set_id( $post->ID );
			} else {
				$this->set_object_read( true );
			}
		} elseif ( is_int( $data ) && 'pos_outlet' === get_post_type( $data ) ) {
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
		$this->data_store = WC_Data_Store::load( 'pos_outlet' );

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
		return 'wc_pos_outlet_get_';
	}

	/*
	 * Getters
	 *
	 * Methods for getting data from the outlet object.
	 */

	/**
	 * Get outlet slug.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_slug( $context = 'view' ) {
		return $this->get_prop( 'slug', $context );
	}

	/**
	 * Get outlet name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return $this->get_prop( 'name', $context );
	}

	/**
	 * Get date_created.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Get date_modified.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Get outlet address line 1.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_address_1( $context = 'view' ) {
		return $this->get_prop( 'address_1', $context );
	}

	/**
	 * Get outlet address line 2.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_address_2( $context = 'view' ) {
		return $this->get_prop( 'address_2', $context );
	}

	/**
	 * Get outlet city.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_city( $context = 'view' ) {
		return $this->get_prop( 'city', $context );
	}

	/**
	 * Get outlet postcode/ZIP.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_postcode( $context = 'view' ) {
		return $this->get_prop( 'postcode', $context );
	}

		/**
		 * Get outlet country.
		 *
		 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
		 * @return string
		 */
	public function get_country( $context = 'view' ) {
		return $this->get_prop( 'country', $context );
	}

	/**
	 * Get outlet state/county.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_state( $context = 'view' ) {
		return $this->get_prop( 'state', $context );
	}

	/**
	 * Get outlet email.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_email( $context = 'view' ) {
		return $this->get_prop( 'email', $context );
	}

	/**
	 * Get outlet phone.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_phone( $context = 'view' ) {
		return $this->get_prop( 'phone', $context );
	}

	/**
	 * Get outlet fax.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_fax( $context = 'view' ) {
		return $this->get_prop( 'fax', $context );
	}

	/**
	 * Get outlet website.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_website( $context = 'view' ) {
		return $this->get_prop( 'website', $context );
	}

	/**
	 * Get outlet Wi-Fi network name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_wifi_network( $context = 'view' ) {
		return $this->get_prop( 'wifi_network', $context );
	}

	/**
	 * Get outlet Wi-Fi network password.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_wifi_password( $context = 'view' ) {
		return $this->get_prop( 'wifi_password', $context );
	}

	/**
	 * Get outlet social network accounts.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return array
	 */
	public function get_social_accounts( $context = 'view' ) {
		return $this->get_prop( 'social_accounts', $context );
	}

	/*
	 * Setters
	 *
	 * Functions for setting outlet data. These should not update anything in the
	 * database itself and should only change what is stored in the class
	 * object.
	 */

	/**
	 * Set outlet name.
	 *
	 * @param string $name Outlet name.
	 */
	public function set_name( $name ) {
		$this->set_prop( 'name', $name );
	}

	/**
	 * Set outlet slug.
	 *
	 * @param string $slug Outlet slug.
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
	 * Set outlet address line 1.
	 *
	 * @param string $address Address line.
	 */
	public function set_address_1( $address ) {
		$this->set_prop( 'address_1', $address );
	}

	/**
	 * Set outlet address line 2.
	 *
	 * @param string $address Address line.
	 */
	public function set_address_2( $address ) {
		$this->set_prop( 'address_2', $address );
	}

	/**
	 * Set outlet city.
	 *
	 * @param string $city City.
	 */
	public function set_city( $city ) {
		$this->set_prop( 'city', $city );
	}

	/**
	 * Set outlet postcod/ZIP.
	 *
	 * @param string $postcode Postcode/ZIP.
	 */
	public function set_postcode( $postcode ) {
		$this->set_prop( 'postcode', $postcode );
	}

	/**
	 * Set outlet country.
	 *
	 * @param string $country Country.
	 */
	public function set_country( $country ) {
		$this->set_prop( 'country', $country );
	}

	/**
	 * Set outlet state/county.
	 *
	 * @param string $state State.
	 */
	public function set_state( $state ) {
		$this->set_prop( 'state', $state );
	}

	/**
	 * Set outlet email.
	 *
	 * @param string $email Email address.
	 */
	public function set_email( $email ) {
		$this->set_prop( 'email', $email );
	}

	/**
	 * Set outlet phone.
	 *
	 * @param string $phone Phone number.
	 */
	public function set_phone( $phone ) {
		$this->set_prop( 'phone', $phone );
	}

	/**
	 * Set outlet fax.
	 *
	 * @param string $fax Fax number.
	 */
	public function set_fax( $fax ) {
		$this->set_prop( 'fax', $fax );
	}

	/**
	 * Set outlet website.
	 *
	 * @param string $website Website.
	 */
	public function set_website( $website ) {
		$this->set_prop( 'website', $website );
	}

	/**
	 * Set outlet Wi-Fi network name.
	 *
	 * @param string $name Network name.
	 */
	public function set_wifi_network( $name ) {
		$this->set_prop( 'wifi_network', $name );
	}

	/**
	 * Set outlet Wi-Fi network password.
	 *
	 * @param string $password Network password.
	 */
	public function set_wifi_password( $password ) {
		$this->set_prop( 'wifi_password', $password );
	}

	/**
	 * Set outlet social accounts.
	 *
	 * @param array $accounts Social network accounts.
	 */
	public function set_social_accounts( $accounts ) {
		$this->set_prop( 'social_accounts', $accounts );
	}

	/*
	 * Other Actions
	 */

	/**
	 * Developers can programmatically return outlets. This function will read those values into our WC_POS_Outlet class.
	 *
	 * @param string $slug   Outlet slug.
	 * @param array  $outlet Array of outlet properties.
	 */
	public function read_manual_outlet( $slug, $outlet ) {
		$this->set_props( $outlet );
		$this->set_slug( $slug );
		$this->set_id( 0 );
		$this->set_virtual( true );
	}
}
