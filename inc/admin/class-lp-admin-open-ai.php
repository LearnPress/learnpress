<?php
/**
 * Manage the admin open ai and display them in admin
 *
 * @package    LearnPress
 * @author     ThimPress
 * @version    1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LearnPress\Helpers\Page;
use LearnPress\Helpers\Template;

/**
 * Class LP_Admin_Notice
 */
class LP_Admin_Open_Ai {
	/**
	 * @var bool
	 */
	protected static $instance = false;

	public function __construct() {
		$lp_settings = LP_Settings::instance();
		if ( $lp_settings->get( 'enable_open_ai' ) !== 'yes' ) {
			return;
		}

		if ( empty( $lp_settings->get( 'open_ai_secret_key' ) ) ) {
			return;
		}

		add_action( 'admin_footer', array( $this, 'create_modals' ) );
	}

	/**
	 * @return void
	 */
	public function create_modals() {
		$template = Template::instance();
		$data = array();

		if (  Page::is_admin_single_course_page() ) {
			$template->get_admin_template( 'open-ai/course-title-modal', compact('data') );
			$template->get_admin_template( 'open-ai/course-des-modal', compact('data') );
			$template->get_admin_template( 'open-ai/create-feature-image-modal', compact('data') );
			$template->get_admin_template( 'open-ai/edit-feature-image-modal', compact('data') );
		}

		if ( Page::is_admin_single_lesson_page() ) {
			$template->get_admin_template( 'open-ai/lesson-title-modal', compact('data') );
			$template->get_admin_template( 'open-ai/lesson-des-modal', compact('data') );
		}

		if ( Page::is_admin_single_quiz_page() ) {
			$template->get_admin_template( 'open-ai/quiz-title-modal', compact('data') );
			$template->get_admin_template( 'open-ai/quiz-des-modal', compact('data') );
		}

		if ( Page::is_admin_single_question_page() ) {
			$template->get_admin_template( 'open-ai/question-title-modal', compact('data') );
			$template->get_admin_template( 'open-ai/question-des-modal', compact('data') );
		}
	}

	/**
	 * @return bool|LP_Admin_Open_Ai
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

LP_Admin_Open_Ai::instance();
