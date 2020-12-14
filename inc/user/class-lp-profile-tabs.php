<?php

/**
 * Class LP_Profile_Tabs
 */
class LP_Profile_Tabs extends LP_Array_Access {

	/**
	 * @var array
	 */
	protected $_tabs = array();

	/**
	 * @var LP_Profile_Tabs
	 */
	protected static $_instance = null;

	/**
	 * @var LP_Profile
	 */
	protected $_profile = null;

	/**
	 * LP_Profile_Tabs constructor.
	 *
	 * @param array $tabs
	 * @param LP_Profile $profile
	 */
	public function __construct( $tabs, $profile = null ) {

		parent::__construct( $tabs );

		$this->_profile = $profile;
		$this->_sanitize();
		$this->_init();
	}

	protected function _init() {
		foreach ( $this->_data as $k => $v ) {
			if ( empty( $v['slug'] ) ) {
				$v['slug'] = $k;
			}
			$this->_data[ $k ] = new LP_Profile_Tab( $k, $v, $this->get_profile() );
		}
	}

	/**
	 * Sort tabs
	 */
	protected function _sanitize() {
		$tabs = $this->_data;
		foreach ( $tabs as $slug => $data ) {
			if ( ! array_key_exists( 'slug', $data ) ) {
				$tabs[ $slug ]['slug'] = $slug;
			}

			if ( ! array_key_exists( 'priority', $data ) ) {
				$tabs[ $slug ]['priority'] = 10;
			}

			if ( empty( $data['sections'] ) ) {
				continue;
			}
			foreach ( $data['sections'] as $section_slug => $section_data ) {
				if ( ! array_key_exists( 'slug', $section_data ) ) {
					$tabs[ $slug ]['sections'][ $section_slug ]['slug'] = $section_slug;
				}

				if ( ! array_key_exists( 'priority', $section_data ) ) {
					$tabs[ $slug ]['sections'][ $section_slug ]['priority'] = 10;
				}
			}
			//$tabs[ $slug ]['sections']
			uasort( $tabs[ $slug ]['sections'], array( $this, '_sort_tabs' ) );
		}

		uasort( $tabs, array( $this, '_sort_tabs' ) );

		$key = md5( serialize( array_keys( $tabs ) ) );
		if ( $key !== get_option( '_lp_tabs_data' ) ) {
			flush_rewrite_rules();
			update_option( '_lp_tabs_data', $key, false );
		}

		$this->_data = $tabs;
	}

	/**
	 * @return LP_Profile|null
	 */
	public function get_profile() {
		return $this->_profile ? $this->_profile : LP_Profile::instance();
	}

	public function get_current_tab( $default = '', $key = true ) {
		global $wp;
		$current = $default;

		if ( ! empty( $_REQUEST['view'] ) ) {
			$current = $_REQUEST['view'];
		} elseif ( ! empty( $wp->query_vars['view'] ) ) {
			$current = $wp->query_vars['view'];
		} else {
			if ( $tab = $this->get_tab_at() ) {
				$current = $tab['slug'];
			}
		}

		if ( $key ) {
			$current_display = $current;
			$current         = false;
			foreach ( $this->get() as $_slug => $data ) {
				if ( $data->get( 'slug' ) === $current_display ) {
					$current = $_slug;
					break;
				}
			}
		}

		return $current;
	}

