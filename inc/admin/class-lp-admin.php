<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Classes
 * @version 1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'LP_Admin' ) ) {
	/**
	 * Class LP_Admin
	 */
	class LP_Admin {

		/**
		 * @var array
		 */
		protected $_static_pages = false;

		/**
		 *  Constructor
		 */
		public function __construct() {
			$this->includes();
			add_action( 'delete_user', array( $this, 'delete_user_data' ) );
			add_action( 'delete_user_form', array( $this, 'delete_user_form' ) );
			add_action( 'wp_ajax_learn_press_rated', array( $this, 'rated' ) );
			add_action( 'admin_notices', array( $this, 'notice_outdated_templates' ) );
			add_action( 'admin_notices', array( $this, 'notice_setup_pages' ) );
			add_action( 'admin_notices', array( $this, 'notice_required_permalink' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			add_action( 'edit_form_after_editor', array( $this, 'wrapper_editor' ), - 10 );
			add_action( 'admin_head', array( $this, 'admin_colors' ) );
			add_action( 'init', array( $this, 'init' ), 50 );
			add_action( 'admin_init', array( $this, 'admin_redirect' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_modal' ) );

			add_filter( 'admin_body_class', array( $this, 'body_class' ) );
			add_filter( 'manage_users_custom_column', array( $this, 'users_custom_column' ), 10, 3 );
			add_filter( 'manage_pages_columns', array( $this, 'page_columns_head' ) );
			add_filter( 'manage_pages_custom_column', array( $this, 'page_columns_content' ), 10, 2 );
			add_filter( 'views_edit-page', array( $this, 'views_pages' ), 10 );
			add_filter( 'pre_get_posts', array( $this, 'filter_pages' ), 10 );
			add_filter( 'views_users', array( $this, 'views_users' ), 10, 1 );
			add_filter( 'user_row_actions', array( $this, 'user_row_actions' ), 10, 2 );
			add_filter( 'get_pages', array( $this, 'add_empty_page' ), 1000, 2 );
			add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
			add_filter( 'views_plugins', array( $this, 'views_plugins' ) );

			LP_Request::register( 'lp-action', array( $this, 'filter_users' ) );

			add_filter( 'learn-press/modal-search-items-args', array( $this, 'filter_modal_search' ) );

			add_filter( 'learn-press/dismissed-notice-response', array(
				$this,
				'on_dismissed_notice_response'
			), 10, 2 );
		}

		/**
		 * @since 3.2.6
		 */
		public function load_modal() {
			if ( in_array( get_post_type(), array( LP_COURSE_CPT, LP_QUIZ_CPT, LP_QUESTION_CPT, LP_ORDER_CPT ) ) ) {
				LP_Modal_Search_Items::instance();
			};

			if ( in_array( get_post_type(), array( LP_ORDER_CPT ) ) ) {
				LP_Modal_Search_Users::instance();
			}
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
		 * @since 3.0.0
		 *
		 * @param array $views
		 *
		 * @return array
		 */
		public function views_plugins( $views ) {
			global $s;

			$search          = $this->get_addons();
			$count_activated = 0;

			if ( $active_plugins = get_option( 'active_plugins' ) ) {
				if ( $search ) {
					foreach ( $search as $k => $v ) {
						if ( in_array( $k, $active_plugins ) ) {
							$count_activated ++;
						}
					}
				}
			}

			if ( $s && false !== stripos( $s, 'learnpress' ) ) {
				$views['learnpress'] = sprintf( '<a href="%s" class="current">%s <span class="count">(%d/%d)</span></a>', admin_url( 'plugins.php?s=learnpress' ), __( 'LearnPress', 'learnpress' ), $count_activated, sizeof( $search ) );
			} else {
				$views['learnpress'] = sprintf( '<a href="%s">%s <span class="count">(%d/%d)</span></a>', admin_url( 'plugins.php?s=learnpress' ), __( 'LearnPress', 'learnpress' ), $count_activated, sizeof( $search ) );
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
		 * @since 3.0.0
		 *
		 * @param array $plugin
		 *
		 * @return bool
		 */
		public function _search_callback( $plugin ) {
			foreach ( $plugin as $value ) {
				if ( is_string( $value ) && false !== stripos( strip_tags( $value ), 'learnpress' ) ) {
					return true;
				}
			}

			return false;
		}

		public function init() {

			///die(get_post_type());
			add_action( 'learn-press/enqueue-script/learn-press-modal-search-items', array(
				'LP_Modal_Search_Items',
				'instance'
			) );
			add_action( 'learn-press/enqueue-script/learn-press-modal-search-users', array(
				'LP_Modal_Search_Users',
				'instance'
			) );

			if ( 'yes' === LP_Request::get_string( 'lp-hide-upgrade-message' ) ) {
				delete_transient( 'lp_upgraded_30' );
			}
		}

		/**
		 * Check if a page is set for WooCommerce.
		 *
		 * @param int $id
		 *
		 * @return bool
		 */
		protected function _is_wc_page( $id ) {
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
					'wc_page_for_terms'
				);
				foreach ( $wc_pages as $for_page ) {
					if ( isset( $a[ $for_page ] ) ) {
						return $a[ $for_page ];
					}
				}
			}

			return false;
		}

		public function wc_add_display_post_states( $post_states, $post ) {
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
		}

		/**
		 * Check if a page is set for Paid Membership Pro.
		 *
		 * @param int $id
		 *
		 * @return bool|mixed
		 */
		protected function _is_pmpro_page( $id ) {
			global $pmpro_pages;
			if ( $pmpro_pages ) {
				$pages = array(
					'account'      => __( 'Account', 'learnpress' ),
					'billing'      => __( 'Billing', 'learnpress' ),
					'cancel'       => __( 'Cancel', 'learnpress' ),
					'checkout'     => __( 'Checkout', 'learnpress' ),
					'confirmation' => __( 'Confirmation', 'learnpress' ),
					'invoice'      => __( 'Invoice', 'learnpress' ),
					'levels'       => __( 'Levels', 'learnpress' )
				);

				foreach ( $pages as $name => $text ) {
					if ( $pmpro_pages[ $name ] == $id ) {
						return $text;
					}
				}
			}

			return false;
		}

		/**
		 * Check if a page is set for Paid Membership Pro.
		 *
		 * @param int $id
		 *
		 * @return bool|mixed
		 */
		protected function _is_bp_page( $id ) {
			if ( function_exists( 'buddypress' ) ) {

				if ( ! $bp_pages = get_option( 'bp-pages' ) ) {
					return false;
				}

				$pages = array(
					'members'  => __( 'Members', 'learnpress' ),
					'activity' => __( 'Activity', 'learnpress' ),
					'register' => __( 'Register', 'learnpress' ),
					'activate' => __( 'Activate', 'learnpress' )
				);

				foreach ( $pages as $name => $text ) {
					if ( isset( $bp_pages[ $name ] ) && $bp_pages[ $name ] == $id ) {
						return $text;
					}
				}
			}

			return false;
		}

		/**
		 * @param string $plugin
		 *
		 * @return array|bool
		 */
		protected function _get_static_pages( $plugin = '' ) {
			if ( false === $this->_static_pages ) {
				$this->_static_pages = array(
					'learnpress'          => array(),
					'WooCommerce'         => array(),
					'Paid Membership Pro' => array(),
					'BuddyPress'          => array()
				);
				$all_pages           = array(
					'courses'          => __( 'Courses', 'learnpress' ),
					'profile'          => __( 'Profile', 'learnpress' ),
					'checkout'         => __( 'Checkout', 'learnpress' ),
					'become_a_teacher' => __( 'Become a Teacher', 'learnpress' )
				);
				foreach ( $all_pages as $name => $title ) {
					if ( ( $page_id = learn_press_get_page_id( $name ) ) && 'publish' === get_post_status( $page_id ) ) {
						$this->_static_pages['learnpress'][ $page_id ] = $title;

						if ( $for_page = $this->_is_wc_page( $page_id ) ) {
							$this->_static_pages['WooCommerce'][ $page_id ] = $for_page;
						}

						if ( $for_page = $this->_is_pmpro_page( $page_id ) ) {
							$this->_static_pages['Paid Membership Pro'][ $page_id ] = $for_page;
						}

						if ( $for_page = $this->_is_bp_page( $page_id ) ) {
							$this->_static_pages['BuddyPress'][ $page_id ] = $for_page;
						}
					}
				}
			}

			return $plugin ? ( ! empty( $this->_static_pages[ $plugin ] ) ? $this->_static_pages[ $plugin ] : false ) : $this->_static_pages;
		}

		/**
		 * Add new column to WP Pages manage to show what page is assigned to.
		 *
		 * @param array $columns
		 *
		 * @return array
		 */
		public function page_columns_head( $columns ) {

			$_columns = $columns;
			$columns  = array();

			foreach ( $_columns as $name => $text ) {
				if ( $name === 'date' ) {
					$columns['lp-page'] = __( 'LearnPress Page', 'learnpress' );
				}
				$columns[ $name ] = $text;
			}

			return $columns;
		}

		/**
		 * Display the page is assigned to LP Page.
		 *
		 * @param string $column_name
		 * @param int    $post
		 */
		public function page_columns_content( $column_name, $post ) {
			$pages = $this->_get_static_pages();
			switch ( $column_name ) {
				case 'lp-page':
					if ( ! empty( $pages['learnpress'][ $post ] ) ) {
						echo $pages['learnpress'][ $post ];
					}

					foreach ( $pages as $plugin => $plugin_pages ) {
						if ( $plugin === 'learnpress' ) {
							continue;
						}

						if ( ! empty( $pages[ $plugin ][ $post ] ) ) {
							echo sprintf( '<p class="for-plugin-page">(%s - %s)</p>', $plugin, $pages[ $plugin ][ $post ] );
						}
					}
			}
		}

		/**
		 * @param $actions
		 *
		 * @return mixed
		 */
		public function views_pages( $actions ) {
			$this->_get_static_pages();
			if ( $pages = $this->_get_static_pages( 'learnpress' ) ) {
				$text = sprintf( __( 'LearnPress Pages (%d)', 'learnpress' ), sizeof( $pages ) );
				if ( 'yes' !== LP_Request::get( 'lp-page' ) ) {
					$actions['lp-page'] = sprintf( '<a href="%s">%s</a>', admin_url( 'edit.php?post_type=page&lp-page=yes' ), $text );
				} else {
					$actions['lp-page'] = $text;
				}
			}

			return $actions;
		}

		/**
		 * @param WP_Query $q
		 *
		 * @return mixed
		 */
		public function filter_pages( $q ) {
			if ( 'page' == LP_Request::get( 'post_type' ) && 'yes' == LP_Request::get( 'lp-page' ) ) {
				if ( $ids = array_keys( $this->_get_static_pages( 'learnpress' ) ) ) {
					$q->set( 'post__in', $ids );
				}
			}

			return $q;
		}

		/**
		 * Add actions to users list
		 *
		 * @param array   $actions
		 * @param WP_User $user
		 *
		 * @return mixed
		 */
		public function user_row_actions( $actions, $user ) {
			$pending_request = LP_User_Factory::get_pending_requests();
			if ( LP_Request::get_string( 'lp-action' ) == 'pending-request' && $pending_request ) {
				$actions = array();
				if ( in_array( $user->ID, $pending_request ) ) {
					$actions['accept']      = sprintf( '<a href="' . admin_url( 'users.php?lp-action=accept-request&user_id=' . $user->ID ) . '">%s</a>', _x( 'Accept', 'pending-request', 'learnpress' ) );
					$actions['delete deny'] = sprintf( '<a class="submitdelete" href="' . admin_url( 'users.php?lp-action=deny-request&user_id=' . $user->ID ) . '">%s</a>', _x( 'Deny', 'pending-request', 'learnpress' ) );
				}
			}

			return $actions;
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

			$user_id = LP_Request::get_int( 'user_id' );

			if ( ! $user_id || ! get_user_by( 'id', $user_id ) ) {
				return;
			}

			$user_data = get_userdata( $user_id );

			if ( in_array( $action, array( 'accept-request', 'deny-request' ) ) ) {

				delete_user_meta( $user_id, '_requested_become_teacher' );

				switch ( $action ) {
					case 'accept-request':
						$be_teacher = new WP_User( $user_id );
						$be_teacher->set_role( LP_TEACHER_ROLE );

						do_action( 'learn-press/user-become-a-teacher-accept', $user_data->user_email );
						wp_redirect( admin_url( 'users.php?lp-action=accepted-request&user_id=' . $user_id ) );
						exit();
					case 'deny-request':
						do_action( 'learn-press/user-become-a-teacher-deny', $user_data->user_email );
						wp_redirect( admin_url( 'users.php?lp-action=denied-request&user_id=' . $user_id ) );
						exit();
				}
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
			if ( $pending_request = LP_User_Factory::get_pending_requests() ) {
				if ( LP_Request::get_string( 'lp-action' ) == 'pending-request' ) {
					$class = ' class="current"';
					foreach ( $views as $k => $view ) {
						$views[ $k ] = preg_replace( '!class="current"!', '', $view );
					}
				} else {
					$class = '';
				}
				$views['pending-request'] = '<a href="' . admin_url( 'users.php?lp-action=pending-request' ) . '"' . $class . '>' . sprintf( __( 'Pending Request %s', 'learnpress' ), '<span class="count">(' . count( $pending_request ) . ')</span>' ) . '</a>';
			}

			return $views;
		}

		/**
		 * Display admin notices
		 */
		public function admin_notices() {

			if ( 'yes' === get_transient( 'lp_upgraded_30' ) ) {
				learn_press_admin_view( 'updates/html-upgrade-message-3.0.0' );
			}

			if ( 'yes' === get_option( 'learn_press_install' ) ) {
				learn_press_admin_view( 'setup/notice-setup' );
			}

			$action = LP_Request::get( 'lp-action' );

			if ( ( in_array( $action, array(
					'accepted-request',
					'denied-request'
				) ) ) && ( $user_id = LP_Request::get_int( 'user_id' ) ) && get_user_by( 'id', $user_id )
			) {
				if ( ! current_user_can( 'promote_user', $user_id ) ) {
					wp_die( __( 'Sorry, you are not allowed to edit this user.', 'learnpress' ) );
				} ?>

                <div class="updated notice">
                    <p><?php echo sprintf( __( 'User has %s to become a teacher.', 'learnpress' ), $action == 'accepted-request' ? 'accepted' : 'denied' ); ?></p>
                </div>

				<?php
			}

			if ( LP()->session->get( 'do-update-learnpress' ) ) {
				learn_press_admin_view( 'updates/html-updated-latest-message' );
			}
		}

		/**
		 * Redirect to setup page if we have just activated LP
		 */
		public function admin_redirect() {
			if ( 'yes' === get_transient( 'lp_activation_redirect' ) && current_user_can( 'install_plugins' ) ) {
				delete_transient( 'lp_activation_redirect' );

				wp_safe_redirect( admin_url( 'index.php?page=lp-setup' ) );
			}
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

		public function admin_colors() {
			global $_wp_admin_css_colors;
			$schema = get_user_option( 'admin_color' );
			if ( empty( $_wp_admin_css_colors[ $schema ] ) ) {
				return;
			}

			$colors = $_wp_admin_css_colors[ $schema ]->colors;
			?>
            <style type="text/css">
                .admin-color {
                    color: <?php echo $colors[0];?>
                }

                .admin-background {
                    color: <?php echo $colors[0];?>
                }
            </style>
			<?php
		}

		/**
		 * Wrapper admin editor.
		 *
		 * @since 3.0.0
		 */
		public function wrapper_editor() {
			$post_type = get_post_type();

			if ( in_array( $post_type, array( LP_COURSE_CPT, LP_QUIZ_CPT, LP_QUESTION_CPT ) ) ) {
				learn_press_admin_view( 'editor-wrapper', array( 'post_type' => $post_type ) );
			}
		}

		public function notice_required_permalink() {

			if ( current_user_can( 'manage_options' ) ) {

				if ( ! get_option( 'permalink_structure' ) ) {
					learn_press_add_notice( sprintf( __( 'LearnPress requires permalink option <strong>Post name</strong> is enabled. Please enable it <a href="%s">here</a> to ensure that all functions work properly.', 'learnpress' ), admin_url( 'options-permalink.php' ) ), 'error' );
				}
			}
		}


		/**
		 * Add notice for missing pages.
		 *
		 * @return mixed
		 */
		public function notice_setup_pages() {

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$missing_pages = array();
			$pages         = apply_filters( 'learn-press/required-pages', array(
				'profile'  => array(
					'title'    => __( 'Profile Page', 'learnpress' ),
					'settings' => admin_url( 'admin.php?page=learn-press-settings&tab=profile' )
				),
				'checkout' => array(
					'title'    => __( 'Checkout Page', 'learnpress' ),
					'settings' => admin_url( 'admin.php?page=learn-press-settings&tab=payments' )
				)
			) );

			foreach ( $pages as $id => $page ) {

				if ( ( $page_id = learn_press_get_page_id( $id ) ) && get_post( $page_id ) ) {
					continue;
				}
				$missing_pages[ $id ] = $page;
			}

			if ( ! $missing_pages ) {
				return;
			}

			$pages = array();

			foreach ( $missing_pages as $id => $page ) {
				$pages[] = __( wp_kses( '<a href="' . $page['settings'] . '">' . $page['title'] . '</a>', array(
					'a' => array( 'href' => array() )
				) ), 'learnpress' );
			}

			$notice = sprintf( __( 'The following required page(s) are currently missing: %s.', 'learnpress' ), join( ', ', $pages ) );
			$notice .= sprintf( __( 'To ensure all functions work properly, please click <a class="button" id="learn-press-create-pages" href="%s">here</a> to create and set it up automatically.', 'learnpress' ), esc_url( wp_nonce_url( admin_url( 'admin.php?lp-ajax=create-pages' ), 'create-pages' ) ) );

			learn_press_add_notice( $notice, 'error' );
		}

		/**
		 * Notices outdated templates.
		 */
		public function notice_outdated_templates() {
			if ( current_user_can( 'manage_options' ) ) {
				$page = '';
				$tab  = '';
				if ( ! empty( $_REQUEST['page'] ) ) {
					$page = $_REQUEST['page'];
				}

				if ( ! empty( $_REQUEST['tab'] ) ) {
					$tab = $_REQUEST['tab'];
				}

				if ( $page == 'learn-press-tools' && $tab == 'templates' ) {
					return;
				}

				if ( LP_Outdated_Template_Helper::detect_outdated_template() ) {
					learn_press_admin_view( 'html-admin-notice-templates' );
				}
			}
		}

		/**
		 * Update option data user rated.
		 */
		public function rated() {
			update_option( 'learn_press_message_user_rated', 'yes' );
			die();
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
			if ( isset( $current_screen->id ) && apply_filters( 'learn_press_display_admin_footer_text', in_array( $current_screen->id, $pages ) ) ) {
				if ( ! get_option( 'learn_press_message_user_rated' ) ) {
					$footer_text = sprintf( __( 'If you like <strong>LearnPress</strong> please leave us a %s&#9733;&#9733;&#9733;&#9733;&#9733;%s rating. A huge thanks from LearnPress team for your generous.', 'learnpress' ), '<a href="https://wordpress.org/support/plugin/learnpress/reviews/?filter=5#postform" target="_blank" class="lp-rating-link" data-rated="' . esc_attr__( 'Thanks :)', 'learnpress' ) . '">', '</a>' );
					ob_start(); ?>
                    <script type="text/javascript">
                        jQuery(function ($) {
                            var $ratingLink = $('a.lp-rating-link').click(function (e) {
                                $.ajax({
                                    url: '<?php echo admin_url( 'admin-ajax.php' );?>',
                                    data: {
                                        action: 'learn_press_rated'
                                    },
                                    success: function () {
                                        $ratingLink.parent().html($ratingLink.data('rated'));
                                    }
                                });
                            });
                        })

                    </script>
					<?php
					echo ob_get_clean();
				}
			}

			return $footer_text;
		}

		function delete_user_form() {
			// What should be displayed here?
		}

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
		 * @since 3.0.10
		 *
		 * @param array  $data
		 * @param string $notice
		 *
		 * @return array
		 */
		public function on_dismissed_notice_response( $data, $notice ) {
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
						$data['error'] = __( 'Fail while joining newsletter! Please try again!', 'learnpress' );
					}

					$url      = 'https://thimpress.com/mailster/subscribe';
					$response = wp_remote_post( $url, array(
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
							'cookies'     => array()
						)
					);
					if ( is_wp_error( $response ) ) {
						$error_message   = $response->get_error_message();
						$data['message'] = __( 'Something went wrong: ', 'learnpress' ) . $error_message;
					} else {
						$data['message'] = __( 'Thank you for subscribing! Please check and click the confirmation link from the email we\'ve just sent to your mail box.', 'learnpress' );
					}
			}

			return $data;
		}

		/**
		 * Include all classes and functions used for admin
		 */
		public function includes() {
			//crazy tu
			// Common function used in admin
			include_once 'lp-admin-functions.php';
			include_once 'lp-admin-actions.php';
			require_once LP_PLUGIN_PATH . 'inc/background-process/class-lp-background-query-items.php';
			include_once 'class-lp-admin-assets.php';
			include_once 'class-lp-admin-dashboard.php';
			include_once 'class-lp-admin-tools.php';
			include_once 'class-lp-admin-ajax.php';
			include_once 'editor/class-lp-admin-editor.php';
			include_once 'class-lp-admin-menu.php';
			include_once 'class-lp-meta-box-tabs.php';
			include_once 'helpers/class-lp-outdated-template-helper.php';
			include_once 'helpers/class-lp-plugins-helper.php';
			include_once 'class-lp-modal-search-items.php';
			include_once 'class-lp-modal-search-users.php';
			include_once 'class-lp-setup-wizard.php';
			include_once 'class-lp-updater.php';
			include_once 'class-lp-install-sample-data.php';
			include_once 'class-lp-reset-data.php';
		}

		/**
		 * Get single instance of self
		 *
		 * @since 3.0.0
		 *
		 * @return bool|LP_Admin
		 */
		public static function instance() {
			static $instance = false;
			if ( ! $instance ) {
				$instance = new self();
			}

			return $instance;
		}
	}
} // End class LP_Admin

return LP_Admin::instance();