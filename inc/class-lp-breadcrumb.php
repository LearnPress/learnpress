<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LP_Breadcrumb class.
 * This class is modified from WC_Breadcrumb of WooCommerce. Thanks to WooThemes :)
 *
 * @class          LP_Breadcrumb
 *
 * @author         ThimPress
 * @version        1.0
 * @package        LearnPress/Classes
 */
class LP_Breadcrumb {

	/**
	 * Breadcrumb trail
	 *
	 * @var array
	 */
	private $crumbs = array();

	/**
	 * Add a crumb so we don't get lost
	 *
	 * @param string $name
	 * @param string $link
	 */
	public function add_crumb( $name, $link = '' ) {
		$this->crumbs[] = array(
			$name,
			$link
		);
	}

	/**
	 * Reset crumbs
	 */
	public function reset() {
		$this->crumbs = array();
	}

	/**
	 * Get the breadcrumb
	 *
	 * @return array
	 */
	public function get_breadcrumb() {
		return apply_filters( 'learn_press_get_breadcrumb', $this->crumbs, $this );
	}

	/**
	 * Generate breadcrumb trail
	 *
	 * @return array of breadcrumbs
	 */
	public function generate() {
		$conditionals = array(
			'is_home',
			'is_404',
			'is_attachment',
			'is_single',
			'learn_press_is_course_category',
			'learn_press_is_course_tag',
			'learn_press_is_courses',
			'is_page',
			'is_post_type_archive',
			'is_category',
			'is_tag',
			'is_author',
			'is_date',
			'is_tax'
		);

		if ( ( !is_front_page() && !( is_post_type_archive() && get_option( 'page_on_front' ) == learn_press_get_page_id( 'courses' ) ) ) || is_paged() ) {
			foreach ( $conditionals as $conditional ) {
				if ( is_callable( $conditional ) && call_user_func( $conditional ) ) {
					$conditional = preg_replace( '/^learn_press_/', '', $conditional );
					$conditional = preg_replace( '/^is_/', '', $conditional );
					if( is_callable( array( $this, 'add_crumbs_' . $conditional ) ) ) {
						call_user_func( array( $this, 'add_crumbs_' . $conditional ) );
					}
					break;
				}
			}

			$this->search_trail();
			$this->paged_trail();

			return $this->get_breadcrumb();
		}

		return array();
	}

	/**
	 * Prepend the courses page to courses breadcrumbs
	 */
	private function prepend_courses_page() {
		$permalinks      = get_option( 'learn_press_permalinks' );
		$courses_page_id = learn_press_get_page_id( 'courses' );
		$courses_page    = get_post( $courses_page_id );

		// If permalinks contain the courses page in the URI prepend the breadcrumb with courses
		if ( $courses_page_id && $courses_page /*&& strstr( $permalinks['lp_course_base'], '/' . $courses_page->post_name )*/ && get_option( 'page_on_front' ) != $courses_page_id ) {
			$this->add_crumb( get_the_title( $courses_page ), get_permalink( $courses_page ) );
		}
	}

	/**
	 * is home trail
	 */
	private function add_crumbs_home() {
		$this->add_crumb( single_post_title( '', false ) );
	}

	/**
	 * 404 trail
	 */
	private function add_crumbs_404() {
		$this->add_crumb( __( 'Error 404', 'learnpress' ) );
	}

	/**
	 * attachment trail
	 */
	private function add_crumbs_attachment() {
		global $post;

		$this->add_crumbs_single( $post->post_parent, get_permalink( $post->post_parent ) );
		$this->add_crumb( get_the_title(), get_permalink() );
	}

	/**
	 * Single post trail
	 *
	 * @param int    $post_id
	 * @param string $permalink
	 */
	private function add_crumbs_single( $post_id = 0, $permalink = '' ) {
		if ( !$post_id ) {
			global $post;
		} else {
			$post = get_post( $post_id );
		}
		if ( 'lp_course' === get_post_type( $post ) ) {
			$this->prepend_courses_page();
			if ( $terms = learn_press_get_course_terms( $post->ID, 'course_category', array( 'orderby' => 'parent', 'order' => 'DESC' ) ) ) {
				$main_term = apply_filters( 'learn_press_breadcrumb_main_term', $terms[0], $terms );
				$this->term_ancestors( $main_term->term_id, 'course_category' );
				$this->add_crumb( $main_term->name, get_term_link( $main_term ) );
			}
		} elseif ( 'post' != get_post_type( $post ) ) {
			$post_type = get_post_type_object( get_post_type( $post ) );
			$this->add_crumb( $post_type->labels->singular_name, get_post_type_archive_link( get_post_type( $post ) ) );
		} else {
			$cat = current( get_the_category( $post ) );
			if ( $cat ) {
				$this->term_ancestors( $cat->term_id, 'post_category' );
				$this->add_crumb( $cat->name, get_term_link( $cat ) );
			}
		}

		$this->add_crumb( get_the_title( $post ), $permalink );
	}

	/**
	 * Page trail
	 */
	private function add_crumbs_page() {
		global $post;

		if ( $post->post_parent ) {
			$parent_crumbs = array();
			$parent_id     = $post->post_parent;

			while ( $parent_id ) {
				$page            = get_post( $parent_id );
				$parent_id       = $page->post_parent;
				$parent_crumbs[] = array( get_the_title( $page->ID ), get_permalink( $page->ID ) );
			}

			$parent_crumbs = array_reverse( $parent_crumbs );

			foreach ( $parent_crumbs as $crumb ) {
				$this->add_crumb( $crumb[0], $crumb[1] );
			}
		}

		$this->add_crumb( get_the_title(), get_permalink() );
		$this->endpoint_trail();
	}

