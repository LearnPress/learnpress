<?php
define( 'LPR_EXPORT_VER', '1.0');	
global $wpdb, $post;	
?>	
<?php lpr_export_header( $post_ids ); ?>
<?php the_generator( 'export' ); ?>	
<?php echo '<?xml version="1.0" encoding="' . get_bloginfo('charset') . "\" ?>\n";?>
<rss version="2.0"
	xmlns:excerpt="http://wordpress.org/export/<?php echo LPR_EXPORT_VER; ?>/excerpt/"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:wp="http://wordpress.org/export/<?php echo LPR_EXPORT_VER; ?>/"
>

<channel>
	<title><?php bloginfo_rss( 'name' ); ?></title>
	<link><?php bloginfo_rss( 'url' ); ?></link>
	<description><?php bloginfo_rss( 'description' ); ?></description>
	<pubDate><?php echo date( 'D, d M Y H:i:s +0000' ); ?></pubDate>
	<language><?php bloginfo_rss( 'language' ); ?></language>
	<wp:wxr_version><?php echo LPR_EXPORT_VER; ?></wp:wxr_version>
	<wp:base_site_url><?php echo wxr_site_url(); ?></wp:base_site_url>
	<wp:base_blog_url><?php bloginfo_rss( 'url' ); ?></wp:base_blog_url>

<?php	
$_lpr_course_prerequisite 	= array();
$_lpr_co_teacher			= array();
$_lpr_course_certificate	= array();
$_lpr_course_sections   	= array();
$_lpr_course_lesson_quiz    = array();
$_lpr_quiz_questions        = array();

$_lpr_course_author_ids     = array();
$_lpr_quiz_question_ids     = array();

$_lpr_courses               = array();
$_lpr_instructors           = array();
$_lpr_taxonomies            = array();

// exports selected courses
while ( $next_posts = array_splice( $post_ids, 0, 20 ) ) {
	$where = 'WHERE ID IN (' . join( ',', $next_posts ) . ')';
	$posts = $wpdb->get_results( "SELECT * FROM {$wpdb->posts} $where" );

	// Begin Loop.
	foreach ( $posts as $post ) {
		setup_postdata( $post );
		$is_sticky = is_sticky( $post->ID ) ? 1 : 0;
        ob_start();
		require( 'lpr-export-item.php' );
        $_lpr_courses[$post->ID] = ob_get_clean();
	} // endforeachh
} // endwhile;	

// if have course prerequisite then export them
if( false && $_lpr_course_prerequisite ){
	$where = 'WHERE ID IN (' . join( ',', $_lpr_course_prerequisite ) . ')';
	$posts = $wpdb->get_results( "SELECT * FROM {$wpdb->posts} $where" );

	// Begin Loop.
	foreach ( $posts as $post ) {
		setup_postdata( $post );
		$is_sticky = is_sticky( $post->ID ) ? 1 : 0;
		require( 'lpr-export-item.php' );
	} // endforeachh
}


// if have course lesson/quiz then export them
if( $_lpr_course_sections ){
	//foreach( $_lpr_course_sections as $section ){
		//$lesson_or_quiz = (array)$section['lesson_quiz'];
		$where = 'WHERE ID IN (' . join( ',', $_lpr_course_sections ) . ')';
		$posts = $wpdb->get_results( "SELECT * FROM {$wpdb->posts} $where" );
		// Begin Loop.
		foreach ( $posts as $post ) {
			setup_postdata( $post );
			$is_sticky = is_sticky( $post->ID ) ? 1 : 0;
			ob_start();
    		require( 'lpr-export-item.php' );
            $_lpr_course_lesson_quiz[$post->ID] = ob_get_clean();

            // get all questions
            $questions = get_post_meta( $post->ID, '_lpr_quiz_questions', true );
            if( is_array( $questions ) ){
                $questions = array_keys( $questions );
                $_lpr_quiz_question_ids = array_merge( $_lpr_quiz_question_ids, $questions );
            }
		} // endforeachh
	//}
}

// export questions
if( $_lpr_quiz_question_ids ){
    $_lpr_quiz_question_ids = array_unique( $_lpr_quiz_question_ids );
    $where = 'WHERE ID IN (' . join( ',', $_lpr_quiz_question_ids ) . ')';
    $posts = $wpdb->get_results( "SELECT * FROM {$wpdb->posts} $where" );
    foreach ( $posts as $post ) {
        setup_postdata( $post );
        $is_sticky = 0;
        ob_start();
        require( 'lpr-export-item.php' );
        $_lpr_quiz_questions[$post->ID] = ob_get_clean();
    }
}

if( $_lpr_course_certificate ){
	$where = 'WHERE ID IN (' . join( ',', $_lpr_course_certificate ) . ')';
	$posts = $wpdb->get_results( "SELECT * FROM {$wpdb->posts} $where" );
	// Begin Loop.
	foreach ( $posts as $post ) {
		setup_postdata( $post );
		$is_sticky = is_sticky( $post->ID ) ? 1 : 0;
		ob_start();
		require( 'lpr-export-item.php' );
        $_lpr_course_certificate[$post->ID] = ob_get_clean();
	} // endforeachh
	
}

if( $_lpr_course_author_ids ){
    foreach ( (array) $_lpr_course_author_ids as $result )
		$authors[] = get_userdata( $result );

	$authors = array_filter( $authors );

	foreach ( $authors as $author ) {
        ob_start();	       
		echo "\t<wp:author>";
		echo '<wp:author_id>' . $author->ID . '</wp:author_id>';
		echo '<wp:author_login>' . $author->user_login . '</wp:author_login>';
		echo '<wp:author_email>' . $author->user_email . '</wp:author_email>';
		echo '<wp:author_display_name>' . wxr_cdata( $author->display_name ) . '</wp:author_display_name>';
		echo '<wp:author_first_name>' . wxr_cdata( $author->user_firstname ) . '</wp:author_first_name>';
		echo '<wp:author_last_name>' . wxr_cdata( $author->user_lastname ) . '</wp:author_last_name>';
		echo "</wp:author>\n";
        $_lpr_course_authors[] = ob_get_clean();
	}
}

if( $_lpr_course_authors ){
    echo "\n<!-- START: Authors -->";
    echo "\n" . join( "\n", $_lpr_course_authors );
    echo "\n<!-- END: Authors -->";
}

if( $_lpr_courses ){
    echo "\n<!-- START: Courses -->";
    echo "\n" . join( "\n", $_lpr_courses );
    echo "\n<!-- END: Courses -->";    
}

if( $_lpr_course_lesson_quiz ){
    echo "\n<!-- START: Lessons and Quizzes -->";
    echo "\n" . join( "\n", $_lpr_course_lesson_quiz );
    echo "\n<!-- END: Lessons and Quizzes -->";    
}

if( $_lpr_quiz_questions ){
    echo "\n<!-- START: Questions -->";
    echo "\n" . join( "\n", $_lpr_quiz_questions );
    echo "\n<!-- END: Questions -->";
}

if( $_lpr_course_certificate ){
    echo "\n<!-- START: Certificate -->";
    echo "\n" . join( "\n", $_lpr_course_certificate );
    echo "\n<!-- END: Certificate -->";    
}
//print_r($_lpr_course_prerequisite);
//print_r($_lpr_co_teacher);
//print_r($_lpr_course_certificate);


?>
</channel>