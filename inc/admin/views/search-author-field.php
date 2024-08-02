<?php
/**
 * Template form search author field for custom post type of course.
 *
 * @since 4.2.6.9
 * @version 1.0.0
 */
$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

// Check condition show field search by author for post.
$show_search_author_field = 0;
if ( ! $screen instanceof WP_Screen ) {
	return;
}
$course_item_types                = learn_press_get_course_item_types();
$screens_show_search_author_field = [
	'edit-' . LP_COURSE_CPT,
	'edit-' . LP_QUESTION_CPT,
	'edit-' . LP_ORDER_CPT,
];

foreach ( $course_item_types as $type ) {
	$screens_show_search_author_field[] = 'edit-' . $type;
}

$show_search_author_field = in_array( $screen->id, $screens_show_search_author_field ) ? 1 : 0;
if ( ! $show_search_author_field ) {
	return;
}

/**
 * @uses WP_List_Table::search_box()
 *
 * HTML return same search_box method
 */
global $typenow;
$post_type        = $typenow;
$post_type_object = get_post_type_object( $post_type );

$input_id = 'post-search-input';

if ( ! empty( $_REQUEST['orderby'] ) ) {
	echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
}
if ( ! empty( $_REQUEST['order'] ) ) {
	echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
}
if ( ! empty( $_REQUEST['post_mime_type'] ) ) {
	echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( $_REQUEST['post_mime_type'] ) . '" />';
}
if ( ! empty( $_REQUEST['detached'] ) ) {
	echo '<input type="hidden" name="detached" value="' . esc_attr( $_REQUEST['detached'] ) . '" />';
}

$value_search = LP_Request::get_param( 's', '' );
?>
<p class="search-box">
	<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>">
		<?php echo $post_type_object->labels->search_items; ?>:
	</label>
	<input type="search"
		   id="<?php echo esc_attr( $input_id ); ?>"
		   name="s"
		   placeholder="<?php _e( 'Search', 'learnpress' ); ?>"
		   value="<?php echo esc_attr( $value_search ) ?>"/>
	<?php submit_button( $post_type_object->labels->search_items, '', '', false, array( 'id' => 'search-submit' ) ); ?>
</p>
