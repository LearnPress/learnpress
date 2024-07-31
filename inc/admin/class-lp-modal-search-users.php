<?php
/**
 * Class LP_Modal_Search_Users
 *
 * @deprecated 4.2.6.9.3
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
				'text_format'  => '{{display_name}} ({{email}})',
				'add_button'   => __( 'Add', 'learnpress' ),
				'close_button' => __( 'Close', 'learnpress' ),
				'title'        => __( 'Search users', 'learnpress' ),
				'number'       => 10,
				'paged'        => 1,
			)
		);

		if ( is_string( $this->_options['exclude'] ) ) {
			$this->_options['exclude'] = explode( ',', $this->_options['exclude'] );
		}
	}

	protected function _get_items() {
		$term       = $this->_options['term'];
		$type       = $this->_options['type'];
		$context    = $this->_options['context'];
		$context_id = $this->_options['context_id'];

		$exclude = array_unique( (array) apply_filters( 'learn-press/modal-search-user/exclude', $this->_options['exclude'], $type, $context, $context_id ) );

		if ( ! empty( $exclude ) ) {
			$exclude = array_map( 'intval', $exclude );
		}

		$args = array(
			'number'  => $this->_options['number'],
			'offset'  => ( $this->_options['paged'] - 1 ) * $this->_options['number'],
			'exclude' => $exclude,
		);
		if ( $term ) {
			$args['search']         = sprintf( '*%s*', esc_attr( $term ) );
			$args['search_columns'] = array( 'user_login', 'user_email' );
		}
		$this->_query_args = apply_filters( 'learn-press/modal-search-users/args', $args, $context, $context_id );

		// The Query
		$this->_query = new WP_User_Query( $args );
		$this->_items = array();

		$results = $this->_query->get_results();
		if ( $results ) {
			foreach ( $results as $user ) {
				$this->_items[ $user->ID ] = $user->user_login;
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
		$items      = $this->get_items();

		if ( $items && $this->_options['number'] > 0 ) {

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

				$pagenum_link = esc_url_raw( remove_query_arg( array_keys( $query_args ), $pagenum_link ) );
				$pagenum_link = trailingslashit( $pagenum_link ) . '%_%';

				$pagination = paginate_links(
					array(
						'base'      => $pagenum_link,
						'total'     => $max_num_pages,
						'current'   => max( 1, $this->_options['paged'] ),
						'mid_size'  => 1,
						'add_args'  => array_map( 'urlencode', $query_args ),
						'prev_text' => __( '<', 'learnpress' ),
						'next_text' => __( '>', 'learnpress' ),
						'type'      => '',
					)
				);
			}
		}

		return $pagination;
	}

	public function get_html_items() {

		$variables = array(
			'id',
			'email',
			'user_login',
			'description',
			'first_name',
			'last_name',
			'nickname',
			'display_name',
		);

		ob_start();

		$items = $this->get_items();

		if ( $items ) {
			foreach ( $items as $id => $item ) {
				$the_user = learn_press_get_user( $id );
				$text     = str_replace( '{{id}}', $the_user->get_id(), $this->_options['text_format'] );
				$data     = array();
				foreach ( $variables as $variable ) {
					$text              = str_replace( '{{' . $variable . '}}', $the_user->get_data( $variable ), $text );
					$data[ $variable ] = $the_user->get_data( $variable );
				}
				$data['id'] = $id;
				printf( '<li class="%s" data-id="%d" data-data="%s"><label>', 'lp-result-item user-' . $id, $id, esc_attr( wp_json_encode( $data ) ) );
				if ( $this->_options['multiple'] ) {
					printf(
						'
                   		<input type="checkbox" value="%d" name="selectedItems[]">
                        <span class="lp-item-text">%s</span>
                    ',
						$id,
						esc_attr( $text )
					);
				} else {
					printf(
						'
                        <a href=""><span class="lp-item-text">%s</span></a>
                    ',
						esc_attr( $text )
					);
				}

				echo '</li>';
			}
		} else {
			echo '<li>' . apply_filters( 'learn-press/modal-search-users/not-found', __( 'No item found', 'learnpress' ), $this->_options['type'] ) . '</li>';
		}

		return ob_get_clean();
	}

	public function js_template() {
		$view = learn_press_get_admin_view( 'modal-search-users' );
		include $view;
	}

	public static function instance() {
		static $instance;
		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}
}
