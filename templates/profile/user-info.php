
<span><?php echo get_avatar( $user->ID ); ?></span>
<strong><?php echo $user->data->user_nicename; ?></strong>
<p><?php echo get_user_meta( $user->ID, 'description', true ); ?></p>