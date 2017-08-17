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

		/**
		 * @var string
		 */
		protected $_role = '';

		/**
		 * @var bool
		 */
		protected static $_hook_added = false;

		/**
		 * @var array
		 */
		protected $_publicity = array();

		/**
		 * @var array
		 */
		protected $_default_actions = array();

		/**
		 * @var LP_User_CURD
		 */
		protected $_curd = null;

		/**
		 *  Constructor
		 *
		 * @param        $user
		 * @param string $role
		 */
		protected function __construct( $user, $role = '' ) {

			$this->_curd = new LP_User_CURD();

			$this->_user = $user;
			$this->get_user();

			if ( ! $role ) {
				$this->_role = $this->get_role();
			}

			$this->_default_actions = apply_filters(
				'learn-press/profile-default-actions',
				array(
					'basic-information' => __( 'Account information updated successfully.', 'learnpress' ),
					'avatar'            => __( 'Account avatar updated successfully.', 'learnpress' ),
					'password'          => __( 'Password updated successfully.', 'learnpress' )
				)
			);

			if ( ! self::$_hook_added ) {
				self::$_hook_added = true;
				add_action( 'learn-press/profile-content', array( $this, 'output' ), 10, 3 );
				add_action( 'learn-press/before-profile-content', array( $this, 'output_section' ), 10, 3 );
				add_action( 'learn-press/profile-section-content', array( $this, 'output_section_content' ), 10, 3 );

				/*
				 * Register actions with request handler class to process
				 * requesting from user profile.
				 */
				foreach ( $this->_default_actions as $action => $message ) {
					/**
					 * @see LP_Profile::save()
					 */
					LP_Request_Handler::register( 'save-profile-' . $action, array( $this, 'save' ) );
				}
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

		/**
		 * Get role of current user.
		 *
		 * @return string
		 */
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

		/**
		 * Get the user of a profile instance.
		 *
		 * @return bool|LP_User|mixed
		 */
		public function get_user() {
			if ( ! $this->_user instanceof LP_Abstract_User ) {
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
			}

			return $this->_user;
		}

		/**
		 * Wrap function for $user->get_data()
		 *
		 * @param string $field
		 *
		 * @return mixed
		 */
		public function get_user_data( $field ) {
			return 'id' === strtolower( $field ) ? $this->_user->get_id() : $this->_user->get_data( $field );
		}

		public function tab_dashboard() {
			learn_press_get_template( 'profile/dashboard.php', array( 'user' => $this->_user ) );
		}

		/**
		 * Get default tabs for profile.
		 *
		 * @return mixed
		 */
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

		/**
		 * Get current section in query string.
		 *
		 * @param string $default
		 * @param bool   $key
		 * @param string $tab
		 *
		 * @return bool|int|mixed|string
		 */
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

			// If find the key instead of value from settings
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

		/**
		 * Get permalink of a tab and section if passed.
		 *
		 * @param bool $tab
		 * @param bool $with_section
		 *
		 * @return mixed|string
		 */
		public function get_tab_link( $tab = false, $with_section = false ) {

			$user = $this->get_user();

			if ( ! $user ) {
				return '';
			}
			$args = array(
				'user' => $user->get_data( 'user_login' )
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

		/**
		 * Check if user can with a capability.
		 *
		 * @param string $capability
		 *
		 * @return mixed
		 */
		public function current_user_can( $capability ) {

			$tab         = substr( $capability, strlen( 'view-tab-' ) );
			$public_tabs = array( 'courses', 'quizzes' );

			// public profile courses and quizzes tab
			if ( in_array( $tab, $public_tabs ) ) {
				$can = true;
			} else {
				if ( get_current_user_id() === $this->_user->get_id() ) {
					$can = true;
				} else {
					$can = ! empty( $this->_publicity[ $capability ] ) && $this->_publicity[ $capability ] == true;
				}
			}

			return apply_filters( 'learn-press/profile-current-user-can', $can, $capability );
		}

		/**
		 * Save profile.
		 *
		 * @param string $nonce . Value of nonce depending on the action requested from profile tab.
		 *
		 * @return mixed
		 */
		public function save( $nonce ) {

			$action  = '';
			$message = '';

			// Find the action by checking the nonce
			foreach ( $this->_default_actions as $_action => $message ) {
				if ( wp_verify_nonce( $nonce, 'learn-press-save-profile-' . $_action ) ) {
					$action = $_action;
					break;
				}
			}

			// If none of actions found.
			if ( ! $action ) {
				return false;
			}
			$return = false;
			switch ( $action ) {
				case 'basic-information':
					$return = learn_press_update_user_profile_basic_information( true );
					break;
				case 'avatar':
					$return = learn_press_update_user_profile_avatar( true );
					break;
				case 'password':
					$return = learn_press_update_user_profile_change_password( true );
					break;
			}
			if ( is_wp_error( $return ) ) {
				learn_press_add_message( $return->get_error_message() );
			} else {
				if ( $return ) {
					learn_press_add_message( $message );
				}
			}

			if ( ! empty( $_REQUEST['redirect'] ) ) {
				$redirect = $_REQUEST['redirect'];
			} else {
				$redirect = learn_press_get_current_url();
			}

			$redirect = apply_filters( 'learn-press/profile-updated-redirect', $redirect, $action );

			if ( $redirect ) {
				wp_redirect( $redirect );
				exit;
			}

			return true;
		}

		/**
		 * Get all orders of profile's user.
		 *
		 * @param bool $group_by_order
		 *
		 * @since 3.x.x
		 *
		 * @return array
		 */
		public function get_user_orders( $group_by_order = false ) {
			return $this->_curd->get_orders( $this->get_user_data( 'id' ), $group_by_order );
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