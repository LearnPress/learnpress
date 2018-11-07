<?php
defined( 'ABSPATH' ) || exit();
die();
?>
<script type="text/html" id="tmpl-page-block">
	<div id="lpr-page-block"></div>
</script>
<script type="text/html" id="tmpl-form-quick-add-lesson-link">
	<div id="form-quick-add-lesson-link" class="lpr-dynamic-form">
		<select name="">
			<option value=""><?php _e( '--Select a Lesson--', 'learnpress' ); ?></option>
			<?php
			global $post;
			$query_args = array(
				'post_type'      => LP_LESSON_CPT,
				'post_status'    => 'publish',
				'author'         => get_current_user_id(),
				'posts_per_page' => - 1,
				'exclude'        => $post->ID
			);
			$query      = new WP_Query( $query_args );
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$p = $query->next_post();
					echo '<option value="' . $p->ID . '">' . $p->post_title . '</option>';
				}
			}
			?>
		</select>
	</div>
</script>