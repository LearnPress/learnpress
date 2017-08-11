<?php

/**
 * Class LP_Modal_Search_Users
 */
class LP_Modal_Search_Users {

	/**
	 * @var array
	 */
	protected $_options = array();

	/**
	 * @var array
	 */
	protected $_query_args = array();

	/**
	 * @var WP_User_Query
	 */
	protected $_query = null;

	/**
	 * @var array
	 */
	protected $_items = array();

	/**
	 * @var bool
	 */
	protected $_changed = true;

	/**
	 * LP_Modal_Search_Users constructor.
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
				'title'        => __( 'Search user', 'learnpress' ),
				'number'       => 2,
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


		$exclude = array();

		if ( ! empty( $this->_options['exclude'] ) ) {
			$exclude = array_map( 'intval', $this->_options['exclude'] );
		}
		$exclude = array_unique( (array) apply_filters( 'learn_press_modal_search_items_exclude', $exclude, $type, $context, $context_id ) );
		$exclude = array_map( 'intval', $exclude );
		$args    = array(
			'number' => $this->_options['number'],
			'offset' => ( $this->_options['paged'] - 1 ) * $this->_options['number']
		);
		if ( $term ) {
			$args['search']         = sprintf( '*%s*', esc_attr( $term ) );
			$args['search_columns'] = array( 'user_login', 'user_email' );
		}
		$this->_query_args = apply_filters( 'learn_press_filter_admin_ajax_modal_search_items_args', $args, $context, $context_id );

		// The Query
		$this->_query = new WP_User_Query( $args );

		$this->_items = array();

		if ( $results = $this->_query->get_results() ) {
			foreach ( $results as $user ) {
				$this->_items[$user->ID] =  $user->user_login;
			}
		}

		return $this->_items;
	}

	public function get_items() {
		if ( $this->_changed ) {
			$this->_get_items();
		}

		return $this->_items;
	}

	function get_pagination() {

		$pagination = '';
		if ( $items = $this->get_items() && $this->_options['number'] > 0 ) {

			$number        = $this->_options['number'];
			$total         = $this->_query->get_total();
			$max_num_pages = intval( $total / $number );
			if ( $total % $number ) {
				$max_num_pages ++;
			}

			if ( $this->_options['paged'] && $max_num_pages > 1 ) {
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
					'total'     => $max_num_pages,
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
				$the_user = get_user_by('ID', $id);
				if($this->_options['multiple']) {
					printf( '
                    <li class="%s" data-id="%2$d" data-text="%3$s">
                        <label>
                            <input type="checkbox" value="%2$d" name="selectedItems[]">
                            <span class="lp-item-text">%3$s</span>
                        </label>
                    </li>
                    ', 'lp-result-item', $id, esc_attr( $the_user->user_login ) );
				}else{
					printf( '
                    <li class="%s" data-id="%2$d" data-text="%3$s">
                        <label>
                            <a href=""><span class="lp-item-text">%3$s</span></a>
                        </label>
                    </li>
                    ', 'lp-result-item', $id, esc_attr( $the_user->user_login ) );
				}
			}
		} else {
			echo '<li>' . apply_filters( 'learn_press_modal_search_items_not_found', __( 'No item found', 'learnpress' ), $this->_options['type'] ) . '</li>';
		}

		return ob_get_clean();
	}

	public function js_template() {
		$view = learn_press_get_admin_view( 'modal-search-users' );
		include $view;
	}

	public static function instance() {
		static $instance = false;
		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}
}