<?php

namespace LearnPress\MCP\Domain;

use LearnPress\MCP\Mappers\ResponseMapper;
use LearnPress\MCP\Support\Errors;
use LearnPress\MCP\Support\Pagination;
use LearnPress\MCP\Support\Permissions;
use LearnPress\MCP\Support\Sanitizer;
use LearnPress\MCP\Support\Validator;
use LearnPress\Filters\Course\CourseJsonFilter;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\Courses;
use LearnPress\Services\CourseService;
use LP_Helper;
use Throwable;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Course create/update/delete executors.
 *
 * Uses LearnPress course models, the CourseService create helper, and standard
 * LearnPress course meta keys. Delete is reversible (trash only).
 */
class CourseTools {

	/**
	 * Execute `learnpress/create-course`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function create_course( $input ) {
		$args  = is_array( $input ) ? $input : array();
		$title = Sanitizer::text( $args['title'] ?? '' );
		if ( '' === $title ) {
			return Errors::invalid( __( 'title is required.', 'learnpress' ) );
		}

		if ( ! Permissions::can_create( LP_COURSE_CPT ) ) {
			return Errors::forbidden();
		}

		$status = Validator::status( $args['status'] ?? '' );
		if ( is_wp_error( $status ) ) {
			return $status;
		}

		$terms = self::resolve_terms( $args );
		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		$thumbnail_id = self::resolve_thumbnail( $args );
		if ( is_wp_error( $thumbnail_id ) ) {
			return $thumbnail_id;
		}

		$instructor_id = absint( $args['instructor_id'] ?? 0 );
		$author_id     = $instructor_id > 0 ? $instructor_id : get_current_user_id();

		$data = array(
			'post_title'   => $title,
			'post_content' => Sanitizer::html( $args['description'] ?? '' ),
			'post_excerpt' => Sanitizer::html( $args['excerpt'] ?? '' ),
			'post_status'  => '' !== $status ? $status : 'draft',
			'post_author'  => $author_id,
			'meta_input'   => self::build_meta( $args ),
		);

		try {
			$course_post = CourseService::instance()->create_info_main( $data );
			$course_id   = $course_post->get_id();
			if ( $course_id <= 0 ) {
				return Errors::internal();
			}

			self::apply_terms( $course_id, $terms );
			if ( $thumbnail_id > 0 ) {
				set_post_thumbnail( $course_id, $thumbnail_id );
			}

			$course = CourseModel::find( $course_id, false );
			if ( ! $course instanceof CourseModel ) {
				return Errors::internal();
			}

			return array( 'course' => ResponseMapper::course_write_result( $course ) );
		} catch ( Throwable $e ) {
			return Errors::from_throwable( $e );
		}
	}

	/**
	 * Execute `learnpress/update-course`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function update_course( $input ) {
		$args      = is_array( $input ) ? $input : array();
		$course_id = Validator::require_id( $args, 'course_id' );
		if ( is_wp_error( $course_id ) ) {
			return $course_id;
		}

		$course_post = CoursePostModel::find( $course_id, true );
		if ( ! $course_post instanceof CoursePostModel ) {
			return Errors::not_found( __( 'Course not found.', 'learnpress' ) );
		}

		if ( ! Permissions::can_edit_post( $course_id ) ) {
			return Errors::forbidden();
		}

		$status = Validator::status( $args['status'] ?? '' );
		if ( is_wp_error( $status ) ) {
			return $status;
		}

		$terms = self::resolve_terms( $args );
		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		$thumbnail_id = self::resolve_thumbnail( $args );
		if ( is_wp_error( $thumbnail_id ) ) {
			return $thumbnail_id;
		}

		try {
			if ( array_key_exists( 'title', $args ) ) {
				$course_post->post_title = Sanitizer::text( $args['title'] );
			}
			if ( array_key_exists( 'description', $args ) ) {
				$course_post->post_content = Sanitizer::html( $args['description'] );
			}
			if ( array_key_exists( 'excerpt', $args ) ) {
				$course_post->post_excerpt = Sanitizer::html( $args['excerpt'] );
			}
			if ( '' !== $status ) {
				$course_post->post_status = $status;
			}
			if ( ! empty( $args['instructor_id'] ) ) {
				$course_post->post_author = absint( $args['instructor_id'] );
			}

			// Stage meta before save() so the synchronous learnpress_courses
			// snapshot (rebuilt on save_post) reflects the new values; otherwise
			// the update response would read stale meta from the old snapshot.
			foreach ( self::build_meta( $args ) as $key => $value ) {
				$course_post->meta_data->{$key} = $value;
			}

			$course_post->save();

			self::apply_terms( $course_id, $terms );
			if ( $thumbnail_id > 0 ) {
				set_post_thumbnail( $course_id, $thumbnail_id );
			}

			$course = CourseModel::find( $course_id, false );
			if ( ! $course instanceof CourseModel ) {
				return Errors::internal();
			}

			return array( 'course' => ResponseMapper::course_object( $course ) );
		} catch ( Throwable $e ) {
			return Errors::from_throwable( $e );
		}
	}

	/**
	 * Execute `learnpress/delete-course` (reversible trash only).
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function delete_course( $input ) {
		$args      = is_array( $input ) ? $input : array();
		$course_id = Validator::require_id( $args, 'course_id' );
		if ( is_wp_error( $course_id ) ) {
			return $course_id;
		}

		$course = CourseModel::find( $course_id, true );
		if ( ! $course instanceof CourseModel ) {
			return Errors::not_found( __( 'Course not found.', 'learnpress' ) );
		}

		if ( ! Permissions::can_delete_post( $course_id ) ) {
			return Errors::forbidden();
		}

		$previous_status = (string) $course->get_status();

		try {
			$trashed = wp_trash_post( $course_id );
			if ( ! $trashed ) {
				return Errors::internal();
			}

			return array(
				'trashed'         => true,
				'course_id'       => $course_id,
				'previous_status' => $previous_status,
				'recovery'        => array(
					'title'           => (string) $course->get_title(),
					'previous_status' => $previous_status,
					'note'            => __( 'Course moved to trash. Restore from WP admin to recover.', 'learnpress' ),
				),
			);
		} catch ( Throwable $e ) {
			return Errors::from_throwable( $e );
		}
	}

	/**
	 * Execute `learnpress/get-courses`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function get_courses( $input ) {
		$args = Sanitizer::input_array( $input, 'learnpress/get-courses' );
		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$page                = Pagination::page( $args['page'] ?? 1 );
		$per_page            = Pagination::per_page( $args['per_page'] ?? 10 );
		$filter              = new CourseJsonFilter();
		$filter->page        = $page;
		$filter->limit       = $per_page;
		$filter->post_status = Sanitizer::status_list( $args['status'] ?? array( 'publish' ) );
		if ( empty( $filter->post_status ) ) {
			$filter->post_status = array( 'publish' );
		}

		if ( ! empty( $args['category'] ) ) {
			$filter->term_ids = array( absint( $args['category'] ) );
		}
		if ( ! empty( $args['instructor'] ) ) {
			$filter->post_author = absint( $args['instructor'] );
		}
		if ( isset( $args['search'] ) ) {
			$filter->post_title = LP_Helper::sanitize_params_submitted( (string) $args['search'] );
		}

		$price_min = isset( $args['price_min'] ) && is_numeric( $args['price_min'] ) ? (float) $args['price_min'] : null;
		$price_max = isset( $args['price_max'] ) && is_numeric( $args['price_max'] ) ? (float) $args['price_max'] : null;
		if ( null !== $price_min && $price_min < 0 ) {
			return Errors::invalid( __( 'price_min must be greater than or equal to 0.', 'learnpress' ) );
		}
		if ( null !== $price_max && $price_max < 0 ) {
			return Errors::invalid( __( 'price_max must be greater than or equal to 0.', 'learnpress' ) );
		}
		if ( null !== $price_min && null !== $price_max && $price_min > $price_max ) {
			return Errors::invalid( __( 'price_min must not be greater than price_max.', 'learnpress' ) );
		}

		global $wpdb;
		if ( null !== $price_min && $wpdb ) {
			$filter->where[] = $wpdb->prepare( 'AND c.price_to_sort >= %f', $price_min );
		}
		if ( null !== $price_max && $wpdb ) {
			$filter->where[] = $wpdb->prepare( 'AND c.price_to_sort <= %f', $price_max );
		}

		try {
			$total_rows = 0;
			$rows       = Courses::get_list_courses( $filter, $total_rows );
			$rows       = is_array( $rows ) ? $rows : array();
			$items      = array();
			foreach ( $rows as $row ) {
				$course = CourseModel::find( absint( $row->ID ?? 0 ), true );
				if ( $course instanceof CourseModel ) {
					$items[] = ResponseMapper::course_summary( $course );
				}
			}

			return array(
				'items'      => $items,
				'pagination' => array(
					'page'        => $page,
					'per_page'    => $per_page,
					'total_items' => (int) $total_rows,
					'total_pages' => Pagination::total_pages( (int) $total_rows, $per_page ),
				),
			);
		} catch ( Throwable $e ) {
			return Errors::internal();
		}
	}

	/**
	 * Execute `learnpress/get-course-details`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function get_course_details( $input ) {
		$args = Sanitizer::input_array( $input, 'learnpress/get-course-details' );
		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$course_id = Validator::require_id( $args, 'course_id' );
		if ( is_wp_error( $course_id ) ) {
			return $course_id;
		}

		$course = CourseModel::find( $course_id, true );
		if ( ! $course instanceof CourseModel ) {
			return Errors::not_found( __( 'Course not found.', 'learnpress' ) );
		}

		$sections = array();
		foreach ( $course->get_section_items() as $section ) {
			$items = array();
			foreach ( $section->items as $item ) {
				$items[] = array(
					'item_id'   => absint( $item->item_id ?? $item->id ?? 0 ),
					'item_type' => (string) ( $item->item_type ?? $item->type ?? '' ),
					'title'     => (string) ( $item->title ?? '' ),
					'preview'   => ! empty( $item->preview ),
				);
			}
			$sections[] = array(
				'section_id'          => absint( $section->section_id ?? $section->id ?? 0 ),
				'section_name'        => (string) ( $section->section_name ?? $section->title ?? '' ),
				'section_description' => (string) ( $section->section_description ?? $section->description ?? '' ),
				'items'               => $items,
			);
		}

		$detail                = ResponseMapper::course_summary( $course );
		$detail['description'] = (string) $course->get_description();
		// Requirements are stored as an array (repeatable field); return as-is, not cast to "Array".
		$detail['requirements'] = $course->get_meta_value_by_key( CoursePostModel::META_KEY_REQUIREMENTS, '' );
		$detail['curriculum']   = array(
			'sections'    => $sections,
			'items_count' => (int) $course->count_items(),
		);

		return array( 'course' => $detail );
	}

	/**
	 * Build LearnPress course meta from provided write fields.
	 *
	 * @param array $args Input args.
	 *
	 * @return array
	 */
	protected static function build_meta( array $args ): array {
		$meta = array();

		if ( array_key_exists( 'price', $args ) ) {
			$meta[ CoursePostModel::META_KEY_REGULAR_PRICE ] = (float) $args['price'];
		}
		if ( array_key_exists( 'sale_price', $args ) ) {
			$meta[ CoursePostModel::META_KEY_SALE_PRICE ] = (float) $args['sale_price'];
		}
		if ( array_key_exists( 'duration', $args ) ) {
			$meta[ CoursePostModel::META_KEY_DURATION ] = Sanitizer::text( $args['duration'] );
		}
		if ( array_key_exists( 'level', $args ) ) {
			$meta[ CoursePostModel::META_KEY_LEVEL ] = Sanitizer::text( $args['level'] );
		}
		if ( array_key_exists( 'requirements', $args ) && is_array( $args['requirements'] ) ) {
			$meta[ CoursePostModel::META_KEY_REQUIREMENTS ] = array_map( array( Sanitizer::class, 'text' ), $args['requirements'] );
		}
		if ( array_key_exists( 'target_audiences', $args ) && is_array( $args['target_audiences'] ) ) {
			$meta[ CoursePostModel::META_KEY_TARGET ] = array_map( array( Sanitizer::class, 'text' ), $args['target_audiences'] );
		}
		if ( array_key_exists( 'features', $args ) && is_array( $args['features'] ) ) {
			$meta[ CoursePostModel::META_KEY_FEATURES ] = array_map( array( Sanitizer::class, 'text' ), $args['features'] );
		}
		if ( array_key_exists( 'faqs', $args ) && is_array( $args['faqs'] ) ) {
			$faqs = array();
			foreach ( $args['faqs'] as $faq ) {
				if ( ! is_array( $faq ) ) {
					continue;
				}
				$faqs[] = array(
					'question' => Sanitizer::text( $faq['question'] ?? '' ),
					'answer'   => Sanitizer::html( $faq['answer'] ?? '' ),
				);
			}
			$meta[ CoursePostModel::META_KEY_FAQS ] = $faqs;
		}

		return $meta;
	}

