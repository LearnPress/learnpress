<?php

/**
 * Class Course Post Model
 * To replace class LP_Course old
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.6.9
 */

namespace LearnPress\Models;

use Exception;
use LearnPress;
use LP_Course_Cache;
use LP_Course_Filter;
use LP_Datetime;

use Throwable;
use WP_Post;
use WP_Term;

class CoursePostModel extends PostModel {
	/**
	 * @var string Post Type
	 */
	public $post_type = LP_COURSE_CPT;

	/**
	 * Const meta key
	 */
	const META_KEY_PRICE = '_lp_price';
	const META_KEY_REGULAR_PRICE = '_lp_regular_price';
	const META_KEY_SALE_PRICE = '_lp_sale_price';
	const META_KEY_SALE_START = '_lp_sale_start';
	const META_KEY_SALE_END = '_lp_sale_end';
	const META_KEY_PASSING_CONDITION = '_lp_passing_condition';
	const META_KEY_DURATION = '_lp_duration';
	const META_KEY_BLOCK_EXPIRE_DURATION = '_lp_block_expire_duration';
	const META_KEY_BLOCK_FINISH = '_lp_block_finished';
	const META_KEY_ALLOW_COURSE_REPURCHASE = '_lp_allow_course_repurchase';
	const META_KEY_COURSE_REPURCHASE_OPTION = '_lp_course_repurchase_option';
	const META_KEY_LEVEL = '_lp_level';
	const META_KEY_STUDENTS = '_lp_students';
	const META_KEY_MAX_STUDENTS = '_lp_max_students';
	const META_KEY_RETAKE_COUNT = '_lp_retake_count';
	const META_KEY_HAS_FINISH = '_lp_has_finish';
	const META_KEY_FEATURED = '_lp_featured';
	const META_KEY_FEATURED_REVIEW = '_lp_featured_review';
	const META_KEY_EXTERNAL_LINK_BY_COURSE = '_lp_external_link_buy_course';
	const META_KEY_IS_SALE = '_lp_course_is_sale';
	const META_KEY_NO_REQUIRED_ENROLL = '_lp_no_required_enroll';
	const META_KEY_OFFLINE_COURSE = '_lp_offline_course';
	const META_KEY_ADDRESS = '_lp_address';
	const META_KEY_DELIVER = '_lp_deliver_type';
	const META_KEY_OFFLINE_LESSON_COUNT = '_lp_offline_lesson_count';

	/**
	 * Get the price of course.
	 *
	 * @return float
	 */
	public function get_price(): float {
		$key_cache = "{$this->ID}/price";
		$price     = LP_Course_Cache::cache_load_first( 'get', $key_cache );

		if ( false === $price ) {
			if ( $this->has_sale_price() ) {
				$price = $this->get_sale_price();
			} else {
				$price = $this->get_regular_price();
			}

			// Save price only on page Single Course
			/*if ( LP_PAGE_SINGLE_COURSE === LP_Page_Controller::page_current() ) {
				update_post_meta( $this->get_id(), '_lp_price', $price );
			}*/

			LP_Course_Cache::cache_load_first( 'set', $key_cache, $price );
		}

		return apply_filters( 'learnPress/course/price', (float) $price, $this->get_id() );
	}

	/**
	 * Get the regular price of course.
	 *
	 * @return float
	 */
	public function get_regular_price(): float {
		// Regular price
		$regular_price = $this->get_meta_value_by_key( self::META_KEY_PRICE, '' ); // For LP version < 1.4.1.2
		if ( metadata_exists( 'post', $this->ID, self::META_KEY_REGULAR_PRICE ) ) {
			$regular_price = $this->get_meta_value_by_key( self::META_KEY_REGULAR_PRICE, '' );
		}

		$regular_price = floatval( $regular_price );

		return apply_filters( 'learnPress/course/regular-price', $regular_price, $this );
	}

	/**
	 * Get the sale price of course. Check if sale price is set
	 * and the dates are valid.
	 *
	 * @return string|float
	 */
	public function get_sale_price() {
		$sale_price_value = $this->get_meta_value_by_key( self::META_KEY_SALE_PRICE, '' );

		if ( '' !== $sale_price_value ) {
			return floatval( $sale_price_value );
		}

		return $sale_price_value;
	}

	/**
	 * Check course has 'sale price'
	 *
	 * @return bool
	 */
	public function has_sale_price(): bool {
		$has_sale_price = false;
		$regular_price  = $this->get_regular_price();
		$sale_price     = $this->get_sale_price();
		$start_date     = $this->get_meta_value_by_key( self::META_KEY_SALE_START );
		$end_date       = $this->get_meta_value_by_key( self::META_KEY_SALE_END );

		if ( $regular_price > $sale_price && is_float( $sale_price ) ) {
			$has_sale_price = true;
		}

		// Check in days sale
		if ( $has_sale_price && ! empty( $start_date ) && ! empty( $end_date ) ) {
			$nowObj = new LP_Datetime();
			// Compare via timezone WP
			$nowStr = $nowObj->toSql( true );
			$now    = strtotime( $nowStr );
			$end    = strtotime( $end_date );
			$start  = strtotime( $start_date );

			$has_sale_price = $now >= $start && $now <= $end;
		}

		return apply_filters( 'learnPress/course/has-sale-price', $has_sale_price, $this );
	}

	/**
	 * Check if a course is Free
	 *
	 * @return bool
	 */
	public function is_free(): bool {
		return apply_filters( 'learnPress/course/is-free', $this->get_price() == 0, $this );
	}

	/**
	 * Get html course price
	 *
	 * @return string
	 * @since 4.1.5
	 * @version 1.0.1
	 * @author tungnx
	 */
	public function get_price_html(): string {
		$price_html = '';

		if ( $this->is_free() ) {
			if ( is_float( $this->get_sale_price() ) ) {
				$price_html .= sprintf( '<span class="origin-price">%s</span>', $this->get_regular_price_html() );
			}

			$price_html .= sprintf( '<span class="free">%s</span>', esc_html__( 'Free', 'learnpress' ) );
			$price_html = apply_filters( 'learn_press_course_price_html_free', $price_html, $this );
		} elseif ( $this->get_meta_value_by_key( self::META_KEY_NO_REQUIRED_ENROLL, 'no' ) === 'yes' ) {
			$price_html .= '';
		} else {
			if ( $this->has_sale_price() ) {
				$price_html .= sprintf( '<span class="origin-price">%s</span>', $this->get_regular_price_html() );
			}

			$price_html .= sprintf( '<span class="price">%s</span>', learn_press_format_price( $this->get_price(), true ) );
			$price_html = apply_filters( 'learn_press_course_price_html', $price_html, $this->has_sale_price(), $this->get_id() );
		}

		return sprintf( '<span class="course-item-price">%s</span>', $price_html );
	}

	/**
	 * Get the regular price format of course.
	 *
	 * @return mixed
	 * @version 1.0.0
	 * @author tungnx
	 * @since 4.1.5
	 */
	public function get_regular_price_html() {
		$price = learn_press_format_price( $this->get_regular_price(), true );

		return apply_filters( 'learnPress/course/regular-price', $price, $this );
	}

	/**
	 * Get post course by ID
	 *
	 * @param int $post_id
	 * @param bool $check_cache
	 *
	 * @return false|static
	 */
	public static function find( int $post_id, bool $check_cache = false ) {
		$filter_post     = new LP_Course_Filter();
		$filter_post->ID = $post_id;

		return self::get_item_model_from_db( $filter_post );
	}
}
