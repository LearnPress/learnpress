<?php
$tabs = apply_filters( 'learn_press_course_tabs', array() );
if ( !empty( $tabs ) ) : ?>
	<?php
	$index        = 0;
	$active_index = - 1;

	foreach ( $tabs as $key => $tab ) {
		if ( !empty( $tab['active'] ) && $tab['active'] == true ) {
			$active_index = $index;
		}
		$index ++;
	}

	if ( $active_index == - 1 ) {
		$active_index = 0;
	}
	$index = 0;

	?>
	<div class="learn-press-tabs learn-press-tabs-wrapper">
		<ul class="learn-press-nav-tabs">
			<?php foreach ( $tabs as $key => $tab ) : ?>
				<?php
				$unikey            = uniqid( $key . '-' );
				$tabs[$key]['key'] = $unikey;
				?>
				<li class="learn-press-nav-tab learn-press-nav-tab-<?php echo esc_attr( $key ); ?><?php echo $index++ == $active_index ? ' active' : ''; ?>" data-tab="<?php echo esc_attr( $key ); ?>">
					<a href="" data-tab="#tab-<?php echo esc_attr( $unikey ); ?>"><?php echo apply_filters( 'learn_press_course_' . $key . '_tab_title', esc_html( $tab['title'] ), $key ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php $index = 0; ?>
		<?php foreach ( $tabs as $key => $tab ) : ?>
			<div class="learn-press-tab-panel learn-press-tab-panel-<?php echo esc_attr( $key ); ?> panel learn-press-tab<?php echo $index ++ == $active_index ? ' active' : ''; ?>" id="tab-<?php echo esc_attr( $tab['key'] ); ?>">
                                <?php if ( apply_filters( 'learn_press_allow_display_tab_section', true, $key, $tab ) ) : ?>
                                    <?php call_user_func( $tab['callback'], $key, $tab ); ?>
                                <?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>

<?php endif; ?>