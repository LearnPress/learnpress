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
use LearnPress\CourseBuilder\CourseBuilderAccessPolicy;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LP_Assets;
use LP_Helper;
use LP_Profile;
use Throwable;
use LP_Page_Controller;

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
		add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_theme_styles' ], 9999 );
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
	 * @since 4.3.x
	 */
	public function layout() {
		try {
			// Enqueue assets(js,css) for Course Builder
			$this->enqueue_assets();

			$profile = LP_Profile::instance();

			if ( ! is_user_logged_in() ) {
				throw new Exception(
					sprintf(
						'<a href="%s">%s</a>',
						$profile->get_login_url(),
						__( 'Authentication required', 'learnpress' )
					)
				);
			} else {
				$userModel = UserModel::find( get_current_user_id(), true );
				if ( ! $userModel->is_instructor() ) {
					throw new Exception( __( "Sorry, you don't have permission to access Course Builder", 'learnpress' ) );
				}
			}

			$layout = [
				'wrapper'     => '<div class="learn-press-course-builder">',
				'header'      => $this->html_header(),
				'body'        => '<div class="lp-cb-body">',
				'sidebar'     => $this->html_sidebar(),
				'content'     => $this->html_content(),
				'body_end'    => '</div>',
				'wrapper_end' => '</div>',
			];

			echo Template::combine_components( $layout );
		} catch ( Throwable $e ) {
			echo Template::print_message(
				wp_kses_post( $e->getMessage() ),
				'error',
				false
			);
		}
	}

	/**
	 * Enqueue scripts, styles and localize data for Course Builder.
	 *
	 * @since 4.3.x
	 * @version 1.0.0
	 */
	protected function enqueue_assets() {

		wp_enqueue_style( 'lp-course-builder' );
		// Load dashicons for sidebar icons
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_script( 'lp-load-ajax' );
		wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js', [], '4.4.7', true );
		wp_enqueue_script( 'lp-course-builder' );
		wp_enqueue_editor();
		wp_enqueue_media();

		// Print lpData inline script if not already printed
		// This ensures lpAjaxUrl is available for AJAX calls
		/*$lp_assets = LP_Assets::instance();
		if ( $lp_assets ) {
			$localize_data = $lp_assets->localize_data_global();
			LP_Helper::print_inline_script_tag( 'lpData', $localize_data, [ 'id' => 'lpData-course-builder' ] );
		}*/
	}

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
	public function dequeue_theme_styles() {
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
	}

	/**
	 * Header with logo and user profile
	 *
	 * @return string
	 * @since 4.3.x
	 * @version 1.0.0
	 */
	protected function html_header(): string {
		$user         = wp_get_current_user();
		$avatar       = get_avatar( $user->ID, 32 );
		$display_name = $user->display_name;
		$profile      = LP_Profile::instance();
		$profile_url  = $profile->get_current_url();
		$logout_url   = wp_logout_url( home_url() );
		$logo_id      = absint( \LP_Settings::get_option( 'course_builder_logo_id', 0 ) );
		$custom_logo  = '';

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
					<a href="%s">
						<svg width="181" height="36" viewBox="0 0 181 36" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
							<rect width="181" height="36" fill="url(#pattern0_5770_7958)"/>
							<defs>
							<pattern id="pattern0_5770_7958" patternContentUnits="objectBoundingBox" width="1" height="1">
							<use xlink:href="#image0_5770_7958" transform="scale(0.00205761 0.0104167)"/>
							</pattern>
							<image id="image0_5770_7958" width="486" height="96" preserveAspectRatio="none" xlink:href="data:image/png;base64,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"/>
							</defs>
						</svg>
					</a>
				</div>',
				esc_url( CourseBuilder::get_link_course_builder() ),
				__( 'Course Builder', 'learnpress' )
			),
			'user'        => sprintf(
				'<div class="lp-cb-top-header__user">
					<div class="lp-cb-top-header__user-avatar">
						%s
						<span class="lp-cb-top-header__online-dot"></span>
					</div>
					<div class="lp-cb-top-header__user-info">
						<span class="lp-cb-top-header__user-name">%s</span>
						<a href="%s" class="lp-cb-top-header__user-link" target="_blank">%s</a>
					</div>
					<a href="%s" class="lp-cb-top-header__logout" title="%s">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M12 3C12.2549 3.00028 12.5 3.09788 12.6854 3.27285C12.8707 3.44782 12.9822 3.68695 12.9972 3.94139C13.0121 4.19584 12.9293 4.44638 12.7657 4.64183C12.6021 4.83729 12.3701 4.9629 12.117 4.993L12 5H7C6.75507 5.00003 6.51866 5.08996 6.33563 5.25272C6.15259 5.41547 6.03566 5.63975 6.007 5.883L6 6V18C6.00003 18.2449 6.08996 18.4813 6.25272 18.6644C6.41547 18.8474 6.63975 18.9643 6.883 18.993L7 19H11.5C11.7549 19.0003 12 19.0979 12.1854 19.2728C12.3707 19.4478 12.4822 19.687 12.4972 19.9414C12.5121 20.1958 12.4293 20.4464 12.2657 20.6418C12.1021 20.8373 11.8701 20.9629 11.617 20.993L11.5 21H7C6.23479 21 5.49849 20.7077 4.94174 20.1827C4.38499 19.6578 4.04989 18.9399 4.005 18.176L4 18V6C3.99996 5.23479 4.29233 4.49849 4.81728 3.94174C5.34224 3.38499 6.06011 3.04989 6.824 3.005L7 3H12ZM17.707 8.464L20.535 11.293C20.7225 11.4805 20.8278 11.7348 20.8278 12C20.8278 12.2652 20.7225 12.5195 20.535 12.707L17.707 15.536C17.5194 15.7235 17.2649 15.8288 16.9996 15.8287C16.7344 15.8286 16.48 15.7231 16.2925 15.5355C16.105 15.3479 15.9997 15.0934 15.9998 14.8281C15.9999 14.5629 16.1054 14.3085 16.293 14.121L17.414 13H12C11.7348 13 11.4804 12.8946 11.2929 12.7071C11.1054 12.5196 11 12.2652 11 12C11 11.7348 11.1054 11.4804 11.2929 11.2929C11.4804 11.1054 11.7348 11 12 11H17.414L16.293 9.879C16.1054 9.69149 15.9999 9.43712 15.9998 9.17185C15.9997 8.90658 16.105 8.65214 16.2925 8.4645C16.48 8.27686 16.7344 8.17139 16.9996 8.1713C17.2649 8.1712 17.5194 8.27649 17.707 8.464Z" fill="currentColor"/>
						</svg>
					</a>
				</div>',
				$avatar,
				esc_html( $display_name ),
				esc_url( $profile_url ),
				__( 'View Profile', 'learnpress' ),
				esc_url( $logout_url ),
				esc_attr__( 'Logout', 'learnpress' )
			),
			'wrapper_end' => '</header>',
		];

		$default_logo_svg = '';
		if ( preg_match( '/<svg\b[^>]*>.*?<\/svg>/is', $header['logo'], $default_logo_matches ) ) {
			$default_logo_svg = $default_logo_matches[0];
		}

		if ( ! empty( $custom_logo ) ) {
			$header['logo'] = preg_replace( '/<svg\b[^>]*>.*?<\/svg>/is', $custom_logo, $header['logo'], 1 );
		}

		if ( ! empty( $default_logo_svg ) ) {
			$default_logo_template = sprintf(
				'<template id="lp-cb-default-logo-template">%s</template>',
				$default_logo_svg
			);

			$header['logo'] = preg_replace_callback(
				'/<div class="lp-cb-top-header__logo">\s*/i',
				static function ( array $matches ) use ( $default_logo_template ): string {
					return $matches[0] . $default_logo_template;
				},
				$header['logo'],
				1
			);
		}

		return Template::combine_components( $header );
	}

	/**
	 * HTML Sidebar
	 *
	 * @return string
	 */
	public function html_sidebar(): string {
		$tabs        = CourseBuilder::get_tabs_arr();
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
	 * @return string
	 * @since 4.3.x
	 * @version 1.0.0
	 */
	public function html_content(): string {
		$tab_current     = CourseBuilder::get_current_tab();
		$section_current = CourseBuilder::get_current_section();
		$post_id         = CourseBuilder::get_post_id();

		ob_start();

		// If viewing entity detail (has post_id), show breadcrumb + horizontal tabs
		if ( ! empty( $post_id ) && ! empty( $section_current ) ) {
			echo $this->render_detail_view( $tab_current, $post_id, $section_current );
		} elseif ( ! empty( $section_current ) ) {
			// Legacy section view (fallback)
			echo $this->html_section( $tab_current, $section_current );
		} else {
			// List view
			echo $this->html_tab( $tab_current );
		}

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
		$is_cb_admin_mode = \LP_Settings::get_option( 'enable_cb_admin_mode', 'no' ) === 'yes';
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
		$tab_current = CourseBuilder::get_current_tab();
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

	/**
	 * Render detail view with breadcrumb and horizontal tabs
	 *
	 * @param string $tab_current
	 * @param int|string $post_id
	 * @param string $section_current
	 *
	 * @return string
	 * @since 4.3.0
	 */
	protected function render_detail_view( $tab_current, $post_id, $section_current ) {
		if ( ! CourseBuilderAccessPolicy::can_access_tab_post( $tab_current, $post_id ) ) {
			return Template::print_message(
				__( "Sorry, you don't have permission to access this content", 'learnpress' ),
				'error',
				false
			);
		}

		$is_new_post = ( $post_id === CourseBuilder::POST_NEW );
		$post        = null;

		if ( ! $is_new_post ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				return Template::print_message( __( 'Item not found.', 'learnpress' ), 'error', false );
			}
		}

		$tab_data = CourseBuilder::get_data( $tab_current );
		$sections = $tab_data['sections'] ?? [];

		// Get status for button labels and status badge
		$status = $is_new_post ? 'auto-draft' : $post->post_status;

		// Dynamic title for new posts based on tab type
		$new_post_titles = array(
			'courses'   => __( 'Add New Course', 'learnpress' ),
			'quizzes'   => __( 'Add New Quiz', 'learnpress' ),
			'lessons'   => __( 'Add New Lesson', 'learnpress' ),
			'questions' => __( 'Add New Question', 'learnpress' ),
		);
		$post_title      = $is_new_post
			? ( $new_post_titles[ $tab_current ] ?? __( 'Add New', 'learnpress' ) )
			: $post->post_title;

		// Status badge HTML (hide for new post)
		$status_badge = '';
		if ( ! $is_new_post && ! empty( $status ) ) {
			// Use type-specific status class based on current tab
			$type_singular = rtrim( $tab_current, 's' ); // courses -> course, quizzes -> quiz, etc.
			$status_label  = 'future' === $status ? __( 'scheduled', 'learnpress' ) : $status;
			$status_badge  = sprintf( '<span class="%1$s-status %2$s">%3$s</span>', esc_attr( $type_singular ), esc_attr( $status ), esc_html( $status_label ) );
		}

		$is_admin_user                         = current_user_can( ADMIN_ROLE );
		$is_instructor_user                    = current_user_can( LP_TEACHER_ROLE );
		$course_post_type                      = get_post_type_object( LP_COURSE_CPT );
		$cap_publish_course                    = ( $course_post_type && isset( $course_post_type->cap->publish_posts ) )
			? $course_post_type->cap->publish_posts
			: 'publish_lp_courses';
		$can_publish_course                    = current_user_can( $cap_publish_course );
		$required_review                       = \LP_Settings::get_option( 'required_review', 'yes' ) === 'yes';
		$use_review_only_workflow              = ( $is_instructor_user && $required_review ) || ! $can_publish_course;
		$is_review_only_course_context         = 'courses' === $tab_current && ! $is_admin_user && $use_review_only_workflow;
		$main_action_status                    = in_array( $status, array( 'publish', 'draft', 'pending', 'future', 'private' ), true ) ? $status : 'publish';
		$show_header_expanded_trash            = ! $is_new_post && in_array( $tab_current, array( 'courses', 'questions', 'quizzes' ), true );
		$header_expanded_duplicate_class_map   = array(
			'courses'   => 'cb-btn-duplicate-course',
			'quizzes'   => 'cb-btn-duplicate-quiz',
			'questions' => 'cb-btn-duplicate-question',
		);
		$header_expanded_duplicate_content_map = array(
			'courses'   => __( 'Are you sure you want to duplicate this course?', 'learnpress' ),
			'quizzes'   => __( 'Are you sure you want to duplicate this quiz?', 'learnpress' ),
			'questions' => __( 'Are you sure you want to duplicate this question?', 'learnpress' ),
		);
		$header_expanded_duplicate_class       = $header_expanded_duplicate_class_map[ $tab_current ] ?? '';
		$header_expanded_duplicate_text        = $header_expanded_duplicate_content_map[ $tab_current ] ?? '';

		ob_start();
		?>
		<div class="lp-cb-content" data-post-id="<?php echo esc_attr( $post_id ); ?>"
			data-is-new="<?php echo $is_new_post ? '1' : '0'; ?>"
			data-status="<?php echo esc_attr( $status ); ?>">

			<div class="lp-cb-header">
				<div class="lp-cb-header__left">
					<h1 class="lp-cb-header__title"><?php echo esc_html( $post_title ); ?></h1>
					<?php echo $status_badge; ?>
					<?php
					$is_cb_admin_mode = \LP_Settings::get_option( 'enable_cb_admin_mode', 'no' ) === 'yes';
					$is_instructor    = current_user_can( LP_TEACHER_ROLE );
					// Hide "Edit with WordPress" for instructors when CB admin mode is on
					// Admins always see this link
					$hide_wp_edit_link = $is_cb_admin_mode && $is_instructor && ! $is_admin_user;
					?>
					<?php if ( ! $is_new_post && ! $hide_wp_edit_link ) : ?>
						<?php $hide_style = ( 'trash' === $status ) ? 'style="display:none"' : ''; ?>
						<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $post_id . '&action=edit' ) ); ?>" class="lp-cb-admin-link" target="_blank" title="<?php esc_attr_e( 'Edit with WordPress', 'learnpress' ); ?>" <?php echo $hide_style; ?>>
							<span class="dashicons dashicons-wordpress"></span>
							<span><?php esc_html_e( 'Edit with WordPress', 'learnpress' ); ?></span>
						</a>
					<?php endif; ?>
				</div>
				<div class="lp-cb-header__actions">
					<?php
					// Only show preview for courses (questions and quizzes don't have standalone permalinks)
					$show_preview = ! $is_new_post && 'courses' === $tab_current;
					?>
					<?php if ( $show_preview ) : ?>
						<?php $hide_style = ( 'trash' === $status ) ? 'style="display:none"' : ''; ?>
						<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="cb-button cb-btn-preview cb-btn-secondary" target="_blank" <?php echo $hide_style; ?>>
							<?php esc_html_e( 'Preview', 'learnpress' ); ?>
						</a>
					<?php endif; ?>
					<div class="cb-header-actions-dropdown cb-header-actions-dropdown--single" data-current-status="<?php echo esc_attr( $main_action_status ); ?>" data-review-only-course="<?php echo esc_attr( $is_review_only_course_context ? 'yes' : 'no' ); ?>">
						<div class="cb-btn-update cb-btn-primary cb-btn-main-action"
							data-status="<?php echo esc_attr( $main_action_status ); ?>"
							data-title-update="<?php esc_attr_e( 'Update', 'learnpress' ); ?>"
							data-title-publish="<?php esc_attr_e( 'Publish', 'learnpress' ); ?>"
							data-title-draft="<?php esc_attr_e( 'Save Draft', 'learnpress' ); ?>"
							data-title-submit-review="<?php esc_attr_e( 'Submit for Review', 'learnpress' ); ?>">
							<?php esc_html_e( 'Update', 'learnpress' ); ?>
						</div>
					</div>
						<?php if ( $show_header_expanded_trash ) : ?>
							<div class="cb-header-action-expanded">
									<button type="button" class="course-action-expanded" aria-haspopup="true" aria-expanded="false" aria-label="<?php esc_attr_e( 'More actions', 'learnpress' ); ?>">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
										<path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
									</svg>
								</button>
								<div class="cb-header-action-expanded__items">
									<?php if ( ! empty( $header_expanded_duplicate_class ) ) : ?>
										<div class="cb-header-action-expanded__duplicate <?php echo esc_attr( $header_expanded_duplicate_class ); ?>"
											data-title="<?php esc_attr_e( 'Are you sure?', 'learnpress' ); ?>"
											data-content="<?php echo esc_attr( $header_expanded_duplicate_text ); ?>">
											<span class="dashicons dashicons-admin-page"></span>
											<?php esc_html_e( 'Duplicate', 'learnpress' ); ?>
										</div>
									<?php endif; ?>
									<div class="cb-header-action-expanded__trash cb-btn-trash">
										<span class="dashicons dashicons-trash"></span>
										<?php esc_html_e( 'Move to Trash', 'learnpress' ); ?>
									</div>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>

			<?php echo $this->render_horizontal_tabs( $tab_current, $post_id, $sections, $section_current ); ?>

			<div class="lp-cb-tab-content">
				<?php
				// Render ALL section contents for client-side tab switching
				foreach ( $sections as $key => $section ) :
					$section_slug    = $section['slug'];
					$is_active_panel = ( $key === $section_current || $section_slug === $section_current );
					$hidden_class    = $is_active_panel ? '' : 'lp-hidden';
					?>
					<div class="lp-cb-tab-panel <?php echo esc_attr( $hidden_class ); ?>" data-section="<?php echo esc_attr( $section_slug ); ?>">
						<?php do_action( "learn-press/course-builder/{$tab_current}/{$section_slug}/layout", $post_id, $is_new_post ); ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render horizontal tab navigation
	 *
	 * @param string $tab
	 * @param int $post_id
	 * @param array $sections
	 * @param string $current_section
	 *
	 * @return string
	 * @since 4.3.0
	 */
	protected function render_horizontal_tabs( $tab, $post_id, $sections, $current_section ) {
		if ( empty( $sections ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="lp-cb-tabs">
			<?php
			foreach ( $sections as $key => $section ) :
				$is_active = $key === $current_section || $section['slug'] === $current_section;
				$classes   = [ 'lp-cb-tabs__item' ];
				if ( $is_active ) {
					$classes[] = 'is-active';
				}
				?>
				<a href="#" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-tab-section="<?php echo esc_attr( $section['slug'] ); ?>">
					<?php echo esc_html( $section['title'] ); ?>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public function html_tab( $tab ) {
		$tab_data = CourseBuilder::get_data( $tab );
		$title    = $tab_data['title'];

		ob_start();
		do_action( "learn-press/course-builder/{$tab}/layout" );
		$content = ob_get_clean();

		$tab_slug = $tab; // preserve slug before overwriting

		$sections = [
			'wrapper'     => '<div class="lp-course-builder-content__tab">',
			'content'     => $content,
			'wrapper_end' => '</div>',
		];

		// Only add generic title for non-courses tabs
		// The courses tab renders its own title inside BuilderTabCourseTemplate
		if ( 'courses' !== $tab_slug ) {
			$sections = array_merge(
				[ 'wrapper' => $sections['wrapper'] ],
				[ 'title' => sprintf( '<h2 class="lp-cb-tab__title">%s</h2>', $title ) ],
				[
					'content'     => $sections['content'],
					'wrapper_end' => $sections['wrapper_end'],
				]
			);
		}

		$tab = $sections;

		return Template::combine_components( $tab );
	}

	public function html_section( $tab, $section ) {
		ob_start();
		do_action( "learn-press/course-builder/{$tab}/{$section}/layout" );
		$content = ob_get_clean();

		$tab = [
			'wrapper'     => '<div class="lp-course-builder-content__section">',
			'content'     => $content,
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $tab );
	}

	public function html_btn_add_new() {
		$tab_current = CourseBuilder::get_current_tab();
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

		$title   = isset( $map_title[ $tab_current ] ) ? $map_title[ $tab_current ] : '';
		$type    = isset( $map_type[ $tab_current ] ) ? $map_type[ $tab_current ] : '';
		$add_new = 'data-add-new-' . esc_attr( $type );

		$btn_add_new = sprintf( '<button %s class="lp-button cb-btn-add-new">', $add_new );
		$btn_close   = '</button>';

		if ( 'courses' === $tab_current ) {
			$btn_add_new = sprintf( '<a href="%s" class="lp-button cb-btn-add-new">', esc_url( CourseBuilder::get_link_add_new_course() ) );
			$btn_close   = '</a>';
		}

		if ( 'quizzes' === $tab_current ) {
			$btn_add_new = sprintf( '<a href="%s" class="lp-button cb-btn-add-new">', esc_url( CourseBuilder::get_link_add_new_quiz() ) );
			$btn_close   = '</a>';
		}

		if ( 'questions' === $tab_current ) {
			$btn_add_new = sprintf( '<a href="%s" class="lp-button cb-btn-add-new">', esc_url( CourseBuilder::get_tab_link( 'questions', CourseBuilder::POST_NEW, 'overview' ) ) );
			$btn_close   = '</a>';
		}

		$btn = [
			'wrapper'     => $btn_add_new,
			'content'     => sprintf( '%s %s', __( 'Add New', 'learnpress' ), $title ),
			'wrapper_end' => $btn_close,
		];

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
			$href  = CourseBuilder::get_tab_link( 'courses', get_the_ID(), 'overview' );
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
						$href = CourseBuilder::get_tab_link( 'courses', absint( $_GET['post'] ), 'overview' );
					} else {
						$href = CourseBuilder::get_link_add_new_course();
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
