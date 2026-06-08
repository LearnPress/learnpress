<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Classes
 * @version 1.0.2
 */

use LearnPress\Background\LPBackgroundAjax;
use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\CourseBuilder\Course\BuilderCourseTemplate;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Admin' ) ) {
	/**
	 * Class LP_Admin
	 */
	class LP_Admin {
		protected static $instance;

		protected $pages = [];

		/**
		 *  Constructor
		 */
		protected function __construct() {
			$this->pages = [
				'courses'           => __( 'Courses', 'learnpress' ),
				'instructors'       => __( 'Instructors', 'learnpress' ),
				'single_instructor' => __( 'Single Instructors', 'learnpress' ),
				'profile'           => __( 'Profile', 'learnpress' ),
				'checkout'          => __( 'Checkout', 'learnpress' ),
				'become_a_teacher'  => __( 'Become an Instructor', 'learnpress' ),
			];
			$this->includes();
			add_action( 'delete_user', array( $this, 'delete_user_data' ) );
			//add_action( 'delete_user_form', array( $this, 'delete_user_form' ) );
			add_action( 'all_admin_notices', array( $this, 'admin_notices' ), - 1 );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_modal' ) );
			add_filter( 'admin_body_class', array( $this, 'body_class' ) );
			//add_filter( 'manage_users_custom_column', array( $this, 'users_custom_column' ), 10, 3 );
			//add_filter( 'manage_pages_columns', array( $this, 'page_columns_head' ) );
			//add_filter( 'manage_pages_custom_column', array( $this, 'page_columns_content' ), 10, 2 );
			add_filter( 'views_edit-page', array( $this, 'views_pages' ), 10 );
			add_filter( 'views_users', array( $this, 'views_users' ), 10, 1 );
			add_filter( 'user_row_actions', array( $this, 'user_row_actions' ), 10, 2 );
			add_filter( 'get_pages', array( $this, 'add_empty_page' ), 1000, 2 );
			add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
			add_filter( 'views_plugins', array( $this, 'views_plugins' ) );

			LP_Request::register( 'lp-action', array( $this, 'filter_users' ) );

			add_filter( 'learn-press/modal-search-items-args', array( $this, 'filter_modal_search' ) );

			/*add_filter(
				'learn-press/dismissed-notice-response',
				array(
					$this,
					'on_dismissed_notice_response',
				),
				10,
				2
			);*/

			// get list items course of user | tungnx
			add_action( 'pre_get_posts', array( $this, 'get_course_items_of_user_backend' ), 10 );
			add_action( 'pre_get_posts', array( $this, 'get_pages_of_lp' ), 10 );

			// Set link item course when edit on Backend | tungnx
			add_filter( 'get_sample_permalink_html', array( $this, 'lp_course_set_link_item_backend' ), 10, 5 );

			// Add "Edit with Course Builder" button below title area for courses
			add_action( 'edit_form_after_title', array( $this, 'add_course_builder_button' ) );

			/*add_action(
				'admin_init',
				function () {
					// From LP v4.2.3 temporary run create pages to add page instructors, single instructor for client upgrade LP.
					// After a long time, will remove this code.
					if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
						LP_Install::create_pages();
					}
				}
			);*/

			add_filter( 'users_list_table_query_args', [ $this, 'exclude_temp_users' ] );

			// Show label 'LearnPress Page' for page of LearnPress.
			//add_filter('post_states_html');
			add_filter( 'display_post_states', [ $this, 'show_label_page_type' ], 10, 2 );
		}

		/**
		 * @since 3.2.6
		 */
		public function load_modal() {
			// Now only add item(s) when create new Order is using.
			if ( in_array( get_post_type(), array( LP_ORDER_CPT ) ) ) {
				LP_Modal_Search_Items::instance();
			}

			/*if ( in_array( get_post_type(), array( LP_ORDER_CPT ) ) ) {
				LP_Modal_Search_Users::instance();
			}*/
		}

		/**
		 * @param $options
		 *
		 * @return array
		 */
		public function filter_modal_search( $options ) {
			$options = wp_parse_args( array( 'title' => __( 'Available Courses', 'learnpress' ) ), $options );

			return $options;
		}

		public function add_empty_page( $pages, $args ) {
			if ( empty( $pages ) && ! empty( $args['class'] ) && strpos( $args['class'], 'lp-list-pages' ) !== false ) {
				$empty_page     = get_default_post_to_edit( 'page' );
				$empty_page->ID = '00000';
				$pages[]        = $empty_page;
			}

			return $pages;
		}

		/**
		 * Add 'LearnPress' tab into views of plugins manage.
		 *
		 * @param array $views
		 *
		 * @return array
		 * @since 3.0.0
		 */
		public function views_plugins( $views ) {
			global $s;

			$search          = $this->get_addons();
			$count_activated = 0;

			$active_plugins = get_option( 'active_plugins' );

			if ( $active_plugins ) {
				if ( $search ) {
					foreach ( $search as $k => $v ) {
						if ( in_array( $k, $active_plugins ) ) {
							++ $count_activated;
						}
					}
				}
			}

			if ( $s && false !== stripos( $s, 'learnpress' ) ) {
				$views['learnpress'] = sprintf(
					'<a href="%s" class="current">%s <span class="count">(%d/%d)</span></a>',
					admin_url( 'plugins.php?s=learnpress' ),
					__( 'LearnPress', 'learnpress' ),
					$count_activated,
					sizeof( $search )
				);
			} else {
				$views['learnpress'] = sprintf(
					'<a href="%s">%s <span class="count">(%d/%d)</span></a>',
					admin_url( 'plugins.php?s=learnpress' ),
					__( 'LearnPress', 'learnpress' ),
					$count_activated,
					sizeof( $search )
				);
			}

			return $views;
		}

		public function get_addons() {
			$all_plugins = apply_filters( 'all_plugins', get_plugins() );

			return array_filter( $all_plugins, array( $this, '_search_callback' ) );
		}

		/**
		 * Callback function for searching plugins have 'learnpress' inside.
		 *
		 * @param array $plugin
		 *
		 * @return bool
		 * @since 3.0.0
		 */
		public function _search_callback( $plugin ) {
			foreach ( $plugin as $value ) {
				if ( is_string( $value ) && false !== stripos( strip_tags( $value ), 'learnpress' ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Check if a page is set for WooCommerce.
		 *
		 * @param int $id
		 *
		 * @return bool
		 */
		/*protected function _is_wc_page( $id ) {
			if ( class_exists( 'WooCommerce' ) ) {
				if ( ! class_exists( 'WC_Admin_Post_Types' ) ) {
					include_once dirname( WC_PLUGIN_FILE ) . '/includes/admin/class-wc-admin-post-types.php';
				}

				$wc_admin_post_types = new WC_Admin_Post_Types();
				if ( is_callable( array( $wc_admin_post_types, 'add_display_post_states' ) ) ) {
					$a = $wc_admin_post_types->add_display_post_states( array(), get_post( $id ) );
				} else {
					$a = $this->wc_add_display_post_states( array(), get_post( $id ) );
				}
				$wc_pages = array(
					'wc_page_for_shop',
					'wc_page_for_cart',
					'wc_page_for_checkout',
					'wc_page_for_myaccount',
					'wc_page_for_terms',
				);
				foreach ( $wc_pages as $for_page ) {
					if ( isset( $a[ $for_page ] ) ) {
						return $a[ $for_page ];
					}
				}
			}

			return false;
		}*/

		/*public function wc_add_display_post_states( $post_states, $post ) {
			if ( wc_get_page_id( 'shop' ) === $post->ID ) {
				$post_states['wc_page_for_shop'] = __( 'Shop Page', 'learnpress' );
			}

			if ( wc_get_page_id( 'cart' ) === $post->ID ) {
				$post_states['wc_page_for_cart'] = __( 'Cart Page', 'learnpress' );
			}

			if ( wc_get_page_id( 'checkout' ) === $post->ID ) {
				$post_states['wc_page_for_checkout'] = __( 'Checkout Page', 'learnpress' );
			}

			if ( wc_get_page_id( 'myaccount' ) === $post->ID ) {
				$post_states['wc_page_for_myaccount'] = __( 'My Account Page', 'learnpress' );
			}

			if ( wc_get_page_id( 'terms' ) === $post->ID ) {
				$post_states['wc_page_for_terms'] = __( 'Terms and Conditions Page', 'learnpress' );
			}

			return $post_states;
		}*/

		/**
		 * Check if a page is set for Paid Membership Pro.
		 *
		 * @param int $id
		 *
		 * @return bool|mixed
		 */
		/*protected function _is_pmpro_page( $id ) {
			global $pmpro_pages;
			if ( $pmpro_pages ) {
				$pages = array(
					'account'      => __( 'Account', 'learnpress' ),
					'billing'      => __( 'Billing', 'learnpress' ),
					'cancel'       => __( 'Cancel', 'learnpress' ),
					'checkout'     => __( 'Checkout', 'learnpress' ),
					'confirmation' => __( 'Confirmation', 'learnpress' ),
					'invoice'      => __( 'Invoice', 'learnpress' ),
					'levels'       => __( 'Levels', 'learnpress' ),
				);

				foreach ( $pages as $name => $text ) {
					if ( $pmpro_pages[ $name ] == $id ) {
						return $text;
					}
				}
			}

			return false;
		}*/

		/**
		 * Check if a page is set for Paid Membership Pro.
		 *
		 * @param int $id
		 *
		 * @return bool|mixed
		 */
		/*protected function _is_bp_page( $id ) {
			if ( function_exists( 'buddypress' ) ) {
				$bp_pages = get_option( 'bp-pages' );
				if ( ! $bp_pages ) {
					return false;
				}

				$pages = array(
					'members'  => __( 'Members', 'learnpress' ),
					'activity' => __( 'Activity', 'learnpress' ),
					'register' => __( 'Register', 'learnpress' ),
					'activate' => __( 'Activate', 'learnpress' ),
				);

				foreach ( $pages as $name => $text ) {
					if ( isset( $bp_pages[ $name ] ) && $bp_pages[ $name ] == $id ) {
						return $text;
					}
				}
			}

			return false;
		}*/

		/**
		 * Get pages is publish set by LearnPress.
		 *
		 * @return array
		 */
		protected function _get_static_pages(): array {
			$static_pages = [];
			foreach ( $this->pages as $name => $title ) {
				$page_id = learn_press_get_page_id( $name );

				if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
					$static_pages[ $page_id ] = $title;
				}
			}

			return $static_pages;
		}

		/**
		 * Show label LearnPress Page for pages set by LearnPress.
		 *
		 * @param array $post_states
		 * @param WP_Post $post
		 *
		 * @return array
		 * @since 4.3.2.6
		 * @version 1.0.0
		 */
		public function show_label_page_type( $post_states, $post ) {
			$post_type = get_post_type( $post );
			if ( $post_type !== 'page' ) {
				return $post_states;
			}

			$page_id_compare = $post->ID;
			foreach ( $this->_get_static_pages() as $page_id => $title ) {
				if ( $page_id == $page_id_compare ) {
					$post_states['lp_page'] = sprintf(
						'<span class="is-lp-page">%s</span>',
						'LearnPress ' . $title . ' Page'
					);
					break;
				}
			}

			return $post_states;
		}

		/**
		 * Add new column to WP Pages manage to show what page is assigned to.
		 *
		 * @param array $columns
		 *
		 * @return array
		 */
		/*public function page_columns_head( $columns ) {

			$_columns = $columns;
			$columns  = array();

			foreach ( $_columns as $name => $text ) {
				if ( $name === 'date' ) {
					$columns['lp-page'] = __( 'LearnPress Page', 'learnpress' );
				}
				$columns[ $name ] = $text;
			}

			return $columns;
		}*/

		/**
		 * Display the page is assigned to LP Page.
		 *
		 * @param string $column_name
		 * @param int $post
		 */
		/*public function page_columns_content( $column_name, $post ) {
			$pages = $this->_get_static_pages();
			switch ( $column_name ) {
				case 'lp-page':
					if ( ! empty( $pages['learnpress'][ $post ] ) ) {
						echo wp_kses_post( $pages['learnpress'][ $post ] );
					}

					foreach ( $pages as $plugin => $plugin_pages ) {
						if ( $plugin === 'learnpress' ) {
							continue;
						}

						if ( ! empty( $pages[ $plugin ][ $post ] ) ) {
							echo sprintf(
								'<p class="for-plugin-page">(%s - %s)</p>',
								$plugin,
								$pages[ $plugin ][ $post ]
							);
						}
					}
			}
		}*/

		/**
		 * @param $actions
		 *
		 * @return mixed
		 */
		public function views_pages( $actions ) {
			$pages = $this->_get_static_pages();

			if ( $pages ) {
				$text = sprintf( __( 'LearnPress Pages (%d)', 'learnpress' ), sizeof( $pages ) );
				if ( 'yes' !== LP_Request::get_param( 'lp-page' ) ) {
					$actions['lp-page'] = sprintf(
						'<a href="%s">%s</a>',
						admin_url( 'edit.php?post_type=page&lp-page=yes' ),
						$text
					);
				} else {
					$actions['lp-page'] = $text;
				}
			}

			return $actions;
		}

		/**
		 * Get pages set by LP with param url lp-page=yes
		 *
		 * @param WP_Query $q
		 *
		 * @return mixed
		 */
		public function get_pages_of_lp( $q ) {
			if ( ! is_admin() || ! $q->is_main_query() ) {
				return $q;
			}

			if ( 'page' == LP_Request::get_param( 'post_type' ) && 'yes' == LP_Request::get_param( 'lp-page' ) ) {
				$ids = $this->_get_static_pages();

				if ( ! empty( $ids ) ) {
					$ids = array_keys( $ids );
					$q->set( 'post__in', $ids );
				}
			}

			return $q;
		}

		/**
		 * Add actions to users list
		 *
		 * @param array $actions
		 * @param WP_User $user
		 *
		 * @return mixed
		 */
		public function user_row_actions( $actions, $user ) {
			$pending_request = self::get_pending_requests();
			if ( LP_Request::get_param( 'lp-action' ) == 'pending-request' && $pending_request ) {
				$actions = array();
				$nonce   = 'nonce=' . wp_create_nonce( 'lp-action-permit-role-teacher' );
				if ( in_array( $user->ID, $pending_request ) ) {
					$actions['accept']      = sprintf(
						'<a href="%s">%s</a>',
						admin_url( "users.php?lp-action=accept-request&user_id={$user->ID}&{$nonce}" ),
						_x( 'Accept', 'pending-request', 'learnpress' )
					);
					$actions['delete deny'] = sprintf(
						'<a class="submitdelete" href="%s">%s</a>',
						admin_url( "users.php?lp-action=deny-request&user_id={$user->ID}&{$nonce}" ),
						_x( 'Deny', 'pending-request', 'learnpress' )
					);
				}
			}

			return $actions;
		}

		public function exclude_temp_users( $args ) {
			if ( LP_Request::get_param( 'lp-action' ) == 'pending-request' ) {
				$args['include'] = self::get_pending_requests();
			}

			return $args;
		}

		/**
		 * Get pending requests be come a Teacher.
		 *
		 * @return array
		 */
		public static function get_pending_requests() {
			global $wpdb;
			$query = $wpdb->prepare(
				"
				SELECT ID
				FROM {$wpdb->users} u
				INNER JOIN {$wpdb->usermeta} um ON um.user_id = u.ID AND um.meta_key = %s
				WHERE um.meta_value = %s
				",
				'_requested_become_teacher',
				'yes'
			);

			return $wpdb->get_col( $query );
		}

		/**
		 * Filter user by custom param
		 *
		 * @param string $action
		 */
		public function filter_users( $action ) {
			if ( ! current_user_can( 'administrator' ) ) {
				return;
			}

			$user_id = LP_Request::get_param( 'user_id', 'int' );
			if ( ! $user_id || ! get_user_by( 'id', $user_id ) ) {
				return;
			}

			$nonce = LP_Request::get_param( 'nonce' );
			if ( ! wp_verify_nonce( $nonce, 'lp-action-permit-role-teacher' ) ) {
				return;
			}

			$user_data = get_userdata( $user_id );
			if ( in_array( $action, array( 'accept-request', 'deny-request' ) ) ) {
				delete_user_meta( $user_id, '_requested_become_teacher' );
			}

			switch ( $action ) {
				case 'accept-request':
					$be_teacher = new WP_User( $user_id );
					$be_teacher->set_role( LP_TEACHER_ROLE );

					/**
					 * Send email to user when admin accept user become a teacher
					 * @use SendEmailAjax::send_mail_become_a_teacher_accept
					 */
					$data_send = [
						'params'       => [ $user_data->user_email ],
						'lp-load-ajax' => 'send_mail_become_a_teacher_accept',
					];
					LPBackgroundAjax::handle( $data_send );

					do_action( 'learn-press/user-become-a-teacher-accept', $user_data->user_email );
					wp_redirect( admin_url( 'users.php?lp-action=accepted-request&user_id=' . $user_id ) );
					exit();
				case 'deny-request':
					/**
					 * Send email to user when admin accept user become a teacher
					 * @use SendEmailAjax::send_mail_become_a_teacher_deny
					 */
					$data_send = [
						'params'       => [ $user_data->user_email ],
						'lp-load-ajax' => 'send_mail_become_a_teacher_deny',
					];
					LPBackgroundAjax::handle( $data_send );

					do_action( 'learn-press/user-become-a-teacher-deny', $user_data->user_email );
					wp_redirect( admin_url( 'users.php?lp-action=denied-request&user_id=' . $user_id ) );
					exit();
			}
		}

		public function users_custom_column( $content, $column_name, $user_id ) {
		}

		/**
		 * Add new view to users views for filtering user by "pending request" of "become a teacher".
		 *
		 * @param array $views
		 *
		 * @return mixed
		 */
		public function views_users( $views ) {
			$pending_request = self::get_pending_requests();

			if ( $pending_request ) {
				if ( LP_Request::get_param( 'lp-action' ) == 'pending-request' ) {
					$class = ' class="current"';
					foreach ( $views as $k => $view ) {
						$views[ $k ] = preg_replace( '!class="current"!', '', $view );
					}
				} else {
					$class = '';
				}
				$views['pending-request'] = '<a href="' . admin_url( 'users.php?lp-action=pending-request' ) . '"' . $class . '>' . sprintf(
					__(
						'Pending Request %s',
						'learnpress'
					),
					'<span class="count">(' . count( $pending_request ) . ')</span>'
				) . '</a>';
			}

			return $views;
		}

		/**
		 * Custom admin body classes.
		 *
		 * @param array $classes
		 *
		 * @return array|string
		 */
		public function body_class( $classes ) {
			$post_type = get_post_type();

			if ( preg_match( '~^lp_~', $post_type ) ) {
				if ( $classes ) {
					$classes = explode( ' ', $classes );
				} else {
					$classes = array();
				}

				$classes[] = 'learnpress';
				$classes   = array_filter( $classes );
				$classes   = array_unique( $classes );
				$classes   = join( ' ', $classes );
			}

			return $classes;
		}

		/**
		 * Display notices on Backend.
		 */
		public function admin_notices() {
			if ( function_exists( 'get_current_screen' ) ) {
				$wp_screen = get_current_screen();
				// Not run on course edit screen, it slows, can affect performance edit course.
				if ( $wp_screen->id === LP_COURSE_CPT ) {
					return;
				}
			}

			// Show template file templates override.
			$page = LP_Request::get_param( 'page' );
			$tab  = LP_Request::get_param( 'tab' );
			if ( $page == 'learn-press-tools' && $tab == 'templates' ) {
				if ( LP_Outdated_Template_Helper::detect_outdated_template() ) {
					learn_press_admin_view( 'html-admin-notice-templates' );
				}
			}

			// Request accept/denied user can become a teacher.
			$action_become_teacher      = LP_Request::get_param( 'lp-action' );
			$user_id                    = LP_Request::get_param( 'user_id', 0, 'int' );
			$type_action_become_teacher = array( 'accepted-request', 'denied-request' );
			if ( in_array( $action_become_teacher, $type_action_become_teacher ) && $user_id && get_user_by( 'id', $user_id ) ) {
				?>
				<div class="updated notice">
					<p>
						<?php
						echo sprintf(
							__( 'A user has %s to become a teacher.', 'learnpress' ),
							$action_become_teacher == 'accepted-request' ? 'accepted' : 'denied'
						);
						?>
					</p>
				</div>
				<?php
			}

			learn_press_admin_view( 'admin-notices.php', [], true );
		}

		/**
		 * Admin footer add review.
		 *
		 * @param $footer_text
		 *
		 * @return string
		 */
		public function admin_footer_text( $footer_text ) {
			$current_screen = get_current_screen();
			$pages          = learn_press_get_screens();

			if ( isset( $current_screen->id ) && apply_filters(
				'learn_press_display_admin_footer_text',
				in_array( $current_screen->id, $pages )
			) ) {
				if ( ! get_option( 'learn_press_message_user_rated' ) ) {
					$footer_text = sprintf(
						__(
							'If you like <strong>LearnPress</strong> please leave us a %1$s&#9733;&#9733;&#9733;&#9733;&#9733;%2$s rating. A huge thanks from the LearnPress team for your generosity.',
							'learnpress'
						),
						'<a href="https://wordpress.org/support/plugin/learnpress/reviews/?filter=5#postform" target="_blank" class="lp-rating-link" data-rated="' . esc_attr__(
							'Thanks :)',
							'learnpress'
						) . '">',
						'</a>'
					);
				}
			}

			return $footer_text;
		}

		/*function delete_user_form() {
			// What should be displayed here?
		}*/

		/**
		 * Delete records related user being deleted in other tables
		 *
		 * @param int $user_id
		 */
		function delete_user_data( $user_id ) {
			learn_press_delete_user_data( $user_id );
		}

		/**
		 * Send data to join newsletter or dismiss.
		 *
		 * @param array $data
		 * @param string $notice
		 *
		 * @return array
		 * @since 3.0.10
		 * @deprecated 4.2.3.1
		 */
		/*public function on_dismissed_notice_response( $data, $notice ) {
			switch ( $notice ) {
				case 'skip-setup-wizard':
					delete_option( 'learn_press_install' );
					break;
				case 'newsletter-button':
					$context = LP_Request::get_string( 'context' );
					if ( ! $context || $context != 'newsletter' ) {
						break;
					}

					$user = learn_press_get_current_user();
					if ( ! $user || $user->get_email() == '' ) {
						$data['error'] = __( 'Failed while joining the newsletter! Please try again!', 'learnpress' );
					}

					$url      = 'https://thimpress.com/mailster/subscribe';
					$response = wp_remote_post(
						$url,
						array(
							'method'      => 'POST',
							'timeout'     => 45,
							'redirection' => 5,
							'httpversion' => '1.0',
							'blocking'    => true,
							'headers'     => array(),
							'body'        => array(
								'_referer' => 'extern',
								'_nonce'   => '4b266caf7b',
								'formid'   => '19',
								'email'    => $user->get_email(),
								'website'  => site_url(),
							),
							'cookies'     => array(),
						)
					);

					if ( is_wp_error( $response ) ) {
						$error_message   = $response->get_error_message();
						$data['message'] = __( 'Something went wrong: ', 'learnpress' ) . $error_message;
					} else {
						$data['message'] = __(
							'Thank you for subscribing! Please check and click the confirmation link from the email that we\'ve just sent to your inbox.',
							'learnpress'
						);
					}
			}

			return $data;
		}*/

		/**
		 * Include all classes and functions used for admin
		 */
		public function includes() {
			// Common function used in admin
			include_once 'lp-admin-functions.php';
			include_once 'lp-admin-actions.php';
			require_once LP_PLUGIN_PATH . 'inc/background-process/class-lp-background-query-items.php';
			include_once 'class-lp-admin-assets.php';
			LP_Admin_Assets::instance();
			include_once 'class-lp-admin-mcp-api-keys.php';
			LP_Admin_MCP_API_Keys::instance();
			include_once 'class-lp-admin-dashboard.php';
			// include_once 'class-lp-admin-tools.php';
			include_once 'class-lp-admin-ajax.php';
			include_once 'editor/class-lp-admin-editor.php';
			include_once 'class-lp-admin-menu.php';
			include_once 'helpers/class-lp-outdated-template-helper.php';
			include_once 'helpers/class-lp-plugins-helper.php';
			include_once 'class-lp-modal-search-items.php';
			//include_once 'class-lp-modal-search-users.php';
			include_once 'class-lp-setup-wizard.php';
			// include_once 'class-lp-updater.php';
			include_once 'class-lp-install-sample-data.php';
			include_once 'class-lp-reset-data.php';
			include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/course/settings.php';
			include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/course/class-lp-meta-box-course-offline.php';
			include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/quiz/settings.php';
			include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/lesson/settings.php';
			//include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/question/settings.php';
		}

		/**
		 * Get courses, item's courses on Backend page post_type
		 *
		 * @param WP_Query $query
		 */
		public function get_course_items_of_user_backend( WP_Query $query ) {
			if ( ! $query->is_main_query() ) {
				return;
			}

			global $post_type, $pagenow;

			if ( current_user_can( ADMIN_ROLE ) ) {
				return;
			}

			if ( ! current_user_can( LP_TEACHER_ROLE ) || ( $pagenow != 'edit.php' ) ) {
				return;
			}

			$post_type_valid = apply_filters(
				'learn-press/filter-user-access-types',
				array( LP_COURSE_CPT, LP_LESSON_CPT, LP_QUIZ_CPT, LP_QUESTION_CPT )
			);

			if ( ! in_array( $post_type, $post_type_valid ) ) {
				return;
			}

			// $query->set( 'author', get_current_user_id() );

			$query = apply_filters( 'learnpress/get-post-type-lp-on-backend', $query );

			//add_filter( 'views_edit-' . $post_type . '', '_learn_press_restrict_view_items', 10 );
			//remove_filter( 'pre_get_posts', array( $this, 'get_course_items_of_user_backend' ), 10 );
		}

		/**
		 * Set link item of course when edit item on Backend
		 *
		 * @param string $post_link
		 * @param int $post_id
		 * @param string $new_title
		 * @param string $new_slug
		 * @param WP_Post|null $post
		 *
		 * @return string
		 * @author tungnx
		 * @since  3.2.7.5
		 * @version 4.0.1
		 */
		public function lp_course_set_link_item_backend( $post_link = '', $post_id = 0, $new_title = '', $new_slug = '', $post = null ) {
			try {
				if ( in_array( $post->post_type, CourseModel::item_types_support() ) ) {
					$course_id_of_item = LP_Course_DB::getInstance()->get_course_by_item_id( $post->ID );
					if ( $course_id_of_item ) {
						$course = learn_press_get_course( $course_id_of_item );
						if ( $course ) {
							$link_item           = urldecode( $course->get_item_link( $post->ID ) );
							$post_slug           = $post->post_name;
							$link_item_edit_slug = preg_replace( '/' . $post_slug . '$/', '', $link_item );

							// For update new slug
							if ( $new_slug ) {
								$post_slug = $new_slug;
							}
							$post_slug = urldecode( $post_slug );

							$slug_arr   = explode( '/', $link_item_edit_slug );
							$count_slug = count( $slug_arr );
							unset( $slug_arr[ $count_slug - 2 ] );
							$link_item_edit_slug = implode( '/', $slug_arr );

							$post_link  = '<strong>Permalink: </strong>';
							$post_link .= '<span id="sample-permalink">';
							$post_link .= '<a href="' . $link_item . '">' . $link_item_edit_slug . '<span id="editable-post-name">' . $post_slug . '</span>/</a>';
							$post_link .= '</span>';
							$post_link .= '&lrm;<span id="edit-slug-buttons">';
							$post_link .= '<button type="button" class="edit-slug button button-small hide-if-no-js" aria-label="Edit permalink">Edit</button>';
							$post_link .= '</span>';
							$post_link .= '<span id="editable-post-name-full">' . $post_slug . '</span>';
						}
					} else {
						$post_link_message = '<span>' . __(
							'Permalink is only available if the item is already assigned to a course.',
							'learnpress'
						) . '</span>';
						$post_link         = sprintf( '<div id="learn-press-box-edit-slug">%s</div>', $post_link_message );
					}
				} elseif ( LP_COURSE_CPT === get_post_type( $post ) ) {
					$post_link = LP_Helper::handle_lp_permalink_structure( $post_link, $post );
				}
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}

			return $post_link;
		}

		/**
		 * Add "Edit with Course Builder" button below title area for courses
		 *
		 * @param WP_Post $post
		 *
		 * @since 4.3.0
		 */
		public function add_course_builder_button( $post ) {
			if ( ! $post || LP_COURSE_CPT !== $post->post_type ) {
				return;
			}

			// Only show for existing courses (not new)
			if ( 'auto-draft' === $post->post_status ) {
				return;
			}

			$course_builder_url = BuilderCourseTemplate::instance()->get_link_edit( $post->ID ) ?? '';
			?>
			<div class="lp-edit-with-course-builder" style="margin: 10px 0 20px 0;">
				<a href="<?php echo esc_url( $course_builder_url ); ?>" class="button button-primary button-large" style="background: #7067ED; border-color: #7067ED; display: inline-flex; align-items: center; gap: 6px; padding: 0 16px; font-size: 14px; border-radius: 3px; line-height: 3;">
					<svg width="26" height="18" viewBox="0 0 69 48" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M50.9291 24.84L50.2591 21.69C50.5491 21.47 50.7391 21.11 50.7391 20.71C50.7391 20.03 50.1891 19.48 49.4991 19.48C48.8091 19.48 48.2591 20.03 48.2591 20.71C48.2591 21.11 48.4491 21.46 48.7391 21.69L48.0691 24.84C47.9891 25.21 48.1791 25.56 48.4791 25.56H50.5091C50.8091 25.56 50.9991 25.21 50.9191 24.84H50.9291Z" fill="white"></path>
						<path d="M50.8892 13.24H50.0092V20.92H48.9892V13.21L46.2992 13.18C43.3992 8.47 38.1692 5.34 32.2192 5.34C30.8292 5.34 29.4792 5.51 28.1892 5.83L24.9492 2.53C24.7992 2.38 24.8892 2.14 25.0992 2.11L40.7292 0L48.5492 9.68L49.7592 11.17L51.0992 12.83C51.2292 12.99 51.1092 13.24 50.8992 13.23L50.8892 13.24Z" fill="white"></path>
						<path d="M44.7392 13.1701L43.1392 17.1401C40.7292 13.1101 36.3092 10.4101 31.2592 10.4101C29.9992 10.4101 28.7892 10.5801 27.6292 10.8901L29.2292 6.93014C30.1992 6.74014 31.1992 6.64014 32.2192 6.64014C37.4092 6.64014 41.9892 9.22014 44.7392 13.1701Z" fill="white"></path>
						<path d="M25.4692 23.6899V23.8499C25.4692 23.9599 25.4592 24.0699 25.4592 24.1899C25.4592 24.0199 25.4592 23.8499 25.4692 23.6899Z" fill="white"></path>
						<path d="M43.3692 24.2601C43.3692 25.2901 43.2392 26.2901 42.9892 27.2501C42.3892 29.5401 41.1192 31.5601 39.4092 33.1001C37.9992 34.3601 36.2892 35.2901 34.3892 35.7701C33.4492 36.0101 32.4592 36.1401 31.4392 36.1401H27.9692V35.8001C27.9692 35.6601 27.9792 35.5301 27.9992 35.3901C28.2092 33.2201 29.5592 31.3901 31.4492 30.4901C31.6092 30.4101 31.7692 30.3401 31.9292 30.2801C32.1692 30.1901 32.4192 30.1201 32.6692 30.0601C32.7292 30.0501 32.7992 30.0301 32.8592 30.0201C35.4892 29.4101 37.4492 27.0601 37.4492 24.2501C37.4492 21.2701 35.2392 18.8001 32.3592 18.3901C32.0792 18.3501 31.7892 18.3301 31.4992 18.3301C31.2392 18.3301 30.9992 18.3501 30.7492 18.3801C28.2992 18.6901 26.2592 20.4801 25.6492 22.8201C25.5292 23.2901 25.4792 23.7701 25.4792 24.2401V42.7601C25.1492 45.6001 22.8092 47.8301 19.9092 47.9901H19.5592H16.4792C18.3092 46.6501 19.5092 44.5001 19.5092 42.0701V23.7001C19.5092 23.5601 19.5192 23.4301 19.5392 23.3001C19.6292 22.2301 19.8492 21.1901 20.2092 20.2201C20.4592 19.5401 20.7692 18.8801 21.1292 18.2501C21.7592 17.1801 22.5492 16.2201 23.4692 15.4001C24.6192 14.3701 25.9792 13.5601 27.4692 13.0401C28.7092 12.6001 30.0492 12.3701 31.4392 12.3701C32.4792 12.3701 33.4792 12.5001 34.4392 12.7501C36.6892 13.3301 38.6792 14.5401 40.2092 16.1901C40.2592 16.2401 40.3092 16.3001 40.3592 16.3501C40.3592 16.3501 40.3592 16.3501 40.3692 16.3601C41.9592 18.1401 43.0192 20.4101 43.2992 22.9001C43.3293 23.1701 43.3492 23.4501 43.3692 23.7201C43.3692 23.8901 43.3692 24.0601 43.3692 24.2401V24.2601Z" fill="white"></path>
						<path d="M25.4692 23.6899V23.8499C25.4692 23.9599 25.4592 24.0699 25.4592 24.1899C25.4592 24.0199 25.4592 23.8499 25.4692 23.6899Z" fill="white"></path>
						<path d="M6.13917 42.0799H18.0692C18.0592 45.3499 15.3792 47.9999 12.0892 47.9999H6.11917C2.83917 47.9999 0.16917 45.3499 0.14917 42.0799V12.3799H0.18917C3.46917 12.3799 6.13917 15.0299 6.13917 18.2999V42.0799Z" fill="white"></path>
						<path d="M67.6736 23.2845L63.9566 19.5676C63.1998 18.8108 61.9757 18.8108 61.219 19.5676L59.0266 21.7599L65.4812 28.2145L67.6736 26.0222C68.4303 25.2654 68.4303 24.0413 67.6736 23.2845Z" fill="white"></path>
						<path d="M45.4722 45.0411L46.3513 45.9203L42.078 46.7883C42.078 46.7883 42.0557 46.3432 41.477 45.7534C40.8983 45.1747 40.442 45.1524 40.442 45.1524L41.3101 40.879L42.1892 41.7582L59.817 24.1305L58.2256 22.5391L40.5979 40.1668L39.2513 46.7883C39.1066 47.4894 39.7298 48.1126 40.4309 47.968L47.0525 46.6214L64.6802 28.9937L63.0888 27.4023L45.4611 45.03L45.4722 45.0411Z" fill="white"></path>
						<path d="M60.6182 24.9316L42.9905 42.5594L44.6932 44.2621L62.3209 26.6343L60.6182 24.9316Z" fill="white"></path>
					</svg>
					<?php esc_html_e( 'Edit with Course Builder', 'learnpress' ); ?>
				</a>
			</div>
			<?php
		}

		/**
		 * @return false|string
		 * @since 3.2.8
		 * @editor tungnx
		 */
		public function get_screen_id() {
			global $current_screen;

			return $current_screen ? $current_screen->id : false;
		}

		/**
		 * Get single instance of self
		 *
		 * @return bool|LP_Admin
		 * @return bool|LP_Admin
		 * @since 3.0.0
		 *
		 */
		public static function instance() {
			if ( ! self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}
}

return LP_Admin::instance();