	public function get_current_section( $default = '', $key = true, $tab = '' ) {
		global $wp;
		$current = $default;
		if ( ! empty( $_REQUEST['section'] ) ) {
			$current = $_REQUEST['section'];
		} elseif ( ! empty( $wp->query_vars['section'] ) ) {
			$current = $wp->query_vars['section'];
		} else {
			if ( false === $tab ) {
				$current_tab = $this->get_current_tab();
			} else {
				$current_tab = $tab;
			}
			if ( $tab = $this->get_tab_at( $current_tab ) ) {
				if ( ! empty( $tab['sections'] ) ) {
					$sections = $tab['sections'];
					$section  = reset( $sections );
					if ( array_key_exists( 'slug', $section ) ) {
						$current = $section['slug'];
					} else {
						$sections = array_keys( $tab['sections'] );
						$current  = reset( $sections );
					}
				}
			}
		}

		// If find the key instead of value from settings
		if ( $key ) {
			$current_display = $current;
			$current         = false;
			foreach ( $this->get() as $_slug => $data ) {
				if ( empty( $data['sections'] ) ) {
					continue;
				}
				foreach ( $data['sections'] as $_slug => $data ) {
					if ( array_key_exists( 'slug', $data ) && ( $data['slug'] === $current_display ) ) {
						$current = $_slug;
						break 2;
					}
				}
			}
		}

		return $current;
	}


	/**
	 * @param bool $tab
	 * @param bool $with_section
	 * @param LP_User $user
	 *
	 * @return string
	 */
	public function get_tab_link( $tab = false, $with_section = false, $user = null ) {

		if ( ( $tab || $with_section ) && empty( $user ) ) {
			$current_user = learn_press_get_current_user();
			$user         = $current_user->get_username();
		}

		$args = array( 'user' => $user );

		if ( isset( $args['user'] ) ) {
			if ( false === $tab ) {
				$tab = $this->get_current_tab( null, false );
			}

			$tab_data = $this->get_tab_at( $tab );
			$tab      = $this->get_slug( $tab_data, $tab );

			if ( $tab ) {
				$args['tab'] = $tab;
			} else {
				unset( $args['user'] );
			}

			if ( $with_section && ! empty( $tab_data['sections'] ) ) {
				if ( $with_section === true ) {
					$section_keys  = array_keys( $tab_data['sections'] );
					$first_section = reset( $section_keys );
					$with_section  = $this->get_slug( $tab_data['sections'][ $first_section ], $first_section );
				}
				$args['section'] = $with_section;
			}
		}
		$args         = array_map( '_learn_press_urlencode', $args );
		$profile_link = trailingslashit( learn_press_get_page_link( 'profile' ) );
		if ( $profile_link ) {
			if ( get_option( 'permalink_structure' ) ) {
				$url = trailingslashit( $profile_link . join( "/", array_values( $args ) ) );
			} else {
				$url = add_query_arg( $args, $profile_link );
			}
		} else {
			$url = get_author_posts_url( $user->get_id() );
		}

		return $url;
	}

	/**
	 * Get the slug of tab or section if defined.
	 *
	 * @param array $tab_or_section
	 * @param string $default
	 *
	 * @return string
	 */
	public function get_slug( $tab_or_section, $default = '' ) {
		if ( is_array( $tab_or_section ) ) {
			return array_key_exists( 'slug', $tab_or_section ) ? $tab_or_section['slug'] : false;
		}

		if ( is_string( $tab_or_section ) ) {
			return $tab_or_section;
		}

		return $tab_or_section ? $tab_or_section->get( 'slug' ) : false;
	}

	/**
	 * Get current link of profile
	 *
	 * @param string $args - Optional. Add more query args to url.
	 * @param bool $with_permalink - Optional. TRUE to build url as friendly url.
	 *
	 * @return mixed|string
	 */
	public function get_current_url( $args = '', $with_permalink = false ) {
		$current_tab = $this->get_current_tab();
		$tab         = $this->get_tab_at( $current_tab );
		$sections    = $tab['sections'];

		$current_section_slug = $this->get_current_section();
		$section              = array();
		if ( isset( $sections[ $current_section_slug ] ) ) {
			$sections[ $current_section_slug ];
		} elseif ( $sections && ! empty( $sections ) ) {
			reset( $sections );
		}
		if ( array_key_exists( 'slug', $section ) ) {
			$current_section_slug = $section['slug'];
		}
		$url = $this->get_tab_link( $this->get_current_tab(), $current_section_slug,
			$this->get_profile()->get_user()->get_username() );

		if ( is_array( $args ) && $args ) {
			if ( ! $with_permalink ) {
				$url = add_query_arg( $args, $url );
			} else {
				$parts = array();
				foreach ( $args as $k => $v ) {
					$parts[] = "{$k}/{$v}";
				}
				$url = trailingslashit( $url ) . join( "/", $parts ) . '/';
			}
		}

		return $url;
	}

