<?php
/**
 * Template hooks Course Builder.
 *
 * @since 4.3.x
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder;

use Exception;
use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\CourseBuilder\Course\BuilderCourseTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Profile;
use LP_Settings;
use Throwable;

class CourseBuilderTemplate {
	use Singleton;

	public function init() {
		//add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
		add_action( 'learn-press/course-builder/layout', [ $this, 'layout' ] );
		// Show link to Course Builder in admin bar
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 80 );
		// Hide admin bar for instructor (not admin)
		add_filter( 'show_admin_bar', [ $this, 'hide_admin_bar_for_instructor' ] );
		// Dequeue theme styles on Course Builder page (must run during wp_head, not after)
		//add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_theme_styles' ], 9999 );
	}

	/**
	 * Hide admin bar for instructor users (not administrators).
	 *
	 * @param bool $show_admin_bar
	 *
	 * @return bool
	 * @since 4.3.0
	 */
	public function hide_admin_bar_for_instructor( bool $show_admin_bar ): bool {
		if ( ! is_user_logged_in() ) {
			return $show_admin_bar;
		}

		$user = UserModel::find( get_current_user_id(), true );
		if ( ! $user ) {
			return $show_admin_bar;
		}

		// Hide admin bar if user is instructor but not admin
		if ( $user->is_instructor() && ! current_user_can( ADMIN_ROLE ) ) {
			return false;
		}

		return $show_admin_bar;
	}

	/**
	 * Allow callback for AJAX.
	 * @use self::render_html_comments
	 *
	 * @param array $callbacks
	 *
	 * @return array
	 */
	/*public function allow_callback( array $callbacks ): array {
		$callbacks[] = get_class( $this ) . ':sidebar';

		return $callbacks;
	}*/

	/**
	 * Layout for Course Builder.
	 *
	 * @since 4.3.6
	 * @version 1.0.0
	 */
	public function layout() {
		try {
			// Enqueue assets(js,css) for Course Builder
			//$this->enqueue_assets();

			// Check permission
			$user_id   = get_current_user_id();
			$userModel = UserModel::find( $user_id, true );
			if ( ! $userModel || ! $userModel->is_instructor() ) {
				throw new Exception( __( "Sorry, you don't have permission to access Course Builder", 'learnpress' ) );
			}

			$data = [
				'userModel' => $userModel,
			];

			$layout = [
				'wrapper'     => '<div class="learn-press-course-builder">',
				'header'      => $this->html_header( $data ),
				'body'        => '<div class="lp-cb-body">',
				'sidebar'     => $this->html_sidebar( $data ),
				'content'     => $this->html_content( $data ),
				'body_end'    => '</div>',
				'wrapper_end' => '</div>',
			];

			echo Template::combine_components( $layout );
		} catch ( Throwable $e ) {
			Template::print_message(
				wp_kses_post( $e->getMessage() ),
				'error'
			);
		}
	}

	/**
	 * Enqueue scripts, styles and localize data for Course Builder.
	 *
	 * @since 4.3.x
	 * @version 1.0.0
	 */
	protected function enqueue_assets() {}

	/**
	 * Auto-detect and dequeue all theme/child-theme stylesheets.
	 * Prevents theme CSS from interfering with Course Builder styles.
	 *
	 * Hooked to `wp_enqueue_scripts` at priority 9999 so it runs DURING wp_head(),
	 * after themes have enqueued their styles but before they are printed.
	 *
	 * Only removes styles whose source URL is within the theme or child-theme directory.
	 * WP core styles, plugin styles, and other assets remain untouched.
	 *
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	/*public function dequeue_theme_styles() {
		global $wp_styles, $wp_scripts;

		if ( ! LP_Page_Controller::is_page_course_builder() ) {
			return;
		}

		$allowed_styles = apply_filters(
			'learn-press/course-builder/allowed-styles',
			[
				'dashicons',
				'admin-bar',
				'buttons',
				'media-views',
				'wp-components',
				'wp-block-library',
				'wp-editor',
				'wp-edit-post',
				'wp-block-editor',
				'wp-components',
				'wp-editor',
				'wp-nux',
				'wp-notices',
			]
		);

		$allowed_scripts = apply_filters(
			'learn-press/course-builder/allowed-scripts',
			[
				'jquery',
				'jquery-core',
				'jquery-migrate',
				'jquery-ui-core',
				'jquery-ui-widget',
				'wp-api-fetch',
				'wp-i18n',
				'wp-components',
				'wp-element',
				'react',
				'react-dom',
				'wp-polyfill',
				'wp-hooks',
				'lodash',
				'moment',
				'heartbeat',
				'wp-data',
				'wp-core-data',
				'wp-url',
				'wp-api',
				'wp-block-editor',
				'wp-blocks',
				'wp-media-utils',
				'wp-compose',
				'regenerator-runtime',
				'wp-a11y',
			]
		);

		if ( ! empty( $wp_styles->queue ) ) {
			foreach ( $wp_styles->queue as $handle ) {
				if ( ! in_array( $handle, $allowed_styles ) && strpos( $handle, 'lp-' ) !== 0 && strpos( $handle, 'learn-press' ) !== 0 && strpos( $handle, 'learnpress' ) !== 0 ) {
					wp_dequeue_style( $handle );
				}
			}
		}

		if ( ! empty( $wp_scripts->queue ) ) {
			foreach ( $wp_scripts->queue as $handle ) {
				if ( ! in_array( $handle, $allowed_scripts ) && strpos( $handle, 'lp-' ) !== 0 && strpos( $handle, 'learn-press' ) !== 0 && strpos( $handle, 'learnpress' ) !== 0 ) {
					wp_dequeue_script( $handle );
				}
			}
		}
	}*/

	/**
	 * Header with logo and user profile
	 *
	 * @param array $data
	 *
	 * @return string
	 * @throws Exception
	 * @version 1.0.0
	 * @since 4.3.6
	 */
	protected function html_header( array $data = [] ): string {
		/** @var UserModel $userModel */
		$userModel = $data['userModel'] ?? false;
		if ( ! $userModel ) {
			return '';
		}

		$avatar       = $userModel->get_avatar_url();
		$display_name = $userModel->get_display_name();
		$profile      = LP_Profile::instance( $userModel->get_id() );
		$profile_url  = $profile->get_tab_link();
		$logout_url   = wp_logout_url( home_url() );
		$logo_id      = absint( LP_Settings::get_option( 'course_builder_logo_id', 0 ) );

		if ( $logo_id ) {
			$custom_logo = wp_get_attachment_image(
				$logo_id,
				'full',
				false,
				[
					'class'    => 'lp-cb-top-header__logo-image',
					'alt'      => __( 'Course Builder', 'learnpress' ),
					'loading'  => 'eager',
					'decoding' => 'async',
				]
			);
		}

		$header = [
			'wrapper'     => '<header class="lp-cb-top-header">',
			'logo'        => sprintf(
				'<div class="lp-cb-top-header__logo">
					<a href="%s">%s</a>
				</div>',
				esc_url( CourseBuilder::get_link_course_builder() ),
				$custom_logo ?? wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-logo-course-builder.svg' ),
			),
			'user'        => sprintf(
				'<div class="lp-cb-top-header__user">
					<div class="lp-cb-top-header__user-avatar">
						<img src="%s" class="lp-cb-top-header__user-avatar-image">
						<span class="lp-cb-top-header__online-dot"></span>
					</div>
					<div class="lp-cb-top-header__user-info">
						<span class="lp-cb-top-header__user-name">%s</span>
						<a href="%s" class="lp-cb-top-header__user-link" target="_blank">%s</a>
					</div>
					<a href="%s" class="lp-cb-top-header__logout" title="%s">
						%s
					</a>
				</div>',
				$avatar,
				esc_html( $display_name ),
				esc_url( $profile_url ),
				__( 'View Profile', 'learnpress' ),
				esc_url( $logout_url ),
				__( 'Logout', 'learnpress' ),
				wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-logout.svg' ),
			),
			'wrapper_end' => '</header>',
		];

		return Template::combine_components( $header );
	}

	/**
	 * HTML Sidebar
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function html_sidebar( array $data = [] ): string {
		$userModel = $data['userModel'] ?? false;
		if ( ! $userModel ) {
			return '';
		}

		$tabs        = CourseBuilder::get_menus_arr();
		$nav_content = '';
		$is_admin    = current_user_can( ADMIN_ROLE );

		$tabs = array_filter(
			$tabs,
			static function ( array $tab ) use ( $is_admin ): bool {
				if ( ! empty( $tab['admin_only'] ) && ! $is_admin ) {
					return false;
				}

				return true;
			}
		);

		usort(
			$tabs,
			static function ( array $a, array $b ): int {
				$a_priority = isset( $a['priority'] ) && is_numeric( $a['priority'] ) ? (int) $a['priority'] : PHP_INT_MAX;
				$b_priority = isset( $b['priority'] ) && is_numeric( $b['priority'] ) ? (int) $b['priority'] : PHP_INT_MAX;

				return $a_priority <=> $b_priority;
			}
		);

		foreach ( $tabs as $tab ) {
			$slug         = $tab['slug'];
			$nav_item     = $this->html_nav_item_main( $slug, $tab );
			$nav_content .= $nav_item;
		}

		$nav = [
			'wrapper'     => '<ul class="lp-cb-sidebar__nav">',
			'content'     => $nav_content,
			'wrapper_end' => '</ul>',
		];

		$toggle = sprintf(
			'<button type="button" class="lp-cb-sidebar__toggle" aria-label="%s" title="%s">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M5 3C4.46957 3 3.96086 3.21071 3.58579 3.58579C3.21071 3.96086 3 4.46957 3 5V19C3 19.5304 3.21071 20.0391 3.58579 20.4142C3.96086 20.7893 4.46957 21 5 21H19C19.5304 21 20.0391 20.7893 20.4142 20.4142C20.7893 20.0391 21 19.5304 21 19V5C21 4.46957 20.7893 3.96086 20.4142 3.58579C20.0391 3.21071 19.5304 3 19 3H5ZM10 5H19V19H10V5ZM8 5H5V19H8V5Z" fill="currentColor"/>
					</svg>
				</button>',
			esc_attr__( 'Toggle Sidebar', 'learnpress' ),
			esc_attr__( 'Toggle Sidebar', 'learnpress' )
		);

		$sidebar = [
			'wrapper'     => '<aside id="lp-course-builder-sidebar" class="lp-cb-sidebar">',
			'nav'         => Template::combine_components( $nav ),
			'toggle'      => $toggle,
			'footer'      => $this->sidebar_footer(),
			'wrapper_end' => '</aside>',
		];

		return Template::combine_components( $sidebar );
	}

	/**
	 * HTML main content area
	 *
	 * @param array $data
	 *
	 * @return string
	 * @since 4.3.6
	 * @version 1.0.0
	 */
	public function html_content( array $data = [] ): string {
		$userModel = $data['userModel'] ?? false;
		if ( ! $userModel ) {
			return '';
		}

		$menu_current = CourseBuilder::get_menu_current();

		ob_start();
		do_action( "learn-press/course-builder/{$menu_current}/layout", $data );
		$content = ob_get_clean();

		$output = [
			'wrapper'     => '<div id="lp-course-builder-content" class="lp-cb-main">',
			'content'     => $content,
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $output );
	}

	/**
	 * Sidebar footer with "Back to Dashboard" link
	 *
	 * @return string
	 * @since 4.3.0
	 */
	protected function sidebar_footer() {
		$is_cb_admin_mode = LP_Settings::get_option( 'enable_cb_admin_mode', 'no' ) === 'yes';
		$is_admin         = current_user_can( ADMIN_ROLE );
		$is_instructor    = current_user_can( LP_TEACHER_ROLE );
		$dashboard_url    = admin_url();

		$footer = [
			'wrapper' => '<div class="lp-cb-sidebar__footer">',
		];

		// Hide "Back to WordPress" for instructors when CB admin mode is on
		// Admins always see this link
		$hide_back_link = $is_cb_admin_mode && $is_instructor && ! $is_admin;

		if ( ! $hide_back_link ) {
			$back_to_wp_text = __( 'Back to WordPress', 'learnpress' );

			$footer['back'] = sprintf(
				'<a href="%s" class="lp-cb-sidebar__item lp-cb-sidebar__back" title="%s" aria-label="%s">
					<span class="dashicons dashicons-wordpress"></span>
					<span class="lp-cb-sidebar__item-title">%s</span>
				</a>',
				esc_url( $dashboard_url ),
				esc_attr( $back_to_wp_text ),
				esc_attr( $back_to_wp_text ),
				esc_html( $back_to_wp_text )
			);
		}

		$footer['wrapper_end'] = '</div>';

		return Template::combine_components( $footer );
	}

	/**
	 * Render main navigation item (persistent sidebar)
	 *
	 * @param string $slug
	 * @param array $tab_data
	 *
	 * @return string
	 * @since 4..0
	 */
	protected function html_nav_item_main( $slug, $tab_data ) {
		$tab_current = CourseBuilder::get_menu_current();
		$is_active   = $slug === $tab_current;
		$classes     = [ 'lp-cb-sidebar__item', $slug ];

		if ( $is_active ) {
			$classes[] = 'is-active';
		}

		$icon  = isset( $tab_data['icon'] ) ? $tab_data['icon'] : '';
		$title = $tab_data['title'];
		$link  = CourseBuilder::get_tab_link( $slug );

		$item = [
			'wrapper'     => sprintf( '<li class="%s">', implode( ' ', $classes ) ),
			'content'     => sprintf(
				'<a href="%s" title="%s" aria-label="%s">
					%s
					<span class="lp-cb-sidebar__item-title">%s</span>
				</a>',
				esc_url( $link ),
				esc_attr( $title ),
				esc_attr( $title ),
				$icon,
				esc_html( $title )
			),
			'wrapper_end' => '</li>',
		];

		return Template::combine_components( $item );
	}

	public function html_btn_add_new() {
		$tab_current = CourseBuilder::get_menu_current();
		$map_title   = [
			'courses'   => __( 'Course', 'learnpress' ),
			'lessons'   => __( 'Lesson', 'learnpress' ),
			'quizzes'   => __( 'Quiz', 'learnpress' ),
			'questions' => __( 'Question', 'learnpress' ),
		];

		$map_type = [
			'lessons'   => 'lesson',
			'quizzes'   => 'quiz',
			'questions' => 'question',
		];

		$title         = isset( $map_title[ $tab_current ] ) ? $map_title[ $tab_current ] : '';
		$type          = isset( $map_type[ $tab_current ] ) ? $map_type[ $tab_current ] : '';
		$add_new       = 'data-add-new-' . esc_attr( $type );
		$template_html = '';
		$template_attr = '';

		$btn_add_new = sprintf( '<button %s class="lp-button cb-btn-add-new">', $add_new );
		$btn_close   = '</button>';

		if ( 'lessons' === $tab_current ) {
			$template_id   = 'lp-tmpl-builder-popup-lesson-tab-new';
			$template_attr = sprintf(
				' data-template="#%1$s" data-popup-type="lesson" data-popup-id="0"',
				esc_attr( $template_id )
			);
			$template_html = sprintf(
				'<script type="text/template" id="%1$s"><div class="lp-builder-popup-overlay"></div><div class="lp-builder-popup lp-builder-popup--loading">%2$s</div></script>',
				esc_attr( $template_id ),
				TemplateAJAX::load_content_via_ajax(
					[
						'id_url'                  => 'builder-popup-lesson-tab-new',
						'lesson_id'               => 0,
						'html_no_load_ajax_first' => sprintf(
							'<div class="lp-builder-popup__loader"><div class="lp-loading-circle"></div><span>%s</span></div>',
							esc_html__( 'Loading...', 'learnpress' )
						),
					],
					[
						'class'  => BuilderPopupTemplate::class,
						'method' => 'render_lesson_popup',
					]
				)
			);
		}

		if ( 'courses' === $tab_current ) {
			$btn_add_new = sprintf(
				'<a href="%s" class="lp-button cb-btn-add-new">',
				esc_url( CourseBuilder::get_link_add_new( 'courses' ) )
			);
			$btn_close   = '</a>';
		}

		if ( 'quizzes' === $tab_current ) {
			$btn_add_new = sprintf(
				'<a href="%s" class="lp-button cb-btn-add-new">',
				esc_url( CourseBuilder::get_link_add_new( 'quizzes' ) )
			);
			$btn_close   = '</a>';
		}

		if ( 'questions' === $tab_current ) {
			$btn_add_new = sprintf(
				'<a href="%s" class="lp-button cb-btn-add-new">',
				esc_url( CourseBuilder::get_link_add_new( 'questions' ) )
			);
			$btn_close   = '</a>';
		}

		$btn = [
			'wrapper'     => str_replace( '>', $template_attr . '>', $btn_add_new ),
			'content'     => sprintf( '%s %s', __( 'Add New', 'learnpress' ), $title ),
			'wrapper_end' => $btn_close,
			'template'    => $template_html,
		];
		$btn = apply_filters( 'learn-press/course-builder/button-add-new', $btn, $tab_current, $type );

		return Template::combine_components( $btn );
	}

	/**
	 * Show link to Course Builder in admin bar
	 */
	public function add_admin_bar_menu( $wp_admin_bar ) {
		$href  = CourseBuilder::get_link_course_builder();
		$title = esc_html__( 'Course Builder', 'learnpress' );

		// Check if on frontend single course page
		if ( is_singular( LP_COURSE_CPT ) && get_the_ID() ) {
			$title = esc_html__( 'Edit with Course Builder', 'learnpress' );
			$href  = BuilderCourseTemplate::instance()->get_link_edit( get_the_ID() );
		}

		// Check if on admin edit course page (post.php or post-new.php)
		if ( is_admin() ) {
			global $post, $pagenow;
			if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
				$post_type = '';
				if ( isset( $_GET['post_type'] ) ) {
					$post_type = sanitize_text_field( wp_unslash( $_GET['post_type'] ) );
				} elseif ( isset( $_GET['post'] ) ) {
					$post_id   = absint( $_GET['post'] );
					$post_type = get_post_type( $post_id );
				} elseif ( $post && isset( $post->post_type ) ) {
					$post_type = $post->post_type;
				}

				if ( LP_COURSE_CPT === $post_type ) {
					$title = esc_html__( 'Edit with Course Builder', 'learnpress' );
					if ( isset( $_GET['post'] ) ) {
						$href = BuilderCourseTemplate::instance()->get_link_edit( $_GET['post'] );
					} else {
						$href = CourseBuilder::get_link_add_new( 'courses' );
					}
				}
			}
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => 'lp-course-builder',
				'title' => '<svg width="26" height="18" viewBox="0 0 69 48" fill="currentColor" xmlns="http://www.w3.org/2000/svg" style="vertical-align: text-bottom;">
								<path d="M50.9291 24.84L50.2591 21.69C50.5491 21.47 50.7391 21.11 50.7391 20.71C50.7391 20.03 50.1891 19.48 49.4991 19.48C48.8091 19.48 48.2591 20.03 48.2591 20.71C48.2591 21.11 48.4491 21.46 48.7391 21.69L48.0691 24.84C47.9891 25.21 48.1791 25.56 48.4791 25.56H50.5091C50.8091 25.56 50.9991 25.21 50.9191 24.84H50.9291Z" fill="currentColor"></path>
								<path d="M50.8892 13.24H50.0092V20.92H48.9892V13.21L46.2992 13.18C43.3992 8.47 38.1692 5.34 32.2192 5.34C30.8292 5.34 29.4792 5.51 28.1892 5.83L24.9492 2.53C24.7992 2.38 24.8892 2.14 25.0992 2.11L40.7292 0L48.5492 9.68L49.7592 11.17L51.0992 12.83C51.2292 12.99 51.1092 13.24 50.8992 13.23L50.8892 13.24Z" fill="currentColor"></path>
								<path d="M44.7392 13.1701L43.1392 17.1401C40.7292 13.1101 36.3092 10.4101 31.2592 10.4101C29.9992 10.4101 28.7892 10.5801 27.6292 10.8901L29.2292 6.93014C30.1992 6.74014 31.1992 6.64014 32.2192 6.64014C37.4092 6.64014 41.9892 9.22014 44.7392 13.1701Z" fill="currentColor"></path>
								<path d="M25.4692 23.6899V23.8499C25.4692 23.9599 25.4592 24.0699 25.4592 24.1899C25.4592 24.0199 25.4592 23.8499 25.4692 23.6899Z" fill="currentColor"></path>
								<path d="M43.3692 24.2601C43.3692 25.2901 43.2392 26.2901 42.9892 27.2501C42.3892 29.5401 41.1192 31.5601 39.4092 33.1001C37.9992 34.3601 36.2892 35.2901 34.3892 35.7701C33.4492 36.0101 32.4592 36.1401 31.4392 36.1401H27.9692V35.8001C27.9692 35.6601 27.9792 35.5301 27.9992 35.3901C28.2092 33.2201 29.5592 31.3901 31.4492 30.4901C31.6092 30.4101 31.7692 30.3401 31.9292 30.2801C32.1692 30.1901 32.4192 30.1201 32.6692 30.0601C32.7292 30.0501 32.7992 30.0301 32.8592 30.0201C35.4892 29.4101 37.4492 27.0601 37.4492 24.2501C37.4492 21.2701 35.2392 18.8001 32.3592 18.3901C32.0792 18.3501 31.7892 18.3301 31.4992 18.3301C31.2392 18.3301 30.9992 18.3501 30.7492 18.3801C28.2992 18.6901 26.2592 20.4801 25.6492 22.8201C25.5292 23.2901 25.4792 23.7701 25.4792 24.2401V42.7601C25.1492 45.6001 22.8092 47.8301 19.9092 47.9901H19.5592H16.4792C18.3092 46.6501 19.5092 44.5001 19.5092 42.0701V23.7001C19.5092 23.5601 19.5192 23.4301 19.5392 23.3001C19.6292 22.2301 19.8492 21.1901 20.2092 20.2201C20.4592 19.5401 20.7692 18.8801 21.1292 18.2501C21.7592 17.1801 22.5492 16.2201 23.4692 15.4001C24.6192 14.3701 25.9792 13.5601 27.4692 13.0401C28.7092 12.6001 30.0492 12.3701 31.4392 12.3701C32.4792 12.3701 33.4792 12.5001 34.4392 12.7501C36.6892 13.3301 38.6792 14.5401 40.2092 16.1901C40.2592 16.2401 40.3092 16.3001 40.3592 16.3501C40.3592 16.3501 40.3592 16.3501 40.3692 16.3601C41.9592 18.1401 43.0192 20.4101 43.2992 22.9001C43.3293 23.1701 43.3492 23.4501 43.3692 23.7201C43.3692 23.8901 43.3692 24.0601 43.3692 24.2401V24.2601Z" fill="currentColor"></path>
								<path d="M25.4692 23.6899V23.8499C25.4692 23.9599 25.4592 24.0699 25.4592 24.1899C25.4592 24.0199 25.4592 23.8499 25.4692 23.6899Z" fill="currentColor"></path>
								<path d="M6.13917 42.0799H18.0692C18.0592 45.3499 15.3792 47.9999 12.0892 47.9999H6.11917C2.83917 47.9999 0.16917 45.3499 0.14917 42.0799V12.3799H0.18917C3.46917 12.3799 6.13917 15.0299 6.13917 18.2999V42.0799Z" fill="currentColor"></path>
								<path d="M67.6736 23.2845L63.9566 19.5676C63.1998 18.8108 61.9757 18.8108 61.219 19.5676L59.0266 21.7599L65.4812 28.2145L67.6736 26.0222C68.4303 25.2654 68.4303 24.0413 67.6736 23.2845Z" fill="currentColor"></path>
								<path d="M45.4722 45.0411L46.3513 45.9203L42.078 46.7883C42.078 46.7883 42.0557 46.3432 41.477 45.7534C40.8983 45.1747 40.442 45.1524 40.442 45.1524L41.3101 40.879L42.1892 41.7582L59.817 24.1305L58.2256 22.5391L40.5979 40.1668L39.2513 46.7883C39.1066 47.4894 39.7298 48.1126 40.4309 47.968L47.0525 46.6214L64.6802 28.9937L63.0888 27.4023L45.4611 45.03L45.4722 45.0411Z" fill="currentColor"></path>
								<path d="M60.6182 24.9316L42.9905 42.5594L44.6932 44.2621L62.3209 26.6343L60.6182 24.9316Z" fill="currentColor"></path>
							</svg>
							<span class="ab-label">' . $title . '</span>',
				'href'  => $href,
			)
		);
	}
}
