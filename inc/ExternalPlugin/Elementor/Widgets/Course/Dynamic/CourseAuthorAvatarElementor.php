<?php
/**
 * Class CourseAuthorAvatarElementor
 *
 * Dynamic course author avatar elementor.
 *
 * @since 4.2.3.5
 * @version 1.0.0
 */
namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic;
use Elementor\Core\DynamicTags\Tag;
use LearnPress\ExternalPlugin\Elementor\LPDynamicElementor;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;

defined( 'ABSPATH' ) || exit;

class CourseAuthorAvatarElementor extends Tag {
	use LPDynamicElementor;
	public function __construct( array $data = [] ) {
		$this->lp_dynamic_title = 'Course Author Avatar';
		$this->lp_dynamic_name  = 'course-author-avatar';
		parent::__construct( $data );
	}

	/**
	 * Render dynamic course author avatar elementor.
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
			echo $singleInstructorTemplate->html_avatar( $author );
		} catch ( \Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}
}
