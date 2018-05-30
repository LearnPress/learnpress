<?php

/**
 * Class LP_Modal_Search_Items.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Modal_Search_Items' ) ) {

	/**
	 * Class LP_Modal_Search_Items
	 */
	class LP_Modal_Search_Items {

		/**
		 * @var array
		 */
		protected $_options = array();

		/**
		 * @var array
		 */
		protected $_query_args = array();

		/**
		 * @var array
		 */
		protected $_items = array();

		/**
		 * @var bool
		 */
		protected $_changed = true;

		/**
		 * LP_Modal_Search_Items constructor.
		 *
		 * @param string $options
		 */
		public function __construct( $options = '' ) {
			add_action( 'admin_print_footer_scripts', array( $this, 'js_template' ) );

			$this->_options = apply_filters( 'learn-press/modal-search-items-args', wp_parse_args(
					$options,
					array(
						'type'         => '',
						'context'      => '',
						'context_id'   => '',
						'exclude'      => '',
						'term'         => '',
						'add_button'   => __( 'Add', 'learnpress' ),
						'close_button' => __( 'Close', 'learnpress' ),
						'title'        => __( 'Search items', 'learnpress' ),
						'limit'        => 10,
						'paged'        => 1
					) )
			);

			if ( is_string( $this->_options['exclude'] ) ) {
				$this->_options['exclude'] = explode( ',', $this->_options['exclude'] );
			}

			add_filter( 'learn-press/modal-search-items/exclude', array(
				$this,
				'exclude_items'
			), 10, 4 );

			add_filter( 'learn-press/modal-search-items/args', array(
				$this,
				'query_args'
			), 10, 3 );

//			add_filter( 'learn-press/modal-search-items/not-found', array(
//				$this,
//				'_modal_search_items_not_found'
//			), 10, 2 );
		}

		/**
		 * Build query args from object options and get posts.
		 *
		 * @return array
		 */
		protected function _get_items() {
			global $wpdb;

			$current_items          = array();
			$current_items_in_order = learn_press_get_request( 'current_items' );
			$user                   = learn_press_get_current_user();

			$term       = $this->_options['term'];
			$type       = $this->_options['type'];
			$context    = $this->_options['context'];
			$context_id = $this->_options['context_id'];

			if ( $current_items_in_order ) {
				foreach ( $current_items_in_order as $item ) {
					$sql = "SELECT meta_value
                        FROM {$wpdb->prefix}learnpress_order_itemmeta 
                        WHERE meta_key = '_course_id' 
                        AND learnpress_order_item_id = $item";
					$id  = $wpdb->get_results( $sql, OBJECT );
					array_push( $current_items, $id[0]->meta_value );
				}
			}

			// @since 3.0.0
			$exclude = array_unique( (array) apply_filters( 'learn-press/modal-search-items/exclude', $this->_options['exclude'], $type, $context, $context_id ) );

			// @deprecated
			$exclude = array_unique( (array) apply_filters( 'learn_press_modal_search_items_exclude', $exclude, $type, $context, $context_id ) );

			if ( is_array( $exclude ) ) {
				$exclude = array_map( 'intval', $exclude );
			}

			$paged = max( 1, $this->_options['paged'] );

			$args = array(
				'post_type'      => array( $type ),
				'post_status'    => 'publish',
				'order'          => 'ASC',
				'orderby'        => 'parent title',
				'exclude'        => $exclude,
				'posts_per_page' => $this->_options['limit'],
				'offset'         => ( $paged - 1 ) * $this->_options['limit']
			);

			if ( $context_id = apply_filters( 'learn-press/modal-search-items/context-id', $context_id, $context ) ) {
				$args['author'] = get_post_field( 'post_author', $context_id );
			}

			if ( $term ) {
				$args['s'] = $term;
			}

			// @since 3.0.0
			$this->_query_args = apply_filters( 'learn-press/modal-search-items/args', $args, $context, $context_id );

			// @deprecated
			$this->_query_args = apply_filters( 'learn_press_filter_admin_ajax_modal_search_items_args', $this->_query_args, $context, $context_id );

			if ( $posts = get_posts( $this->_query_args ) ) {
				$this->_items = wp_list_pluck( $posts, 'ID' );
			}

			return $this->_items;
		}

		/**
		 * Get the items
		 *
		 * @return array
		 */
		public function get_items() {
			if ( $this->_changed ) {
				$this->_get_items();
			}

			return $this->_items;
		}

		/**
		 * Get pagination in html.
		 *
		 * @param bool $html
		 *
		 * @return array|string
		 */
		function get_pagination( $html = true ) {

			$pagination = '';
			if ( $items = $this->get_items() ) {
				$args = $this->_query_args;

				if ( ! empty( $args['exclude'] ) ) {
					$args['post__not_in'] = $args['exclude'];
				}

				$q = new WP_Query( $args );

				if ( $this->_options['paged'] && $q->max_num_pages > 1 ) {

					$pagenum_link = html_entity_decode( get_pagenum_link() );

					$query_args = array();
					$url_parts  = explode( '?', $pagenum_link );

					if ( isset( $url_parts[1] ) ) {
						wp_parse_str( $url_parts[1], $query_args );
					}

					$pagenum_link = remove_query_arg( array_keys( $query_args ), $pagenum_link );
					$pagenum_link = trailingslashit( $pagenum_link ) . '%_%';

					$pagination = array(
						'base'      => $pagenum_link,
						'total'     => $q->max_num_pages,
						'current'   => max( 1, $this->_options['paged'] ),
						'mid_size'  => 1,
						'add_args'  => array_map( 'urlencode', $query_args ),
						'prev_text' => __( '<', 'learnpress' ),
						'next_text' => __( '>', 'learnpress' ),
						'type'      => ''
					);

					if ( $html ) {
						$pagination = paginate_links( $pagination );
					}
				}
				$this->_changed = false;
			}

			return $pagination;
		}

		/**
		 * Return string of list items
		 *
		 * @return string
		 */
		public function get_html_items() {
			ob_start();
			if ( $items = $this->get_items() ) {
				foreach ( $items as $id => $item ) {
					$type        = get_post_type( $item );
					$type_object = get_post_type_object( $type );
					$type_name   = $type_object ? $type_object->labels->singular_name : '';
					printf( '
                    <li class="%s" data-id="%2$d" data-type="%4$s" data-text="%3$s">
                        <label>
                            <input type="checkbox" value="%2$d" name="selectedItems[]">
                            <span class="lp-item-text">%3$s (%5$s - #%6$s)</span>
                        </label>
                    </li>
                    ', 'lp-result-item', $item, esc_attr( get_the_title( $item ) ), $type, $type_name, $item );
				}
			} else {

				// @since 3.0.0
				$item_not_found = apply_filters( 'learn-press/modal-search-items/not-found', __( 'No item found', 'learnpress' ), $this->_options['type'] );

				// @deprecated
				$item_not_found = apply_filters( 'learn_press_modal_search_items_not_found', $item_not_found );

				echo '<li>' . $item_not_found . '</li>';
			}

			return ob_get_clean();
		}

		/**
		 * JS Modal template.
		 */
		public function js_template() {
			$view = learn_press_get_admin_view( 'modal-search-items' );
			include $view;
		}

		/**
		 * @param array $args
		 * @param string $context
		 * @param string $context_id
		 *
		 * @return mixed
		 */
		public static function query_args( $args, $context, $context_id ) {
			if ( ( LP_ORDER_CPT === get_post_type( $context_id ) ) && ( LP_COURSE_CPT === $args['post_type'] ) ) {
				if ( ! empty( $args['author'] ) ) {
					unset( $args['author'] );
				}
			}

			return $args;
		}

		/**
		 * Filter to exclude the items has already added to it's parent.
		 * Each item only use one time
		 *
		 * @param        $exclude
		 * @param        $type
		 * @param string $context
		 * @param null $context_id
		 *
		 * @return array
		 */
		public static function exclude_items( $exclude, $type, $context = '', $context_id = null ) {
			global $wpdb;
			$used_items = array();
			switch ( $type ) {
				case 'lp_lesson':
				case 'lp_quiz':
					$query      = $wpdb->prepare( "
						SELECT item_id
						FROM {$wpdb->prefix}learnpress_section_items si
						INNER JOIN {$wpdb->prefix}learnpress_sections s ON s.section_id = si.section_id
						INNER JOIN {$wpdb->posts} p ON p.ID = s.section_course_id
						WHERE %d
						AND p.post_type = %s
					", 1, LP_COURSE_CPT );
					$used_items = $wpdb->get_col( $query );
					break;
				case 'lp_question':
					$query      = $wpdb->prepare( "
						SELECT question_id
						FROM {$wpdb->prefix}learnpress_quiz_questions AS qq
						INNER JOIN {$wpdb->posts} q ON q.ID = qq.quiz_id
						WHERE %d
						AND q.post_type = %s
					", 1, LP_QUIZ_CPT );
					$used_items = $wpdb->get_col( $query );
					break;

			}
			if ( $used_items && $exclude ) {
				$exclude = array_merge( $exclude, $used_items );
			} else if ( $used_items ) {
				$exclude = $used_items;
			}

			return is_array( $exclude ) ? array_unique( $exclude ) : array();
		}

		/**
		 * @param $message
		 * @param $type
		 *
		 * @return string
		 */
		public static function items_not_found( $message, $type ) {
			switch ( $type ) {
				case LP_LESSON_CPT:
					$message = __( 'There are no available lessons for this course, please use ', 'learnpress' );
					$message .= '<a target="_blank" href="' . admin_url( 'post-new.php?post_type=lp_lesson' ) . '">' . esc_html__( 'Add new item', 'learnpress' ) . '</a>';
					break;
				case LP_QUIZ_CPT:
					$message = __( 'There are no available quizzes for this course, please use ', 'learnpress' );
					$message .= '<a target="_blank" href="' . admin_url( 'post-new.php?post_type=lp_quiz' ) . '">' . esc_html__( 'Add new item', 'learnpress' ) . '</a>';
					break;
				case LP_QUESTION_CPT:
					$message = __( 'There are no available questions for this quiz, please use ', 'learnpress' );
					$message .= '<a target="_blank" href="' . admin_url( 'post-new.php?post_type=lp_question' ) . '">' . esc_html__( 'Add new item', 'learnpress' ) . '</a>';
					break;
			}

			return $message;
		}

		/**
		 * @return bool|LP_Modal_Search_Items
		 */
		public static function instance() {
			static $instance = false;
			if ( ! $instance ) {
				$instance = new self();
			}

			return $instance;
		}
	}
}