	/**
	 * Validate category/tag IDs against their taxonomies.
	 *
	 * @param array $args Input args.
	 *
	 * @return array|WP_Error { categories: int[], tags: int[], has_categories: bool, has_tags: bool }
	 */
	protected static function resolve_terms( array $args ) {
		$result = array(
			'categories'     => array(),
			'tags'           => array(),
			'has_categories' => array_key_exists( 'category_ids', $args ),
			'has_tags'       => array_key_exists( 'tag_ids', $args ),
		);

		if ( $result['has_categories'] ) {
			$ids = Sanitizer::id_list( $args['category_ids'] );
			foreach ( $ids as $id ) {
				if ( ! term_exists( $id, CoursePostModel::TAXONOMY_CATEGORY ) ) {
					return Errors::invalid(
						sprintf(
							/* translators: %d: term id. */
							__( 'Invalid course category ID: %d.', 'learnpress' ),
							$id
						)
					);
				}
			}
			$result['categories'] = $ids;
		}

		if ( $result['has_tags'] ) {
			$ids = Sanitizer::id_list( $args['tag_ids'] );
			foreach ( $ids as $id ) {
				if ( ! term_exists( $id, CoursePostModel::TAXONOMY_TAG ) ) {
					return Errors::invalid(
						sprintf(
							/* translators: %d: term id. */
							__( 'Invalid course tag ID: %d.', 'learnpress' ),
							$id
						)
					);
				}
			}
			$result['tags'] = $ids;
		}

		return $result;
	}

