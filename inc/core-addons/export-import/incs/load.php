<?php
/**
 * Project:     learnpress.
 * Author:      TuNN
 * Date:        20 Mar 2015
 *
 * Copyright 2007-2014 thimpress.com. All rights reserved.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define( 'LPR_CERTIFICATE_CPT',          'lpr_certificate' );
define( 'LPR_CERTIFICATE_SLUG',         'certificate' );
define( 'LPR_CERTIFICATE_TYPE_SLUG',    'certificate-type' );

/**
 * Register certificate post type
 */
function lpr_certificate_post_type() {
	register_post_type( LPR_CERTIFICATE_CPT,
		array(
			'labels'             => array(
				'name'          => __( 'Certificate', 'learn_press' ),
				'menu_name'     => __( 'Certificates', 'learn_press' ),
				'singular_name' => __( 'Certificate', 'learn_press' ),
				'add_new_item'  => __( 'Add New Certificate', 'learn_press' ),
				'edit_item'     => __( 'Edit Certificate', 'learn_press' ),
				'all_items'     => __( 'Certificates', 'learn_press' ),
			),
			'public'             => false,
			//'taxonomies'         => array( 'assignment-type' ),
			'publicly_queryable' => true,
			'show_ui'            => true,
			'has_archive'        => true,
			'capability_type'    => LPR_LESSON_CPT,
			'map_meta_cap'       => true,
			'show_in_menu'       => 'learn_press',
			'show_in_admin_bar'  => true,
			'show_in_nav_menus'  => true,
			'supports'           => array(
				'title',
				//'editor',
				'author',
				//'revisions',
			),
			//'hierarchical'       => true,
			'rewrite'            => array( 'slug' => LPR_CERTIFICATE_SLUG, )// 'hierarchical' => true, 'with_front' => true )
		)
	);
}

// Hook into the 'init' action
add_action( 'init', 'lpr_certificate_post_type' );
