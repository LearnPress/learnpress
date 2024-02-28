<?php

/**
 * Class LP_Profile_Tabs
 */
class LP_Profile_Tabs {
	/**
	 * @var array
	 */
	protected $_data = array();
	/**
	 * @var LP_Profile_Tabs
	 */
	protected static $_instance = null;

	/**
	 * @var LP_Profile
	 */
	protected $profile = null;

	/**
	 * LP_Profile_Tabs constructor.
	 *
	 * @param array $tabs
	 * @param LP_Profile $profile
	 */
	public function __construct( $tabs, $profile ) {
		$tabs_tmp = [];
		foreach ( $tabs as $k => $v ) {
			$tabs_tmp[ $k ]              = $v;
			$tabs_tmp[ $k ]['key_index'] = $k;
			if ( ! array_key_exists( 'priority', $v ) ) {
				$tabs_tmp[ $k ]['priority'] = 10;
			}
			if ( ! array_key_exists( 'slug', $v ) ) {
				$tabs_tmp[ $k ]['slug'] = $k;
			}
		}

		// Sort tab by priority.
		usort(
			$tabs_tmp,
			function ( $tab1, $tab2 ) {
				if ( $tab1['priority'] < $tab2['priority'] ) {
					return - 1;
				} elseif ( $tab1['priority'] > $tab2['priority'] ) {
					return 1;
				} else {
					return 0;
				}
			}
		);

		foreach ( $tabs_tmp as $v ) {
			$k                 = $v['key_index'];
			$this->_data[ $k ] = new LP_Profile_Tab( $k, $v, $profile );
		}

		$this->profile = $profile;
	}

	/**
	 * @return LP_Profile|null
	 */
	public function get_profile() {
		return $this->profile;
	}

	public function get_current_tab( $default = '', $key = true ) {
		global $wp;
		$current = $default;

		if ( ! empty( $_REQUEST['view'] ) ) {
			$current = sanitize_text_field( $_REQUEST['view'] );
		} elseif ( ! empty( $wp->query_vars['view'] ) ) {
			$current = $wp->query_vars['view'];
		} else {
			$tab = $this->get_tab_at();
			if ( $tab instanceof LP_Profile_Tab ) {
				$current = $tab->get( 'slug' );
			}
		}

		if ( $key ) {
			$current_display = $current;
			$current         = false;
			foreach ( $this->get() as $_slug => $data ) {
				if ( is_object( $data ) ) {
					if ( $data->get( 'slug' ) === $current_display ) {
						$current = $_slug;
						break;
					}
				} elseif ( is_array( $data ) ) {
					if ( $data['slug'] === $current_display ) {
						$current = $_slug;
						break;
					}
				}
			}
		}

		return $current;
	}

