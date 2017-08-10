<?php

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

		$args['author'] = get_post_field( 'post_author', $context_id );

		if ( $term ) {
			$args['s'] = $term;
		}
		$this->_query_args = apply_filters( 'learn_press_filter_admin_ajax_modal_search_items_args', $args, $context, $context_id );

		$posts        = get_posts( $this->_query_args );
		$this->_items = array();

		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post ) {
				if ( in_array( $post->ID, $current_items ) ) {
					continue;
				}
				$this->_items[] = $post->ID;
			}

		}

		return $this->_items;
	}

	/**
	 * Get items.
	 *
	 * @since 3.0.0
	 *
	 * @return array Array post id
	 */
	public function get_items() {
		if ( $this->_changed ) {
			$this->_get_items();
		}

		return $this->_items;
	}

	function get_pagination() {

		$pagination = '';
		if ( $items = $this->get_items() ) {
			$q = new WP_Query( $this->_query_args );

			if ( $this->_options['paged'] && $q->max_num_pages > 1 ) {
				$pagenum_link = html_entity_decode( get_pagenum_link() );

				$query_args = array();
				$url_parts  = explode( '?', $pagenum_link );

				if ( isset( $url_parts[1] ) ) {
					wp_parse_str( $url_parts[1], $query_args );
				}

				$pagenum_link = remove_query_arg( array_keys( $query_args ), $pagenum_link );
				$pagenum_link = trailingslashit( $pagenum_link ) . '%_%';
				$pagination   = paginate_links( array(
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
		}

		return $pagination;
	}

	public function get_html_items() {
		ob_start();
		if ( $items = $this->get_items() ) {
			foreach ( $items as $id => $item ) {
				printf( '
                    <li class="%s" data-id="%2$d" data-type="%4$s" data-text="%3$s">
                        <label>
                            <input type="checkbox" value="%2$d" name="selectedItems[]">
                            <span class="lp-item-text">%3$s</span>
                        </label>
                    </li>
                    ', 'lp-result-item', $id, esc_attr( get_the_title( $item ) ), get_the_title( $item ) );
			}
		} else {
			echo '<li>' . apply_filters( 'learn_press_modal_search_items_not_found', __( 'No item found', 'learnpress' ), $this->_options['type'] ) . '</li>';
		}

		return ob_get_clean();
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
                                            :context-id="contextId" :show="show" :callbacks="callbacks"
                                            v-on:close="close">
            </learn-press-modal-search-items>
        </div>
		<?php
	}
}