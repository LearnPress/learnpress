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
use WP_User;

class CourseBuilderTemplate {
	use Singleton;

	const MENU_COURSES   = 'courses';
	const MENU_LESSONS   = 'lessons';
	const MENU_QUIZZES   = 'quizzes';
	const MENU_QUESTIONS = 'questions';
	const MENU_SETTINGS  = 'settings';

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
		// Check enable hide admin bar for instructor
		if ( ! LP_Settings::get_option( 'hide_admin_bar_for_instructor', 'no' ) ) {
			return $show_admin_bar;
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

		$sidebar_toggle_icon = wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-cb-sidebar-toggle.svg' );
		$toggle              = sprintf(
			'<button type="button" class="lp-cb-sidebar__toggle" aria-label="%s" title="%s">
					%s
				</button>',
			esc_attr__( 'Toggle Sidebar', 'learnpress' ),
			esc_attr__( 'Toggle Sidebar', 'learnpress' ),
			$sidebar_toggle_icon
		);

		$sidebar = [
			'wrapper'     => '<aside id="lp-course-builder-sidebar" class="lp-cb-sidebar">',
			'nav'         => Template::combine_components( $nav ),
			'toggle'      => $toggle,
			'footer'      => $this->sidebar_footer( $data ),
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

		//Todo: new way - Switch layout display by menu, via model
		switch ( $menu_current ) {
			case self::MENU_COURSES:
				//BuilderCourseTemplate::instance()->layout( $data );
				break;
			default:
				//$content = apply_filters( "learn-press/course-builder/content/layout", $menu_current, $data );
				break;
		}

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
	 * @param array $data
	 *
	 * @return string
	 * @since 4.3.0
	 */
	protected function sidebar_footer( array $data = [] ): string {
		$userModel = $data['userModel'] ?? false;
		if ( ! $userModel instanceof UserModel ) {
			return '';
		}

		$hide_instructor_access_admin_screen = LP_Settings::is_hide_instructor_access_admin_screen();
		$wp_user = new WP_User( $userModel );
		$is_instructor    = user_can( $wp_user,  UserModel::ROLE_INSTRUCTOR );
		$dashboard_url    = admin_url();

		$footer = [
			'wrapper' => '<div class="lp-cb-sidebar__footer">',
		];

		// Hide "Back to WordPress" for instructors when CB admin mode is on
		// Admins always see this link
		$hide_back_link = $hide_instructor_access_admin_screen && $is_instructor;

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

		$admin_bar_icon = wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-cb-admin-bar.svg' );

		$wp_admin_bar->add_node(
			array(
				'id'    => 'lp-course-builder',
				'title' => sprintf(
					'<span class="lp-cb-admin-bar-icon">%1$s</span><span class="ab-label">%2$s</span>',
					$admin_bar_icon,
					$title
				),
				'href'  => $href,
			)
		);
	}
}
