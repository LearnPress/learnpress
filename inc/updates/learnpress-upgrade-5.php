<?php

use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\Courses;

/**
 * Class LP_Upgrade_5
 *
 * Helper class for updating database to version 5
 *
 * @version 1.0.0
 * @since 4.2.6.9
 */
class LP_Upgrade_5 extends LP_Handle_Upgrade_Steps {
	/**
	 * @var LP_Upgrade_4
	 */
	protected static $instance = null;

	/**
	 * Get Instance
	 *
	 * @return LP_Upgrade_5
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * LP_Upgrade_5 constructor.
	 *
	 * @see sync_table_courses
	 * @see finish_upgrade
	 */
	protected function __construct() {
		$this->version = '5.0.0';
		/**
		 * Name key not > 50 character
		 */
		$this->group_steps = apply_filters(
			'lp/upgrade/5/steps',
			array(
				'learnpress_user_items' => new LP_Group_Step(
					'learnpress_user_items',
					'Optimize database courses',
					array(
						'sync_table_courses'  => new LP_Step(
							'sync_table_courses',
							'Sync table courses',
							'This process sync post type lp_course to table learnpress_courses'
						),
						'finish_upgrade' => new LP_Step(
							'finish_upgrade',
							'Update settings',
							'Update settings'
						),
					)
				),
			)
		);
	}

	/**
	 * Step Create Tables
	 *
	 * @param array $data
	 *
	 * @return LP_Step
	 */
	protected function sync_table_courses( array $data ): LP_Step {
		$response = new LP_Step( __FUNCTION__, 'Sync table courses' );
		$limit    = 5;
		$page     = 1;

		try {
			if ( empty( $data ) ) {
				// Check table learnpress_courses exists.
				LP_Install::instance()->create_table_courses();

				$total_row           = 0;
				$filter              = new LP_Course_Filter();
				$filter->post_status = [];
				$filter->where[]     = "AND p.post_status != 'auto-draft'";
				$filter->limit       = $limit;
				Courses::get_courses( $filter, $total_row );
				$total_pages = LP_Database::get_total_pages( $limit, $total_row );
			} else {
				$page        = $data['page'];
				$total_pages = $data['total_pages'];
			}

			// Get courses from table posts.
			$filter                  = new LP_Course_Filter();
			$filter->page            = $page;
			$filter->limit           = $limit;
			$filter->post_status     = [];
			$filter->where[]         = "AND p.post_status != 'auto-draft'";
			$filter->run_query_count = false;
			$courses                 = Courses::get_courses( $filter );
			foreach ( $courses as $course_obj ) {
				$coursePostModel = new CoursePostModel( $course_obj );
				$coursePostModel->get_all_metadata();
				$courseModelNew = new CourseModel( $coursePostModel );
				$courseModelNew->get_price();
				$courseModelNew->save();
				$bg = LP_Background_Single_Course::instance();
				$bg->data(
					array(
						'handle_name' => 'save_post',
						'course_id'   => $courseModelNew->get_id(),
						'data'        => [],
					)
				)->dispatch();
			}

			if ( $page >= $total_pages ) {
				return $this->finish_step( $response, 'Step ' . __FUNCTION__ . ' finished' );
			}

			$percent                     = $filter->page * 100 / $total_pages;
			$percent                     = floatval( number_format( $percent, 2 ) );
			$response->percent           = $percent;
			$response->status            = 'success';
			$response->data->page        = ++ $page;
			$response->data->total_pages = $total_pages;
		} catch ( Exception $e ) {
			$response->message = $this->error_step( $response->name, $e->getMessage() );
		}

		return $response;
	}

	/**
	 * Convert Learnpress Settings
	 * 1. Courses thumbnail dimensions convert.
	 * 2. Profile thumbnail dimensions convert.
	 * 3. Profile rename dashboard to overview.
	 * 4. Block course by duration.
	 * 5. Block course when finished.
	 * 6. Assessment course by quizzes - Evaluate.
	 *
	 * @return LP_Step
	 */
	protected function finish_upgrade(): LP_Step {
		$response = new LP_Step( __FUNCTION__, '' );
		$lp_db    = LP_Database::getInstance();

		try {
			// Finish upgrade.
			update_option( LP_KEY_DB_VERSION, 5 );
		} catch ( Exception $e ) {
			$response->message = $this->error_step( $response->name, $e->getMessage() );

			return $response;
		}

		return $this->finish_step( $response, __FUNCTION__ . ' finished' );
	}
}

LP_Upgrade_5::get_instance();
