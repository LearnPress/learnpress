<?php
/**
 * Class CountStudentDynamicElementor
 *
 * Dynamic count student of many courses elementor.
 *
 * @since 4.2.5.4
 * @version 1.0.0
 */
namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic;
use Elementor\Core\DynamicTags\Tag;
use LearnPress\ExternalPlugin\Elementor\LPDynamicElementor;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use Throwable;

defined( 'ABSPATH' ) || exit;

class CountStudentDynamicElementor extends Tag {
	use LPDynamicElementor;

	public function __construct( array $data = [] ) {
		$this->lp_dynamic_title = 'Count Student many courses';
		$this->lp_dynamic_name  = 'count-student-courses';
		parent::__construct( $data );
	}

	public function render() {
		$listCoursesTemplate = ListCoursesTemplate::instance();

		try {
			echo $listCoursesTemplate->html_count_students();
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}
}
