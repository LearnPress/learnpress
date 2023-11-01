<?php
/**
 * Class CountCoursesFreeDynamicElementor
 *
 * Dynamic count courses free elementor.
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

class CountCoursesFreeDynamicElementor extends Tag {
	use LPDynamicElementor;

	public function __construct( array $data = [] ) {
		$this->lp_dynamic_title = 'Count Courses Free';
		$this->lp_dynamic_name  = 'count-courses-free';
		parent::__construct( $data );
	}

	public function render() {
		$listCoursesTemplate = ListCoursesTemplate::instance();

		try {
			echo $listCoursesTemplate->html_count_course_free();
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}
}
