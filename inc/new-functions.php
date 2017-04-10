<?php


add_action( 'get_header', function () {
	return;
	echo "XXXXXXXXXXXXXXXXXXXXXXXXXXX\n\n";
	learn_press_execute_time();
	print_r( learn_press_get_course_curriculumx( 2424 ) );
	learn_press_execute_time();

	learn_press_execute_time();
	print_r( _learn_press_get_courses_curriculum( array( 2424 ), true ) );
	learn_press_execute_time();
	echo "yyyyyyyyyyyyyyyyyyyyy";
} );

/**
 * Create an object with WP_Post format.
 *
 * @param array $data
 *
 * @return object
 */
function learn_press_create_default_post( $data = array() ) {
	if ( !function_exists( 'get_default_post_to_edit' ) ) {
		include_once ABSPATH . '/wp-admin/includes/post.php';
	}

	$post = get_default_post_to_edit();
	if ( $data ) {
		$data = (array) $data;
		foreach ( $data as $prop => $value ) {
			$post->{$prop} = $value;
		}
	}
	return $post;
}

function learn_press_get_course_curriculumx( $course_id, $force = false ) {
	static $cached = false;
	$force      = !$cached || $force;
	$curriculum = LP_Cache::get_course_curriculum( $course_id );
	die();
	if ( $force || $curriculum === false ) {
		global $wpdb;
		/*echo $query = $wpdb->prepare( "
			SELECT si.*, s.section_name, s.section_order, s.section_description
			FROM {$wpdb->prefix}learnpress_sections s
			INNER JOIN {$wpdb->prefix}learnpress_section_items si ON si.section_id = s.section_id
				WHERE s.section_id IN(
					SELECT cc.section_id
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->prefix}learnpress_sections cc ON p.ID = cc.section_course_id
					WHERE p.ID IN(%d)
				 )
			ORDER BY s.section_order, si.item_order ASC
		", $course_id );*/
		$query      = $wpdb->prepare( "
			SELECT s.*, si.*, p.*
			FROM {$wpdb->prefix}posts p
			INNER JOIN {$wpdb->prefix}learnpress_section_items si ON si.item_id = p.ID
			INNER JOIN {$wpdb->prefix}learnpress_sections s ON s.section_id = si.section_id
			WHERE s.section_id IN(
				SELECT cc.section_id
					FROM {$wpdb->prefix}posts p
					INNER JOIN {$wpdb->prefix}learnpress_sections cc ON p.ID = cc.section_course_id
					WHERE p.ID IN(%d)
					ORDER BY `section_order` ASC
			 )
			ORDER BY s.section_course_id, s.section_order, si.item_order ASC
		", $course_id );
		$curriculum = array();
		$meta_ids   = array( $course_id );
		$item_ids   = array();
		if ( $results = $wpdb->get_results( $query ) ) {
			foreach ( $results as $result ) {
				$section_id = $result->section_id;
				if ( empty( $curriculum[$section_id] ) ) {
					$curriculum[$result->section_id] = (object) array(
						'section_id'          => $result->section_id,
						'section_name'        => $result->section_name,
						'section_course_id'   => $result->section_course_id,
						'section_order'       => $result->section_order,
						'section_description' => $result->section_description,
						'items'               => array()
					);
				}
				$item                  = learn_press_create_default_post( $result );
				$item->section_item_id = $result->section_item_id;
				$item->section_id      = $result->section_id;
				$item->item_id         = $result->ID;

				$curriculum[$section_id]->items[$item->item_id] = $item;
				// Update post to cache
				wp_cache_delete( $item->ID, 'posts' );
				wp_cache_add( $item->ID, $item, 'posts' );

				if ( $item->post_type == LP_QUIZ_CPT ) {
					// Todo: Parse quiz questions
				}
				$item_ids   = $item->ID;
				$meta_ids[] = $item->ID;
			}

			// Update post to cache
			$course                   = get_post( $course_id );
			$course->curriculum_items = is_admin() ? maybe_serialize( $item_ids ) : $item_ids;

			wp_cache_replace( $course_id, $course, 'posts' );

			// Update meta cache for posts
			update_meta_cache( 'post', $meta_ids );

		}

		LP_Cache::set_course_curriculum( $course_id, $curriculum );
	}
	$cached = true;
	return $curriculum;
}