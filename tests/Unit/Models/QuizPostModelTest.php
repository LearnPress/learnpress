<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Models;

use Brain\Monkey\Functions;
use LearnPress\Models\QuizPostModel;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

/**
 * Unit tests for QuizPostModel option getters.
 *
 * Strategy: pre-populate $meta_data so getters read meta without DB / get_post_meta.
 */
class QuizPostModelTest extends BrainMonkeyTestCase {

	/**
	 * Build a QuizPostModel with meta_data pre-populated.
	 *
	 * @param array $meta key → value pairs written to meta_data.
	 */
	private function make_quiz( array $meta = [] ): QuizPostModel {
		Functions\when( 'maybe_unserialize' )->returnArg( 1 );

		$quiz            = new QuizPostModel( [ 'ID' => 10 ] );
		$quiz->meta_data = (object) $meta;

		return $quiz;
	}

	/**
	 * Build a QuizPostModel without meta, get_post_meta returns $value.
	 *
	 * @param mixed $value value returned by get_post_meta.
	 */
	private function make_quiz_no_meta( $value ): QuizPostModel {
		Functions\when( 'maybe_unserialize' )->returnArg( 1 );
		Functions\when( 'get_post_meta' )->justReturn( $value );

		return new QuizPostModel( [ 'ID' => 10 ] );
	}

	// -------------------------------------------------------------------------
	// get_pagination
	// -------------------------------------------------------------------------

	public function test_get_pagination_returns_meta_value(): void {
		$quiz = $this->make_quiz( [ QuizPostModel::META_KEY_PAGINATION => '5' ] );

		$this->assertSame( 5, $quiz->get_pagination() );
	}

	public function test_get_pagination_zero_shows_all_on_one_page(): void {
		$quiz = $this->make_quiz( [ QuizPostModel::META_KEY_PAGINATION => '0' ] );

		$this->assertSame( 0, $quiz->get_pagination() );
	}

	public function test_get_pagination_zero_from_post_meta_shows_all_on_one_page(): void {
		$quiz = $this->make_quiz_no_meta( '0' );

		$this->assertSame( 0, $quiz->get_pagination() );
	}

	public function test_get_pagination_number_from_post_meta(): void {
		$quiz = $this->make_quiz_no_meta( '3' );

		$this->assertSame( 3, $quiz->get_pagination() );
	}

	public function test_get_pagination_negative_becomes_absolute_value(): void {
		$quiz = $this->make_quiz( [ QuizPostModel::META_KEY_PAGINATION => '-3' ] );

		$this->assertSame( 3, $quiz->get_pagination() );
	}

	public function test_get_pagination_invalid_string_becomes_zero(): void {
		$quiz = $this->make_quiz( [ QuizPostModel::META_KEY_PAGINATION => 'abc' ] );

		$this->assertSame( 0, $quiz->get_pagination() );
	}

	public function test_get_pagination_default_one_when_meta_missing(): void {
		$quiz = $this->make_quiz_no_meta( '' );

		$this->assertSame( 1, $quiz->get_pagination() );
	}

	// -------------------------------------------------------------------------
	// has_review
	// -------------------------------------------------------------------------

	public function test_has_review_true_when_yes(): void {
		$quiz = $this->make_quiz( [ QuizPostModel::META_KEY_REVIEW => 'yes' ] );

		$this->assertTrue( $quiz->has_review() );
	}

	public function test_has_review_false_when_no(): void {
		$quiz = $this->make_quiz( [ QuizPostModel::META_KEY_REVIEW => 'no' ] );

		$this->assertFalse( $quiz->has_review() );
	}

	public function test_has_review_default_yes_when_meta_missing(): void {
		$quiz = $this->make_quiz_no_meta( '' );

		$this->assertTrue( $quiz->has_review() );
	}
}
