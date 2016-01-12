<?php
/**
 * User Profile tabs
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wp_query;

$tabs = learn_press_user_profile_tabs( $user );
if( !empty( $wp_query->query_vars['tab']  ) ){
	$current = $wp_query->query_vars['tab'];
}else{
	$tab_keys = array_keys( $tabs );
	$current = reset( $tab_keys );
}
if ( ! empty( $tabs ) ) : ?>

	<div class="user-profile-tabs learn-press-tabs-wrapper">

		<?php foreach ( $tabs as $id => $tab ) : ?>
			<div class="panel entry-content learn-press-tab" id="tab-<?php echo esc_attr( $id ); ?>">
				<div class="entry-tab-inner">
				<?php if( is_callable( $tab['callback'] ) ): ?>

					<?php call_user_func_array( $tab['callback'], array( $id, $tab, $user ) ); ?>

				<?php endif;?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="profile-content-left">
		<ul class="tabs learn-press-tabs">
			<?php foreach ( $tabs as $key => $tab ) : ?>
				<li class="<?php echo esc_attr( $key ); ?>_tab<?php echo $current == $key ? ' current' : '';?>">
					<a href="#tab-<?php echo esc_attr( $key ); ?>" data-slug="<?php echo add_query_arg( '', '', learn_press_get_page_link( 'profile' ) . $user->user_login ) . '/' . $key;?>"><?php echo apply_filters( 'learn_press_profile_' . $key . '_tab_title', esc_html( $tab['title'] ), $key ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>

	<div class="clearfix"></div>

<?php endif; ?>
<script>
	jQuery(function($){
		$('.learn-press-tabs li a').click(function(e){
			e.preventDefault();
			$($(this).attr('href')).css('visibility', 'visible').fadeIn().siblings().fadeOut();
			$(this).parent().addClass('current').siblings().removeClass('current');
			LearnPress.setUrl($(this).data('slug'));
		});
		$('.learn-press-tabs li.current a').trigger('click');
	})
</script>