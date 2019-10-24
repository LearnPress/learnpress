<?php
add_filter( 'learn-press/create-user-item-meta', function ( $meta, $item ) {
//	switch ( $item['item_type'] ) {
//		case LP_QUIZ_CPT:
//			shuffle( $meta['questions'] );
//	}

	return $meta;
}, 100, 2 );

