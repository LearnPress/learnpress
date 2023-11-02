<?php
/**
 * Class CourseAuthorNameElementor
 *
 * Dynamic course author name elementor.
 *
 * @since 4.2.3.5
 * @version 1.0.0
 */
namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic;
use Elementor\Core\DynamicTags\Tag;
use LearnPress\ExternalPlugin\Elementor\LPDynamicElementor;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;

defined( 'ABSPATH' ) || exit;

class CourseAuthorNameElementor extends Tag {
	use LPDynamicElementor;

	public function __construct( array $data = [] ) {
		$this->lp_dynamic_title = 'Course Author Name';
		$this->lp_dynamic_name  = 'course-author-name';
		parent::__construct( $data );
	}

	/**
	 * Render dynamic course author name elementor.
	 *
	 * @return void
	 */
	public function render() {
		$singleInstructorTemplate = SingleInstructorTemplate::instance();

		try {
			$course = $this->get_course();
			if ( ! $course ) {
				return;
			}

			$author = $course->get_author();
			if ( ! $author ) {
				return;
			}
			echo $singleInstructorTemplate->html_display_name( $author );
		} catch ( \Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}
}
