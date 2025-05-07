<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseItemElements;

use LearnPress;
use LP_Debug;
use LP_Global;
use Throwable;

/**
 * Class ItemContentBlockType
 *
 * Handle register, render block template
 */
class ItemContentBlockType extends AbstractCourseItemBlockType {
	public $block_name = 'item-content';

	public function get_supports(): array {
		return [
			'align'                => [ 'wide', 'full' ],
			'color'                => [
				'gradients'  => true,
				'background' => true,
				'text'       => true,
			],
			'typography'           => [
				'fontSize'                    => true,
				'__experimentalFontWeight'    => true,
				'__experimentalTextTransform' => true,
			],
			'shadow'               => true,
			'spacing'              => [
				'padding' => true,
				'margin'  => true,
			],
			'__experimentalBorder' => [
				'color'  => true,
				'radius' => true,
				'width'  => true,
			],
		];
	}

	/**
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 *
	 * @return false|string
	 */
	public function render_content_block_template( array $attributes, $content, $block ): string {
		$html = '';

		try {
					ob_start();
					echo '<div id="learn-press-content-item">

			 <div class="content-item-scrollable">
			     <div class="content-item-wrap">

			<div class="content-item-summary">

			<h1 class="course-item-title lesson-title">Lesson 5</h1>

			<div class="content-item-description lesson-description">
			 <p>Vidisse advesperascit mediocribus adulter everti fiant redderet sequens discimus hinc licentiam lectulis levares</p>
			<p>Acupenserem dicat spelunca opifices cuiusque oculis iniquus platonis littera summis miseriarum testimonium dicat hominem inventus</p>
			<p>Relinquet debuerunt tuae animoque verbis prima vitiose negotii inchoatum don sapiens postulo luci illustris amitti falli consul propter proferebas possent</p>
			<p>Necopinato crimen dempta soletis evolare democritus doleas secusne brevem pertinacem levem hieronymi periculo diem tuum</p>
			<p>Primos singulis persecuti exponere modicum aculeis relinquunt atque evolare phaedro detractus refert spectare squilla odio</p>
			<p>Concordiae mene sensim vivunt postulet quaeram quali cupit optatius praeterita disseretur potius eam augendae praesidii erunt pollicentur recusant hortandus</p>
			<p>Multarum consequentia confecimus miserrimus statui quidque liceat occultissimarum intervalla longinquum iisque</p>
			<p>Sanguine zeno artem usum summum dissensione eorum alias attinet prosunt appellamus sensitque vestra consuetudo commodaita</p>
			<p>Serpere vitae labefactare commovebat quanto dies subito qualis impedit ingredimur incontentae aderit etenim rationis malitiam audax amicitia quodsi malitiam</p>
			<p>Pietatem quibus gravior ita mentio essentne putaverunt habere nominati gravis vocant legem iocari sequens duxisse viros simonides</p>
			</div>
			 <form method="post" name="learn-press-form-complete-lesson" action="http://lp.local/lp-ajax-handle?complete-lesson" class="learn-press-form form-button " data-title="Complete lesson" data-confirm="Do you want to complete the lesson &quot;Lesson 5&quot;?">

			     <input type="hidden" name="lesson_id" value="767">
			     <input type="hidden" name="course_id" value="762">
			     <input type="hidden" name="nonce" value="099bbede99">
			     <input type="hidden" name="lp-load-ajax" value="user_complete_lesson">
			     <button class="lp-button button-complete-lesson lp-btn-complete-item" type="submit">
			         Complete        </button>

			 </form>

			</div>

			     </div>
			 </div>

			</div>';

			$html_content = ob_get_clean();
			$html         = $this->get_output( $html_content );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}
