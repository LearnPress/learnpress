<?php
/**
 * Class SampleDataService
 *
 * Create and delete sample course data.
 *
 * @since 4.4.5
 * @version 1.0.0
 */

namespace LearnPress\Services;

use Exception;
use LearnPress\Databases\PostDB;
use LearnPress\Databases\QuestionAnswersDB;
use LearnPress\Databases\QuizQuestionsDB;
use LearnPress\Filters\PostFilter;
use LearnPress\Filters\QuestionAnswersFilter;
use LearnPress\Filters\QuizQuestionsFilter;
use LearnPress\Helpers\Singleton;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\CourseSectionItemModel;
use LearnPress\Models\CourseSectionModel;
use LearnPress\Models\LessonPostModel;
use LearnPress\Models\PostModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\Quiz\QuizQuestionModel;
use LearnPress\Models\QuizPostModel;
use LP_WP_Filesystem;
use Throwable;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Class SampleDataService
 */
class SampleDataService {
	use Singleton;

	/**
	 * Singleton init.
	 */
	public function init(): void {}

	/**
	 * Meta key to mark sample data.
	 */
	public const KEY_META_SAMPLE_DATA = '_lp_sample_data';

	/**
	 * @var array
	 */
	protected $section_range = array( 3, 3 );

	/**
	 * @var array
	 */
	protected $item_range = array( 5, 10 );

	/**
	 * @var array
	 */
	protected $question_range = array( 2, 5 );

	/**
	 * @var array
	 */
	protected $answer_range = array( 2, 5 );

	/**
	 * @var int
	 */
	protected $max_content_paragraph = 5;

	/**
	 * @var string
	 */
	protected $dummy_text = '';

	/**
	 * Install sample course data.
	 *
	 * @param array $params
	 *
	 * @return array
	 * @throws Exception
	 */
	public function install( array $params ): CoursePostModel {
		$dummy_text       = LP_WP_Filesystem::instance()->file_get_contents( LP_PLUGIN_PATH . '/dummy-data/dummy-text.txt' );
		$this->dummy_text = preg_split( '!\s!', $dummy_text );

		$section_range = $params['section_range'] ?? array();
		if ( is_array( $section_range ) && 2 === count( $section_range ) ) {
			$this->section_range = array_map( 'intval', $section_range );
		}

		$item_range = $params['item_range'] ?? array();
		if ( is_array( $item_range ) && 2 === count( $item_range ) ) {
			$this->item_range = array_map( 'intval', $item_range );
		}

		$question_range = $params['question_range'] ?? array();
		if ( is_array( $question_range ) && 2 === count( $question_range ) ) {
			$this->question_range = array_map( 'intval', $question_range );
		}

		$answer_range = $params['answer_range'] ?? array();
		if ( is_array( $answer_range ) && 2 === count( $answer_range ) ) {
			$this->answer_range = array_map( 'intval', $answer_range );
		}

		$data = array(
			'price' => floatval( $params['price'] ?? 0 ),
			'name'  => sanitize_text_field( $params['name'] ?? '' ),
		);

		$coursePostModel = $this->create_course( $data );
		$course_id       = $coursePostModel->get_id();

		$this->create_sections( $coursePostModel );

		$courseModel = CourseModel::find( $course_id, true );
		// Unset value of keys for calculate again.
		unset( $courseModel->first_item_id );
		unset( $courseModel->total_items );
		unset( $courseModel->sections_items );
		unset( $courseModel->meta_data->_lp_final_quiz );
		$courseModel->get_first_item_id();
		$courseModel->get_total_items();
		$courseModel->get_section_items();
		$courseModel->get_final_quiz();
		$courseModel->save();

		return $coursePostModel;
	}