	/**
	 * Product category trail
	 */
	private function add_crumbs_course_category() {
		$current_term = $GLOBALS['wp_query']->get_queried_object();

		$this->prepend_courses_page();
		$this->term_ancestors( $current_term->term_id, 'course_category' );
		$this->add_crumb( $current_term->name );
	}

	/**
	 * Course tag trail
	 */
	private function add_crumbs_course_tag() {
		$current_term = $GLOBALS['wp_query']->get_queried_object();

		$this->prepend_courses_page();
		$this->add_crumb( sprintf( __( 'Courses tagged &ldquo;%s&rdquo;', 'learnpress' ), $current_term->name ) );
	}

	/**
	 * Courses archive breadcrumb
	 */
	private function add_crumbs_courses() {
		if ( get_option( 'page_on_front' ) == learn_press_get_page_id( 'courses' ) ) {
			return;
		}

		$_name = learn_press_get_page_id( 'courses' ) ? get_the_title( learn_press_get_page_id( 'courses' ) ) : '';

		if ( !$_name ) {
			$course_post_type = get_post_type_object( 'course' );
			$_name             = $course_post_type->labels->singular_name;
		}

		$this->add_crumb( $_name, get_post_type_archive_link( 'lp_course' ) );
	}

	/**
	 * Post type archive trail
	 */
	private function add_crumbs_post_type_archive() {
		$post_type = get_post_type_object( get_post_type() );

		if ( $post_type ) {
			$this->add_crumb( $post_type->labels->singular_name, get_post_type_archive_link( get_post_type() ) );
		}
	}

	/**
	 * Category trail
	 */
	private function add_crumbs_category() {
		$this_category = get_category( $GLOBALS['wp_query']->get_queried_object() );

		if ( 0 != $this_category->parent ) {
			$this->term_ancestors( $this_category->parent, 'post_category' );
			$this->add_crumb( $this_category->name, get_category_link( $this_category->term_id ) );
		}

		$this->add_crumb( single_cat_title( '', false ), get_category_link( $this_category->term_id ) );
	}

	/**
	 * Tag trail
	 */
	private function add_crumbs_tag() {
		$queried_object = $GLOBALS['wp_query']->get_queried_object();
		$this->add_crumb( sprintf( __( 'Posts tagged &ldquo;%s&rdquo;', 'learnpress' ), single_tag_title( '', false ) ), get_tag_link( $queried_object->term_id ) );
	}

	/**
	 * Add crumbs for date based archives
	 */
	private function add_crumbs_date() {
		if ( is_year() || is_month() || is_day() ) {
			$this->add_crumb( get_the_time( 'Y' ), get_year_link( get_the_time( 'Y' ) ) );
		}
		if ( is_month() || is_day() ) {
			$this->add_crumb( get_the_time( 'F' ), get_month_link( get_the_time( 'Y' ), get_the_time( 'm' ) ) );
		}
		if ( is_day() ) {
			$this->add_crumb( get_the_time( 'd' ) );
		}
	}

	/**
	 * Add crumbs for date based archives
	 */
	private function add_crumbs_tax() {
		$this_term = $GLOBALS['wp_query']->get_queried_object();
		$taxonomy  = get_taxonomy( $this_term->taxonomy );

		$this->add_crumb( $taxonomy->labels->name );

		if ( 0 != $this_term->parent ) {
			$this->term_ancestors( $this_term->parent, 'post_category' );
			$this->add_crumb( $this_term->name, get_term_link( $this_term->term_id, $this_term->taxonomy ) );
		}

		$this->add_crumb( single_term_title( '', false ), get_term_link( $this_term->term_id, $this_term->taxonomy ) );
	}

	/**
	 * Add a breadcrumb for author archives
	 */
	private function add_crumbs_author() {
		global $author;

		$userdata = get_userdata( $author );
		$this->add_crumb( sprintf( __( 'Author: %s', 'learnpress' ), $userdata->display_name ) );
	}

	/**
	 * Add crumbs for a term
	 *
	 * @param string $taxonomy
	 */
	private function term_ancestors( $term_id, $taxonomy ) {
		$ancestors = get_ancestors( $term_id, $taxonomy );
		$ancestors = array_reverse( $ancestors );

		foreach ( $ancestors as $ancestor ) {
			$ancestor = get_term( $ancestor, $taxonomy );

			if ( !is_wp_error( $ancestor ) && $ancestor ) {
				$this->add_crumb( $ancestor->name, get_term_link( $ancestor ) );
			}
		}
	}

	/**
	 * Endpoints
	 */
	private function endpoint_trail() {

	}

	/**
	 * Add a breadcrumb for search results
	 */
	private function search_trail() {
		if ( is_search() ) {
			$this->add_crumb( sprintf( __( 'Search results for &ldquo;%s&rdquo;', 'learnpress' ), get_search_query() ), remove_query_arg( 'paged' ) );
		}
	}

	/**
	 * Add a breadcrumb for pagination
	 */
	private function paged_trail() {
		if ( get_query_var( 'paged' ) ) {
			$this->add_crumb( sprintf( __( 'Page %d', 'learnpress' ), get_query_var( 'paged' ) ) );
		}
	}
}
