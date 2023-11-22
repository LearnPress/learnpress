<?php
/**
 * Template hooks List Instructors.
 *
 * @since 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Instructor;

use LearnPress\Helpers\Template;
use LP_Assets;
use LP_Helper;
use LP_Page_Controller;
use LP_User;
use LP_WP_Filesystem;
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
		add_action( 'wp_head', [ $this, 'add_internal_scripts_to_head' ] );
	}

	public function add_internal_scripts_to_head() {
		if ( ! LP_Page_Controller::is_page_instructors() ) {
			return;
		}

		LP_Helper::print_inline_script_tag( 'lpSkeletonParam', lp_archive_skeleton_get_args(), [ 'id' => 'lpSkeletonParam' ] );
		?>
		<script id="lp-list-instructors-data">
			const lpInstructorsUrl = '<?php echo learn_press_get_page_link( 'instructors' ); ?>';
			const urlListInstructorsAPI = '<?php echo site_url( 'wp-json/lp/v1/instructors' ); ?>';
		</script>
		<?php
		$is_rtl = is_rtl() ? '-rtl' : '';
		$min    = LP_Assets::$_min_assets;
		?>
		<style id="lp-list-instructors">
			<?php echo wp_remote_fopen( LP_Assets::instance()->url( 'css/instructors' . $is_rtl . $min . '.css' ) ); ?>
		</style>
		<script id="lp-list-instructors" async data-wp-strategy="async" >
			<?php //echo wp_remote_fopen( LP_Assets::instance()->url( 'js/dist/frontend/instructors' . $min . '.js' ) ); ?>
			<?php echo LP_WP_Filesystem::instance()->file_get_contents( LP_PLUGIN_PATH . 'assets/js/dist/frontend/instructors' . $min . '.js' ); ?>
		</script>
		<?php
	}

	/**
	 * List section of layout.
	 *
	 * @param array $data
	 *
	 * @return void
	 * @version 1.0.0
	 * @since 4.2.3
	 */
	public function sections( array $data = [] ) {
		if ( ! LP_Page_Controller::is_page_instructors() ) {
			wp_enqueue_style( 'lp-instructors' );
			wp_enqueue_script( 'lp-instructors' );
		}
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
	 * @return false|string
	 * @version 1.0.0
	 * @since 4.2.3
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

	/**
	 * Pagination of list instructor.
	 *
	 * @param int $page
	 * @param int $limit
	 * @param int $total_courses
	 *
	 * @return string
	 */
	public function instructors_pagination( int $page, int $limit, int $total_courses ): string {
		$content = '';

		$instructors_page_id  = learn_press_get_page_id( 'instructors' );
		$instructors_page_url = trailingslashit( get_permalink( $instructors_page_id ) );

		try {
			$total_pages     = \LP_Database::get_total_pages( $limit, $total_courses );
			$data_pagination = array(
				'total'    => $total_pages,
				'current'  => max( 1, $page ),
				'base'     => esc_url_raw( trailingslashit( $instructors_page_url . 'page/%#%' ) ),
				'format'   => '',
				'per_page' => $limit,
			);

			ob_start();
			Template::instance()->get_frontend_template( 'shared/pagination.php', $data_pagination );
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}
}
