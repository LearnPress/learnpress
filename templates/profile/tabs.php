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
$profile_link = learn_press_get_page_link( 'profile' );
$current_user = wp_get_current_user();
if ( ! empty( $tabs ) && !empty( $tabs[ $current ] ) ) : ?>
	<ul class="tabs learn-press-tabs clearfix">
		<?php foreach ( $tabs as $key => $tab ) : ?>
		<?php
		if( $user->user_login != $current_user->user_login && $key == 'orders') {
			continue;
		}
		?>
			<li class="<?php echo esc_attr( $key ); ?>_tab<?php echo $current == $key ? ' current' : '';?>">
				<?php if( get_option( 'permalink_structure' ) ) : ?>
				<a href="<?php echo add_query_arg( '', '', learn_press_get_page_link( 'profile' ) . $user->user_login ) . '/' . $key; ?>" data-slug="<?php echo add_query_arg( '', '', learn_press_get_page_link( 'profile' ) . $user->user_login ) . '/' . $key;?>"><?php echo apply_filters( 'learn_press_profile_' . $key . '_tab_title', esc_html( $tab['title'] ), $key ); ?></a>
				<?php else: ?>
				<a href="<?php echo add_query_arg( array( 'user' => $user->user_login, 'tab' => $key ), learn_press_get_page_link( 'profile' ) ); ?>" data-slug="<?php echo add_query_arg( '', '', learn_press_get_page_link( 'profile' ) . $user->user_login ) . '/' . $key;?>"><?php echo apply_filters( 'learn_press_profile_' . $key . '_tab_title', esc_html( $tab['title'] ), $key ); ?></a>
				<?php endif;?>
			</li>
		<?php endforeach; ?>
	</ul>
	</div>
	<div class="user-profile-tabs learn-press-tabs-wrapper">
		<?php foreach ( $tabs as $key => $tab ) : ?>
		<?php if( $current == $key ){ ?>
			<div class="learn-press-tab" id="tab-<?php echo esc_attr( $key ); ?>">
				<div class="entry-tab-inner">
				<?php if( is_callable( $tab['callback'] ) ): ?>

					<?php call_user_func_array( $tab['callback'], array( $key, $tab, $user ) ); ?>

				<?php endif;?>
				</div>
			</div>
		<?php }?>
		<?php endforeach; ?>
	</div>



	<div class="clearfix"></div>

<?php endif; ?>