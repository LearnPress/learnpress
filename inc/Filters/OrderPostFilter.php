<?php

namespace LearnPress\Filters;

use LearnPress\Databases\PostDB;

defined( 'ABSPATH' ) || exit();

/**
 * Class OrderPostFilter
 *
 * Filter post type LP Order
 *
 * @version 1.0.0
 * @since 4.2.9.3
 */
class OrderPostFilter extends PostFilter {
	public $post_type = 'lp_order';
}
