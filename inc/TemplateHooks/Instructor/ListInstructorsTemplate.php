<?php
/**
 * Template hooks List Instructors.
 *
 * @since 4.2.3
 * @version 1.0.0
 */
namespace LearnPress\TemplateHooks\Instructor;

use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Course;
use LP_Course_Filter;
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
	 * @return void
	 */
	public function sections( array $data = [] ) {
		wp_enqueue_style( 'learnpress' );
		wp_enqueue_script( 'lp-instructors' );
		/**
		 * @var WP_Query $wp_query
		 */
		global $wp_query;

		ob_start();
		try {
			$html_wrapper = apply_filters(
				'learn-press/single-instructor/sections/wrapper',
				[
					'<article class="lp-content-area">'  => '</article>',
					'<div class="lp-list-instructors">' => '</div>',
				]
			);
			?>
			<ul class="lp-instructor-list">
				<li class="lp-loading">
				</li>
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
}
