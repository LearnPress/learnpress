<?php
/**
 * Class CourseAuthorUrlElementor
 *
 * Dynamic course author url elementor.
 *
 * @since 4.2.3.5
 * @version 1.0.0
 */
namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic;
use Elementor\Core\DynamicTags\Tag;
use LearnPress\ExternalPlugin\Elementor\LPDynamicElementor;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use Elementor\Modules\DynamicTags\Module;

defined( 'ABSPATH' ) || exit;

class CourseAuthorUrlElementor extends Tag {
	use LPDynamicElementor;
	public function __construct( array $data = [] ) {
		$this->lp_dynamic_title = 'Course Author Url';
		$this->lp_dynamic_name  = 'course-author-url';
		$this->lp_dynamic_categories = [ Module::URL_CATEGORY ];
		parent::__construct( $data );
	}

	/**
	 * Render dynamic course author url elementor.
	 *
	 * @return void
	 */
	public function render() {
		try {
			$course = $this->get_course();
			if ( ! $course ) {
				return;
			}

			$author = $course->get_author();
			if ( ! $author ) {
				return;
			}
			echo $author->get_url_instructor();
		} catch ( \Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}
}
