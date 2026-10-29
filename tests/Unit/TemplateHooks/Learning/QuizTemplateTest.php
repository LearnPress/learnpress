<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\TemplateHooks\Learning;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LearnPress\Models\QuizPostModel;
use LearnPress\TemplateHooks\Learning\QuizTemplate;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use Mockery;

require_once dirname( __DIR__, 4 ) . '/inc/class-lp-duration.php';

/**
 * Unit tests for QuizTemplate (quiz-level HTML, not depend on user).
 */
class QuizTemplateTest extends BrainMonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'wp_kses_post' )->returnArg( 1 );
	}

	/**
	 * @param array $args [ 'duration' => string, 'grade' => float, 'questions' => int, 'content' => string ]
	 *
	 * @return QuizPostModel|Mockery\MockInterface
	 */
	private function make_quiz( array $args = [] ) {
		$args = array_merge(
			[
				'duration'  => '30 minute',
				'grade'     => 80.0,
				'questions' => 5,
				'content'   => '<p>Quiz description</p>',
			],
			$args
		);

		$quiz = Mockery::mock( QuizPostModel::class )->makePartial();
		$quiz->shouldReceive( 'get_id' )->andReturn( 20 );
		$quiz->shouldReceive( 'get_duration' )->andReturn( $args['duration'] );
		$quiz->shouldReceive( 'get_passing_grade' )->andReturn( $args['grade'] );
		$quiz->shouldReceive( 'count_questions' )->andReturn( $args['questions'] );
		$quiz->shouldReceive( 'get_the_content' )->andReturn( $args['content'] );

		return $quiz;
	}

	// -------------------------------------------------------------------------
	// html_description
	// -------------------------------------------------------------------------

	public function test_html_description_wraps_content(): void {
		$html = QuizTemplate::instance()->html_description( $this->make_quiz() );

		$this->assertSame( '<div class="quiz-content"><p>Quiz description</p></div>', $html );
	}

	public function test_html_description_applies_filter(): void {
		Filters\expectApplied( 'learn-press/quiz/html-description' )
			->once()
			->andReturnUsing(
				static function ( array $section ) {
					$section['content'] = 'Filtered';

					return $section;
				}
			);

		$html = QuizTemplate::instance()->html_description( $this->make_quiz() );

		$this->assertSame( '<div class="quiz-content">Filtered</div>', $html );
	}

	// -------------------------------------------------------------------------
	// html_meta
	// -------------------------------------------------------------------------

	public function test_html_meta_shows_duration_grade_and_question_count(): void {
		$html = QuizTemplate::instance()->html_meta( $this->make_quiz() );

		$this->assertStringStartsWith( '<ul class="quiz-intro">', $html );
		$this->assertStringContainsString( 'quiz-intro-item--duration', $html );
		$this->assertStringContainsString( '<span class="quiz-intro-item__content">00:30:00</span>', $html );
		$this->assertStringContainsString( 'quiz-intro-item--passing-grade', $html );
		$this->assertStringContainsString( '<span class="quiz-intro-item__content">80</span>', $html );
		$this->assertStringContainsString( 'quiz-intro-item--questions-count', $html );
		$this->assertStringContainsString( '<span class="quiz-intro-item__content">5</span>', $html );
	}

	public function test_html_meta_shows_dashes_when_no_duration_and_no_grade(): void {
		$html = QuizTemplate::instance()->html_meta( $this->make_quiz( [ 'duration' => '0 minute', 'grade' => 0.0 ] ) );

		$this->assertSame( 2, substr_count( $html, '<span class="quiz-intro-item__content">--</span>' ) );
	}

	public function test_html_meta_fields_filter_can_add_field(): void {
		Filters\expectApplied( 'learn-press/quiz/meta-fields' )
			->once()
			->andReturnUsing(
				static function ( array $fields ) {
					$fields['custom'] = [
						'title'   => 'Custom:',
						'content' => 'Value',
					];

					return $fields;
				}
			);

		$html = QuizTemplate::instance()->html_meta( $this->make_quiz() );

		$this->assertStringContainsString( 'quiz-intro-item--custom', $html );
		$this->assertStringContainsString( '<span class="quiz-intro-item__content">Value</span>', $html );
	}

	// -------------------------------------------------------------------------
	// format_duration
	// -------------------------------------------------------------------------

	public function test_format_duration(): void {
		$template = QuizTemplate::instance();

		$this->assertSame( '00:00:00', $template->format_duration( 0 ) );
		$this->assertSame( '01:01:01', $template->format_duration( 3661 ) );
		$this->assertSame( '25:00:00', $template->format_duration( 90000 ) );
		$this->assertSame( '00:00:00', $template->format_duration( -5 ) );
	}
}