	/**
	 * Uninstall sample data.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function uninstall() {
		$posts = $this->get_sample_posts();
		if ( ! $posts ) {
			throw new Exception( esc_html__( 'No data sample.', 'learnpress' ) );
		}

		foreach ( $posts as $post ) {
			$post_id = (int) $post->ID;

			switch ( $post->post_type ) {
				case LP_COURSE_CPT:
					$this->delete_sample_course( $post_id );
					break;
				case LP_QUIZ_CPT:
					$this->delete_sample_quiz( $post_id );

					$quizPostModel = QuizPostModel::find( $post_id );
					if ( $quizPostModel instanceof QuizPostModel ) {
						$quizPostModel->delete();
					}
					break;
				case LP_QUESTION_CPT:
					$this->delete_sample_question_answers( $post_id );

					$questionPostModel = QuestionPostModel::find( $post_id );
					if ( $questionPostModel instanceof QuestionPostModel ) {
						$questionPostModel->delete();
					}
					break;
				case LP_LESSON_CPT:
					$lessonPostModel = LessonPostModel::find( $post_id );
					if ( $lessonPostModel instanceof LessonPostModel ) {
						$lessonPostModel->delete();
					}
					break;
				default:
					$postModel = PostModel::find_by_id( $post_id );
					if ( $postModel instanceof PostModel ) {
						$postModel->delete();
					}
					break;
			}
		}
	}

	/**
	 * Get all posts marked as "sample data".
	 *
	 * Use PostDB/PostFilter instead of raw $wpdb query.
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function get_sample_posts(): array {
		$db         = PostDB::getInstance();
		$post_types = array( LP_COURSE_CPT, LP_LESSON_CPT, LP_QUIZ_CPT, LP_QUESTION_CPT );
		$posts      = array();

		foreach ( $post_types as $post_type ) {
			$filter                  = new PostFilter();
			$filter->post_type       = $post_type;
			$filter->only_fields     = array( 'p.ID', 'p.post_type' );
			$filter->join[]          = sprintf(
				"INNER JOIN %s AS pm ON p.ID = pm.post_id AND pm.meta_key = '%s' AND pm.meta_value = 'yes'",
				$db->tb_postmeta,
				self::KEY_META_SAMPLE_DATA
			);
			$filter->run_query_count = false;

			$rows = $db->get_posts( $filter );
			if ( is_array( $rows ) ) {
				$posts = array_merge( $posts, $rows );
			}
		}

		return $posts;
	}

	/**
	 * Delete a sample course: its section items (lessons/quizzes with their
	 * questions/answers), sections, the course row and the course post.
	 *
	 * @param int $course_id
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function delete_sample_course( int $course_id ) {
		// Not load from cache, to get from Posts table.
		$courseModel = CourseModel::find( $course_id );

		if ( $courseModel instanceof CourseModel ) {
			$sections_items = $courseModel->get_section_items();

			foreach ( $sections_items as $section ) {
				$section_id = $section->section_id ?? 0;

				// Only delete section, not delete items inside section.
				$courseSectionModel = CourseSectionModel::find( $section_id, $course_id, false );
				if ( $courseSectionModel instanceof CourseSectionModel ) {
					$courseSectionModel->delete();
				}
			}

			// No need delete here, delete via hook when trigger $coursePostModel->delete().
		}

		// Delete course post and via hook will delete course on the learnpress_courses table.
		$coursePostModel = CoursePostModel::find_by_id( $course_id );
		if ( $coursePostModel instanceof CoursePostModel ) {
			$coursePostModel->delete();
		}
	}

	/**
	 * Delete quiz questions and their answers of a sample quiz.
	 * Does not delete the quiz post itself.
	 *
	 * @param int $quiz_id
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function delete_sample_quiz( int $quiz_id ) {
		$db                      = QuizQuestionsDB::getInstance();
		$filter                  = new QuizQuestionsFilter();
		$filter->quiz_id         = $quiz_id;
		$filter->query_count     = false;
		$filter->run_query_count = false;
		$filter->field_count     = QuizQuestionsFilter::COL_QUIZ_QUESTION_ID;
		$quiz_questions          = $db->get_quiz_questions( $filter );

		if ( is_array( $quiz_questions ) ) {
			foreach ( $quiz_questions as $quiz_question ) {
				// Only delete map quiz question, not delete question inside quiz.
				$quizQuestionModel = new QuizQuestionModel( $quiz_question );
				$quizQuestionModel->delete();
			}
		}
	}

	/**
	 * Delete all answers of a sample question.
	 *
	 * @param int $question_id
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function delete_sample_question_answers( int $question_id ) {
		$db                 = QuestionAnswersDB::getInstance();
		$filter             = new QuestionAnswersFilter();
		$filter->where[]    = $db->wpdb->prepare( 'AND question_id = %d', $question_id );
		$filter->collection = $db->tb_lp_question_answers;
		$db->delete_execute( $filter );
	}

	/**
	 * Generate content with 'lorem' text.
	 *
	 * @param int $min
	 * @param int $max
	 * @param int $paragraphs
	 *
	 * @return string
	 */
	protected function generate_content( $min = 100, $max = 500, $paragraphs = 10 ) {
		$length = rand( $min, $max );
		$max    = sizeof( $this->dummy_text ) - 1;
		$words  = array();

		for ( $i = 0; $i < $length; $i++ ) {
			$words[] = $this->dummy_text[ rand( 0, $max ) ];
		}

		$p = array();

		if ( ! $paragraphs ) {
			$paragraphs = $this->max_content_paragraph;
		}

		while ( $words && sizeof( $p ) < $paragraphs ) {
			$len = rand( 10, 20 );
			$cut = array_splice( $words, 0, $len );
			$p[] = '<p>' . ucfirst( join( ' ', $cut ) ) . '</p>';
		}

		return join( '', $p );
	}

