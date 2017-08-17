<?php

class LP_Query_Search {
	/**
	 * Search posts.
	 *
	 * @param mixed $args
	 *
	 * @return mixed
	 */
	public static function search_items( $args = '' ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'term'       => '',
				'type'       => '',
				'context'    => '',
				'context_id' => 0,
				'include'    => '',
				'exclude'    => '',
				'fields'     => array(),
				'limit'      => - 1,
				'paged'      => 0
			)
		);

		$user       = learn_press_get_current_user();
		$term       = $args['term'];
		$type       = $args['type'];
		$context    = $args['context'];
		$context_id = absint( $args['context_id'] );
		$include    = array();
		$exclude    = array();
		$authors    = array();
		/*$current_items_in_order = learn_press_get_request( 'current_items' );
		$current_items          = array();

		foreach ( $current_items_in_order as $item ) {
			$sql = "SELECT meta_value
                        FROM {$wpdb->prefix}learnpress_order_itemmeta
                        WHERE meta_key = '_course_id'
                        AND learnpress_order_item_id = $item";
			$id  = $wpdb->get_results( $sql, OBJECT );
			array_push( $current_items, $id[0]->meta_value );
		}*/


		if ( ! empty( $args['exclude'] ) ) {
			if ( is_string( $args['exclude'] ) ) {
				$args['exclude'] = explode( ',', $args['exclude'] );
			}
			$exclude = array_map( 'intval', $args['exclude'] );
		}

		if ( ! empty( $args['include'] ) ) {
			if ( is_string( $args['include'] ) ) {
				$args['eincludexclude'] = explode( ',', $args['include'] );
			}
			$include = array_map( 'intval', $args['include'] );
		}

		$exclude = apply_filters( 'learn-press/search-items/exclude', $exclude, $type, $context, $context_id );
		$include = apply_filters( 'learn-press/search-items/include', $include, $type, $context, $context_id );


		if ( ! $user->is_admin() ) {
			$authors[] = $user->get_id();
		}

		if ( $context && $context_id ) {
			if ( get_post_type( $context_id ) == $context ) {
				$post_author = get_post_field( 'post_author', $context_id );
				$authors[]   = $post_author;
				if ( $post_author != $user->get_id() ) {
					$authors[] = $user->get_id();
				}
			}
		}

		$query_args = array(
			'post_type'      => array( $type ),
			'posts_per_page' => $args['limit'],
			'post_status'    => 'publish',
			'order'          => 'ASC',
			'orderby'        => 'parent title',
			'post__not_in'        => $exclude,
			'include'        => $include,
			'author'         => $authors
		);

		if ( $term ) {
			$query_args['s'] = $term;
		}

		if ( $args['paged'] ) {
			$query_args['offset'] = ( $args['paged'] - 1 ) * $args['limit'];
		}


		$query_args = apply_filters( 'learn-press/search-items/args', $query_args, $type, $context, $context_id );
		global $wp_query, $wpdb;
		$posts = get_posts( $query_args );
		$q     = new WP_Query( $query_args );
		print_r($q);

		return array( 'items' => $posts, 'total' => $q->found_posts, 'pages' => $q->max_num_pages );
	}
}