	public function get_current_section( $default = '', $key = true, $tab = '' ) {
		global $wp;

		$current = $default;

		if ( ! empty( $_REQUEST['section'] ) ) {
			$current = sanitize_text_field( $_REQUEST['section'] );
		} elseif ( ! empty( $wp->query_vars['section'] ) ) {
			$current = $wp->query_vars['section'];
		} else {
			if ( false === $tab ) {
				$current_tab = $this->get_current_tab();
			} else {
				$current_tab = $tab;
			}

			$tab = $this->get_tab_at( $current_tab );
			if ( $tab instanceof LP_Profile_Tab ) {
				if ( ! empty( $tab->get( 'sections' ) ) ) {
					$sections = $tab->get( 'sections' );
					$section  = reset( $sections );
					if ( array_key_exists( 'slug', $section ) ) {
						$current = $tab->get( 'slug' );
					} else {
						$sections = array_keys( $tab->get( 'sections' ) );
						$current  = reset( $sections );
					}
				}
			}
		}

		if ( $key ) {
			$current_display = $current;
			$current         = false;

			foreach ( $this->get() as $_slug => $data ) {
				if ( empty( $data->get( 'sections' ) ) ) {
					continue;
				}

				foreach ( $data->get( 'sections' ) as $_slug => $data ) {
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
	 * @since 3.0.0
	 * @version 4.0.0
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
			if ( $tab_data instanceof LP_Profile_Tab ) {
				$slug = $this->get_slug( $tab_data, $tab );
				if ( $slug ) {
					$args['tab'] = $slug;
				} else {
					unset( $args['user'] );
				}

				if ( $with_section && ! empty( $tab_data->get( 'sections' ) ) ) {
					if ( $with_section === true ) {
						$section_keys  = array_keys( $tab_data->get( 'sections' ) );
						$first_section = reset( $section_keys );
						$with_section  = $this->get_slug( $tab_data->get( 'sections' )[ $first_section ], $first_section );
					}
					$args['section'] = $with_section;
				}
			}
		}
		$profile_link = trailingslashit( learn_press_get_page_link( 'profile' ) );

		if ( $profile_link ) {
			if ( get_option( 'permalink_structure' ) ) {
				$url = trailingslashit( $profile_link . join( '/', array_values( $args ) ) );
			} else {
				$url = esc_url_raw( add_query_arg( $args, $profile_link ) );
			}
		} else {
			$url = get_author_posts_url( $user->get_id() );
		}

		return apply_filters( 'learnpress/profile/tab/link', $url, $tab, $with_section, $user );
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
	 * @Todo tungnx - need check this function
	 */
	public function get_current_url( $args = '', $with_permalink = false ) {
		$current_tab = $this->get_current_tab();
		$tab         = $this->get_tab_at( $current_tab );
		if ( ! $tab instanceof LP_Profile_Tab ) {
			return '';
		}

		$current_section_slug = $this->get_current_section();
		$url                  = $this->get_tab_link( $this->get_current_tab(), $current_section_slug, $this->get_profile()->get_user()->get_username() );
		if ( is_array( $args ) && $args ) {
			if ( ! $with_permalink ) {
				$url = esc_url_raw( add_query_arg( $args, $url ) );
			} else {
				$parts = array();

				foreach ( $args as $k => $v ) {
					$parts[] = "{$k}/{$v}";
				}

				$url = trailingslashit( $url ) . join( '/', $parts ) . '/';
			}
		}

		return $url;
	}

	/**
	 * Get tab data at a position.
	 *
	 * @param int $position Optional. Indexed number or slug.
	 *
	 * @return false|LP_Profile_Tab
	 */
	public function get_tab_at( $position = 0 ) {
		if ( ! $position ) {
			$position = 0;
		}

		if ( $this->get() ) {
			$tabs = $this->get();

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
		return $this->get();
	}

	public function get( $key = false ) {
		return false !== $key ? ( array_key_exists( $key, $this->_data ) ? $this->_data[ $key ] : false ) : $this->_data;
	}

	protected function _sort_tabs( $a, $b ) {
		if ( $a['priority'] === $b['priority'] ) {
			return 0;
		}

		return $a['priority'] < $b['priority'] ? - 1 : 1;
	}
}

/**
 * Class LP_Profile_Tab
 *
 * @since 3.0.0
 */
class LP_Profile_Tab {
	protected $_data = array();
	/**
	 * @var LP_Profile
	 */
	protected $profile = null;

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
		//parent::__construct( $data );
		$this->_data = is_array( $data ) ? $data : (array) $data;

		$this->profile = $profile;
		$this->id      = $id;
	}

	public function sections() {
		$profile      = $this->get_profile();
		$sections     = array();
		$all_sections = $this->get( 'sections' );

		if ( $all_sections ) {
			foreach ( $all_sections as $section_key => $section ) {

				if ( $profile->is_hidden( $section ) ) {
					continue;
				}
				$sections[ $section_key ] = $section;
			}
		}

		return $sections;
	}

	public function get( $key = false ) {
		return false !== $key ? ( array_key_exists( $key, $this->_data ) ? $this->_data[ $key ] : false ) : $this->_data;
	}

	public function get_profile() {
		return $this->profile;
	}

	/**
	 * @deprecated 4.2.6.2
	 */
	public function user_can_view() {
		_deprecated_function( __METHOD__, '4.2.6.2' );
		return false;
		if ( $this->is_public() || current_user_can( ADMIN_ROLE ) ) {
			return true;
		}

		$can = $this->get_profile()->current_user_can( "view-tab-{$this->id}" );

		return $can;
	}

	/**
	 * @deprecated 4.2.6.2
	 */
	public function user_can_view_section( $section ) {
		_deprecated_function( __METHOD__, '4.2.6.2' );
		return false;
		return $this->get_profile()->current_user_can( "view-section-{$section}" );
	}

	public function is_hidden() {
		return $this->get( 'hidden' );
	}

	public function tab_is_visible_for_user() {
		return $this->is_current();
	}

	public function is_current() {
		return isset( $this['is_current'] ) ? $this['is_current'] : false;
	}

	/**
	 * Tab is public for all users can view.
	 *
	 * @return bool
	 * @since 4.0.0
	 * @deprecated 4.2.6.2
	 */
	public function is_public() {
		_deprecated_function( __METHOD__, '4.2.6.2' );
		return false;
		$public_tabs = $this->profile->get_public_tabs();

		return $public_tabs && in_array( $this->id, $public_tabs );
	}
}
