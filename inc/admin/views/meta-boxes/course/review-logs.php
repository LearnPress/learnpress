<?php

?>
<h4>
	<?php if( $total_reviews ) { ?>
		<?php printf( _nx( '%d review', '%d reviews', $total_reviews, 'learn_press' ), $total_reviews );?>
	<?php }else{ ?>
		<?php _e( 'This course has not got any review yet', 'learn_press' );?>
	<?php } ?>
</h4>
<ul class="learn-press-review-logs">
	<?php foreach( $reviews as $review ){?>
		<?php $user = get_userdata( $review->user_id );?>
	<li>
		<div class="review-user">
			<span class="user-avatar"><?php echo get_avatar( $review->user_id ); ?></span>
		</div>
		<div class="review-content">
			<strong class="user-nicename"><?php echo $user->user_nicename; ?></strong>
			<div class="review-message"><?php echo $review->message;?></div>
			<span class="lp-label <?php echo $review->status == 'publish' ? 'lp-label-preview' : ( $review->user_type == 'reviewer' ? 'lp-label-final' : 'lp-label-format' ) ;?>">
				<?php echo $review->status == 'publish' ? __( 'Publish', 'learn_press' ) : ( $review->user_type == 'reviewer' ? __( 'Soft rejected', 'learn_press' ) : __( 'Submit for review', 'learn_press' ) );?>
			</span>
		</div>
	</li>
	<?php }?>
</ul>
<?php if( $total_reviews > 10 ){ ?>
	<?php if( $total_reviews == $count_reviews ){?>
		<a href="<?php echo remove_query_arg( 'view_all_review' );?>"><?php _e( 'View less', 'learn_press' );?></a>
	<?php }else{ ?>
		<a href="<?php echo add_query_arg( 'view_all_review', '1' );?>"><?php _e( 'View all', 'learn_press' );?></a>
	<?php }?>
<?php } ?>
