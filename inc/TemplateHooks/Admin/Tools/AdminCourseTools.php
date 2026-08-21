<?php
/**
 * Admin templates for LearnPress tools.
 *
 * @package LearnPress\TemplateHooks\Admin\Tools
 * @since 4.4.5
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Admin\Tools;

use Exception;
use LearnPress\Databases\DataBase;
use LearnPress\Databases\UserItemsDB;
use LearnPress\Filters\UserItemsFilter;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LP_Database;
use stdClass;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdminCourseTools
 */
class AdminCourseTools {
	use Singleton;

	/**
	 * Hooks initialization.
	 */
	public function init(): void {
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
	}

	/**
	 * Register AJAX callbacks allowed for loading content via AJAX.
	 *
	 * @param array $callbacks List of allowed callbacks.
	 *
	 * @return array
	 */
	public function allow_callback( array $callbacks ): array {
		$callbacks[] = self::class . ':render_courses_to_reset_progress';

		return $callbacks;
	}

	/**
	 * Render paginated list of courses that have enrolled users.
	 *
	 * @param array $data Request arguments.
	 *
	 * @return stdClass
	 * @throws Exception
	 */
	public static function render_courses_to_reset_progress( array $data ): stdClass {
		$content = new stdClass();

		try {
			$item_selecting = $data['item_selecting'] ?? [];
			$search_title   = $data['search_title'] ?? '';
			$paged          = intval( $data['paged'] ?? 1 );
			if ( $paged < 1 ) {
				$paged = 1;
			}

			$selected_compare = new stdClass();
			if ( ! empty( $item_selecting ) && is_array( $item_selecting ) ) {
				foreach ( $item_selecting as $item ) {
					if ( ! isset( $item['id'] ) ) {
						continue;
					}
					$selected_compare->{$item['id']} = true;
				}
			}

			$limit = 10;

			$db                  = UserItemsDB::getInstance();
			$total_rows          = 0;
			$filter              = new UserItemsFilter();
			$filter->limit       = $limit;
			$filter->item_type   = LP_COURSE_CPT;
			$filter->only_fields = [ 'item_id', 'user_item_id', 'post_title' ];
			$filter->join[]      = "INNER JOIN {$db->tb_lp_courses} AS c ON ui.item_id = c.ID";

			if ( ! empty( $search_title ) ) {
				$filter->where[] = $db->wpdb->prepare( 'AND c.post_title LIKE %s', "%s{$search_title}%s" );
			}

			$userCourses = $db->get_user_items( $filter, $total_rows );

			$total_pages = DataBase::get_total_pages( $limit, $total_rows );

			$html_lis = '';
			if ( empty( $userCourses ) ) {
				$html_lis = Template::print_message(
					esc_html__( 'No courses found', 'learnpress' ),
					'info',
					false
				);
			} else {
				foreach ( $userCourses as $userCourse ) {
					$courseModel = CourseModel::find( $userCourse->item_id, true );
					$checked     = '';
					if ( isset( $selected_compare->{$courseModel->get_id()} ) ) {
						$checked = ' checked="checked"';
					}

					$students_text = sprintf(
					/* translators: %d: number of enrolled students */
						__( '%d students', 'learnpress' ),
						number_format_i18n( (int) $courseModel->count_students() )
					);

					$title_display = sprintf(
						'<span class="title">%s<strong>(#%d - %s)</strong></span>',
						esc_html( $courseModel->get_title() ),
						esc_html( $courseModel->get_id() ),
						esc_html( $students_text )
					);

					$html_lis .= sprintf(
						'<li class="lp-select-item">%s%s</li>',
						sprintf(
							'<input name="lp-select-item"
							value="%d" data-type="%s"
							data-title="%s" %s
							type="checkbox" />',
							esc_attr( $courseModel->get_id() ),
							esc_attr( LP_COURSE_CPT ),
							esc_attr( $title_display ),
							$checked
						),
						$title_display
					);
				}
			}

			$section = [
				'ul'         => '<ul class="list-items">',
				'items'      => $html_lis,
				'ul_end'     => '</ul>',
				'pagination' => Template::instance()->html_pagination(
					[
						'total_pages' => $total_pages,
						'paged'       => $paged,
					]
				),
			];

			$content->content = Template::combine_components( $section );
		} catch ( Throwable $exception ) {
			$content->content = Template::print_message( $exception->getMessage(), 'error', false );
		}

		return $content;
	}
}
