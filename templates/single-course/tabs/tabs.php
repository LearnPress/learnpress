<?php
$tabs  = apply_filters( 'learn_press_course_tabs', array() );
$index = 0;
if ( !empty( $tabs ) ) : ?>

	<div class="learn-press-tabs learn-press-tabs-wrapper">
		<ul class="learn-press-nav-tabs">
			<?php foreach ( $tabs as $key => $tab ) : ?>
				<?php
				$unikey            = uniqid( $key . '-' );
				$tabs[$key]['key'] = $unikey;
				?>
				<li class="learn-press-nav-tab learn-press-nav-tab-<?php echo esc_attr( $key ); ?><?php echo $index ++ == 0 ? ' active' : ''; ?>" data-tab="<?php echo esc_attr( $key ); ?>">
					<a href="" data-tab="#tab-<?php echo esc_attr( $unikey ); ?>"><?php echo apply_filters( 'learn_press_course_' . $key . '_tab_title', esc_html( $tab['title'] ), $key ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php $index = 0; ?>
		<?php foreach ( $tabs as $key => $tab ) : ?>
			<div class="learn-press-tab-panel learn-press-tab-panel-<?php echo esc_attr( $key ); ?> panel learn-press-tab<?php echo $index ++ == 0 ? ' active' : ''; ?>" id="tab-<?php echo esc_attr( $tab['key'] ); ?>">
				<?php call_user_func( $tab['callback'], $key, $tab ); ?>
			</div>
		<?php endforeach; ?>
	</div>

<?php endif; ?>
