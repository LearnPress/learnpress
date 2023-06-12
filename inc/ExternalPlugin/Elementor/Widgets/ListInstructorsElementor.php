<?php
/**
 * Class SingleInstructorElementor
 *
 * @sicne 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets;

use Exception;
use LearnPress\ExternalPlugin\Elementor\LPElementorWidgetBase;
use LearnPress\Helpers\Config;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use WP_User_Query;

class ListInstructorsElementor extends LPElementorWidgetBase {
	public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'List Instructors', 'learnpress' );
		$this->name     = 'list_instructors';
		$this->keywords = [ 'list instructor', 'instructor', 'list' ];
		parent::__construct( $data, $args );
	}

	protected function register_controls() {
		$this->controls = Config::instance()->get( 'list-instructors', 'elementor/instructor' );
		parent::register_controls();
	}

	protected function render() {
		try {
			$settings = $this->get_settings_for_display();

			/**
			 * Get instructor id
			 *
			 * If is page single instructor, will be get instructor id from query var
			 * If is set instructor id in setting widget, will be get instructor id from widget
			 */
			//$instructor_id = get_query_var( 'instructor' );
			$instructor_id = 1;
			$instructor    = learn_press_get_user( $instructor_id );
			if ( ! $instructor ) {
				throw new Exception( __( 'Instructor not found', 'learnpress' ) );
			}

			if ( empty( $settings['layouts'] ) ) {
				return;
			}

			// Query
			$args = apply_filters(
				'learnpress/instructor-list/argsx',
				array(
					'number'   => $params['number'] ?? 4,
					'paged'    => $params['paged'] ?? 1,
					'orderby'  => $params['orderby'] ?? 'display_name',
					'order'    => $params['order'] ?? 'asc',
					'role__in' => [ 'lp_teacher', 'administrator' ],
					'fields'   => 'ID',
				)
			);

			$query = new WP_User_Query( $args );

			$instructors = $query->get_results();
			// End Query

			$layout = $settings['layouts'][0];

			// Show list instructors
			$singleInstructorTemplate = SingleInstructorTemplate::instance();
			echo '<ul>';
			foreach ( $instructors as $instructor_id ) {
				$instructor = learn_press_get_user( $instructor_id );
				?>
				<li>
					<?php echo $singleInstructorTemplate->render_data( $instructor, html_entity_decode( $layout['layout_html'] ) ); ?>
				</li>
				<?php
			}
			echo '</ul>';
			// End show list instructors
		} catch ( \Throwable $e ) {
			echo $e->getMessage();
		}
	}


}
