<?php
/**
 * Template hooks Settings in Course Builder.
 *
 * @since 4.3.x
 * @version 1.0.2
 */

namespace LearnPress\TemplateHooks\CourseBuilder\Settings;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LP_Settings;
use Throwable;

class BuilderSettingsTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/course-builder/settings/layout', [ $this, 'layout' ] );
	}

	public function layout( array $data = [] ) {
		try {
			if ( ! $this->can_manage_settings( $data ) ) {
				echo Template::print_message( __( 'Only administrators can manage instructor access in Course Builder.', 'learnpress' ), 'error', false );
				return;
			}

			wp_enqueue_script( 'lp-course-builder' );

			$settings_data   = $this->get_settings_data();
			$layout_sections = apply_filters(
				'learn-press/course-builder/settings/sections',
				[
					'wrapper'     => '<div class="lp-cb-settings">',
					'header'      => $this->html_header(),
					'content'     => '<div class="lp-cb-settings__content">',
					'form'        => $this->html_content( $settings_data ),
					'content_end' => '</div>',
					'wrapper_end' => '</div>',
				],
				$settings_data
			);

			echo Template::combine_components( $layout_sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}

	protected function html_header(): string {
		return Template::combine_components(
			[
				'wrapper'     => '<div class="cb-tab-header">',
				'title'       => sprintf( '<h2 class="lp-cb-tab__title">%s</h2>', __( 'Settings', 'learnpress' ) ),
				'wrapper_end' => '</div>',
			]
		);
	}

	protected function can_manage_settings( array $data = [] ): bool {
		$userModel = $data['userModel'] ?? false;
		if ( $userModel instanceof UserModel ) {
			return user_can( $userModel->get_id(), ADMIN_ROLE );
		}

		$user_id = get_current_user_id();

		return $user_id && current_user_can( ADMIN_ROLE );
	}

	protected function get_settings_data(): array {
		$course_builder_logo = absint( LP_Settings::get_option( 'course_builder_logo_id', 0 ) );
		$course_builder_src  = $course_builder_logo ? wp_get_attachment_image_url( $course_builder_logo, 'full' ) : '';

		return apply_filters(
			'learn-press/course-builder/settings/data',
			[
				'hide_instructor_access_admin_screen' => LP_Settings::is_hide_instructor_access_admin_screen(),
				'course_builder_logo' => $course_builder_logo,
				'course_builder_src'  => $course_builder_src,
				'has_custom_logo'     => ! empty( $course_builder_src ),
			]
		);
	}

	protected function html_content( array $settings_data ): string {
		$form_sections = apply_filters(
			'learn-press/course-builder/settings/form/sections',
			[
				'wrapper'     => '<form id="lp-cb-settings-form" method="post" novalidate>',
				'access'      => $this->html_section_instructor_access( $settings_data ),
				'logo'        => $this->html_section_logo( $settings_data ),
				'wrapper_end' => '</form>',
			],
			$settings_data
		);

		return Template::combine_components( $form_sections );
	}

	protected function html_section_instructor_access( array $settings_data ): string {
		$field_checked = checked( $settings_data['hide_instructor_access_admin_screen'], true, false );
		$field         = [
			'wrapper'     => '<div class="form-field lp-cb-settings__field hide_instructor_access_admin_screen_field">',
			'label'       => sprintf(
				'<label for="hide_instructor_access_admin_screen">%s</label>',
				esc_html__( 'Restrict Instructor Access', 'learnpress' )
			),
			'input'       => sprintf(
				'<input type="checkbox"
				id="hide_instructor_access_admin_screen"
				name="hide_instructor_access_admin_screen" value="yes" %s>',
				$field_checked
			),
			'description' => sprintf(
				'<span class="description">%s</span>',
				esc_html__(
					'When enabled, the admin menu will be hidden for instructors. Instructors will not be able to access wp-admin.',
					'learnpress'
				)
			),
			'wrapper_end' => '</div>',
		];

		$section = apply_filters(
			'learn-press/course-builder/settings/section/instructor-access',
			[
				'wrapper'     => '<div class="lp-cb-settings__section">',
				'field'       => Template::combine_components( $field ),
				'wrapper_end' => '</div>',
			],
			$settings_data
		);

		return Template::combine_components( $section );
	}

	protected function html_section_logo( array $settings_data ): string {
		$preview_default_class = trim( 'lp-cb-logo-setting__preview-default ' . ( $settings_data['has_custom_logo'] ? 'is-hidden' : '' ) );
		$preview_image_class   = $settings_data['has_custom_logo'] ? '' : 'is-hidden';
		$remove_btn_class      = trim( 'button lp-button lp-cb-logo-setting__btn lp-cb-logo-setting__btn-danger ' . ( $settings_data['has_custom_logo'] ? '' : 'is-hidden' ) );

		$preview = [
			'wrapper'     => '<div class="lp-cb-logo-setting__preview">',
			'default'     => sprintf( '<span class="%s" data-cb-logo-preview-default></span>', esc_attr( $preview_default_class ) ),
			'image'       => sprintf(
				'<img class="%1$s" src="%2$s" alt="%3$s" data-cb-logo-preview-image />',
				esc_attr( $preview_image_class ),
				esc_url( $settings_data['course_builder_src'] ),
				esc_attr__( 'Course Builder logo preview', 'learnpress' )
			),
			'wrapper_end' => '</div>',
		];

		$logo_setting = [
			'wrapper'             => sprintf(
				'<div class="lp-cb-logo-setting" data-cb-logo-setting data-cb-default-logo-url="%s">',
				esc_url( LP_PLUGIN_URL . 'assets/images/icons/ico-logo-course-builder.svg' )
			),
			'preview_wrapper'     => '<div class="lp-cb-logo-setting__preview-wrap" data-cb-logo-preview-wrap>',
			'preview_row'         => '<div class="lp-cb-logo-setting__preview-row">',
			'preview'             => Template::combine_components( $preview ),
			'preview_row_end'     => '</div>',
			'actions'             => '<div class="lp-cb-logo-setting__actions">',
			'btn_remove'          => sprintf( '<button type="button" class="%s" data-cb-logo-remove>%s</button>', esc_attr( $remove_btn_class ), esc_html__( 'Remove', 'learnpress' ) ),
			'btn_replace'         => sprintf( '<button type="button" class="button lp-button lp-cb-logo-setting__btn" data-cb-logo-choose>%s</button>', esc_html__( 'Replace', 'learnpress' ) ),
			'actions_end'         => '</div>',
			'preview_wrapper_end' => '</div>',
			'input_logo_id'       => sprintf(
				'<input type="hidden" id="course_builder_logo_id" name="course_builder_logo_id" value="%d" />',
				absint( $settings_data['course_builder_logo'] )
			),
			'input_logo_remove'   => '<input type="hidden" name="course_builder_logo_remove" value="no" />',
			'wrapper_end'         => '</div>',
		];

		$field = [
			'wrapper'     => '<div class="form-field lp-cb-settings__field course_builder_logo_field">',
			'label'       => sprintf( '<label for="course_builder_logo_id">%s</label>', esc_html__( 'Course Builder Logo', 'learnpress' ) ),
			'setting'     => Template::combine_components( $logo_setting ),
			'wrapper_end' => '</div>',
		];

		$section = apply_filters(
			'learn-press/course-builder/settings/section/logo',
			[
				'wrapper'     => '<div class="lp-cb-settings__section">',
				'field'       => Template::combine_components( $field ),
				'wrapper_end' => '</div>',
			],
			$settings_data
		);

		return Template::combine_components( $section );
	}
}
