<?php
if ( !class_exists( 'LP_Meta_Box_Tabs' ) ) {
	class LP_Meta_Box_Tabs {
		public $args = array();

		public function __construct( $args = array() ) {
			$defaults   = array(
				'id'        => uniqid( 'lp-meta-box-tabs-' ),
				'title'     => __( 'Meta box tabs', 'learnpress' ),
				'callback'  => array( $this, 'show' ),
				'post_type' => '',
				'context'   => 'normal',
				'priority'  => 'high',
				'tabs'      => array()
			);
			$this->args = wp_parse_args( $args, $defaults );

			add_action( 'edit_form_after_editor', array( $this, 'display' ), 10 );
			add_filter( 'get_edit_post_link', array( $this, 'add_tab_arg' ) );
			//add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		}

		public function add_tab_arg( $m ) {
			if ( array_key_exists( 'learn-press-meta-box-tab', $_REQUEST ) && !empty( $_REQUEST['learn-press-meta-box-tab'] ) ) {
				$m = add_query_arg( 'tab', $_REQUEST['learn-press-meta-box-tab'], $m );
			}
			return $m;
		}

		public function display() {
			global $post;
			if ( !empty( $this->args['post_type'] ) ) {
				if ( !is_array( $this->args['post_type'] ) ) {
					$this->args['post_type'] = preg_split( '!\s+!', $this->args['post_type'] );
				}
			}
			if ( empty( $this->args['post_type'] ) ) {
				return;
			}

			if ( !in_array( get_post_type(), $this->args['post_type'] ) ) {
				return;
			}
			include learn_press_get_admin_view( 'meta-boxes/tabs' );
		}

		public function add_meta_boxes() {
			if ( !empty( $this->args['post_type'] ) ) {
				if ( !is_array( $this->args['post_type'] ) ) {
					$this->args['post_type'] = preg_split( '!\s+!', $this->args['post_type'] );
				}
			}
			if ( empty( $this->args['post_type'] ) ) {
				return;
			}
			foreach ( $this->args['post_type'] as $post_type ) {
				add_meta_box(
					$this->opt( 'id' ),
					$this->opt( 'title' ),
					$this->opt( 'callback' ),
					$post_type,
					$this->opt( 'context' ),
					$this->opt( 'priority' )
				);
				add_filter( "postbox_classes_{$post_type}_" . $this->opt( 'id' ), array( $this, 'postbox_classes' ) );
			}
		}

		public function opt( $name ) {
			return array_key_exists( $name, $this->args ) ? $this->args[$name] : '';
		}

		public function show() {
		}

		public function postbox_classes( $classes ) {
			$classes[] = 'lp-meta-box-tabs';
			return array_filter( $classes );
		}

		public function get_tabs() {
			$tabs       = $this->opt( 'tabs' );
			$post_types = $this->opt( 'post_type' );
			if ( $post_types ) {
				foreach ( $post_types as $post_type ) {
					$tabs = apply_filters( "learn_press_{$post_type}_tabs", $tabs );
				}
			}
			return $tabs;
		}
	}
}