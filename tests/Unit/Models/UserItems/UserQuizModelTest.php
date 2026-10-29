<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Models\UserItems;

use Brain\Monkey\Functions;
use Exception;
use LearnPress\Databases\UserItemResultsDB;
use LearnPress\Filters\UserItemResultsFilter;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserQuizModel;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use Mockery;
use ReflectionProperty;
use WP_Error;

/**
 * Unit tests for UserQuizModel instant check guard, can check and history filter.
 *
 * Strategy: QuestionPostModel::find() is served from wp_cache_get, UserItemResultsDB singleton
 * is replaced by a mock, so no DB access is needed.
 */
class UserQuizModelTest extends BrainMonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();

		// QuestionPostModel::find() builds a filter before reading cache.
		if ( ! class_exists( 'LP_Question_Filter', false ) ) {
			eval( 'class LP_Question_Filter { public $ID = 0; }' );
		}
	}

	protected function tearDown(): void {
		( new ReflectionProperty( UserItemResultsDB::class, '_instance' ) )->setValue( null, null );

		parent::tearDown();
	}

	/**
	 * Make QuestionPostModel::find( $id, true ) return a model from cache.
	 */
	private function stub_question_found(): void {
		Functions\when( 'maybe_unserialize' )->returnArg( 1 );
		Functions\when( 'wp_cache_get' )->justReturn( new QuestionPostModel( [ 'ID' => 5 ] ) );
	}

	/**
	 * Partial mock UserQuizModel with can_check_instant_answer() returning $can_check.
	 *
	 * @param mixed $can_check
	 *
	 * @return UserQuizModel|Mockery\MockInterface
	 */
	private function make_user_quiz( $can_check ) {
		$userQuizModel = Mockery::mock( UserQuizModel::class )->makePartial();
		$userQuizModel->shouldReceive( 'can_check_instant_answer' )->with( 5 )->andReturn( $can_check );

		return $userQuizModel;
	}

	// -------------------------------------------------------------------------
	// instant_check_question
	// -------------------------------------------------------------------------

	public function test_instant_check_question_throws_wp_error_message(): void {
		$this->stub_question_found();
		$userQuizModel = $this->make_user_quiz( new WP_Error( 'already_checked', 'This question is already selected' ) );
		$userQuizModel->shouldNotReceive( 'calculate_quiz_result' );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'This question is already selected' );

		$userQuizModel->instant_check_question( 5, 'a' );
	}

	public function test_instant_check_question_throws_when_filter_returns_false(): void {
		$this->stub_question_found();
		$userQuizModel = $this->make_user_quiz( false );
		$userQuizModel->shouldNotReceive( 'calculate_quiz_result' );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Cannot check the answer to the question.' );

		$userQuizModel->instant_check_question( 5, 'a' );
	}

	public function test_instant_check_question_returns_checked_when_allowed(): void {
		$this->stub_question_found();
		$userQuizModel = $this->make_user_quiz( true );
		$userQuizModel->shouldReceive( 'get_user_item_id' )->andReturn( 0 );
		$userQuizModel->shouldReceive( 'calculate_quiz_result' )
			->once()
			->with(
				[
					'instant_check' => 1,
					5               => 'a',
				]
			)
			->andReturn(
				[
					'questions' => [
						5 => [
							'mark'    => 2,
							'correct' => true,
						],
					],
				]
			);

		$checked = $userQuizModel->instant_check_question( 5, 'a' );

		$this->assertSame(
			[
				'answered' => 'a',
				'mark'     => 2,
				'correct'  => true,
			],
			$checked
		);
	}

	// -------------------------------------------------------------------------
	// get_history
	// -------------------------------------------------------------------------

	public function test_get_history_filters_by_user_id(): void {
		$captured = null;
		$db       = Mockery::mock( UserItemResultsDB::class );
		$db->shouldReceive( 'get_user_item_results' )
			->once()
			->andReturnUsing(
				function ( UserItemResultsFilter $filter ) use ( &$captured ) {
					$captured = $filter;

					return [];
				}
			);
		( new ReflectionProperty( UserItemResultsDB::class, '_instance' ) )->setValue( null, $db );

		$userQuizModel            = new UserQuizModel();
		$userQuizModel->user_id   = 7;
		$userQuizModel->item_id   = 20;
		$userQuizModel->item_type = LP_QUIZ_CPT;
		$userQuizModel->ref_id    = 30;
		$userQuizModel->ref_type  = LP_COURSE_CPT;

		$history = $userQuizModel->get_history( 3 );

		$this->assertSame( [], $history );
		$this->assertInstanceOf( UserItemResultsFilter::class, $captured );
		$this->assertSame( 7, $captured->user_id );
		$this->assertSame( 20, $captured->item_id );
		$this->assertSame( 30, $captured->ref_id );
		$this->assertSame( 4, $captured->limit );
	}

	// -------------------------------------------------------------------------
	// can_check_instant_answer
	// -------------------------------------------------------------------------

	/**
	 * @param bool $instant_check
	 * @param UserCourseModel|false $userCourseModel
	 * @param int[] $checked_questions
	 *
	 * @return UserQuizModel|Mockery\MockInterface
	 */
	private function make_user_quiz_to_check( bool $instant_check, $userCourseModel, array $checked_questions = [] ) {
		$quizPostModel = Mockery::mock( QuizPostModel::class );
		$quizPostModel->shouldReceive( 'has_instant_check' )->andReturn( $instant_check );

		$userQuizModel = Mockery::mock( UserQuizModel::class )->makePartial();
		$userQuizModel->shouldReceive( 'get_quiz_post_model' )->andReturn( $quizPostModel );
		$userQuizModel->shouldReceive( 'get_user_course_model' )->andReturn( $userCourseModel );
		$userQuizModel->shouldReceive( 'get_checked_questions' )->andReturn( $checked_questions );

		return $userQuizModel;
	}

	/**
	 * @param true|WP_Error $can_impact_item
	 *
	 * @return UserCourseModel|Mockery\MockInterface
	 */
	private function make_user_course( $can_impact_item = true ) {
		$userCourseModel = Mockery::mock( UserCourseModel::class );
		$userCourseModel->shouldReceive( 'can_impact_item' )->andReturn( $can_impact_item );

		return $userCourseModel;
	}

	public function test_can_check_instant_answer_true_when_all_valid(): void {
		$userQuizModel = $this->make_user_quiz_to_check( true, $this->make_user_course(), [ 6 ] );

		$this->assertTrue( $userQuizModel->can_check_instant_answer( 5 ) );
	}

	public function test_can_check_instant_answer_error_when_instant_check_disable(): void {
		$userQuizModel = $this->make_user_quiz_to_check( false, $this->make_user_course() );

		$can_check = $userQuizModel->can_check_instant_answer( 5 );

		$this->assertInstanceOf( WP_Error::class, $can_check );
		$this->assertSame( 'instant_check_disable', $can_check->get_error_code() );
	}

	public function test_can_check_instant_answer_error_when_user_not_enrolled(): void {
		$userQuizModel = $this->make_user_quiz_to_check( true, false );

		$can_check = $userQuizModel->can_check_instant_answer( 5 );

		$this->assertInstanceOf( WP_Error::class, $can_check );
		$this->assertSame( 'not_enrolled_course', $can_check->get_error_code() );
	}

	public function test_can_check_instant_answer_error_when_course_can_not_impact_item(): void {
		// Course finished or expired.
		$error         = new WP_Error( 'course_finished', 'Course is finished' );
		$userQuizModel = $this->make_user_quiz_to_check( true, $this->make_user_course( $error ) );

		$can_check = $userQuizModel->can_check_instant_answer( 5 );

		$this->assertSame( $error, $can_check );
	}

	public function test_can_check_instant_answer_error_when_already_checked(): void {
		$userQuizModel = $this->make_user_quiz_to_check( true, $this->make_user_course(), [ 5 ] );

		$can_check = $userQuizModel->can_check_instant_answer( 5 );

		$this->assertInstanceOf( WP_Error::class, $can_check );
		$this->assertSame( 'already_checked', $can_check->get_error_code() );
	}
}
