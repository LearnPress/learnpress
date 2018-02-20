<?php
global $wp_meta_boxes;
$screen = get_current_screen();
$page = $screen->id;
$context = 'normal';
$hidden = get_hidden_meta_boxes( $screen );

if ( isset( $wp_meta_boxes[ $page ][ $context ] ) ) {
	echo '<h3 id="course-tabs-h3">';
	foreach ( array( 'high', 'sorted', 'core', 'default', 'low' ) as $priority ) {
		if ( isset( $wp_meta_boxes[$page][$context][$priority] ) ) {
			foreach ( (array) $wp_meta_boxes[$page][$context][$priority] as $box ) {
				if ( false == $box || !$box['title'] )
					continue;
				$i ++;
				$hidden_class = in_array( $box['id'], $hidden ) ? ' hide-if-js' : '';
				if( $box['id'] == 'course_tabs') continue;
				echo '<a href="#' . $box['id'] . '">' . $box['title'] . '</a>';
			}
		}
	}
	echo '</h3>';
}