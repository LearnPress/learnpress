<?php

/**
 * Class LP_Course_Template
 *
 * Groups templates related course and items
 *
 * @since 3.x.x
 */
class LP_Template_General extends LP_Abstract_Template {

	/*public function filter_block_content_template( $located, $template_name, $args, $template_path, $default_path ) {
		if ( $template_name == 'global/block-content.php' ) {
			$can_view_item = false;

			if ( ! is_user_logged_in() ) {
				$can_view_item = 'not-logged-in';
			} elseif ( ! learn_press_current_user_enrolled_course() ) {
				$can_view_item = 'not-enrolled';
			}
			learn_press_get_template( 'single-course/content-protected.php', array( 'can_view_item' => $can_view_item ) );

			return false;
		}

		return $located;

	}*/

	public function term_conditions_template() {
		$page_id = learn_press_get_page_id( 'term_conditions' );
		if ( $page_id ) {
			$page_link = get_page_link( $page_id );
			learn_press_get_template( 'checkout/term-conditions.php', array( 'page_link' => $page_link ) );
		}
	}

	public function breadcrumb( $args = array() ) {
		$args = wp_parse_args(
			$args,
			apply_filters(
				'learn_press_breadcrumb_defaults',
				array(
					'delimiter'   => '<li class="breadcrumb-delimiter"><i class="fas fa-chevron-right"></i></li>',
					'wrap_before' => '<ul class="learn-press-breadcrumb">',
					'wrap_after'  => '</ul>',
					'before'      => '',
					'after'       => '',
					'home'        => _x( 'Home', 'breadcrumb', 'learnpress' ),
				)
			)
		);

		$breadcrumbs = new LP_Breadcrumb();

		if ( $args['home'] ) {
			$breadcrumbs->add_crumb( $args['home'], apply_filters( 'learn_press_breadcrumb_home_url', home_url() ) );
		}

		$args['breadcrumb'] = $breadcrumbs->generate();

		learn_press_get_template( 'global/breadcrumb.php', $args );
	}

	public function become_teacher_messages() {
		$messages = LP_Shortcode_Become_A_Teacher::get_messages();
		if ( ! $messages ) {
			return;
		}

		learn_press_get_template( 'global/become-teacher-form/message.php', array( 'messages' => $messages ) );
	}

	public function become_teacher_heading() {
		$messages = LP_Shortcode_Become_A_Teacher::get_messages();
		if ( $messages ) {
			return;
		}
		?>
		<h3><?php _e( 'Fill out the form and send us your requesting.', 'learnpress' ); ?></h3>
		<?php
	}

	public function become_teacher_form_fields() {
		$messages = LP_Shortcode_Become_A_Teacher::get_messages();

		if ( $messages ) {
			return;
		}

		learn_press_get_template( 'global/become-teacher-form/form-fields.php' );
	}

	public function become_teacher_button() {
		$messages = LP_Shortcode_Become_A_Teacher::get_messages();

		if ( $messages ) {
			return;
		}

		learn_press_get_template( 'global/become-teacher-form/button.php' );
	}

	/**
	 * Get header for course page
	 */
	public function template_header() {
		get_header( 'course' );
	}

	/**
	 * Get header for course page
	 */
	public function template_footer() {
		get_footer( 'course' );
	}
}

return new LP_Template_General();
