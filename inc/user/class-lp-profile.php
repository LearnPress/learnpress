<?php
defined( 'ABSPATH' ) || exit;

require_once 'class-lp-profile-tabs.php';

if ( ! class_exists( 'LP_Profile' ) ) {
	/**
	 * Class LP_Profile
	 *
	 * Main class to control the profile of a user
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
		protected $_privacy = array();

		/**
		 * @var array
		 */
		protected $_default_actions = array();

		/**
		 * @var LP_User_CURD
		 */
		protected $_curd = null;

		/**
		 * @var null
		 */
		protected $_tabs = null;

		/**
		 * @var array
		 */
		protected $_default_settings = array();

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
					'basic-information' => esc_html__( 'Account information updated successfully.', 'learnpress' ),
					'avatar'            => esc_html__( 'Account avatar updated successfully.', 'learnpress' ),
					'password'          => esc_html__( 'Password updated successfully.', 'learnpress' ),
					'privacy'           => esc_html__( 'Account privacy updated successfully.', 'learnpress' ),
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
					LP_Request::register( 'save-profile-' . $action, array( $this, 'save' ) );
				}
			}

		}

		/**
		 * Prevent access view owned course in non admin, instructor profile page.
		 * @deprecated 4.1.7.3
		 */
		public function init() {
			_deprecated_function( __FUNCTION__, '4.1.7.3' );
			/*$profile = self::instance();
			$user    = $profile->get_user();
			$role    = $user->get_role();

			if ( ! in_array( $role, array( 'admin', 'instructor' ) ) ) {
				unset( $this->_default_settings['courses']['sections']['owned'] );

				$data_tabs   = apply_filters( 'learn-press/profile-tabs', $this->_default_settings );
				$this->_tabs = new LP_Profile_Tabs( $data_tabs, self::instance() );
			}*/
		}

		public function is_guest() {
			return ! $this->_user || $this->_user && $this->_user->is_guest();
		}

		public function output( $tab, $args, $user ) {
			$location = learn_press_locate_template( 'profile/tabs/' . $tab . '.php' );

			if ( $location && file_exists( $location ) ) {
				include $location;
			}
		}

		public function output_section( $tab_key, $tab_data, $user ) {
			learn_press_get_template( 'profile/tabs/sections.php', compact( 'tab_key', 'tab_data', 'user' ) );
		}

		public function output_section_content( $section, $args, $user ) {
			global $wp;

			$current = $this->get_current_section();

			if ( $current === $section ) {
				$location = learn_press_locate_template( 'profile/tabs/' . $this->get_current_tab() . '/' . $section . '.php' );
				if ( $location && file_exists( $location ) ) {
					include $location;
				}
			}
		}

		/**
		 * Get role of current user.
		 *
		 * @return string
		 */
		protected function get_role() {
			$user = $this->get_user();

			if ( $user ) {
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
				} elseif ( is_string( $this->_user ) ) {
					$user = get_user_by( 'login', $this->_user );

					if ( $user ) {
						$this->_user = learn_press_get_user( $user->ID );
					}
				}

				if ( ! $this->_user && ! is_user_logged_in() ) {
					$this->_user = learn_press_get_current_user();
				}

				$this->_privacy = apply_filters(
					'learn-press/check-privacy-setting',
					array(
						'view-tab-dashboard' => self::get_option_publish_profile() == 'yes',
						'view-tab-courses'   => $this->get_privacy( 'courses' ) == 'yes',
						'view-tab-quizzes'   => $this->get_privacy( 'quizzes' ) == 'yes',
					),
					$this
				);
			}

			return $this->_user;
		}

		public function is_current_user() {
			$user = $this->get_user();

			return $user ? $user->is( 'current' ) : false;
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

		public function get_login_url( $redirect = false ) {
			return learn_press_get_login_url( $redirect !== false ? $redirect : LP_Helper::getUrlCurrent() );
		}

		/**
		 * Get default tabs for profile.
		 *
		 * @return LP_Profile_Tabs
		 */
		public function get_tabs() {
			//if ( $this->_tabs === null ) {
				$settings        = LP_Settings::instance();
				$course_sections = array();

				$this->_default_settings = array(
					'courses'       => array(
						'title'    => esc_html__( 'Courses', 'learnpress' ),
						'slug'     => $settings->get( 'profile_endpoints.courses', 'courses' ),
						'callback' => array( LP_Template_Profile::class, 'tab_courses' ),
						'priority' => 1,
						'icon'     => '<i class="fas fa-book-open"></i>',
					),
					'quizzes'       => array(
						'title'    => esc_html__( 'Quizzes', 'learnpress' ),
						'slug'     => $settings->get( 'profile_endpoints.quizzes', 'quizzes' ),
						'callback' => array( $this, 'tab_quizzes' ),
						'priority' => 20,
						'icon'     => '<i class="fas fa-puzzle-piece"></i>',
					),
					'orders'        => array(
						'title'    => esc_html__( 'Orders', 'learnpress' ),
						'slug'     => $settings->get( 'profile_endpoints.orders', 'orders' ),
						'callback' => array( $this, 'tab_orders' ),
						'priority' => 25,
						'icon'     => '<i class="fas fa-shopping-cart"></i>',
					),
					'order-details' => array(
						'title'    => esc_html__( 'Order details', 'learnpress' ),
						'slug'     => $settings->get( 'profile_endpoints.order-details', 'order-details' ),
						'hidden'   => true,
						'callback' => array( $this, 'tab_order_details' ),
						'priority' => 30,
					),
					'settings'      => array(
						'title'    => esc_html__( 'Settings', 'learnpress' ),
						'slug'     => $settings->get( 'profile_endpoints.settings', 'settings' ),
						'callback' => array( $this, 'tab_settings' ),
						'sections' => array(
							'basic-information' => array(
								'title'    => esc_html__( 'General', 'learnpress' ),
								'slug'     => $settings->get( 'profile_endpoints.settings-basic-information', 'basic-information' ),
								'callback' => array( $this, 'tab_order_details' ),
								'priority' => 10,
								'icon'     => '<i class="fas fa-home"></i>',
							),
							'avatar'            => array(
								'title'    => esc_html__( 'Avatar', 'learnpress' ),
								'callback' => array( $this, 'tab_order_details' ),
								'slug'     => $settings->get( 'profile_endpoints.settings-avatar', 'avatar' ),
								'priority' => 20,
								'icon'     => '<i class="fas fa-user-circle"></i>',
							),
							'change-password'   => array(
								'title'    => esc_html__( 'Password', 'learnpress' ),
								'slug'     => $settings->get( 'profile_endpoints.settings-change-password', 'change-password' ),
								'callback' => array( $this, 'tab_order_details' ),
								'priority' => 30,
								'icon'     => '<i class="fas fa-key"></i>',
							),
						),
						'priority' => 90,
						'icon'     => '<i class="fas fa-cog"></i>',
					),
					'logout'        => array(
						'title'    => esc_html__( 'Logout', 'learnpress' ),
						'slug'     => learn_press_profile_logout_slug(),
						'icon'     => '<i class="fas fa-sign-out-alt"></i>',
						'priority' => 100,
					),
				);

				if ( 'yes' === self::get_option_publish_profile() ) {
					$this->_default_settings['settings']['sections']['privacy'] = array(
						'title'    => esc_html__( 'Privacy', 'learnpress' ),
						'slug'     => $settings->get( 'profile_endpoints.settings-privacy', 'privacy' ),
						'priority' => 40,
						'callback' => array( $this, 'tab_order_details' ),
						'icon'     => '<i class="fas fa-user-secret"></i>',
					);
				}
				//}

				$tabs        = $this->_default_settings;
				$tabs        = apply_filters( 'learn-press/profile-tabs', $tabs );
				$this->_tabs = new LP_Profile_Tabs( $tabs, $this );

				return $this->_tabs;
		}

		public function get_slug( $data, $default = '' ) {
			return $this->get_tabs()->get_slug( $data, $default );
		}

		/**
		 * Enable custom avatar?
		 *
		 * @return bool
		 * @deprecated 4.2.2.2
		 */
		/*public function is_enable_avatar() {
			$profile_avatar = get_option( 'learn_press_profile_avatar' );

			if ( ! $profile_avatar ) {
				update_option( 'learn_press_profile_avatar', 'yes' );
			}

			$setting_avatar = LP_Settings::instance()->get( 'profile_endpoints.settings-avatar' );

			if ( ! $setting_avatar ) {
				$profile_endpoints['settings-basic-information'] = 'basic-information';
				$profile_endpoints['settings-avatar']            = 'avatar';
				$profile_endpoints['settings-change-password']   = 'change-password';

				update_option( 'learn_press_profile_endpoints', $profile_endpoints, 'yes' );
				add_rewrite_rule( '(.?.+?)/avatar(/(.*))?/?$', 'index.php?pagename=$matches[1]&section=avatar', 'top' );
			}

			return LP_Settings::instance()->get( 'profile_avatar' ) === 'yes';
		}*/

		/**
		 * Get current tab slug in query string.
		 *
		 * @param string $default Optional.
		 * @param bool   $key Optional. True if return the key instead of value.
		 *
		 * @return string
		 */
		public function get_current_tab( $default = '', $key = true ) {
			return $this->get_tabs()->get_current_tab( $default, $key );
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
			return $this->get_tabs()->get_current_section( $default, $key, $tab );
		}

		/**
		 * Get tab data at a position.
		 *
		 * @param int $position Optional. Indexed number or slug.
		 *
		 * @return mixed
		 */
		public function get_tab_at( $position = 0 ) {
			return $this->get_tabs()->get_tab_at( $position );
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

			$url = $this->get_tabs()->get_tab_link( $tab, $with_section, $user->get_username() );

			/**
			 * @deprecated
			 */
			$url = apply_filters( 'learn_press_user_profile_link', $url, $user->get_id(), $tab );

			return apply_filters( 'learn-press/user-profile-url', $url, $user->get_id(), $tab );
		}

		/**
		 * Get current link of profile
		 *
		 * @param string $args - Optional. Add more query args to url.
		 * @param bool   $with_permalink - Optional. TRUE to build url as friendly url.
		 *
		 * @return mixed|string
		 */
		public function get_current_url( $args = '', $with_permalink = false ) {
			return $this->get_tabs()->get_current_url( $args, $with_permalink );
		}

		/**
		 * Check if the $key is current tab.
		 *
		 * @param string $key
		 *
		 * @return bool
		 */
		public function is_current_tab( $key ) {
			global $wp_query;

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
			return is_array( $tab_or_section ) && array_key_exists( 'hidden', $tab_or_section ) && $tab_or_section['hidden'];
		}

		public function is_public() {
			return $this->current_user_can( 'view-tab-dashboard' ) || is_super_admin();
		}

		public function get_default_public_tabs() {
			return apply_filters( 'learn-press/profile/privacy-tabs', array( 'overview' ) );
		}

		public function get_public_tabs() {
			$privacy     = get_user_meta( $this->get_user_data( 'id' ), '_lp_profile_privacy', true );
			$public_tabs = $this->get_default_public_tabs();

			if ( $privacy ) {
				foreach ( $privacy as $k => $is_yes ) {
					if ( $is_yes === 'yes' || is_super_admin() ) {
						$public_tabs[] = $k;
					}
				}
			}

			return $public_tabs;
		}

		/**
		 * Check if user can with a capability.
		 */
		public function current_user_can( $capability ) {
			$tab         = substr( $capability, strlen( 'view-tab-' ) );
			$public_tabs = $this->get_default_public_tabs();

			if ( current_user_can( ADMIN_ROLE ) ) {
				$can = true;
			} elseif ( in_array( $tab, $public_tabs ) || $this->is_current_user() ) {
				$can = true;
			} else {
				if ( empty( $this->_privacy['view-tab-dashboard'] ) || ( false === $this->_privacy['view-tab-dashboard'] ) ) {
					$can = false;
				} else {
					$can = ! empty( $this->_privacy[ $capability ] ) && ( $this->_privacy[ $capability ] == true );
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
			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				return new WP_Error( 2, 'The user is invalid' );
			}

			$message        = [
				'status'  => 'error',
				'content' => '',
			];
			$message_action = '';

			foreach ( $this->_default_actions as $_action => $message_action ) {
				if ( wp_verify_nonce( $nonce, 'learn-press-save-profile-' . $_action ) ) {
					$action = $_action;
					break;
				}
				$action = '';
			}

			if ( ! isset( $action ) ) {
				return false;
			}

			$return = false;
			switch ( $action ) {
				case 'basic-information':
					$return = learn_press_update_user_profile_basic_information( true );
					break;
				case 'password':
					$return = learn_press_update_user_profile_change_password( true );
					break;
				case 'privacy':
					$privacy = LP_Request::get_array( 'privacy' );

					if ( ! $privacy ) {
						update_user_meta( get_current_user_id(), '_lp_profile_privacy', array() );
					} else {
						update_user_meta( get_current_user_id(), '_lp_profile_privacy', $privacy );
					}
			}

			if ( is_wp_error( $return ) ) {
				$message['content'] = $return->get_error_message();
			} else {
				$message['status']  = 'success';
				$message['content'] = $message_action;
			}

			learn_press_set_message( $message );

			if ( ! empty( $_REQUEST['redirect'] ) ) {
				$redirect = esc_url_raw( $_REQUEST['redirect'] );
			} else {
				$redirect = LP_Helper::getUrlCurrent();
			}

			$redirect = apply_filters( 'learn-press/profile-updated-redirect', $redirect, $action );

			if ( $redirect ) {
				wp_redirect( $redirect );
				exit;
			}

			return true;
		}

		/**
		 * Get settings for profile privacy tab.
		 *
		 * @return array
		 * @since 4.0.0
		 */
		public function get_privacy_settings() {
			$privacy = array(
				array(
					'name'        => esc_html__( 'Courses', 'learnpress' ),
					'id'          => 'courses',
					'default'     => 'yes',
					'type'        => 'yes-no',
					'description' => esc_html__( 'Public your profile courses.', 'learnpress' ),
				),
				array(
					'name'        => esc_html__( 'Quizzes', 'learnpress' ),
					'id'          => 'quizzes',
					'default'     => 'yes',
					'type'        => 'yes-no',
					'description' => esc_html__( 'Public your profile quizzes.', 'learnpress' ),
				),
			);

			return apply_filters( 'learn-press/profile-privacy-settings', $privacy );
		}

		/**
		 * Get privacy profile settings.
		 *
		 * @param string $tab
		 *
		 * @return array|mixed
		 * @since 3.0.0
		 */
		public function get_privacy( $tab = '' ) {
			$privacy = get_user_meta( $this->get_user_data( 'id' ), '_lp_profile_privacy', true );

			return isset( $privacy[ $tab ] ) ? $privacy[ $tab ] : '';
		}

		/**
		 * Get all orders of profile's user.
		 *
		 * @param mixed $args
		 *
		 * @return array
		 * @since 3.0.0
		 */
		public function get_user_orders( $args = '' ) {
			$args = wp_parse_args(
				$args,
				array(
					'group_by_order' => true,
					'status'         => '',
				)
			);

			return $this->_curd->get_orders( $this->get_user_data( 'id' ), $args );
		}

		/**
		 * Query order of user is viewing profile.
		 *
		 * @param string $args
		 *
		 * @return LP_Query_List_Table
		 */
		public function query_orders( $args = '' ) {
			global $wp_query;

			$query = array(
				'items'      => array(),
				'total'      => 0,
				'num_pages'  => 0,
				'pagination' => '',
			);

			$query_args = array();

			if ( is_array( $args ) ) {
				foreach ( array( 'status', 'group_by_order' ) as $k ) {
					if ( isset( $args[ $k ] ) ) {
						$query_args[ $k ] = $args[ $k ];
					}
				}
			}

			if ( empty( $query_args['status'] ) ) {
				$query_args['status'] = 'completed processing cancelled pending';
			}

			$order_ids = $this->get_user_orders( $query_args );

			if ( $order_ids ) {
				$default_args = array(
					'paged' => 1,
					'limit' => 10,
				);

				if ( $this->get_current_tab() === 'orders' && isset( $wp_query->query_vars['view_id'] ) ) {
					$default_args['paged'] = $wp_query->query_vars['view_id'];
				}

				$args   = wp_parse_args( $args, $default_args );
				$offset = isset( $args['limit'] ) && $args['limit'] > 0 && $args['paged'] ? ( $args['paged'] - 1 ) * $args['limit'] : 0;

				$query_order = new WP_Query(
					array(
						'post_type'      => LP_ORDER_CPT,
						'posts_per_page' => $args['limit'],
						'offset'         => $offset,
						'post_status'    => 'any',
						'post__in'       => array_keys( $order_ids ),
						'orderby'        => 'post__in',
						'fields'         => 'ids',
					)
				);

				if ( $query_order->have_posts() ) {
					$orders = ( isset( $args['fields'] ) && 'ids' === $args['fields'] ) ? $query_order->posts : array_filter( array_map( 'learn_press_get_order', $query_order->posts ) );

					$query['orders']     = $orders;
					$query['total']      = $query_order->found_posts;
					$query['num_pages']  = $query_order->max_num_pages;
					$query['pagination'] = learn_press_paging_nav(
						array(
							'num_pages' => $query['num_pages'],
							'base'      => learn_press_user_profile_link( $this->get_user_data( 'id' ), LP_Settings::instance()->get( 'profile_endpoints.profile-orders' ) ),
							'format'    => $GLOBALS['wp_rewrite']->using_permalinks() ? user_trailingslashit( '%#%', '' ) : '?paged=%#%',
							'echo'      => false,
							'paged'     => $args['paged'],
						)
					);

					$query = new LP_Query_List_Table(
						array(
							'total' => $query_order->found_posts,
							'paged' => $args['paged'],
							'limit' => $args['limit'],
							'pages' => $query['num_pages'],
							'items' => $orders,
						)
					);
				}
			} else {
				$query = new LP_Query_List_Table( $query );
			}

			return $query;
		}

		/**
		 * Query user's courses
		 *
		 * @param string $type - Optional. [own, purchased, enrolled, etc]
		 * @param array  $args - Optional.
		 *
		 * @return LP_Query_List_Table
		 */
		public function query_courses( string $type = 'own', array $args = array() ): LP_Query_List_Table {
			$lp_user_items_db = LP_User_Items_DB::getInstance();
			$courses          = array();

			switch ( $type ) {
				case 'purchased':
					// $query = $this->_curd->query_purchased_courses( $this->get_user_data( 'id' ), $args );
					$filter              = new LP_User_Items_Filter();
					$filter->only_fields = array( 'DISTINCT (item_id) AS item_id' );
					$filter->field_count = 'ui.item_id';
					$filter->user_id     = $this->get_user_data( 'id' );
					$status              = $args['status'] ?? '';
					if ( $status != LP_COURSE_FINISHED ) {
						$filter->graduation = $status;
					} else {
						$filter->status = $status;
					}
					$filter->page   = $args['paged'] ?? 1;
					$filter->limit  = $args['limit'] ?? $filter->limit;
					$total_rows     = 0;
					$filter         = apply_filters( 'lp/api/profile/courses/purchased/filter', $filter, $args );
					$result_courses = LP_User_Item_Course::get_user_courses( $filter, $total_rows );

					$course_ids = LP_Database::get_values_by_key( $result_courses, 'item_id' );

					$courses = array(
						'total' => $total_rows,
						'paged' => $filter->page,
						'limit' => $filter->limit,
						'items' => $course_ids,
					);
					break;
				case 'own':
					//$query = $this->_curd->query_own_courses( $this->get_user_data( 'id' ), $args );
					$filter              = new LP_Course_Filter();
					$filter->fields      = array( 'ID' );
					$filter->post_author = $this->get_user_data( 'id' );
					$filter->post_status = isset( $args['status'] ) && ! empty( $args['status'] ) ? $args['status'] : array( 'publish', 'pending' );
					$filter->page        = $args['paged'] ?? 1;
					$filter->limit       = $args['limit'] ?? $filter->limit;
					$total_rows          = 0;
					$filter              = apply_filters( 'lp/api/profile/courses/own/filter', $filter, $args );
					$result_courses      = LP_Course::get_courses( $filter, $total_rows );

					$course_ids = LP_Database::get_values_by_key( $result_courses );

					$courses = array(
						'total' => $total_rows,
						'paged' => $filter->page,
						'limit' => $filter->limit,
						'items' => $course_ids,
					);
					break;
			}

			return new LP_Query_List_Table( $courses );
		}

		/**
		 * @param mixed $args
		 *
		 * @return array|LP_Query_List_Table
		 * @deprecated 4.2.2.4
		 */
		public function query_quizzes( $args = '' ) {
			_deprecated_function( __METHOD__, '4.2.2.4' );
			return $this->_curd->query_quizzes( $this->get_user_data( 'id' ), $args );
		}

		/**
		 * Get the order is viewing details.
		 */
		public function get_view_order() {
			global $wp_query;

			$order = false;
			if ( isset( $wp_query->query_vars['view_id'] ) ) {
				$order = learn_press_get_order( $wp_query->query_vars['view_id'] );
			}

			return $order;
		}

		/**
		 * Get filters for own courses tab.
		 *
		 * @param string $current_filter
		 *
		 * @return array
		 * @deprecated 4.2.0
		 */
		public function get_own_courses_filters( $current_filter = '' ) {
			_deprecated_function( __METHOD__, '4.2.0' );
			$url = $this->get_current_url();

			$defaults = array(
				'all'     => sprintf( '<a href="%s">%s</a>', esc_url_raw( $url ), esc_html__( 'All', 'learnpress' ) ),
				'publish' => sprintf( '<a href="%s">%s</a>', esc_url_raw( add_query_arg( 'filter-status', 'publish', $url ) ), esc_html__( 'Publish', 'learnpress' ) ),
				'pending' => sprintf( '<a href="%s">%s</a>', esc_url_raw( add_query_arg( 'filter-status', 'pending', $url ) ), esc_html__( 'Pending', 'learnpress' ) ),
			);

			if ( ! $current_filter ) {
				$keys           = array_keys( $defaults );
				$current_filter = reset( $keys );
			}

			foreach ( $defaults as $k => $v ) {
				if ( $k === $current_filter ) {
					$defaults[ $k ] = sprintf( '<span>%s</span>', strip_tags( $v ) );
				}
			}

			return apply_filters(
				'learn-press/profile/own-courses-filters',
				$defaults
			);
		}

		/**
		 * Get filters for purchased courses tab.
		 *
		 * @param string $current_filter
		 *
		 * @return array
		 * @deprecated 4.2.0
		 */
		public function get_purchased_courses_filters( $current_filter = '' ) {
			_deprecated_function( __METHOD__, '4.2.0' );
			$url      = $this->get_current_url( false );
			$defaults = array(
				'all'          => sprintf( '<a href="%s">%s</a>', esc_url_raw( $url ), __( 'All', 'learnpress' ) ),
				'finished'     => sprintf( '<a href="%s">%s</a>', esc_url_raw( add_query_arg( 'filter-status', 'finished', $url ) ), __( 'Finished', 'learnpress' ) ),
				'passed'       => sprintf( '<a href="%s">%s</a>', esc_url_raw( add_query_arg( 'filter-status', 'passed', $url ) ), __( 'Passed', 'learnpress' ) ),
				'failed'       => sprintf( '<a href="%s">%s</a>', esc_url_raw( add_query_arg( 'filter-status', 'failed', $url ) ), __( 'Failed', 'learnpress' ) ),
				'not-enrolled' => sprintf( '<a href="%s">%s</a>', esc_url_raw( add_query_arg( 'filter-status', 'not-enrolled', $url ) ), __( 'Not enrolled', 'learnpress' ) ),
			);

			if ( ! $current_filter ) {
				$keys           = array_keys( $defaults );
				$current_filter = reset( $keys );
			}

			foreach ( $defaults as $k => $v ) {
				if ( $k === $current_filter ) {
					$defaults[ $k ] = sprintf( '<span>%s</span>', strip_tags( $v ) );
				}
			}

			return apply_filters(
				'learn-press/profile/purchased-courses-filters',
				$defaults
			);
		}

		/**
		 * Get filters for purchased courses tab.
		 *
		 * @param string $current_filter
		 *
		 * @return array
		 */
		public function get_quizzes_filters( $current_filter = '' ) {
			$url      = $this->get_tab_link( 'quizzes' );
			$defaults = array(
				'all'       => sprintf( '<a href="%s">%s</a>', esc_url_raw( $url ), __( 'All', 'learnpress' ) ),
				'completed' => sprintf( '<a href="%s">%s</a>', esc_url_raw( add_query_arg( 'filter-status', 'completed', $url ) ), __( 'Finished', 'learnpress' ) ),
				'passed'    => sprintf( '<a href="%s">%s</a>', esc_url_raw( add_query_arg( 'filter-graduation', 'passed', $url ) ), __( 'Passed', 'learnpress' ) ),
				'failed'    => sprintf( '<a href="%s">%s</a>', esc_url_raw( add_query_arg( 'filter-graduation', 'failed', $url ) ), __( 'Failed', 'learnpress' ) ),
			);

			if ( ! $current_filter ) {
				$keys           = array_keys( $defaults );
				$current_filter = reset( $keys );
			}

			foreach ( $defaults as $k => $v ) {
				if ( $k === $current_filter ) {
					$defaults[ $k ] = sprintf( '<span>%s</span>', strip_tags( $v ) );
				}
			}

			return apply_filters(
				'learn-press/profile/quizzes-filters',
				$defaults
			);
		}

		/**
		 * @param bool $redirect
		 *
		 * @return string
		 * @deprecated 4.1.7.3
		 */
		public function logout_url( $redirect = false ) {
			_deprecated_function( __FUNCTION__, '4.1.7.3' );
			/*if ( $this->enable_login() ) {
				$profile_url = learn_press_get_page_link( 'profile' );
				$url         = esc_url_raw(
					add_query_arg(
						array(
							'lp-logout' => 'true',
							'nonce'     => wp_create_nonce( 'lp-logout' ),
						),
						untrailingslashit( $profile_url )
					)
				);

				if ( $redirect !== false ) {
					$url = esc_url_raw( add_query_arg( 'redirect', urlencode( $redirect ), $url ) );
				}
			} else {
				$url = wp_logout_url( $redirect !== false ? $redirect : $this->get_current_url() );
			}

			return apply_filters( 'learn-press/logout-url', $url );*/
		}

		/**
		 * Echo class for main div.
		 *
		 * @param bool   $echo
		 * @param string $more
		 *
		 * @return string
		 */
		public function main_class( $echo = true, $more = '' ) {
			$classes = array( 'lp-user-profile' );

			if ( $this->is_current_user() ) {
				$classes[] = 'current-user';
			}

			if ( ! is_user_logged_in() ) {
				$classes[] = 'guest';
			}

			if ( has_action( 'learn-press/before-user-profile' ) ) {
				$classes[] = 'has-sidebar';
			}

			$classes = LP_Helper::merge_class( $classes, $more );

			$class = ' class="' . implode( ' ', apply_filters( 'learn-press/profile/class', $classes ) ) . '"';

			if ( $echo ) {
				echo wp_kses_post( $class );
			}

			return $class;
		}

		/**
		 * Return true if the tab is visible for current user.
		 *
		 * @param string $tab_key
		 * @param array  $tab_data
		 *
		 * @return bool
		 */
		public function tab_is_visible_for_user( $tab_key, $tab_data = null ) {
			return $this->is_current_tab( $tab_key ) && $this->current_user_can( "view-tab-{$tab_key}" );
		}

		/**
		 * Return true if the section is visible for current user.
		 *
		 * @param string $section_key
		 * @param array  $section_data
		 *
		 * @return bool
		 */
		public function section_is_visible_for_user( $section_key, $section_data = array() ) {
			return $this->current_user_can( "view-section-{$section_key}" ) && ! $this->is_hidden( $section_data );
		}

		/**
		 * TRUE if enable show login form in user profile if user is not logged in.
		 *
		 * @return bool
		 */
		public function enable_login() {
			return 'yes' === LP_Settings::instance()->get( 'enable_login_profile' );
		}

		/**
		 * TRUE if enable show register form in user profile if user is not logged in.
		 *
		 * @return bool
		 */
		public function enable_register() {
			return 'yes' === LP_Settings::instance()->get( 'enable_register_profile' );
		}

		/**
		 * Get queried user in profile link.
		 *
		 * @param string $return
		 *
		 * @return false|WP_User
		 * @since 3.0.0
		 */
		public static function get_queried_user( $return = '' ) {
			global $wp_query;

			if ( isset( $wp_query->query['user'] ) ) {
				$user = get_user_by( 'login', urldecode( $wp_query->query['user'] ) );
			} else {
				$user = get_user_by( 'id', get_current_user_id() );
			}

			return $return === 'id' && $user ? $user->ID : $user;
		}

		/**
		 * Return true if there is a name of user in profile link.
		 *
		 * @return bool
		 */
		public static function is_queried_user() {
			global $wp_query;

			return isset( $wp_query->query['user'] ) ? $wp_query->query['user'] : false;
		}

		public function get_upload_profile_src( $size = '' ) {
			$user                 = $this->get_user();
			$uploaded_profile_src = $user->get_data( 'uploaded_profile_src' );

			if ( empty( $uploaded_profile_src ) ) {
				$profile_picture = get_user_meta( $user->get_id(), '_lp_profile_picture', true );

				if ( $profile_picture ) {
					$upload    = learn_press_user_profile_picture_upload_dir();
					$file_path = $upload['basedir'] . DIRECTORY_SEPARATOR . $profile_picture;

					if ( file_exists( $file_path ) ) {
						$uploaded_profile_src = $upload['baseurl'] . '/' . $profile_picture;

						// if ( $user->get_data( 'profile_picture_changed' ) == 'yes' ) {
						// 	$uploaded_profile_src = add_query_arg(
						// 		'r',
						// 		md5( rand( 0, 10 ) / rand( 1, 1000000 ) ),
						// 		$user->get_data( 'uploaded_profile_src' )
						// 	);
						// 	delete_user_meta( $user->get_id(), '_lp_profile_picture_changed' );
						// }
					} else {
						$uploaded_profile_src = false;
					}

					$user->set_data( 'uploaded_profile_src', $uploaded_profile_src );
				}
			}

			return $uploaded_profile_src;
		}

		public function get_profile_picture( $type = '', $size = 96 ) {
			$user = $this->get_user();
			$args = learn_press_get_avatar_thumb_size();

			if ( $type == 'gravatar' ) {
				remove_filter( 'pre_get_avatar', 'learn_press_pre_get_avatar_callback', 1 );
			}

			$profile_picture_src = $this->get_upload_profile_src();

			if ( $profile_picture_src ) {
				$user->set_data( 'profile_picture_src', $profile_picture_src );
			}

			$avatar = get_avatar( $user->get_id(), $args['width'], '', esc_attr__( 'User Avatar', 'learnpress' ), $args );

			if ( $type == 'gravatar' ) {
				add_filter( 'pre_get_avatar', 'learn_press_pre_get_avatar_callback', 1, 5 );
			}

			return $avatar;
		}

		/**
		 * Get option enable "Publish profile"
		 *
		 * @return string
		 */
		public static function get_option_publish_profile(): string {
			return LP_Settings::get_option( 'publish_profile', 'no' );
		}

		/**
		 * Get statistic info of user
		 *
		 * @return array
		 * @since 4.1.6
		 * @version 1.0.0
		 */
		public function get_statistic_info(): array {
			$user      = $this->_user;
			$statistic = array(
				'enrolled_courses'  => 0,
				'active_courses'    => 0,
				'completed_courses' => 0,
				'total_courses'     => 0,
				'total_users'       => 0,
			);

			try {
				if ( ! $user ) {
					throw new Exception( 'The user is invalid' );
				}

				$user_id          = $user->get_id();
				$lp_user_items_db = LP_User_Items_DB::getInstance();
				$lp_course_db     = LP_Course_DB::getInstance();

				// Count status
				$filter          = new LP_User_Items_Filter();
				$filter->user_id = $user_id;
				$count_status    = $lp_user_items_db->count_status_by_items( $filter );

				$count_users_attend_courses_of_author = 0;
				$courses_of_author                    = 0;
				if ( $user->can_create_course() ) {
					// Get total users attend course of author
					$filter_count_users                   = $lp_user_items_db->count_user_attend_courses_of_author( $user_id );
					$count_users_attend_courses_of_author = $lp_user_items_db->get_user_courses( $filter_count_users );

					// Get total courses publish of author
					$filter_count_courses = $lp_course_db->count_courses_publish_of_author( $user_id );
					$courses_of_author    = $lp_course_db->get_courses( $filter_count_courses );
				}

				$statistic['enrolled_courses']  = intval( $count_status->{LP_COURSE_PURCHASED} ?? 0 ) + intval( $count_status->{LP_COURSE_ENROLLED} ?? 0 ) + intval( $count_status->{LP_COURSE_FINISHED} ?? 0 );
				$statistic['active_courses']    = $count_status->{LP_COURSE_GRADUATION_IN_PROGRESS} ?? 0;
				$statistic['completed_courses'] = $count_status->{LP_COURSE_FINISHED} ?? 0;
				$statistic['total_courses']     = $courses_of_author;
				$statistic['total_users']       = $count_users_attend_courses_of_author;
			} catch ( Throwable $e ) {

			}

			return apply_filters( 'lp/profile/statistic', $statistic, $user );
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
				$user_id = self::get_queried_user( 'id' );

				if ( ! $user_id ) {
					$user_id = get_current_user_id();
				}
			}

			if ( empty( self::$_instances[ $user_id ] ) ) {
				self::$_instances[ $user_id ] = new self( $user_id );
			}

			return self::$_instances[ $user_id ];
		}
	}
}
