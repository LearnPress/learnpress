<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( learn_press_get_user_option( 'hide-notice-template-files' ) == 'yes' ) {
	return;
}
$template_dir   = get_template_directory();
$stylesheet_dir = get_stylesheet_directory();
$cradle         = LP_Outdated_Template_Helper::detect_outdated_template();
$theme          = wp_get_theme();
$theme_name     = array();

if ( $template_dir === $stylesheet_dir ) {
	$theme_name[] = $theme['Name'];
} else {
	if ( $cradle['parent_item'] ) {
		$theme_name[] = $parent = $theme->__get( 'parent_theme' );
	}
	if ( $cradle['child_item'] ) {
		$theme_name[] = $theme['Name'];
	}
}
$theme_name = implode( ' & ', $theme_name );

$readmore = 'https://thimpress.com/knowledge-base/outdated-template-fix/';
?>
<div id="message" class="learn-press-message notice-warning notice">
    <p><?php printf( wp_kses( __( 'There is a new update of LearnPress. You may need to update your theme <strong>(%s)</strong> to avoid outdated template files.', 'learnpress' ), array( 'strong' => array() ) ), $theme_name ); ?></p>
    <p class="outdated-readmore-link"><?php echo sprintf( wp_kses( __( 'This is not a bug, don\'t worry. Read more about Outdated template files notice <a href="%s" target="_blank">here</a>.', 'learnpress' ), array(
			'a' => array(
				'href'   => array(),
				'target' => array()
			)
		) ), esc_url( $readmore ) ); ?>  </p>
    <p>
        <a class="button"
           href="<?php echo admin_url( 'admin.php?page=learn-press-tools&amp;tab=templates' ); ?>"><?php esc_attr_e( 'View list of outdated templates', 'learnpress' ); ?></a>
    </p>
    <a href="<?php echo admin_url( 'themes.php/?lp-hide-notice=template-files' ); ?>"
       class="learn-press-admin-notice-dismiss"></a>
</div>