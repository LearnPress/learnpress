<?php
//
//class LP_Market_Products {
//	public function __construct() {
//
//		add_filter( 'tpl-market-products', array( $this, 'market_products' ) );
//
//	}
//
//	public function market_products( $products = array() ) {
//		$products['learnpress'] = array(
//			'name'  => __( 'LearnPress', 'learnpress' ),
//			'desc'  => __( 'Ahihi', 'learnpress' ),
//			'priority'  => 5,
//			'items' => array(
//				array(
//					'name'        => 'LearnPress Course Review',
//					'slug'        => 'learnpress-course-review',
//					'required'    => false,
//					'version'     => '2.0',
//					'description' => 'Adding review for course By ThimPress.',
//					'add-on'      => true,
//				),
//				array(
//					'name'        => 'LearnPress Gradebook',
//					'slug'        => 'learnpress-gradebook',
//					'required'    => false,
//					'version'     => '2.0',
//					'premium' => true,
//					'description' => 'Adding review for course By ThimPress.',
//					'add-on'      => true,
//				),
//			)
//		);
//
//		return $products;
//	}
//}
//
//return new LP_Market_Products();