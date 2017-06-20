<?php
$education_themes = LP_Plugins_Helper::get_related_themes( 'education' );
$other_themes     = LP_Plugins_Helper::get_related_themes( 'other' );
if ( ! $education_themes && ! $other_themes ) {
	_e( 'No related themes.', 'learnpress' );

	return;
}
$ref = learn_press_get_item_ref();

if ( $education_themes ) {
	?>
    <h2><?php printf( __( 'Education Support (<span>%d</span>)', 'learnpress' ), LP_Plugins_Helper::count_themes( 'education' ) ); ?></h2>
    <ul class="addons-browse related-themes widefat">
		<?php
		foreach ( $education_themes as $theme ) {
			$theme['url'] = add_query_arg( $ref, $theme['url'] );
			learn_press_admin_view( 'addons/html-loop-theme', array( 'theme' => $theme ) );
		}
		?>
    </ul>
<?php } ?>

<?php if ( $other_themes ) { ?>
    <h2><?php printf( __( 'Other (<span>%d</span>)', 'learnpress' ), LP_Plugins_Helper::count_themes( 'other' ) ); ?></h2>
    <ul class="addons-browse related-themes widefat">
		<?php
		foreach ( $other_themes as $theme ) {
			$theme['url'] = add_query_arg( $ref, $theme['url'] );
			learn_press_admin_view( 'addons/html-loop-theme', array( 'theme' => $theme ) );
		}
		?>
    </ul>
<?php } ?>