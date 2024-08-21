<?php

use LearnPress\Helpers\Config;
use LearnPress\Models\Courses;
use LearnPress\TemplateHooks\Profile\ProfileOrdersTemplate;

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

		protected static $_instance = null;

		/**
		 * @var LP_User Current user viewing profile
		 */
		protected $user_current = false;

		/**
		 * @var LP_User User of Profile
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
		 * @deprecated 4.2.6.2
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
			$this->_curd        = new LP_User_CURD();
			$this->user_current = learn_press_get_current_user();
			$this->_user        = learn_press_get_user( $user );
			//$this->get_user();

			/*if ( ! $role ) {
				$this->_role = $this->get_role();
			}*/

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

				/*foreach ( $this->_default_actions as $action => $message ) {
					LP_Request::register( 'save-profile-' . $action, array( $this, 'save' ) );
				}*/

				foreach ( $this->_default_actions as $action => $message ) {
					if ( isset( $_REQUEST[ 'save-profile-' . $action ] ) ) {
						$this->save( $_REQUEST[ 'save-profile-' . $action ] );
					}
				}
			}
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
		 * @deprecated 4.2.6.2
		 */
		protected function get_role() {
			_deprecated_function( __METHOD__, '4.2.6.2' );

			return '';
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
		 * @return bool|LP_User
		 * @since 3.0.0
		 * @version 1.0.2
		 */
		public function get_user() {
			return $this->_user;
		}

		public function get_user_current() {
			return $this->user_current;
		}

		/**
		 * Check current user view self profile.
		 *
		 * @return bool
		 */
		public function is_current_user(): bool {
			return $this->get_user() instanceof LP_User && $this->get_user()->get_id() === $this->get_user_current()->get_id();
		}

		/**
		 * Wrap function for $user->get_data()
		 *
		 * @param string $field
		 *
		 * @return mixed
		 */
		public function get_user_data( $field ) {
			$user_id   = 0;
			$user_data = [];
			if ( $this->_user instanceof LP_User ) {
				$user_id   = $this->_user->get_id();
				$user_data = $this->_user->get_data( $field );
			}

			return 'id' === strtolower( $field ) ? $user_id : $user_data;
		}

		/**
		 * @deprecated 4.2.6.2
		 */
		public function tab_dashboard() {
			_deprecated_function( __METHOD__, '4.2.6.2' );

			return;
			learn_press_get_template( 'profile/dashboard.php', array( 'user' => $this->_user ) );
		}

		public function get_login_url( $redirect = false ) {
			return learn_press_get_login_url( $redirect !== false ? $redirect : LP_Helper::getUrlCurrent() );
		}

		/**
		 * Get tabs.
		 *
		 * @return array
		 * @since 4.2.6.4
		 * @version 1.0.0
		 */
		public static function get_tabs_arr(): array {
			return Config::instance()->get( 'profile-tabs' );
		}

		/**
		 * Get default tabs for profile.
		 * Hide tabs if user is not Administrator/Instructor.
		 *
		 * @return LP_Profile_Tabs
		 */
		public function get_tabs() {
			$user_of_profile = $this->get_user();
			$tabs            = self::get_tabs_arr();

			/*
			 * Check if user not Admin/Instructor, will be hide tab Courses.
			 */
			if ( $user_of_profile instanceof LP_User
			     && ! in_array( $user_of_profile->get_data( 'role' ), [ ADMIN_ROLE, LP_TEACHER_ROLE ] ) ) {
				unset( $tabs['courses'] );
			}

			$tabs        = apply_filters( 'learn-press/get-profile-tabs', $tabs, $user_of_profile, $this->user_current );
			$this->_tabs = new LP_Profile_Tabs( $tabs, $this );

			return $this->_tabs;
		}

		public function get_slug( $data, $default = '' ) {
			return $this->get_tabs()->get_slug( $data, $default );
		}

		/**
		 * Get current tab slug in query string.
		 *
		 * @param string $default Optional.
		 * @param bool $key Optional. True if return the key instead of value.
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
		 * @param bool $key
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
		 * @param bool $with_permalink - Optional. TRUE to build url as friendly url.
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

		/**
		 * @deprecated 4.2.6.2
		 */
		public function is_public() {
			_deprecated_function( __METHOD__, '4.2.6.2' );

			return false;

			return $this->current_user_can( 'view-tab-dashboard' ) || is_super_admin();
		}

		public function get_default_public_tabs() {
			return apply_filters( 'learn-press/profile/privacy-tabs', [] );
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
		 *
		 * @since 3.0.0
		 * @version 1.0.2
		 */
		public function current_user_can( $capability ) {
			$privacy = array(
				'view-tab-courses'    => self::get_option_publish_profile() === 'yes',
				'view-tab-my-courses' => $this->get_privacy( 'courses' ) == 'yes',
				'view-tab-quizzes'    => $this->get_privacy( 'quizzes' ) == 'yes',
			);

			if ( current_user_can( ADMIN_ROLE ) ) {
				$can = true;
			} elseif ( $this->is_current_user() ) {
				$can = true;
			} else {
				$can = ! empty( $privacy[ $capability ] ) && $privacy[ $capability ] === true;
			}

			return apply_filters( 'learn-press/profile-current-user-can', $can, $capability, $this );
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
					'description' => esc_html__( 'Public your profile courses attended.', 'learnpress' ),
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
		 * @version 1.0.1
		 */
		public function get_privacy( string $tab = '' ) {
			$user_id = $this->get_user() ? $this->get_user()->get_id() : 0;
			$privacy = get_user_meta( $user_id, '_lp_profile_privacy', true );

			return $privacy[ $tab ] ?? '';
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
							'base'      => learn_press_user_profile_link( $this->get_user_data( 'id' ), LP_Settings::instance()->get( 'profile_endpoints.orders' ) ),
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
		 * @param array $args - Optional.
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
					$filter = new LP_Course_Filter();
					Courses::handle_params_for_query_courses( $filter, $args );
					$filter->fields      = array( 'ID' );
					$filter->post_author = $this->get_user_data( 'id' );
					$filter->post_status = ! empty( $args['status'] ) ? $args['status'] : array(
						'publish',
						'pending',
					);
					$filter->page        = $args['paged'] ?? 1;
					$filter->limit       = $args['limit'] ?? $filter->limit;
					$total_rows          = 0;
					$filter              = apply_filters( 'lp/api/profile/courses/own/filter', $filter, $args );
					$result_courses      = Courses::get_courses( $filter, $total_rows );

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
		 * Echo class for main div.
		 *
		 * @param bool $echo
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
		 * @param array $tab_data
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
		 * @param array $section_data
		 *
		 * @return bool
		 */
		public function section_is_visible_for_user( $section_key, $section_data = array() ) {
			return $this->current_user_can( "view-section-{$section_key}" ) && ! $this->is_hidden( $section_data );
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

		public function get_upload_profile_src( $size = '' ) {
			$user = $this->get_user();
			if ( ! $user ) {
				return '';
			}

			$uploaded_profile_src = $user->get_data( 'uploaded_profile_src' );

			if ( empty( $uploaded_profile_src ) ) {
				$profile_picture = get_user_meta( $user->get_id(), '_lp_profile_picture', true );

				if ( $profile_picture ) {
					// Check if hase slug / at the beginning of the path, if not, add it.
					$slash           = substr( $profile_picture, 0, 1 ) === '/' ? '' : '/';
					$profile_picture = $slash . $profile_picture;
					// End check.
					$upload    = learn_press_user_profile_picture_upload_dir();
					$file_path = $upload['basedir'] . $profile_picture;

					if ( file_exists( $file_path ) ) {
						$uploaded_profile_src = $upload['baseurl'] . $profile_picture;
					} else {
						$uploaded_profile_src = false;
					}

					$user->set_data( 'uploaded_profile_src', $uploaded_profile_src );
				}
			}

			return apply_filters( 'learn-press/profile/get-upload-profile-src', $uploaded_profile_src, $user->get_id() );
		}

		/**
		 * Get profile image of user.
		 *
		 * @param $type
		 * @param $size
		 *
		 * @return string
		 */
		public function get_profile_picture( $type = '', $size = 96 ): string {
			$avatar = '';

			try {
				$user = $this->get_user();
				$args = [
					'width'  => $size,
					'height' => $size,
				];
				if ( 96 === $size ) {
					$args = learn_press_get_avatar_thumb_size();
				}

				$avatar_url = $this->get_upload_profile_src();
				if ( ! empty( $avatar_url ) ) {
					$user->set_data( 'profile_picture_src', $avatar_url );
				} else {
					$avatar_url = get_avatar_url( $user->get_id(), $args );
					if ( empty( $avatar_url ) ) {
						$avatar_url = LP_PLUGIN_URL . 'assets/images/avatar-default.png';
					}
				}

				$avatar = apply_filters(
					'learn-press/user-profile/avatar',
					sprintf(
						'<img alt="%s" class="avatar" src="%s" height="%d" width="%d">',
						esc_attr__( 'User Avatar', 'learnpress' ),
						$avatar_url,
						$args['width'] ?? 96,
						$args['height'] ?? 96
					),
					$avatar_url,
					$args
				);
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
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
					$filter_count_courses = $lp_course_db->count_courses_of_author( $user_id, [ 'publish' ] );
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
		 * Get register fields custom
		 *
		 * @return mixed|null
		 * @since 4.2.6.4
		 */
		public static function get_register_fields_custom() {
			return apply_filters(
				'learn-press/profile/register-fields-custom',
				LP_Settings::get_option( 'register_profile_fields', [] )
			);
		}

		/**
		 * Get an instance of LP_Profile for a user id
		 *
		 * @param $user_id
		 *
		 * @return LP_Profile mixed
		 * @since 3.0.0
		 * @version 1.0.4
		 */
		public static function instance( $user_id = 0 ) {
			$is_page_profile = LP_Page_Controller::page_is( 'profile' );

			if ( $is_page_profile ) {
				if ( empty( self::$_instance ) ) {
					$user_name = get_query_var( 'user' );
					if ( ! empty( $user_name ) ) {
						$user    = get_user_by( 'login', urldecode( $user_name ) );
						$user_id = $user ? $user->ID : 0;
					} else {
						$user_id = get_current_user_id();
					}

					self::$_instance = new self( $user_id );
				}

				return self::$_instance;
			} else {
				if ( empty( self::$_instances[ $user_id ] ) ) {
					self::$_instances[ $user_id ] = new self( $user_id );
				}

				return self::$_instances[ $user_id ];
			}
		}
	}
}