	/**
	 * Get tab data at a position.
	 *
	 * @param int $position Optional. Indexed number or slug.
	 *
	 * @return mixed
	 */
	public function get_tab_at( $position = 0 ) {
		if ( ! $position ) {
			$position = 0;
		}

		if ( $tabs = $this->get() ) {
			if ( is_numeric( $position ) ) {
				$tabs = array_values( $tabs );
				if ( ! empty( $tabs[ $position ] ) ) {
					return $tabs[ $position ];
				}

			} else {
				if ( ! empty( $tabs[ $position ] ) ) {
					return $tabs[ $position ];
				}
			}


		}

		return false;
	}

	public function tabs() {
		$profile = $this->get_profile();
		$tabs    = array();
		if ( $all_tabs = $this->get() ) {
			foreach ( $all_tabs as $key => $tab ) {
				// If current user do not have permission and/or tab is invisible
				if ( ! $profile->current_user_can( 'view-tab-' . $key ) ) {
					continue;
				}

				$tabs[ $key ] = $tab;
			}
		}

		return $tabs;
	}

	public function get( $key = false ) {
		return false !== $key ? ( array_key_exists( $key,
			$this->_data ) ? $this->_data[ $key ] : false ) : $this->_data;
	}

	protected function _sort_tabs( $a, $b ) {
		if ( $a['priority'] === $b['priority'] ) {
			return 0;
		}

		return $a['priority'] < $b['priority'] ? - 1 : 1;
	}

	/**
	 * Remove tab.
	 *
	 * @param $key
	 */
	public function remove_tab( $key ) {
		$tabs = $this->_data;

		foreach ( $tabs as $slug => $data ) {
			if ( $key == $slug ) {
				unset( $tabs[ $key ] );
			}
		}

		$this->_data = $tabs;
	}
}

/**
 * Class LP_Profile_Tab
 *
 * @since 3.0.0
 */
class LP_Profile_Tab extends LP_Array_Access {

	/**
	 * @var LP_Profile
	 */
	protected $_profile = null;

	/**
	 * @var string
	 */
	public $id = '';

	/**
	 * LP_Profile_Tab constructor.
	 *
	 * @param string $id
	 * @param array $data
	 * @param LP_Profile $profile
	 */
	public function __construct( $id, $data, $profile ) {
		parent::__construct( $data );
		$this->_profile = $profile;
		$this->id       = $id;
	}

	public function sections() {
		$profile  = $this->get_profile();
		$sections = array();
		if ( $all_sections = $this->get( 'sections' ) ) {
			foreach ( $all_sections as $section_key => $section ) {

				// If current user do not have permission and/or tab is invisible
				if ( $profile->is_hidden( $section ) ) {
					continue;
				}
				$sections[ $section_key ] = $section;
			}
		}

		return $sections;
	}

	public function get( $key = false ) {
		return false !== $key ? ( array_key_exists( $key,
			$this->_data ) ? $this->_data[ $key ] : false ) : $this->_data;
	}

	public function get_profile() {
		return $this->_profile ? $this->_profile : LP_Profile::instance();
	}

	public function user_can_view() {
		$can = $this->get_profile()->current_user_can( "view-tab-{$this->id}" );

		return $can;
	}

	public function user_can_view_section( $section ) {
		return $this->get_profile()->current_user_can( "view-section-{$section}" );
	}

	public function is_hidden() {
		return $this->get( 'hidden' );
	}
}
