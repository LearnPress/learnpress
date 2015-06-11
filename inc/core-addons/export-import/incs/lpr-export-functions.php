<?php
/**
 * Wrap given string in XML CDATA tag.
 *
 * @since 2.1.0
 *
 * @param string $str String to wrap in XML CDATA tag.
 * @return string
 */
function wxr_cdata( $str ) {
	if ( seems_utf8( $str ) == false )
		$str = utf8_encode( $str );

	// $str = ent2ncr(esc_html($str));
	$str = '<![CDATA[' . str_replace( ']]>', ']]]]><![CDATA[>', $str ) . ']]>';

	return $str;
}

/**
 * Return the URL of the site
 *
 * @since 2.5.0
 *
 * @return string Site URL.
 */
function wxr_site_url() {
	// Multisite: the base URL.
	if ( is_multisite() )
		return network_home_url();
	// WordPress (single site): the blog URL.
	else
		return get_bloginfo_rss( 'url' );
}

/**
 * Output a cat_name XML tag from a given category object
 *
 * @since 2.1.0
 *
 * @param object $category Category Object
 */
function wxr_cat_name( $category ) {
	if ( empty( $category->name ) )
		return;

	echo '<wp:cat_name>' . wxr_cdata( $category->name ) . '</wp:cat_name>';
}

/**
 * Output a category_description XML tag from a given category object
 *
 * @since 2.1.0
 *
 * @param object $category Category Object
 */
function wxr_category_description( $category ) {
	if ( empty( $category->description ) )
		return;

	echo '<wp:category_description>' . wxr_cdata( $category->description ) . '</wp:category_description>';
}

/**
 * Output a tag_name XML tag from a given tag object
 *
 * @since 2.3.0
 *
 * @param object $tag Tag Object
 */
function wxr_tag_name( $tag ) {
	if ( empty( $tag->name ) )
		return;

	echo '<wp:tag_name>' . wxr_cdata( $tag->name ) . '</wp:tag_name>';
}

/**
 * Output a tag_description XML tag from a given tag object
 *
 * @since 2.3.0
 *
 * @param object $tag Tag Object
 */
function wxr_tag_description( $tag ) {
	if ( empty( $tag->description ) )
		return;

	echo '<wp:tag_description>' . wxr_cdata( $tag->description ) . '</wp:tag_description>';
}

/**
 * Output a term_name XML tag from a given term object
 *
 * @since 2.9.0
 *
 * @param object $term Term Object
 */
function wxr_term_name( $term ) {
	if ( empty( $term->name ) )
		return;

	echo '<wp:term_name>' . wxr_cdata( $term->name ) . '</wp:term_name>';
}

/**
 * Output a term_description XML tag from a given term object
 *
 * @since 2.9.0
 *
 * @param object $term Term Object
 */
function wxr_term_description( $term ) {
	if ( empty( $term->description ) )
		return;

	echo '<wp:term_description>' . wxr_cdata( $term->description ) . '</wp:term_description>';
}

/**
 * Output list of authors with posts
 *
 * @since 3.1.0
 *
 * @param array $post_ids Array of post IDs to filter the query by. Optional.
 */
function wxr_authors_list( array $post_ids = null ) {
	global $wpdb;

	if ( !empty( $post_ids ) ) {
		$post_ids = array_map( 'absint', $post_ids );
		$and = 'AND ID IN ( ' . implode( ', ', $post_ids ) . ')';
	} else {
		$and = '';
	}

	$authors = array();
	$results = $wpdb->get_results( "SELECT DISTINCT post_author FROM $wpdb->posts WHERE post_status != 'auto-draft' $and" );
	foreach ( (array) $results as $result )
		$authors[] = get_userdata( $result->post_author );

	$authors = array_filter( $authors );

	foreach ( $authors as $author ) {
		echo "\t<wp:author>";
		echo '<wp:author_id>' . $author->ID . '</wp:author_id>';
		echo '<wp:author_login>' . $author->user_login . '</wp:author_login>';
		echo '<wp:author_email>' . $author->user_email . '</wp:author_email>';
		echo '<wp:author_display_name>' . wxr_cdata( $author->display_name ) . '</wp:author_display_name>';
		echo '<wp:author_first_name>' . wxr_cdata( $author->user_firstname ) . '</wp:author_first_name>';
		echo '<wp:author_last_name>' . wxr_cdata( $author->user_lastname ) . '</wp:author_last_name>';
		echo "</wp:author>\n";
	}
}

/**
 * Ouput all navigation menu terms
 *
 * @since 3.1.0
 */
function wxr_nav_menu_terms() {
	$nav_menus = wp_get_nav_menus();
	if ( empty( $nav_menus ) || ! is_array( $nav_menus ) )
		return;

	foreach ( $nav_menus as $menu ) {
		echo "\t<wp:term><wp:term_id>{$menu->term_id}</wp:term_id><wp:term_taxonomy>nav_menu</wp:term_taxonomy><wp:term_slug>{$menu->slug}</wp:term_slug>";
		wxr_term_name( $menu );
		echo "</wp:term>\n";
	}
}

/**
 * Output list of taxonomy terms, in XML tag format, associated with a post
 *
 * @since 2.3.0
 */
function wxr_post_taxonomy() {
	$post = get_post();

	$taxonomies = get_object_taxonomies( $post->post_type );
	if ( empty( $taxonomies ) )
		return;
	$terms = wp_get_object_terms( $post->ID, $taxonomies );

	foreach ( (array) $terms as $term ) {
		echo "\t\t<category domain=\"{$term->taxonomy}\" nicename=\"{$term->slug}\" id=\"{$term->term_id}\" parent=\"{$term->parent}\" description=\" {$term->description}\">" . wxr_cdata( $term->name ) . "</category>\n";
	}
}

function wxr_filter_postmeta( $return_me, $meta_key ) {
	if ( '_edit_lock' == $meta_key )
		$return_me = true;
	return $return_me;
}

function lpr_export_header( $post_ids ){

    if( sizeof( $post_ids ) > 1 ){
        $filename = 'learnpress-courses';
    }else{
        foreach( $post_ids as $post_id ) {
            $p = get_post($post_id);

            if( $p && ( 'lpr_course' == get_post_type( $post_id ) ) ){
                $filename = 'learnpress-' . $p->post_name;
                break;
            }
        }
    }
    if( empty( $filename ) ) wp_die( __( 'Export fail', 'learn_press' ) );
    $filename .= '-' .date( 'Ymd' ) . '.xml';

	header( 'Content-Description: File Transfer' );
	header( 'Content-Disposition: attachment; filename=' . $filename );
	header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );
}

function lpr_export_attachment( $post ){
    if ( has_post_thumbnail( $post->ID ) ) :
        $attachment_id = get_post_thumbnail_id( $post->ID );
        $attachment = wp_get_attachment_image_src( $attachment_id, 'full' );
        $url = $attachment[0];
        if( $data = @file_get_contents( $url ) ){
            $data = base64_encode($data);
        }else{
            $data = $url;
        }
        $parts = explode('.', basename($url));
        array_pop( $parts );
        $filename = join('.', $parts );
        ?>
        <wp:attachment id="<?php echo $attachment_id;?>" mime_type="<?php echo get_post_mime_type($attachment_id);?>" filename="<?php echo $filename;?>"><?php echo wxr_cdata( $data ); ?></wp:attachment>
        <?php
    endif;
}