	/**
	 * Generate title with 'lorem' text.
	 *
	 * @param int $min
	 * @param int $max
	 *
	 * @return string
	 */
	protected function generate_title( $min = 10, $max = 15 ) {
		$length = rand( $min, $max );
		$max    = sizeof( $this->dummy_text ) - 1;
		$words  = array();
		for ( $i = 0; $i < $length; $i++ ) {
			$words[] = $this->dummy_text[ rand( 0, $max ) ];
		}

		return ucfirst( join( ' ', $words ) );
	}

	/**
	 * Create course.
	 *
	 * @param array $data
	 *
	 * @return CoursePostModel
	 * @throws Exception
	 */
	protected function create_course( array $data ): CoursePostModel {
		$title = $data['name'] ?? '';

		$meta_input = array(
			CoursePostModel::META_KEY_DURATION    => '10 week',
			CoursePostModel::META_KEY_SAMPLE_DATA => 'yes',
			CoursePostModel::META_KEY_LEVEL       => 'all',
		);

		// Set price.
		if ( $data['price'] > 0 ) {
			$meta_input[ CoursePostModel::META_KEY_PRICE ]         = $data['price'];
			$meta_input[ CoursePostModel::META_KEY_REGULAR_PRICE ] = $data['price'];
		}

		// Requirements.
		$requirements = array();
		for ( $i = 0, $n = rand( 5, 10 ); $i <= $n; $i++ ) {
			$requirements[] = $this->generate_title();
		}
		$meta_input[ CoursePostModel::META_KEY_REQUIREMENTS ] = $requirements;

		// Target audiences.
		$target_audiences = array();
		for ( $i = 0, $n = rand( 5, 10 ); $i <= $n; $i++ ) {
			$target_audiences[] = $this->generate_title();
		}
		$meta_input[ CoursePostModel::META_KEY_TARGET ] = $target_audiences;

		// Key features.
		$key_features = array();
		for ( $i = 0, $n = rand( 5, 10 ); $i <= $n; $i++ ) {
			$key_features[] = $this->generate_title();
		}
		$meta_input[ CoursePostModel::META_KEY_FEATURES ] = $key_features;

		// FAQs.
		$faqs = array();
		for ( $i = 0, $n = rand( 5, 10 ); $i <= $n; $i++ ) {
			$faqs[] = array( $this->generate_title() . '?', $this->generate_content( 20, 30, 3 ) );
		}
		$meta_input[ CoursePostModel::META_KEY_FAQS ] = $faqs;

		// Featured review.
		$meta_input[ CoursePostModel::META_KEY_FEATURED_REVIEW ] = $this->generate_title( 30, 40 );

		$data_insert = array(
			'post_title'   => ! empty( $title ) ? $title : __( 'Sample course', 'learnpress' ),
			'post_type'    => LP_COURSE_CPT,
			'post_status'  => 'publish',
			'post_content' => $this->generate_content( 25, 40, 5 ),
			'post_author'  => get_current_user_id(),
			'meta_input'   => $meta_input,
		);

		$courseService = CourseService::instance();
		return $courseService->create_info_main( $data_insert );
	}

