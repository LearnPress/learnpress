<?php
global $wp_meta_boxes, $post;

$tabs = $this->get_tabs( 'tabs' );
if ( ! $tabs ) {
	return;
}
$current_tab = ! empty( $_REQUEST['tab'] ) ? LP_Helper::sanitize_params_submitted( $_REQUEST['tab'] ) : '';
?>
<div id="learn-press-admin-editor-metabox-settings" class="learn-press-tabs vertical initialize">
	<div class="tabs-heading">
		<h4><?php echo $this->opt( 'title' ); ?></h4>
	</div>
	<div class="tabs-container">
		<ul class="tabs-nav">
			<?php
			$remove_meta_boxes = array();
			foreach ( $tabs as $k => $tab ) {
				if ( is_array( $tab ) ) {
					$tab = wp_parse_args(
						$tab, array(
							'title'    => '',
							'id'       => '',
							'callback' => '',
							'meta_box' => '',
							'icon'     => ''
						)
					);
					if ( $tab['meta_box'] ) {
						call_user_func( $tab['callback'] );

						$page     = get_post_type();
						$contexts = array( 'normal', 'advanced' );
						foreach ( $contexts as $context ) {
							if ( isset( $wp_meta_boxes[ $page ][ $context ] ) ) {
								foreach ( array( 'high', 'sorted', 'core', 'default', 'low' ) as $priority ) {
									if ( isset( $wp_meta_boxes[ $page ][ $context ][ $priority ] ) ) {
										foreach ( (array) $wp_meta_boxes[ $page ][ $context ][ $priority ] as $box ) {
											if ( false == $box || ! $box['title'] || $box['id'] != ( $tab['meta_box'] ) ) {
												continue;
											}
											ob_start();
											call_user_func( $box['callback'], $post, $box );
											$tab['content'] = ob_get_clean();
											$tab['title']   = $box['title'];
											$tab['id']      = $box['id'];
											//$tab['icon']    = ! empty( $box['icon'] ) ? $box['icon'] : '';
											unset( $wp_meta_boxes[ $page ][ $context ][ $priority ] );
											break 3;
										}
									}
								}
							}
						}
					}
				} elseif ( $tab instanceof RW_Meta_Box ) {
					$metabox             = $tab;
					$tab                 = array(
						'title'    => $metabox->meta_box['title'],
						'id'       => $metabox->meta_box['id'],
						'icon'     => ! empty( $metabox->meta_box['icon'] ) ? $metabox->meta_box['icon'] : '',
						'callback' => array( $tab, 'show' )
					);
					$remove_meta_boxes[] = $metabox;
				}
				if ( empty( $tab['title'] ) ) {
					continue;
				}
				if ( empty( $tab['id'] ) ) {
					$tab['id'] = sanitize_title( $tab['title'] );
				}
				if ( empty( $current_tab ) || ( $current_tab == $tab['id'] ) ) {
					$current_tab = $tab;
				}
				$classes = array( $tab['id'] );
				if ( is_array( $current_tab ) && $current_tab['id'] == $tab['id'] ) {
					$classes[] = 'active';
				}
				if ( ! empty( $tab['icon'] ) ) {
					$classes[] = 'icon';
				}
				$classes = apply_filters( 'learn-press/admin/tab-class', $classes, $tab );
				echo sprintf( '<li class="%s">', join( ' ', $classes ) );
				?>
				<a <?php echo ! empty( $tab['icon'] ) ? 'class="' . $tab['icon'] . '"' : ''; ?>
					href="<?php echo add_query_arg( 'tab', $tab['id'] ); ?>"><?php echo esc_html( $tab['title'] ); ?></a>
				<?php
				echo '</li>';
				$tabs[ $k ] = $tab;
			}
			?>
		</ul>
		<ul class="tabs-content-container" data-text="<?php esc_attr_e( 'Initializing...', 'learnpress' ); ?>">
			<?php
			foreach ( $tabs as $tab ) {
				if ( empty( $tab['title'] ) ) {
					continue;
				}
				echo '<li id="meta-box-tab-' . $tab['id'] . '" class="' . $tab['id'] . ( is_array( $current_tab ) && $current_tab['id'] == $tab['id'] ? ' active' : '' ) . '">';
				if ( ! empty( $tab['content'] ) ) {
					echo $tab['content'];
				} elseif ( ! empty( $tab['callback'] ) && is_callable( $tab['callback'] ) ) {
					call_user_func( $tab['callback'] );
				} else {
					do_action( 'learn_press_meta_box_tab_content', $tab );
				}
				echo '</li>';
			}
			if ( ! empty( $remove_meta_boxes ) ) {
				$contexts = array( 'normal', 'side', 'advanced' );
				foreach ( $remove_meta_boxes as $meta_box ) {
					if ( $meta_box instanceof RW_Meta_Box ) {
						$mbox = $meta_box->meta_box;
						foreach ( $mbox['post_types'] as $page ) {
							foreach ( $contexts as $context ) {
								remove_meta_box( $mbox['id'], $page, $context );
								if ( ! empty( $wp_meta_boxes[ $page ][ $context ]['sorted'][ $mbox['id'] ] ) ) {
									$wp_meta_boxes[ $page ][ $context ]['sorted'][ $mbox['id'] ] = false;
								}
							}
						}
					} else {

					}
				}
			}

			?>
		</ul>
	</div>
	<?php
	if ( is_array( $current_tab ) ) {
		echo '<input type="hidden" name="learn-press-meta-box-tab" value="' . $current_tab['id'] . '" />';
	}
	?>
</div>