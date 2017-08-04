<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'LP_Profile' ) ) {
	/**
	 * Class LP_Profile
	 *
	 * Main class to controls the profile of a user
	 */
	class LP_Profile {
		/**
		 * The instances of all users has initialed a profile
		 *
		 * @var array
		 */
		protected static $_instances = array();

		/**
		 * @var LP_User
		 */
		protected $_user = false;

		protected $_role = '';

		protected static $_hook_added = false;

		protected $_publicity = array();

		/**
		 *  Constructor
		 */
		protected function __construct( $user, $role = '' ) {

			$this->_user = $user;
			$this->get_user();

			if ( ! $role ) {
				$this->_role = $this->get_role();
			}

			if ( ! self::$_hook_added ) {
				self::$_hook_added = true;
				add_action( 'learn-press/profile-content', array( $this, 'output' ), 10, 3 );
				add_action( 'learn-press/before-profile-content', array( $this, 'output_section' ), 10, 3 );
				add_action( 'learn-press/profile-section-content', array( $this, 'output_section_content' ), 10, 3 );
			}
		}

		public function output( $tab, $args, $user ) {
			if ( ( $location = learn_press_locate_template( 'profile/tabs/' . $tab . '.php' ) ) && file_exists( $location ) ) {
				include $location;
			}
		}

		public function output_section( $tab_key, $tab_data, $user ) {
			learn_press_get_template( 'profile/tabs/sections.php', compact( 'tab_key', 'tab_data', 'user' ) );
		}

		public function output_section_content( $section, $args, $user ) {
			global $wp;
			$current = $this->get_current_section();// ! empty( $wp->query_vars['section'] ) ? $wp->query_vars['section'] : false;
			if ( $current === $section ) {
				if ( ( $location = learn_press_locate_template( 'profile/tabs/edit/' . $section . '.php' ) ) && file_exists( $location ) ) {
					include $location;
				} else {
					echo $location;
				}
			}

		}

		protected function get_role() {
			if ( $user = learn_press_get_current_user( false ) ) {
				if ( ! $user->is_guest() ) {
					if ( $this->_user->is_admin() ) {
						return 'admin';
					}

					if ( $this->_user->is_instructor() ) {
						return 'instructor';
					}

					return 'user';
				}
			}

			return '';
		}

		public function get_user() {
			if ( is_numeric( $this->_user ) ) {
				$this->_user = learn_press_get_user( $this->_user );
			} elseif ( empty( $this->_user ) ) {
				$this->_user = learn_press_get_current_user( true );
			}

			$settings         = LP()->settings;
			$this->_publicity = array(
				'view-tab-courses'           => $settings->get( 'profile_publicity.courses' ) === 'yes',
				'view-tab-basic-information' => $settings->get( 'profile_publicity.basic-information' ) === 'yes',
			);

			return $this->_user;
		}

		public function tab_dashboard() {
			learn_press_get_template( 'profile/dashboard.php', array( 'user' => $this->_user ) );
		}

		public function get_tabs() {
			$settings = LP()->settings;
			$defaults = array(
				''              => array(
					'title'    => __( 'Dashboard', 'learnpress' ),
					'slug'     => $settings->get( 'profile_endpoints.profile-dashboard', '' ),
					'callback' => array( $this, 'tab_dashboard' )
				),
				'courses'       => array(
					'title'    => __( 'Courses', 'learnpress' ),
					'slug'     => $settings->get( 'profile_endpoints.profile-courses', 'courses' ),
					'callback' => array( $this, 'tab_courses' )
				),
				'quizzes'       => array(
					'title'    => __( 'Quizzes', 'learnpress' ),
					'slug'     => $settings->get( 'profile_endpoints.profile-quizzes', 'quizzes' ),
					'callback' => array( $this, 'tab_quizzes' )
				),
				'orders'        => array(
					'title'    => __( 'Orders', 'learnpress' ),
					'slug'     => $settings->get( 'profile_endpoints.profile-orders', 'orders' ),
					'callback' => array( $this, 'tab_orders' )
				),
				'order-details' => array(
					'title'    => __( 'Order details', 'learnpress' ),
					'slug'     => $settings->get( 'profile_endpoints.profile-order-details', 'order-details' ),
					'hidden'   => true,
					'callback' => array( $this, 'tab_order_details' )
				),
				'settings'      => array(
					'title'    => __( 'Settings', 'learnpress' ),
					'slug'     => $settings->get( 'profile_endpoints.profile-settings', 'settings' ),
					'callback' => array( $this, 'tab_settings' ),
					'sections' => array(
						'basic-information' => array(
							'title'    => __( 'General', 'learnpress' ),
							'slug'     => $settings->get( 'profile_endpoints.settings-basic-information', 'basic-information' ),
							'callback' => array( $this, 'tab_order_details' )
						),
						'avatar'            => array(
							'title'    => __( 'Avatar', 'learnpress' ),
							'callback' => array( $this, 'tab_order_details' ),
							'slug'     => $settings->get( 'profile_endpoints.settings-avatar', 'avatar' ),
						),
						'change-password'   => array(
							'title'    => __( 'Password', 'learnpress' ),
							'slug'     => $settings->get( 'profile_endpoints.settings-change-password', 'change-password' ),
							'hidden'   => true,
							'callback' => array( $this, 'tab_order_details' )
						)
					)
				)
			);

			$tabs = apply_filters( 'learn-press/profile-tabs', $defaults );

			foreach ( $tabs as $slug => $data ) {
				if ( ! array_key_exists( 'slug', $data ) ) {
					$data['slug'] = $slug;
				}
				if ( empty( $data['sections'] ) ) {
					continue;
				}
				foreach ( $data['sections'] as $section_slug => $section_data ) {
					if ( ! array_key_exists( 'slug', $section_data ) ) {
						$tabs[ $slug ]['sections'][ $section_slug ]['slug'] = $section_slug;
					}
				}
			}

			return $tabs;
			$course_endpoint = LP()->settings->get( 'profile_endpoints.profile-courses' );
			if ( ! $course_endpoint ) {
				$course_endpoint = 'profile-courses';
			}

			$quiz_endpoint = LP()->settings->get( 'profile_endpoints.profile-quizzes' );
			if ( ! $quiz_endpoint ) {
				$quiz_endpoint = 'profile-quizzes';
			}

			$order_endpoint = LP()->settings->get( 'profile_endpoints.profile-orders' );
			if ( ! $order_endpoint ) {
				$order_endpoint = 'profile-orders';
			}

			$view_order_endpoint = LP()->settings->get( 'profile_endpoints' );
			if ( ! $view_order_endpoint ) {
				$view_order_endpoint = 'order';
			}

			$defaults = array(

				$course_endpoint => array(
					'title'    => __( 'Courses', 'learnpress' ),
					'base'     => 'courses',
					'callback' => 'learn_press_profile_tab_courses_content'
				)
			);

			if ( $this->_user->get_id() == get_current_user_id() ) {
				$defaults[ $order_endpoint ] = array(
					'title'    => __( 'Orders', 'learnpress' ),
					'base'     => 'orders',
					'callback' => 'learn_press_profile_tab_orders_content'
				);
			}

			$tabs = apply_filters( 'learn_press_get_user_profile_tabs', $defaults, $this->_user );
			if ( $this->_user->get_id() == get_current_user_id() ) {
				$tabs['settings'] = array(
					'title'    => apply_filters( 'learn_press_user_profile_tab_edit_title', __( 'Settings', 'learnpress' ) ),
					'base'     => 'settings',
					'callback' => 'learn_press_profile_tab_edit_content'
				);
			}

			foreach ( $tabs as $slug => $opt ) {
				if ( ! empty( $defaults[ $slug ] ) ) {
					continue;
				}
				LP()->query_vars[ $slug ] = $slug;
				add_rewrite_endpoint( $slug, EP_PAGES );
			}

			return $tabs;
		}

		/**
		 * Get current tab slug in query string.
		 *
		 * @param string $default Optional.
		 * @param bool   $key     Optional. True if return the key instead of value.
		 *
		 * @return string
		 */
		public function get_current_tab( $default = '', $key = true ) {
			global $wp;
			$current = $default;
			if ( ! empty( $_REQUEST['view'] ) ) {
				$current = $_REQUEST['view'];
			} else if ( ! empty( $wp->query_vars['view'] ) ) {
				$current = $wp->query_vars['view'];
			} else {
				if ( $tab = $this->get_tab_at() ) {
					$current = $tab['slug'];
				}
			}
			if ( $key ) {
				$current_display = $current;
				$current         = false;
				foreach ( $this->get_tabs() as $_slug => $data ) {
					if ( array_key_exists( 'slug', $data ) && ( $data['slug'] === $current_display ) ) {
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
			} else if ( ! empty( $wp->query_vars['section'] ) ) {
				$current = $wp->query_vars['section'];
			} else {
				if ( false === $tab ) {
					$current_tab = $this->get_current_tab();
				} else {
					$current_tab = $tab;
				}
				if ( $tab = $this->get_tab_at( $current_tab ) ) {
					if ( ! empty( $tab['sections'] ) ) {
						$section = reset( $tab['sections'] );
						if ( array_key_exists( 'slug', $section ) ) {
							$current = $section['slug'];
						} else {
							$sections = array_keys( $tab['sections'] );
							$current  = reset( $sections );
						}
					}
				}
			}

			if ( $key ) {
				$current_display = $current;
				$current         = false;
				foreach ( $this->get_tabs() as $_slug => $data ) {
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
		 * Get tab data at a position.
		 *
		 * @param int $position Optional. Indexed number or slug.
		 *
		 * @return mixed
		 */
		public function get_tab_at( $position = 0 ) {
			if ( $tabs = $this->get_tabs() ) {
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

		public function get_tab_link( $tab = false, $with_section = false ) {

			$user = $this->get_user();

			if ( ! $user ) {
				return '';
			}
			$args = array(
				'user' => $user->get_data( 'username' )
			);
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

			/**
			 * @deprecated
			 */
			$url = apply_filters( 'learn_press_user_profile_link', $url, $user->get_id(), $tab );

			return apply_filters( 'learn-press/user-profile-url', $url, $user->get_id(), $tab );
		}

		/**
		 * Check if the $key is current tab.
		 *
		 * @param string $key
		 *
		 * @return bool
		 */
		public function is_current_tab( $key ) {
			return $this->get_current_tab() === $key;
		}

		/**
		 * Check if the $key is current section.
		 *
		 * @param string $key
		 * @param string $tab
		 *
		 * @return bool
		 */
		public function is_current_section( $key, $tab = '' ) {
			return $this->get_current_section() === $key;
		}

		/**
		 * Check if a tab or section is hidden.
		 *
		 * @param array $tab_or_section
		 *
		 * @return bool
		 */
		public function is_hidden( $tab_or_section ) {
			return array_key_exists( 'hidden', $tab_or_section ) && $tab_or_section['hidden'];
		}

		/**
		 * Get the slug of tab or section if defined.
		 *
		 * @param array  $tab_or_section
		 * @param string $default
		 *
		 * @return string
		 */
		public function get_slug( $tab_or_section, $default = '' ) {
			return array_key_exists( 'slug', $tab_or_section ) ? $tab_or_section['slug'] : $default;
		}

		public function current_user_can( $capability ) {
			$can = false;
			if ( get_current_user_id() === $this->_user->get_id() ) {
				$can = true;
			} else {
				$can = ! empty( $this->_publicity[ $capability ] ) && $this->_publicity[ $capability ] == true;
			}

			return apply_filters( 'learn-press/profile-current-user-can', $can, $capability );
		}

		/**
		 * Get an instance of LP_Profile for a user id
		 *
		 * @param $user_id
		 *
		 * @return LP_Profile mixed
		 */
		public static function instance( $user_id = 0 ) {
			if ( ! $user_id ) {
				$user_id = get_current_user_id();
			}
			if ( empty( self::$_instances[ $user_id ] ) ) {
				self::$_instances[ $user_id ] = new self( $user_id );
			}

			return self::$_instances[ $user_id ];
		}
	}
}