	/**
	 * Create sections.
	 *
	 * @param CoursePostModel $coursePostModel
	 * @throws Exception
	 */
	protected function create_sections( CoursePostModel $coursePostModel ) {
		$section_length = call_user_func_array( 'rand', $this->section_range );

		for ( $i = 1; $i <= $section_length; $i++ ) {
			$courseSectionModel = $coursePostModel->add_section(
				[ 'section_name' => __( 'Section ', 'learnpress' ) . $i ]
			);
			$section_id         = $courseSectionModel->get_section_id();

			if ( $section_id ) {
				$this->create_section_items( $courseSectionModel );
			}
		}
	}

	/**
	 * Create section items.
	 *
	 * @param CourseSectionModel $courseSectionModel
	 * @throws Exception
	 */
	protected function create_section_items( CourseSectionModel $courseSectionModel ) {
		static $lesson_count = 1;
		static $quiz_count   = 1;

		$order = 0;

		$item_length = call_user_func_array( 'rand', $this->item_range );

		for ( $i = 1; $i < $item_length; $i++ ) {
			$this->create_lesson_and_add_to_section(
				$courseSectionModel,
				__( 'Lesson ', 'learnpress' ) . $lesson_count++
			);
		}

		$quiz_id = $this->create_quiz_and_add_to_section(
			$courseSectionModel,
			__( 'Quiz ', 'learnpress' ) . $quiz_count++
		);

		if ( $quiz_id ) {
			++$order;
		}
	}

	/**
	 * Create lesson.
	 *
	 * @param string $name
	 * @param CourseSectionModel $courseSectionModel
	 * @return CourseSectionItemModel|false
	 * @throws Exception
	 */
	protected function create_lesson_and_add_to_section( CourseSectionModel $courseSectionModel, string $name ) {
		$lessonPostModel               = new LessonPostModel();
		$lessonPostModel->post_title   = $name;
		$lessonPostModel->post_status  = 'publish';
		$lessonPostModel->post_author  = get_current_user_id();
		$lessonPostModel->post_type    = LP_LESSON_CPT;
		$lessonPostModel->post_content = $this->generate_content();
		$lessonPostModel->meta_data->{self::KEY_META_SAMPLE_DATA} = 'yes';

		$lessonPostModel->save();

		$items              = [
			'items' => [
				[
					'id'    => $lessonPostModel->get_id(),
					'type'  => LP_LESSON_CPT,
				],
			],
		];
		$courseSectionItems = $courseSectionModel->add_items( $items );

		return $courseSectionItems[0] ?? false;
	}

