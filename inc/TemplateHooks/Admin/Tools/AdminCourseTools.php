<?php
/**
 * Admin templates for LearnPress tools.
 *
 * @package LearnPress\TemplateHooks\Admin\Tools
 * @since 4.4.6
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
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
use LP_Helper;
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
		$callbacks[] = self::class . ':render_items_to_reset_progress';

		return $callbacks;
	}

	/**
	 * Render paginated list of course items (lesson, quiz, etc.) for reset progress.
	 *
	 * @param array $data Request arguments.
	 *
	 * @return stdClass
	 * @since 4.4.6
	 * @version 1.0.0
	 */
	public static function render_items_to_reset_progress( array $data ): stdClass {
		$content = new stdClass();

		try {
			// Check permission
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				throw new Exception( __( 'You do not have permission to perform this action.', 'learnpress' ) );
			}

			$item_selecting = $data['item_selecting'] ?? [];
			$search_item    = trim( $data['lp-search-item'] ?? '' );
			$search_course  = trim( $data['lp-search-course'] ?? '' );
			$search_user    = trim( $data['lp-search-user'] ?? '' );
			$item_type      = trim( $data['lp-item-type'] ?? '' );
			$paged          = max( 1, intval( $data['paged'] ?? 1 ) );

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
			$filter->page        = $paged;
			$filter->only_fields = [
				'DISTINCT(ui.user_item_id)',
				'ui.item_id',
				'ui.user_id',
				'ui.item_type',
				'ui.ref_id',
			];

			// Filter by ref_type = course (items belong to a course)
			$filter->ref_type = LP_COURSE_CPT;

			// Get only user_id > 0
			$filter->where[] = 'AND ui.user_id > 0';

			// Filter by item type
			if ( ! empty( $item_type ) ) {
				$filter->item_type = $item_type;
			} else {
				$item_types   = CourseModel::item_types_support();
				$types_format = LP_Helper::db_format_array( $item_types, '%s' );
				// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$filter->where[] = $db->wpdb->prepare(
					'AND ui.item_type IN (' . $types_format . ')',
					...$item_types
				);
				// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			}

			// Search item by title
			if ( ! empty( $search_item ) ) {
				$filter->join[]  = "INNER JOIN {$db->tb_posts} AS p ON ui.item_id = p.ID";
				$filter->where[] = $db->wpdb->prepare(
					'AND p.post_title LIKE %s',
					'%' . $db->wpdb->esc_like( $search_item ) . '%'
				);
			}

			// Search course by title
			if ( ! empty( $search_course ) ) {
				$filter->join[]  = "INNER JOIN {$db->tb_posts} AS pc ON ui.ref_id = pc.ID";
				$filter->where[] = $db->wpdb->prepare(
					'AND pc.post_title LIKE %s',
					'%' . $db->wpdb->esc_like( $search_course ) . '%'
				);
			}

			// Search user
			if ( ! empty( $search_user ) ) {
				$filter->join[]  = "INNER JOIN {$db->tb_users} AS u ON ui.user_id = u.ID";
				$esc_search_user = '%' . $db->wpdb->esc_like( $search_user ) . '%';
				$filter->where[] = $db->wpdb->prepare(
					'AND (u.display_name LIKE %s OR u.user_login LIKE %s OR u.user_email LIKE %s)',
					$esc_search_user,
					$esc_search_user,
					$esc_search_user
				);
			}

			$userItems   = $db->get_user_items( $filter, $total_rows );
			$total_pages = DataBase::get_total_pages( $limit, $total_rows );

			$html_lis = '';
			if ( empty( $userItems ) ) {
				$html_lis = Template::print_message(
					esc_html__( 'No data found', 'learnpress' ),
					'info',
					false
				);
			} else {
				foreach ( $userItems as $userItem ) {
					$user_item_id = intval( $userItem->user_item_id ?? 0 );
					if ( empty( $user_item_id ) ) {
						continue;
					}

					$checked = '';
					if ( isset( $selected_compare->{$user_item_id} ) ) {
						$checked = ' checked="checked"';
					}

					$item_title_raw   = get_the_title( $userItem->item_id );
					$item_title       = $item_title_raw ? $item_title_raw : __( 'Unknown', 'learnpress' );
					$course_title_raw = get_the_title( $userItem->ref_id );
					$course_title     = $course_title_raw ? $course_title_raw : __( 'Unknown', 'learnpress' );
					$userModel        = UserModel::find( $userItem->user_id, true );
					$user_name        = $userModel ? $userModel->get_display_name() : __( 'Unknown', 'learnpress' );

					$title_display = sprintf(
						'<span class="title">%s <strong>(#%d)</strong> - %s - %s (#%d)</span>',
						esc_html( $item_title ),
						esc_html( $userItem->item_id ),
						esc_html( $course_title ),
						esc_html( $user_name ),
						esc_html( $userItem->user_id )
					);

					$html_lis .= sprintf(
						'<li class="lp-select-item">%s%s</li>',
						sprintf(
							'<input name="lp-select-item"
							value="%1$s"
							data-id="%1$d"
							data-title="%2$s"
							%3$s
							type="checkbox" />',
							esc_attr( $user_item_id ),
							esc_attr( $title_display ),
							esc_attr( $checked )
						),
						$title_display,
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

	/**
	 * Render paginated list of courses that have enrolled users.
	 *
	 * @param array $data Request arguments.
	 *
	 * @return stdClass
	 * @since 4.4.6
	 * @version 1.0.0
	 */
	public static function render_courses_to_reset_progress( array $data ): stdClass {
		$content = new stdClass();

		try {
			$item_selecting = $data['item_selecting'] ?? [];
			$search_course  = trim( $data['lp-search-course'] ?? '' );
			$search_user    = trim( $data['lp-search-user'] ?? '' );
			$paged          = max( 1, intval( $data['paged'] ?? 1 ) );

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
			$filter->page        = $paged;
			$filter->item_type   = LP_COURSE_CPT;
			$filter->only_fields = [ 'DISTINCT(ui.user_item_id)', 'ui.item_id', 'ui.user_id' ];

			// Get only courses has items attendance
			$filter_course_attendance                      = new UserItemsFilter();
			$filter_course_attendance->only_fields         = [ 'parent_id' ];
			$filter_course_attendance->ref_type            = LP_COURSE_CPT;
			$filter_course_attendance->return_string_query = 1;
			$filter_course_attendance->limit               = -1;
			$query_course_attendance                       = $db->get_user_items( $filter_course_attendance );
			$filter->where[]                               = "AND ui.user_item_id IN ({$query_course_attendance})";
			// End get only courses has items attendance

			// Get only user_id > 0
			$filter->where[] = 'AND ui.user_id > 0';

			// Search course
			if ( ! empty( $search_course ) ) {
				$filter->only_fields[] = 'post_title';
				$filter->join[]        = "INNER JOIN {$db->tb_lp_courses} AS c ON ui.item_id = c.ID";
				$filter->where[]       = $db->wpdb->prepare(
					'AND c.post_title LIKE %s',
					'%' . $db->wpdb->esc_like( $search_course ) . '%'
				);
			}

			// Search user
			if ( ! empty( $search_user ) ) {
				$filter->join[]  = "INNER JOIN {$db->tb_users} AS u ON ui.user_id = u.ID";
				$esc_search_user = '%' . $db->wpdb->esc_like( $search_user ) . '%';
				$filter->where[] = $db->wpdb->prepare(
					'AND (u.display_name LIKE %s OR u.user_login LIKE %s OR u.user_email LIKE %s)',
					$esc_search_user,
					$esc_search_user,
					$esc_search_user
				);
			}

			$userCourses = $db->get_user_items( $filter, $total_rows );
			$total_pages = DataBase::get_total_pages( $limit, $total_rows );

			$html_lis = '';
			if ( empty( $userCourses ) ) {
				$html_lis = Template::print_message(
					esc_html__( 'No data found', 'learnpress' ),
					'info',
					false
				);
			} else {
				foreach ( $userCourses as $userCourse ) {
					$userCourseModel = UserCourseModel::find( $userCourse->user_id, $userCourse->item_id, true );
					if ( ! $userCourseModel ) {
						continue;
					}

					$courseModel = $userCourseModel->get_course_model();
					$checked     = '';
					if ( isset( $selected_compare->{$userCourseModel->get_user_item_id()} ) ) {
						$checked = ' checked="checked"';
					}

					$userModel = $userCourseModel->get_user_model();
					$user_id   = $userModel ? $userModel->get_id() : 0;

					$students_text = sprintf(
					/* translators: %d: number of enrolled students */
						__( '%1$s (#%2$d)', 'learnpress' ),
						$user_id ? $userModel->get_display_name() : esc_html__( 'Unknown', 'learnpress' ),
						$user_id
					);

					$title_display = sprintf(
						'<span class="title">%s<strong>(#%d - %s)</strong></span>',
						esc_html( $courseModel ? $courseModel->get_title() : __( 'Unknown', 'learnpress' ) ),
						esc_html( $courseModel ? $courseModel->get_id() : 0 ),
						esc_html( $students_text )
					);

					$html_lis .= sprintf(
						'<li class="lp-select-item">%s%s</li>',
						sprintf(
							'<input name="lp-select-item"
							value="%1$s"
							data-id="%1$d"
							data-title="%2$s"
							%3$s
							type="checkbox" />',
							esc_attr( $userCourseModel->get_user_item_id() ),
							esc_attr( $title_display ),
							esc_attr( $checked )
						),
						$title_display,
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
