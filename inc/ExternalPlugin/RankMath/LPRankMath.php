<?php
/**
 * Class LPYoastSeo
 *
 * Compatible with wordpress.org/plugins/wordpress-seo/
 * @since 4.2.7.7
 * @version 1.0.0
 */
namespace LearnPress\ExternalPlugin\RankMath;

use LearnPress\Helpers\Singleton;
use LearnPress\Models\CourseModel;
use LP_Helper;
use LP_Page_Controller;

class LPRankMath {
	use Singleton;

	public function init() {
		add_filter(
			'rank_math/sitemap/exclude_post_type',
			[ $this, 'disable_link_sitemap_with_items_course' ],
			10,
			2
		);
	}

	/**
	 * Disable link sitemap with items course (lesson, quiz, question)
	 *
	 * @param $bool
	 * @param $post_type
	 *
	 * @return bool
	 */
	public function disable_link_sitemap_with_items_course( $bool, $post_type ) {
		$item_types   = CourseModel::item_types_support();
		$item_types[] = LP_QUESTION_CPT;
		if ( in_array( $post_type, $item_types ) ) {
			$bool = true;
		}

		return $bool;
	}
}
