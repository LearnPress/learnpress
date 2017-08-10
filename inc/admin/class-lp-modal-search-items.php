<?php

class LP_Modal_Search_Items {
	protected $_options = array();

	public function __construct( $options = '' ) {
		add_action( 'admin_print_footer_scripts', array( $this, 'js_template' ) );
		$this->_options = wp_parse_args(
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
			)
		);
	}

	public function get_data() {
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

		$exclude = array();

		if ( ! empty( $this->_options['exclude'] ) ) {
			$exclude = array_map( 'intval', $this->_options['exclude'] );
		}
		$exclude = array_unique( (array) apply_filters( 'learn_press_modal_search_items_exclude', $exclude, $type, $context, $context_id ) );
		$exclude = array_map( 'intval', $exclude );
		$args    = array(
			'post_type'      => array( $type ),
			'post_status'    => 'publish',
			'order'          => 'ASC',
			'orderby'        => 'parent title',
			'exclude'        => $exclude,
			'posts_per_page' => $this->_options['limit'],
			'offset'         => ( $this->_options['paged'] - 1 ) * $this->_options['limit']
		);
		if ( ! $user->is_admin() ) {
			$args['author'] = $user->get_id();
		}

		if ( $context && $context_id ) {
			switch ( $context ) {
				/**
				 * If is search lesson/quiz for course only search the items of course's author
				 */
				case 'course-items':
					if ( get_post_type( $context_id ) == LP_COURSE_CPT ) {
						$post_author = get_post_field( 'post_author', $context_id );
						$authors     = array( $post_author );
						if ( $post_author != $user->get_id() ) {
							$authors[] = $user->get_id();
						}
						$args['author'] = $authors;
					}
					break;
				/**
				 * If is search question for quiz only search the items of course's author
				 */
				case 'quiz-items':
					if ( get_post_type( $context_id ) == LP_QUIZ_CPT ) {
						$post_author = get_post_field( 'post_author', $context_id );
						$authors     = array( $post_author );
						if ( $post_author != $user->get_id() ) {
							$authors[] = $user->get_id();
						}
						$args['author'] = $authors;
					}
					break;
				case 'order-items':

			}
		}
		if ( $term ) {
			$args['s'] = $term;
		}
		$args = apply_filters( 'learn_press_filter_admin_ajax_modal_search_items_args', $args, $context, $context_id );

		print_r( $args );
		$posts       = get_posts( $args );
		$found_items = array();

		if ( ! empty( $posts ) ) {
			if ( $current_items_in_order ) {
				foreach ( $posts as $post ) {
					if ( in_array( $post->ID, $current_items ) ) {
						continue;
					}
					$found_items[ $post->ID ]             = $post;
					$found_items[ $post->ID ]->post_title = ! empty( $post->post_title ) ? $post->post_title : sprintf( '(%s)', __( 'Untitled', 'learnpress' ) );
				}
			} else {
				foreach ( $posts as $post ) {
					$found_items[ $post->ID ]             = $post;
					$found_items[ $post->ID ]->post_title = ! empty( $post->post_title ) ? $post->post_title : sprintf( '(%s)', __( 'Untitled', 'learnpress' ) );
				}
			}
		}

		return $found_items;
	}

	function xxx(){
		$nav = '';

		ob_start();
		if ( $found_items ) {
			foreach ( $found_items as $id => $item ) {
				printf( '
                        <li class="%s" data-id="%2$d" data-type="%4$s" data-text="%3$s">
                            <label>
                                <input type="checkbox" value="%2$d" name="selectedItems[]">
                                <span class="lp-item-text">%3$s</span>
                            </label>
                        </li>
					    ', 'lp-result-item', $id, esc_attr( $item->post_title ), $item->post_type );
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
				$nav          = paginate_links( array(
					'base'      => $pagenum_link,
					'total'     => $q->max_num_pages,
					'current'   => max( 1, $this->_options['paged'] ),
					'mid_size'  => 1,
					'add_args'  => array_map( 'urlencode', $query_args ),
					'prev_text' => __( '<', 'learnpress' ),
					'next_text' => __( '>', 'learnpress' ),
					'type'      => ''
				) );
			}
		} else {
			echo '<li>' . apply_filters( 'learn_press_modal_search_items_not_found', __( 'No item found', 'learnpress' ), $type ) . '</li>';
		}


		$response = array(
			'html' => ob_get_clean(),
			'nav'  => $nav,
			'data' => $found_items
		);

		print_r($response);

		return $response;
    }

	public function js_template() {
		?>
        <script type="text/x-template" id="learn-press-modal-search-items">
            <div id="modal-search-items">
                <div class="modal-overlay">

                </div>
                <div class="modal-wrapper">
                    <div class="modal-container">
                        <header><?php echo $this->_options['title']; ?></header>
                        <article>
                            <input type="text" name="search" @keyup="doSearch" ref="term" value="search here"/>
                            <ul class="search-results" @click="selectItem"></ul>
                        </article>
                        <footer v-if="hasItems">
                            <div class="search-nav" @click="loadPage">
                            </div>
                            <button class="button"
                                    @click="addItems"><?php echo $this->_options['add_button']; ?></button>
                            <button class="button"
                                    @click="close"><?php echo $this->_options['close_button']; ?></button>
                        </footer>
                    </div>
                </div>
            </div>
        </script>
        <div id="vue-modal-search-items" style="position: relative;z-index: 10000;">
            <learn-press-modal-search-items v-if="show" :post-type="postType" :term="term" :contex="context"
                                            :context-id="contextId" :show="show" :callbacks="callbacks" v-on:close="close">
            </learn-press-modal-search-items>
        </div>
		<?php
	}
}