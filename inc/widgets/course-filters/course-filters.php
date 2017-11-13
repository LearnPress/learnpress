<?php
if ( !class_exists( 'LP_Widget_Course_Filters' ) ) {
	/**
	 * Class LP_Widget_Course_Filters
	 */
	class LP_Widget_Course_Filters extends LP_Widget {
		public function __construct() {
			$prefix        = '';
			$this->options = array(
				'title'               => array(
					'name' => __( 'Title', 'learnpress' ),
					'id'   => "{$prefix}title",
					'type' => 'text',
					'std'  => __( 'Course filters', 'learnpress' )
				),
				'filter_by'           => array(
					'name'    => __( 'Filter by', 'learnpress' ),
					'id'      => "{$prefix}filter_by",
					'type'    => 'checkbox_list',
					'std'     => '',
					'options' => ''
				),
				'attribute_operation' => array(
					'name'    => __( 'Attribute operation', 'learnpress' ),
					'id'      => "{$prefix}attribute_operator",
					'type'    => 'select',
					'std'     => 'and',
					'options' => array(
						'and' => __( 'And', 'learnpress' ),
						'or'  => __( 'Or', 'learnpress' )
					)
				),
				'value_operation'     => array(
					'name'    => __( 'Value operation', 'learnpress' ),
					'id'      => "{$prefix}value_operator",
					'type'    => 'select',
					'std'     => 'and',
					'options' => array(
						'and' => __( 'And', 'learnpress' ),
						'or'  => __( 'Or', 'learnpress' )
					)
				),
				'ajax_filter'         => array(
					'name' => __( 'Ajax filter', 'learnpress' ),
					'id'   => "{$prefix}ajax_filter",
					'type' => 'checkbox',
					'std'  => '0',
					'desc' => __( 'Use ajax to fetch content while filtering', 'learnpress' )
				),
				'button_filter'       => array(
					'name' => __( 'Button filter', 'learnpress' ),
					'id'   => "{$prefix}button_filter",
					'type' => 'checkbox',
					'std'  => '0',
					'desc' => __( 'If checked, user has to click this button to start filtering', 'learnpress' )
				)
			);
			parent::__construct();
			add_filter( 'learn_press_widget_display_content-' . $this->id_base, 'learn_press_is_courses' );
			if ( !is_admin() ) {
				LP_Assets::enqueue_script( 'course-filter', LP_Assets::url( 'js/frontend/course-filters.js' ) );
			}
		}

		public function normalize_options() {
			$this->options['filter_by']['options'] = $this->get_filter_by_options();
			return $this->options;
		}

		public function get_filter_by_options() {
			$options = array();
			if ( $attributes = learn_press_get_attributes() ) {
				foreach ( $attributes as $attribute ) {
					$options[LP_COURSE_ATTRIBUTE . '-' . $attribute->slug] = $attribute->name;
				}
			}
			return $options;
		}

		public static function get_main_tax_query() {
			global $wp_the_query;

			$tax_query = isset( $wp_the_query->tax_query, $wp_the_query->tax_query->queries ) ? $wp_the_query->tax_query->queries : array();

			return $tax_query;
		}

		public static function get_main_meta_query() {
			global $wp_the_query;

			$args       = $wp_the_query->query_vars;
			$meta_query = isset( $args['meta_query'] ) ? $args['meta_query'] : array();

			return $meta_query;
		}

		protected function get_filtered_term_course_counts( $term_ids, $taxonomy, $query_type ) {
			global $wpdb;

			$tax_query  = $this->get_main_tax_query();
			$meta_query = $this->get_main_meta_query();
			settype( $taxonomy, 'array' );
			if ( 'or' === $query_type ) {
				foreach ( $tax_query as $key => $query ) {
					if ( in_array( $query['taxonomy'], $taxonomy ) ) {//} === $query['taxonomy'] ) {
						unset( $tax_query[$key] );
					}
				}
			}

			$meta_query     = new WP_Meta_Query( $meta_query );
			$tax_query      = new WP_Tax_Query( $tax_query );
			$meta_query_sql = $meta_query->get_sql( 'post', $wpdb->posts, 'ID' );
			$tax_query_sql  = $tax_query->get_sql( $wpdb->posts, 'ID' );

			$query           = array();
			$query['select'] = "SELECT COUNT( DISTINCT {$wpdb->posts}.ID ) as term_count, terms.term_id as term_count_id";
			$query['from']   = "FROM {$wpdb->posts}";
			$query['join']   = "
				INNER JOIN {$wpdb->term_relationships} AS term_relationships ON {$wpdb->posts}.ID = term_relationships.object_id
				INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy USING( term_taxonomy_id )
				INNER JOIN {$wpdb->terms} AS terms USING( term_id )
			" . $tax_query_sql['join'] . $meta_query_sql['join'];

			$query['where'] = "
				WHERE {$wpdb->posts}.post_type IN ( 'lp_course' )
				AND {$wpdb->posts}.post_status = 'publish'
			" . $tax_query_sql['where'] . $meta_query_sql['where'] . "
				AND terms.term_id IN (" . implode( ',', array_map( 'absint', $term_ids ) ) . ")
			";

			if ( $search = $this->get_main_search_query_sql() ) {
				$query['where'] .= ' AND ' . $search;
			}
			$query['group_by'] = "GROUP BY terms.term_id";
			$query             = apply_filters( 'learn_press_get_filtered_term_course_counts_query', $query );
			$query             = implode( ' ', $query );
			$results           = $wpdb->get_results( $query );

			return wp_list_pluck( $results, 'term_count', 'term_count_id' );
		}

		/**
		 * Based on WP_Query::parse_search
		 */
		public static function get_main_search_query_sql() {
			global $wp_the_query, $wpdb;

			$args         = $wp_the_query->query_vars;
			$search_terms = isset( $args['search_terms'] ) ? $args['search_terms'] : array();
			$sql          = array();

			foreach ( $search_terms as $term ) {
				$include = '-' !== substr( $term, 0, 1 );
				if ( $include ) {
					$like_op  = 'LIKE';
					$andor_op = 'OR';
				} else {
					$like_op  = 'NOT LIKE';
					$andor_op = 'AND';
					$term     = substr( $term, 1 );
				}
				$like  = '%' . $wpdb->esc_like( $term ) . '%';
				$sql[] = $wpdb->prepare( "(($wpdb->posts.post_title $like_op %s) $andor_op ($wpdb->posts.post_excerpt $like_op %s) $andor_op ($wpdb->posts.post_content $like_op %s))", $like, $like, $like );
			}

			if ( !empty( $sql ) && !is_user_logged_in() ) {
				$sql[] = "($wpdb->posts.post_password = '')";
			}

			return implode( ' AND ', $sql );
		}

		public function show() {
			include learn_press_locate_widget_template( $this->get_slug() );
		}
	}
}