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
		 *  Constructor
		 */
		public function __construct() {
			$this->includes();
			add_action( 'delete_user', array( $this, 'delete_user_data' ) );
			add_action( 'delete_user_form', array( $this, 'delete_user_form' ) );
			add_action( 'wp_ajax_learn_press_rated', array( $this, 'rated' ) );
			add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );

			add_action( 'admin_notices', array( $this, 'notice_outdated_templates' ) );
			add_action( 'admin_notices', array( $this, 'notice_setup_page' ) );
			add_action( 'admin_notices', array( $this, 'notice_required_permalink' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );

			add_action( 'edit_form_after_editor', array( $this, 'wrapper_editor' ), - 10 );
			add_action( 'admin_head', array( $this, 'admin_colors' ) );
			add_filter( 'admin_body_class', array( $this, 'body_class' ) );

			add_action( 'admin_init', array( $this, 'admin_redirect' ) );
			add_filter( 'manage_users_custom_column', array( $this, 'users_custom_column' ), 10, 3 );
			add_filter( 'views_users', array( $this, 'views_users' ), 10, 1 );
			add_filter( 'user_row_actions', array( $this, 'user_row_actions' ), 10, 2 );
			LP_Request::register( 'lp-action', array( $this, 'filter_users' ) );
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
			if ( $pending_request = LP_User_Factory::get_pending_requests() ) {
				if ( in_array( $user->ID, $pending_request ) ) {
					$actions['accept'] = sprintf( '<a href="' . admin_url( 'users.php?lp-action=accept-request&user_id=' . $user->ID ) . '">%s</a>', _x( 'Accept', 'pending-request', 'learnpress' ) );
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
			switch ( $action ) {
				case 'accept-request':
					if ( ( $user_id = LP_Request::get_int( 'user_id' ) ) && $user = get_user_by( 'id', $user_id ) ) {
						$be_teacher = new WP_User( $user_id );
						$be_teacher->set_role( LP_TEACHER_ROLE );
						delete_user_meta( $user_id, '_requested_become_teacher' );

						do_action( 'learn-press/user-become-a-teacher', $user_id );

						wp_redirect( admin_url( 'users.php?lp-action=accepted-request&user_id=' . $user_id ) );
						exit();
					}
					break;
				case 'deny-request':
					break;
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
			if ( 'yes' === get_option( 'learn_press_install' ) ) {
				learn_press_admin_view( 'setup/notice-setup' );
			}

			if ( ( 'accepted-request' === LP_Request::get( 'lp-action' ) ) && ( $user_id = LP_Request::get_int( 'user_id' ) ) && get_user_by( 'id', $user_id ) ) {
				if ( ! current_user_can( 'promote_user', $user_id ) ) {
					wp_die( __( 'Sorry, you are not allowed to edit this user.' ) );
				}
				echo '<div class="updated notice">' . __( 'User has accepted to become a teacher.', 'learnpress' ) . '</div>';
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

		public function wrapper_editor() {
			if ( LP_COURSE_CPT == get_post_type() ) {
				learn_press_admin_view( 'course/editor-wrapper' );
			} elseif ( LP_QUIZ_CPT == get_post_type() ) {
				learn_press_admin_view( 'quiz/editor-wrapper' );
			} elseif ( LP_QUESTION_CPT == get_post_type() ) {
				learn_press_admin_view( 'question/editor-wrapper' );
			}
		}

		public function notice_required_permalink() {

			if ( current_user_can( 'manage_options' ) ) {

				if ( ! get_option( 'permalink_structure' ) ) {
					learn_press_add_notice( sprintf( __( 'LearnPress requires permalink option <strong>Post name</strong> is enabled. Please enable it <a href="%s">here</a> to ensure that all functions work properly.', 'learnpress' ), admin_url( 'options-permalink.php' ) ), 'error' );
				}
			}
		}

		public function notice_setup_page() {

			$args = array(
				array(
					'name_option' => 'learn_press_profile_page_id',
					'id'          => 'lp-admin-warning-profile',
					'title'       => __( 'Profile Page', 'learnpress' ),
					'url'         => admin_url( 'admin.php?page=learn-press-settings&tab=profile' )
				),
				array(
					'name_option' => 'learn_press_checkout_page_id',
					'id'          => 'lp-admin-warning-checkout',
					'title'       => __( 'Checkout Page', 'learnpress' ),
					'url'         => admin_url( 'admin.php?page=learn-press-settings&tab=payments' )
				),
			);

			if ( current_user_can( 'manage_options' ) ) {

				$notice = esc_html__( 'The following required page(s) are currently missing: ', 'learnpress' );
				$count  = 0;
				$pages  = array();

				foreach ( $args as $key => $arg ) {
					$item_page_id   = get_option( $arg['name_option'] );
					$item_transient = get_transient( $arg['id'] );
					$item_page      = get_post( $item_page_id );

					if ( empty( $item_transient ) && ( empty( $item_page_id ) || empty( $item_page ) ) ) {
						$count ++;
						$pages[] = array(
							'url'   => $arg['url'],
							'title' => $arg['title']
						);

					}
				}

				foreach ( $pages as $key => $page ) {
					if ( $key == ( $count - 1 ) && $count != 1 ) {
						$notice .= esc_html__( ' and ', 'learnpress' );
					}
					$notice .= __( wp_kses( '<a href="' . $page['url'] . '">' . $page['title'] . '</a>', array( 'a' => array( 'href' => array() ) ) ), 'learnpress' );
				}

				$notice .= '.' . esc_html__( ' Please click to the link to set it up, ensure all functions work properly.', 'learnpress' );

				return $count ? learn_press_add_notice( $notice, 'error' ) : '';
			}

			return '';
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
					$footer_text = sprintf( __( 'If you like <strong>LearnPress</strong> please leave us a %s&#9733;&#9733;&#9733;&#9733;&#9733;%s rating. A huge thanks in advance!', 'learnpress' ), '<a href="https://wordpress.org/support/plugin/learnpress/reviews/?filter=5#postform" target="_blank" class="lp-rating-link" data-rated="' . esc_attr__( 'Thanks :)', 'learnpress' ) . '">', '</a>' );
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
			include_once 'class-lp-admin-menu.php';
			include_once 'class-lp-meta-box-tabs.php';
			include_once 'helpers/class-lp-outdated-template-helper.php';
			include_once 'helpers/class-lp-plugins-helper.php';
			include_once 'class-lp-modal-search-items.php';
			include_once 'class-lp-modal-search-users.php';
			include_once 'class-lp-setup-wizard.php';
			include_once 'class-lp-updater.php';
			include_once 'class-lp-install-sample-data.php';
		}
	}
} // End class LP_Admin
return new LP_Admin();