<?php
global $post;
$index = $total_reviews;
?>
<h4>
	<?php if ( $total_reviews ) { ?>
		<?php printf( _nx( '%d review', '%d reviews', $total_reviews, 'learnpress' ), $total_reviews ); ?>
	<?php } else { ?>
		<?php _e( 'This course has not got any reviews yet', 'learnpress' ); ?>
	<?php } ?>
</h4>
<ul class="learn-press-review-logs clearfix">
	<?php foreach ( $reviews as $review ) { ?>
		<?php $user = get_userdata( $review->user_id ); ?>
		<li>
			<div class="review-index">#<?php echo $index --; ?></div>
			<div class="review-user">
				<span class="user-avatar"><?php echo get_avatar( $review->user_id ); ?></span>
			</div>
			<div class="review-content">
				<strong class="user-nicename"><?php echo $user->user_nicename; ?></strong>

				<div class="review-message"><?php echo $review->message; ?></div>
			<span class="lp-label <?php echo $review->status == 'publish' ? 'lp-label-preview' : ( $review->user_type == 'reviewer' ? 'lp-label-final' : 'lp-label-format' ); ?>">
				<?php echo $review->status == 'publish' ? __( 'Publish', 'learnpress' ) : ( $review->user_type == 'reviewer' ? __( 'Soft rejected', 'learnpress' ) : __( 'Submit for review', 'learnpress' ) ); ?>
			</span>
				<?php if ( current_user_can( 'delete_others_lp_courses' ) ) { ?>
					&nbsp;&nbsp;
					<a href="<?php echo wp_nonce_url( admin_url( 'post.php?post=' . $post->ID . '&action=edit&delete_log=' . $review->review_log_id ), 'delete_log_' . $post->ID . '_' . $review->review_log_id ); ?>"><?php _e( 'Delete', 'learnpress' ); ?></a>
				<?php } ?>
			</div>
		</li>
	<?php } ?>
</ul>
<?php if ( $total_reviews > 10 ) { ?>
	<p>
		<?php if ( $total_reviews == $count_reviews ) { ?>
			<a href="<?php echo remove_query_arg( 'view_all_review' ); ?>"><?php _e( 'View less', 'learnpress' ); ?></a>
		<?php } else { ?>
			<a href="<?php echo add_query_arg( 'view_all_review', '1' ); ?>"><?php _e( 'View all', 'learnpress' ); ?></a>
		<?php } ?>
	</p>
<?php } ?>
