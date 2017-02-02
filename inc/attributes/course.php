<?php

/**
 *
 */
if ( !class_exists( 'LP_Course_Attributes' ) ) {
	class LP_Course_Attributes {
		private $_tax = '';

		private $_screen = false;

		public function __construct() {
			add_action( 'init', array( $this, 'register' ), 100 );
			add_action( 'load-edit-tags.php', array( $this, 'ready' ) );

			add_filter( 'learn_press_admin_tabs_info', array( $this, 'admin_tab' ) );
			add_filter( "course_attribute_row_actions", array( $this, 'row_actions' ), 10, 2 );
			add_filter( 'terms_clauses', array( $this, 'term_clauses' ), 10, 3 );
			add_filter( 'learn_press_admin_tabs_on_pages', array( $this, 'admin_tabs_pages' ) );
		}

		public function ready() {
			if ( !empty( $_REQUEST['taxonomy'] ) && strpos( $_REQUEST['taxonomy'], 'course_attribute' ) !== false ) {
				$this->_tax    = $_REQUEST['taxonomy'];
				$this->_screen = get_current_screen();
				if ( $this->_screen->id == 'edit-course_attribute' ) {
					add_filter( "manage_{$this->_screen->id}_columns", array( $this, 'columns' ) );
					add_filter( "manage_{$this->_tax}_custom_column", array( $this, 'column' ), 10, 3 );
				}
			}
		}

		public function column( $content, $column_name, $term_id ) {
			if ( $column_name == 'terms' ) {
				if ( $terms = learn_press_get_attribute_terms( $term_id ) ) {
					$attribute   = get_term( $term_id );
					$term_labels = array();
					foreach ( $terms as $term ) {
						$term_labels[] = sprintf( '<a href="%s">%s</a>', get_edit_term_link( $term->term_id, $term->taxonomy, LP_COURSE_CPT ), $term->name );
					}
					$content = join( ', ', $term_labels );

					$content .= '<div class="row-actions">' . $this->terms_row_actions( $attribute ) . '</div>';
				}
			}
			return $content;
		}

		public function terms_row_actions( $tax ) {
			$uri           = wp_doing_ajax() ? wp_get_referer() : $_SERVER['REQUEST_URI'];
			$edit_link     = add_query_arg(
				'wp_http_referer',
				urlencode( wp_unslash( $uri ) ),
				add_query_arg( array( 'taxonomy' => 'course_attribute-' . $tax->slug, 'post_type' => LP_COURSE_CPT ), admin_url( 'term.php' ) )
			);
			$terms_actions = array(
				'edit'   => sprintf(
					'<a href="%s" aria-label="%s" >%s </a > ',
					esc_url( $edit_link ),
					esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $tax->name ) ),
					__( 'Edit', 'learnpress' )
				),
				'delete' => sprintf(
					'<a href="%s" aria-label="%s" >%s </a > ',
					esc_url( $edit_link ),
					'',
					__( 'Clear', 'learnpress' )
				)
			);
			return join( ' | ', $terms_actions );
		}

		public function columns( $columns ) {
			if ( $this->_tax == 'course_attribute' && !empty( $columns['posts'] ) ) {
				unset( $columns['posts'] );
			}
			$columns['terms'] = __( 'Terms', 'learnpress' );
			return $columns;
		}

		public function admin_tabs_pages( $pages ) {
			if ( !empty( $_REQUEST['taxonomy'] ) && strpos( $_REQUEST['taxonomy'], 'course_attribute' ) !== false ) {
				$screen_id = get_current_screen()->id;
				$pages[]   = $screen_id;
			}
			return $pages;
		}

		public function term_clauses( $a, $b, $c ) {
			//print_r( func_get_args() );
			return $a;
		}

		public function admin_tab( $tabs ) {
			if ( !empty( $_REQUEST['taxonomy'] ) && strpos( $_REQUEST['taxonomy'], 'course_attribute' ) !== false ) {
				$screen_id = get_current_screen()->id;
			} else {
				$screen_id = 'edit-course_attribute';
			}
			$tabs[] = array(
				"link" => "edit-tags.php?taxonomy=course_attribute&post_type=lp_course",
				"name" => __( "Attributes", "learnpress" ),
				"id"   => $screen_id,
			);
			return $tabs;
		}

		public function register() {
			register_taxonomy(
				'course_attribute',
				array( 'lp_course' ),
				array(
					'label'        => __( 'Attribute', 'learnpress' ),
					'labels'       => array(
						'name'          => __( 'Attributes', 'learnpress' ),
						'singular_name' => __( 'Attribute', 'learnpress' ),
						'menu_name'     => __( 'Attributes', 'learnpress' ),
						'add_new_item'  => __( 'Add New Attribute', 'learnpress' ),
						'all_items'     => __( 'All Attributes', 'learnpress' )
					),
					'show_ui'      => true,
					'query_var'    => true,
					'show_in_menu' => 'learn_press',
					'public'       => false,
					'rewrite'      => array(
						'slug'         => 'course_attribute',//empty( $permalinks['category_base'] ) ? _x( 'product - category', 'slug', 'woocommerce' ) : $permalinks['category_base'],
						'with_front'   => false,
						'hierarchical' => true,
					),
				)
			);

			if ( $attributes = learn_press_get_course_attributes() ) {
				foreach ( $attributes as $attribute ) {
					$this->_register_custom_attribute( $attribute );
				}
			}
		}

		public function row_actions( $actions, $tag ) {

			return $actions;
		}

		private function _register_custom_attribute( $attribute ) {
			$name          = $attribute->name;
			$singular_name = $attribute->name;
			$tax_data      = array(
				'hierarchical'          => true,
				'update_count_callback' => '_update_post_term_count',
				'labels'                => array(
					'name'              => $name,
					'singular_name'     => $singular_name,
					'search_items'      => sprintf( __( 'Search Course %s', 'learnpress' ), $name ),
					'all_items'         => sprintf( __( 'All Course %s', 'learnpress' ), $name ),
					'parent_item'       => sprintf( __( 'Parent Course %s', 'learnpress' ), $singular_name ),
					'parent_item_colon' => sprintf( __( 'Parent Course %s:', 'learnpress' ), $singular_name ),
					'edit_item'         => sprintf( __( 'Edit Course %s', 'learnpress' ), $singular_name ),
					'update_item'       => sprintf( __( 'Update Course %s', 'learnpress' ), $singular_name ),
					'add_new_item'      => sprintf( __( 'Add New Course %s', 'learnpress' ), $singular_name ),
					'new_item_name'     => sprintf( __( 'New Course %s', 'learnpress' ), $singular_name ),
					'not_found'         => sprintf( __( 'No &quot;Course %s&quot; found', 'learnpress' ), $singular_name ),
				),
				'show_ui'               => true,
				'show_in_quick_edit'    => false,
				'show_in_menu'          => false,
				'show_in_nav_menus'     => false,
				'meta_box_cb'           => false,
				'query_var'             => false,
				'rewrite'               => false,
				'sort'                  => false,
				'public'                => false,
				'show_in_nav_menus'     => false/*,
				'capabilities'          => array(
					'manage_terms' => 'manage_lp_course_terms',
					'edit_terms'   => 'edit_lp_course_terms',
					'delete_terms' => 'delete_lp_course_terms',
					'assign_terms' => 'assign_lp_course_terms',
				)*/
			);

			$tax_data['rewrite'] = array(
				'slug'         => $attribute->slug,// empty( $permalinks['attribute_base'] ) ? '' : trailingslashit( $permalinks['attribute_base'] ) . sanitize_title( $tax->attribute_name ),
				'with_front'   => false,
				'hierarchical' => true
			);

			register_taxonomy(
				sprintf( 'course_attribute-%s', $attribute->slug ),
				apply_filters( 'learn_press_course_attribute_object_' . $attribute->slug, array( 'lp_course' ) ),
				apply_filters( 'learn_press_course_attribute_args_' . $attribute->slug, $tax_data )
			);

		}
	}
}
return new LP_Course_Attributes();