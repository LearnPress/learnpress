<?php
/**
 * Template hooks List Instructors.
 *
 * @since 4.2.3
 * @version 1.0.0
 */
namespace LearnPress\TemplateHooks\Instructor;

use LearnPress\Helpers\Template;
use LP_User;
use Throwable;
use WP_Query;

class ListInstructorsTemplate {
	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	protected function __construct() {
		add_action( 'learn-press/list-instructors/layout', [ $this, 'sections' ] );
		//add_action( 'wp_head', [ $this, 'add_internal_style_to_head' ] );
	}

	/*public function add_internal_style_to_head() {
		echo '<style id="123123" type="text/css">body{background: red !important;}</style>';
	}*/

	/**
	 * List section of layout.
	 *
	 * @param array $data
	 *
	 * @since 4.2.3
	 * @version 1.0.0
	 * @return void
	 */
	public function sections( array $data = [] ) {
		wp_enqueue_style( 'lp-instructors' );
		wp_enqueue_script( 'lp-instructors' );
		/**
		 * @var WP_Query $wp_query
		 */
		global $wp_query;

		ob_start();
		try {
			$html_wrapper = apply_filters(
				'learn-press/list-instructors/sections/wrapper',
				[
					'<article class="lp-content-area">' => '</article>',
					'<div class="lp-list-instructors">' => '</div>',
				],
				$data
			);
			?>
			<ul class="ul-list-instructors">
				<?php lp_skeleton_animation_html( 10 ); ?>
			</ul>
			<?php
			$init_data = array();
			if ( ! empty( get_query_var( 'paged' ) ) ) {
				$init_data['paged'] = get_query_var( 'paged' );
			}
			?>
			<input type="hidden" name="init-data" value="<?php echo esc_attr( json_encode( $init_data ) ); ?>">
			<?php
			$content = ob_get_clean();
			echo Template::instance()->nest_elements( $html_wrapper, $content );
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}

	/**
	 * Get instructor item.
	 *
	 * @param LP_User $instructor
	 *
	 * @return false|string
	 */
	public function instructor_item( LP_User $instructor ) {
		$content      = '';
		$html_wrapper = apply_filters(
			'learn-press/list-instructors/instructor_item/wrapper',
			[
				'<li class="item-instructor">' => '</li>',
			],
			$instructor
		);

		ob_start();
		try {
			$singleInstructorTemplate = SingleInstructorTemplate::instance();

			$sections = apply_filters(
				'learn-press/list-instructors/instructor_item/sections',
				[
					'img'      => [ 'text_html' => $singleInstructorTemplate->html_avatar( $instructor ) ],
					'name'     => [ 'text_html' => $singleInstructorTemplate->html_display_name( $instructor ) ],
					'info'     => [ 'text_html' => $this->instructor_item_info( $instructor ) ],
					'btn_view' => [ 'text_html' => $singleInstructorTemplate->html_button_view( $instructor ) ],
				],
				$instructor,
				$singleInstructorTemplate
			);
			Template::instance()->print_sections( $sections, compact( 'instructor' ) );
			$content = ob_get_clean();
			$content = Template::instance()->nest_elements( $html_wrapper, $content );
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get instructor info: total courses, total students.
	 *
	 * @param LP_User $instructor
	 *
	 * @since 4.2.3
	 * @version 1.0.0
	 * @return false|string
	 */
	public function instructor_item_info( LP_User $instructor ) {
		$content      = '';
		$html_wrapper = apply_filters(
			'learn-press/list-instructors/instructor_item/info/wrapper',
			[
				'<div class="instructor-info">' => '</div>',
			],
			$instructor
		);

		ob_start();
		try {
			$singleInstructorTemplate = SingleInstructorTemplate::instance();

			$html_total_courses = sprintf(
				'<div class="instructor-count-courses"><span class="lp-ico courses">%s</span> %s</div>',
				wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-courses.svg' ),
				$singleInstructorTemplate->html_count_courses( $instructor )
			);

			$html_total_students = sprintf(
				'<div class="instructor-count-students"><span class="lp-ico students">%s</span> %s</div>',
				wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-students.svg' ),
				$singleInstructorTemplate->html_count_students( $instructor )
			);

			$sections = apply_filters(
				'learn-press/list-instructors/instructor_item/info/sections',
				[
					'total_courses'  => [ 'text_html' => $html_total_courses ],
					'total_students' => [ 'text_html' => $html_total_students ],
				],
				$instructor,
				$singleInstructorTemplate
			);
			Template::instance()->print_sections( $sections, compact( 'instructor' ) );
			$content = ob_get_clean();
			$content = Template::instance()->nest_elements( $html_wrapper, $content );
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}
}