	/**
	 * Create quiz.
	 *
	 * @param string $name
	 * @param CourseSectionModel $courseSectionModel
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function create_quiz_and_add_to_section( CourseSectionModel $courseSectionModel, string $name ): int {
		$quizPostModel               = new QuizPostModel();
		$quizPostModel->post_title   = $name;
		$quizPostModel->post_status  = 'publish';
		$quizPostModel->post_author  = get_current_user_id();
		$quizPostModel->post_type    = LP_QUIZ_CPT;
		$quizPostModel->post_content = $this->generate_content( 25, 40, 2 );
		$quizPostModel->meta_data->{self::KEY_META_SAMPLE_DATA} = 'yes';

		$quizPostModel->save();

		$quiz_id = $quizPostModel->get_id();
		if ( $quiz_id ) {
			$items = [
				'items' => [
					[
						'id'    => $quizPostModel->get_id(),
						'type'  => LP_QUIZ_CPT,
					],
				],
			];

			$courseSectionItems = $courseSectionModel->add_items(
				$items
			);

			$this->create_quiz_questions( $quiz_id );
		}

		return $quiz_id;
	}

	/**
	 * Create questions of a quiz.
	 *
	 * @param int $quiz_id
	 */
	protected function create_quiz_questions( $quiz_id ) {
		static $question_index = 1;
		global $wpdb;

		$question_count = call_user_func_array( 'rand', $this->question_range );
		for ( $i = 1; $i <= $question_count; $i++ ) {
			$data = array(
				'post_title'   => 'Question ' . $question_index++,
				'post_type'    => LP_QUESTION_CPT,
				'post_status'  => 'publish',
				'post_content' => $this->generate_content( 25, 40, 2 ),
			);

			$question_id = wp_insert_post( $data );

			if ( ! $question_id ) {
				continue;
			}

			$type = $this->get_question_type();

			update_post_meta( $question_id, '_lp_type', $type );
			update_post_meta( $question_id, self::KEY_META_SAMPLE_DATA, 'yes' );

			$quiz_question_data = array(
				'quiz_id'     => $quiz_id,
				'question_id' => $question_id,
			);

			$wpdb->insert(
				$wpdb->learnpress_quiz_questions,
				$quiz_question_data,
				array( '%d', '%d' )
			);

			if ( $wpdb->insert_id ) {
				$this->create_question_answers( $question_id, $type );
			} else {
				error_log( 'create_quiz_questions => ' . $wpdb->last_error );
			}
		}
	}

	/**
	 * Create answers for a question.
	 *
	 * @param int    $question_id
	 * @param string $type
	 */
	protected function create_question_answers( $question_id, $type ) {
		global $wpdb;

		$answers = $this->get_answers( $type );
		foreach ( $answers as $order => $answer ) {
			$data = array(
				'question_id' => $question_id,
				'title'       => $answer['title'],
				'value'       => $answer['value'],
				'is_true'     => $answer['is_true'],
				'order'       => $order + 1,
			);

			$wpdb->insert(
				$wpdb->learnpress_question_answers,
				$data,
				array( '%d', '%s', '%s', '%s', '%d' )
			);
		}
	}

	/**
	 * Get random answers by type of question.
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	protected function get_answers( $type ) {
		$answers = array();

		$option_count = 'true_or_false' === $type ? 2 : call_user_func_array( 'rand', $this->answer_range );

		for ( $i = 1; $i <= $option_count; $i++ ) {
			$answers[] = array(
				'title'   => $this->generate_title(),
				'value'   => learn_press_random_value(),
				'is_true' => 'no',
			);
		}

		// Set option is TRUE randomize.
		if ( 'multi_choice' !== $type ) {
			$at                        = rand( 0, sizeof( $answers ) - 1 );
			$answers[ $at ]['is_true'] = 'yes';
			$answers[ $at ]['title']   = _x( '[TRUE] - ', 'install-sample-course', 'learnpress' ) . $answers[ $at ]['title'];
		} else {
			$has_true_option = false;
			while ( ! $has_true_option ) {
				foreach ( $answers as $k => $v ) {
					$answers[ $k ]['is_true'] = rand( 0, 100 ) % 2 ? 'yes' : 'no';

					if ( 'yes' === $answers[ $k ]['is_true'] ) {
						$answers[ $k ]['title'] = _x( ' [TRUE] - ', 'install-sample-course', 'learnpress' ) . $answers[ $k ]['title'];
						$has_true_option        = true;
					}
				}
			}
		}

		return $answers;
	}

	/**
	 * Get random type for a question.
	 *
	 * @return string
	 */
	protected function get_question_type() {
		$types = array(
			'true_or_false',
			'single_choice',
			'multi_choice',
		);

		return $types[ rand( 0, sizeof( $types ) - 1 ) ];
	}
}
