<?php
/**
 * Class LP_Settings_Profile
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Classes/Settings
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

class LP_Settings_Advanced extends LP_Abstract_Settings_Page {

	public function __construct() {
		$this->id   = 'advanced';
		$this->text = esc_html__( 'Advanced', 'learnpress' );

		parent::__construct();
	}

	public function get_settings( $section = '', $tab = '' ) {
		return apply_filters(
			'learn_press_advanced_settings',
			array(
				array(
					'type'  => 'title',
					'title' => esc_html__( 'Style', 'learnpress' ),
					'id'    => 'lp_metabox_setting_advanced',
				),
				array(
					'title'    => esc_html__( 'Primary color', 'learnpress' ),
					'desc'     => sprintf( __( 'Default: %s', 'learnpress' ), '<code>#ffb606</code>' ),
					'id'       => 'primary_color',
					'type'     => 'color',
					'css'      => 'width:6em;',
					'default'  => '#ffb606',
					'autoload' => false,
					'desc_tip' => true,
				),
				array(
					'title'    => esc_html__( 'Secondary color', 'learnpress' ),
					'desc'     => sprintf( __( 'Default: %s', 'learnpress' ), '<code>#442e66</code>' ),
					'id'       => 'secondary_color',
					'type'     => 'color',
					'css'      => 'width:6em;',
					'default'  => '#442e66',
					'autoload' => false,
					'desc_tip' => true,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'lp_metabox_setting_advanced',
				),
				array(
					'type'  => 'title',
					'title' => esc_html__( 'Other', 'learnpress' ),
					'id'    => 'lp_metabox_advanced_other',
				),
				array(
					'title'         => esc_html__( 'Enable gutenberg', 'learnpress' ),
					'id'            => 'enable_gutenberg_course',
					'default'       => 'no',
					'type'          => 'checkbox',
					'checkboxgroup' => 'start',
					'desc'          => esc_html__( 'Course', 'learnpress' ),
				),
				array(
					'id'            => 'enable_gutenberg_lesson',
					'default'       => 'no',
					'type'          => 'checkbox',
					'checkboxgroup' => '',
					'desc'          => esc_html__( 'Lesson', 'learnpress' ),
				),
				array(
					'id'            => 'enable_gutenberg_quiz',
					'default'       => 'no',
					'type'          => 'checkbox',
					'checkboxgroup' => '',
					'desc'          => esc_html__( 'Quiz', 'learnpress' ),
				),
				array(
					'id'            => 'enable_gutenberg_question',
					'default'       => 'no',
					'type'          => 'checkbox',
					'checkboxgroup' => 'end',
					'desc'          => esc_html__( 'Question', 'learnpress' ),
				),
				array(
					'title'   => esc_html__( 'Debug Mode', 'learnpress' ),
					'id'      => 'debug',
					'default' => 'no',
					'type'    => 'checkbox',
					'desc'    => esc_html__( 'Enable debug mode for the developer.', 'learnpress' ),
				),
				array(
					'title'   => esc_html__( 'Sticky Quiz Paging', 'learnpress' ),
					'id'      => 'navigation_position',
					'default' => 'yes',
					'type'    => 'checkbox',
					'desc'    => esc_html__( 'The question\'s navigation position is sticky. If this option is disabled, the question navigation position will be below the quiz content', 'learnpress' ),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'lp_metabox_advanced_other',
				),
			)
		);
	}
}

return new LP_Settings_Advanced();
