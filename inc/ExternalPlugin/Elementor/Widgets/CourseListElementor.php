<?php
/**
 * Class CourseListElementor
 *
 * @sicne 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets;

use LearnPress\ExternalPlugin\Elementor\LPElementorWidgetBase;
use LearnPress\Helpers\Config;

class CourseListElementor extends LPElementorWidgetBase {
	public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'List Courses', 'learnpress' );
		$this->name     = 'list_courses';
		$this->keywords = [ 'courses' ];
		$this->icon     = 'eicon-post-list';
		parent::__construct( $data, $args );
	}

	protected function register_controls() {
		$this->controls = Config::instance()->get( 'list-courses', 'elementor/course' );
		parent::register_controls();
	}

	public function render() {
		try {
			$settings      = $this->get_settings_for_display();
			$filter        = new \LP_Course_Filter();
			$filter->limit = $settings['limit'] ?? 5;
			$sort_int      = $settings['sort_in'] ?? '';

			$layout = $settings['layout'] ?? 'grid';

			if ( ! empty( $settings['category_ids'] ) ) {
				$filter->term_ids = $settings['category_ids'];
			}

			if ( ! empty( $settings['order_by'] ) ) {
				$filter->order = $settings['order_by'];
			}

			if ( ! empty( $settings['courses_ids'] ) ) {
				$filter->post_ids = $settings['courses_ids'];
			}

			switch ( $sort_int ) {
				case 'recent':
					$filter->order_by .= 'post_date';
					break;
				case 'popular':
					$filter->order_by = 'popular';
					break;
				case 'featured':
					$filter->sort_by = 'on_feature';
					break;
				default:
					$filter->order_by = 'post_title';
					break;
			}

			$courses = \LP_Course::get_courses( $filter );

			if ( empty( $courses ) ) {
				\LearnPress::instance()->template( 'course' )->no_courses_found();
				return;
			}
			?>
			<div class="lp-archive-courses">
				<ul class="learn-press-courses" data-layout="<?php echo esc_attr( $layout ); ?>"
					data-size="<?php echo absint( $settings['number_posts'] ?? 10 ); ?>">
					<?php
					global $post;
					foreach ( $courses as $course ) {
						$post = $course;
						setup_postdata( $course );
						learn_press_get_template( 'content-course.php' );
					}
					wp_reset_postdata();
					?>
				</ul>
			</div>
			<?php
		} catch ( \Throwable $e ) {
			error_log( __METHOD__ . ' ' . $e->getMessage() );
		}

	}

	public function get_style_depends() {
		wp_register_style( 'learnpress', LP_PLUGIN_URL . 'assets/css/learnpress.css', array(), uniqid() );

		return array( 'learnpress' );
	}
}