	/**
	 * Persist validated taxonomy relationships.
	 *
	 * @param int   $course_id Course ID.
	 * @param array $terms     Resolved terms.
	 *
	 * @return void
	 */
	protected static function apply_terms( int $course_id, array $terms ): void {
		if ( ! empty( $terms['has_categories'] ) ) {
			wp_set_post_terms( $course_id, $terms['categories'], CoursePostModel::TAXONOMY_CATEGORY );
		}
		if ( ! empty( $terms['has_tags'] ) ) {
			wp_set_post_terms( $course_id, $terms['tags'], CoursePostModel::TAXONOMY_TAG );
		}
	}

	/**
	 * Validate an optional featured image attachment ID.
	 *
	 * @param array $args Input args.
	 *
	 * @return int|WP_Error 0 when not provided.
	 */
	protected static function resolve_thumbnail( array $args ) {
		if ( ! array_key_exists( 'featured_image_id', $args ) ) {
			return 0;
		}

		$id = absint( $args['featured_image_id'] );
		if ( $id <= 0 ) {
			return 0;
		}

		if ( 'attachment' !== get_post_type( $id ) || ! wp_attachment_is_image( $id ) ) {
			return Errors::invalid( __( 'featured_image_id must reference a valid image attachment.', 'learnpress' ) );
		}

		return $id;
	}
}
