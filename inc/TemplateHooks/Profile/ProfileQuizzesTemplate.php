<?php
/**
 * Class ProfileQuizzesTemplate.
 *
 * @since 4.2.8.2
 * @version 1.0.1
 */

namespace LearnPress\TemplateHooks\Profile;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LearnPress\TemplateHooks\Table\TableListTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;
use LearnPress\Models\UserItems\UserQuizModel;
use LP_Database;
use LP_Profile;
use LP_Datetime;
use Exception;
use LP_User_Items_Filter;
use stdClass;
use Throwable;

class ProfileQuizzesTemplate {
	use Singleton;

	public function init() {
		add_filter( 'lp/rest/ajax/allow_callback', array( $this, 'allow_callback' ) );
	}

	/**
	 * Set up the callback for the AJAX request.
	 *
	 * @param array $callbacks
	 *
	 * @return array
	 */
	public function allow_callback( $callbacks ) {
		$callbacks[] = get_class( $this ) . ':renderContent';

		return $callbacks;
	}

	/**
	 * @throws Exception
	 */
	public static function tab_content() {
		$html_wrapper = array(
			'<div class="learn-press-subtab-content">' => '</div>',
		);

		$profile = LP_Profile::instance();
		if ( ! $profile->get_user() ) {
			throw new Exception( __( 'Invalid User Profile', 'learnpress' ) );
		}

		$user_id = $profile->get_user()->get_id();
		if ( ! $user_id ) {
			throw new Exception( __( 'User is not exist', 'learnpress' ) );
		}

		/**
		 * @uses ProfileQuizzesTemplate::renderContent()
		 */
		$callback = array(
			'class'  => self::class,
			'method' => 'renderContent',
		);
		$args     = array(
			'id_url'  => 'profile_quizzes',
			'user_id' => $user_id,
			'paged'   => 1,
			'type'    => 'all',
		);

		$content = TemplateAJAX::load_content_via_ajax( $args, $callback );
		$html    = Template::instance()->nest_elements( $html_wrapper, $content );
		echo $html;
	}

	/**
	 * Render the content for the quiz tab.
	 *
	 * @param array $args
	 *
	 * @return stdClass
	 */
	public static function renderContent( array $args ): stdClass {
		$content = new stdClass();
		$html    = '';

		try {
			$userModel = UserModel::find( $args['user_id'], true );
			if ( ! $userModel ) {
				throw new Exception( __( 'Invalid User', 'learnpress' ) );
			}

			// Check permission, self user or admin can view
			$user_current_id = get_current_user_id();
			if ( $user_current_id !== $userModel->get_id()
				&& ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				throw new Exception( __( 'You do not have permission to view this profile.', 'learnpress' ) );
			}

			$total_rows = 0;
			$limit      = apply_filters(
				'learnpress/profile/user-quizzes/limit',
				get_option( 'posts_per_page', 10 )
			);

			$filter          = new LP_User_Items_Filter();
			$filter->user_id = $userModel->get_id();
			$filter->limit   = $limit;
			$filter->page    = $args['paged'];

			switch ( $args['type'] ) {
				case UserItemModel::STATUS_COMPLETED:
					$filter->status = LP_ITEM_COMPLETED;
					break;
				case UserItemModel::GRADUATION_PASSED:
					$filter->graduation = LP_GRADUATION_PASSED;
					break;
				case UserItemModel::GRADUATION_FAILED:
					$filter->graduation = LP_GRADUATION_FAILED;
					break;
			}

			$user_quizzes = $userModel->get_quizzes_attend( $filter, $total_rows );
			$total_pages  = LP_Database::get_total_pages( $limit, $total_rows );

			$header = [
				[
					'title' => __( 'Quiz', 'learnpress' ),
				],
				[
					'title' => __( 'Result', 'learnpress' ),
				],
				[
					'title' => __( 'Time spent', 'learnpress' ),
				],
				[
					'title' => __( 'Started Date', 'learnpress' ),
				],
			];
			$body   = [];

			if ( ! empty( $user_quizzes ) ) {
				foreach ( $user_quizzes as $user_quiz ) {
					$userQuizModel = UserQuizModel::find_user_item(
						$user_quiz->user_id,
						$user_quiz->item_id,
						$user_quiz->item_type,
						$user_quiz->ref_id,
						$user_quiz->ref_type,
						true
					);
					if ( ! $userQuizModel ) {
						continue;
					}

					$quizPostModel = $userQuizModel->get_quiz_post_model();
					if ( ! $quizPostModel ) {
						continue;
					}

					$courseModel = $userQuizModel->get_course_model();

					$item_body = [
						sprintf(
							'<a href="%s">%s</a>',
							esc_url( $courseModel->get_item_link( $quizPostModel->get_id() ) ),
							esc_html( $quizPostModel->get_the_title() )
						),
						sprintf(
							'<span class="result-percent">%s%%</span><span class="lp-label label-%s">&nbsp;%s</span>',
							esc_html( $userQuizModel->get_result()['result'] ),
							esc_attr( $user_quiz->status ),
							$userQuizModel->get_status_label( $user_quiz->graduation )
						),
						$userQuizModel->get_time_spend(),
						( new LP_Datetime( $userQuizModel->get_start_time() ) )->format( LP_Datetime::I18N_FORMAT ),
					];

					$body[] = $item_body;
				}

				$section_footer = [
					'page_result' => TableListTemplate::instance()->html_page_result(
						[
							'paged'      => $args['paged'],
							'per_page'   => $limit,
							'total_rows' => $total_rows,
						]
					),
					'pagination'  => ListCoursesTemplate::instance()->html_pagination_number(
						[
							'total_pages' => $total_pages,
							'paged'       => $args['paged'],
						]
					),
				];

				$table_args = array(
					'header'      => $header,
					'body'        => $body,
					'footer'      => Template::combine_components( $section_footer ),
					'class_table' => 'profile-list-table profile-list-quizzes',
				);

				$html = TableListTemplate::instance()->html_table( $table_args );
			} else {
				$html = Template::print_message(
					__( 'No quizzes found', 'learnpress' ),
					'info',
					false
				);
			}

			$section = [
				'header'  => self::instance()->html_tabs_header( $args['type'] ),
				'content' => $html,
			];

			$content->content     = Template::combine_components( $section );
			$content->total_pages = $total_pages;
			$content->paged       = $args['paged'];

		} catch ( Throwable $e ) {
			$content->content = Template::print_message( $e->getMessage(), 'error', false );
		}

		return $content;
	}

	/**
	 * Render the header for the quiz tab.
	 *
	 * @param string $tab_active The active tab.
	 *
	 * @return string.
	 */
	public static function html_tabs_header( string $tab_active = 'all' ): string {
		$filter_types = apply_filters(
			'learnpress/profile/quiz-tab/header/filter-types',
			array(
				'all'       => __( 'All', 'learnpress' ),
				'completed' => __( 'Finished', 'learnpress' ),
				'passed'    => __( 'Passed', 'learnpress' ),
				'failed'    => __( 'Failed', 'learnpress' ),
			)
		);

		$html_li = '';
		foreach ( $filter_types as $type => $label ) {
			$html_li .= sprintf(
				'<li class="%s%s">
					<span data-filter="%s">%s</span>
				</li>',
				$type,
				$type === $tab_active ? ' active' : '',
				esc_attr( $type ),
				esc_attr( $label )
			);
		}

		$section = [
			'wrapper'     => '<div class="learn-press-tabs">',
			'types'       => '<div class="learn-press-filters quiz-filter-types">',
			'lis'         => $html_li,
			'types_end'   => '</div>',
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $section );
	}
}
