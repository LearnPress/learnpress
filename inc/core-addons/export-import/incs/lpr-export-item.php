<?php echo "\n";?><item>
	<title><?php echo apply_filters( 'the_title_rss', $post->post_title ); ?></title>
	<link><?php the_permalink_rss() ?></link>
	<pubDate><?php echo mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true ), false ); ?></pubDate>
	<dc:creator><?php echo wxr_cdata( get_the_author_meta( 'login' ) ); ?></dc:creator>
	<guid isPermaLink="false"><?php the_guid(); ?></guid>
	<description></description>
	<content:encoded><?php
		/**
		 * Filter the post content used for WXR exports.
		 *
		 * @since 2.5.0
		 *
		 * @param string $post_content Content of the current post.
		 */
		echo wxr_cdata( apply_filters( 'the_content_export', $post->post_content ) );
	?></content:encoded>
	<excerpt:encoded><?php
		/**
		 * Filter the post excerpt used for WXR exports.
		 *
		 * @since 2.6.0
		 *
		 * @param string $post_excerpt Excerpt for the current post.
		 */
		echo wxr_cdata( apply_filters( 'the_excerpt_export', $post->post_excerpt ) );
	?></excerpt:encoded>
	<wp:post_id><?php echo $post->ID; ?></wp:post_id>
	<wp:post_date><?php echo $post->post_date; ?></wp:post_date>
	<wp:post_date_gmt><?php echo $post->post_date_gmt; ?></wp:post_date_gmt>
	<wp:comment_status><?php echo $post->comment_status; ?></wp:comment_status>
	<wp:ping_status><?php echo $post->ping_status; ?></wp:ping_status>
	<wp:post_name><?php echo $post->post_name; ?></wp:post_name>
	<wp:status><?php echo $post->post_status; ?></wp:status>
	<wp:post_parent><?php echo $post->post_parent; ?></wp:post_parent>
	<wp:menu_order><?php echo $post->menu_order; ?></wp:menu_order>
	<wp:post_type><?php echo $post->post_type; ?></wp:post_type>
	<wp:post_password><?php echo $post->post_password; ?></wp:post_password>
    <wp:post_author_id><?php echo $post->post_author;?></wp:post_author_id>
	<wp:is_sticky><?php echo $is_sticky; ?></wp:is_sticky>
	<?php lpr_export_attachment( $post );?>
	<?php wxr_post_taxonomy(); ?>
	<?php	
    if( !in_array( $post->post_author, $_lpr_course_author_ids )){
        $_lpr_course_author_ids[] = $post->post_author;
    }
	$postmeta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE post_id = %d", $post->ID ) );
	foreach ( $postmeta as $meta ) :	
		if ( apply_filters( 'wxr_export_skip_postmeta', false, $meta->meta_key, $meta ) )
			continue;
		do_action( 'lpr_export_postmeta', $meta->meta_key, $meta );
		
		switch( $meta->meta_key ){
			case '_lpr_course_prerequisite':
            
                continue;
				$_lpr_course_prerequisite[] = $meta->meta_value;
				break;
			case '_lpr_co_teacher':
                continue;
				$_lpr_co_teacher[] = $meta->meta_value;
				break;
			case '_lpr_course_certificate':
				$_lpr_course_certificate[] = $meta->meta_value;
				break;
			case '_lpr_course_lesson_quiz':
				$metavalue = maybe_unserialize( $meta->meta_value );
				if( is_array( $metavalue ) ){
                    foreach( $metavalue as $section ) {
                        if( !empty( $section['lesson_quiz'] ) ){
                            $_lpr_course_sections = array_merge($_lpr_course_sections, $section['lesson_quiz'] );
                        }

                    }
				}
				break;
		}
	echo "\n";
	?>
	<wp:postmeta>
		<wp:meta_key><?php echo $meta->meta_key; ?></wp:meta_key>
		<wp:meta_value><?php echo wxr_cdata( $meta->meta_value ); ?></wp:meta_value>
	</wp:postmeta><?php	
	endforeach;

	$comments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_approved <> 'spam'", $post->ID ) );
	foreach ( $comments as $c ) : ?><wp:comment>
		<wp:comment_id><?php echo $c->comment_ID; ?></wp:comment_id>
		<wp:comment_author><?php echo wxr_cdata( $c->comment_author ); ?></wp:comment_author>
		<wp:comment_author_email><?php echo $c->comment_author_email; ?></wp:comment_author_email>
		<wp:comment_author_url><?php echo esc_url_raw( $c->comment_author_url ); ?></wp:comment_author_url>
		<wp:comment_author_IP><?php echo $c->comment_author_IP; ?></wp:comment_author_IP>
		<wp:comment_date><?php echo $c->comment_date; ?></wp:comment_date>
		<wp:comment_date_gmt><?php echo $c->comment_date_gmt; ?></wp:comment_date_gmt>
		<wp:comment_content><?php echo wxr_cdata( $c->comment_content ) ?></wp:comment_content>
		<wp:comment_approved><?php echo $c->comment_approved; ?></wp:comment_approved>
		<wp:comment_type><?php echo $c->comment_type; ?></wp:comment_type>
		<wp:comment_parent><?php echo $c->comment_parent; ?></wp:comment_parent>
		<wp:comment_user_id><?php echo $c->user_id; ?></wp:comment_user_id>
		<?php		
		$c_meta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->commentmeta WHERE comment_id = %d", $c->comment_ID ) );
		foreach ( $c_meta as $meta ) :
			/**
			 * Filter whether to selectively skip comment meta used for WXR exports.
			 *
			 * Returning a truthy value to the filter will skip the current meta
			 * object from being exported.
			 *
			 * @since 4.0.0
			 *
			 * @param bool   $skip     Whether to skip the current comment meta. Default false.
			 * @param string $meta_key Current meta key.
			 * @param object $meta     Current meta object.
			 */
			if ( apply_filters( 'wxr_export_skip_commentmeta', false, $meta->meta_key, $meta ) ) {
				continue;
			}
		?>
		<wp:commentmeta>
			<wp:meta_key><?php echo $meta->meta_key; ?></wp:meta_key>
			<wp:meta_value><?php echo wxr_cdata( $meta->meta_value ); ?></wp:meta_value>
		</wp:commentmeta><?php 
		endforeach; 
		?></wp:comment><?php 
		endforeach; 
		echo "\n";
		?>
</item>