<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\TemplateHooks\UserItem;

use LearnPress\Models\Question\QuestionPostFIBModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\TemplateHooks\UserItem\UserQuizTemplate;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use ReflectionMethod;

/**
 * Question snapshot saved in quiz result ('questionSnapshot') is used instead of querying the question.
 */
class UserQuizTemplateSnapshotTest extends BrainMonkeyTestCase {

	private function build( array $question_result ) {
		$ref = new ReflectionMethod( UserQuizTemplate::class, 'get_question_model_from_result' );

		return $ref->invoke( UserQuizTemplate::instance(), $question_result );
	}

	private function snapshot( string $type = 'multi_choice' ): array {
		return [
			'ID'             => 3211,
			'question_type'  => $type,
			'post_title'     => 'My question',
			'post_content'   => 'Content',
			'meta_data'      => [
				'_lp_type' => $type,
				'_lp_mark' => '1',
			],
			'answers_config' => [ [ 'title' => 'First option' ] ],
		];
	}

	public function test_model_built_from_snapshot_without_query(): void {
		$model = $this->build( [ 'questionSnapshot' => $this->snapshot() ] );

		$this->assertInstanceOf( QuestionPostModel::class, $model );
		$this->assertSame( 3211, $model->get_id() );
		$this->assertSame( 'multi_choice', $model->get_type() );
		$this->assertSame( 'My question', $model->post_title );
		$this->assertSame( 'Content', $model->post_content );
	}

	public function test_model_subclass_picked_by_type(): void {
		$model = $this->build( [ 'questionSnapshot' => $this->snapshot( 'fill_in_blanks' ) ] );

		$this->assertInstanceOf( QuestionPostFIBModel::class, $model );
	}

	public function test_old_result_without_snapshot_returns_false(): void {
		$this->assertFalse( $this->build( [ 'answered' => 'a' ] ) );
	}

	public function test_snapshot_without_id_returns_false(): void {
		$snapshot = $this->snapshot();
		unset( $snapshot['ID'] );

		$this->assertFalse( $this->build( [ 'questionSnapshot' => $snapshot ] ) );
	}
}
