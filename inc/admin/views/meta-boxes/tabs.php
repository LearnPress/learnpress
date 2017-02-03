<?php
$tabs = $this->get_tabs( 'tabs' );
if ( !$tabs ) {
	return;
}
$current_tab = !empty( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : '';
?>
<div class="learn-press-tabs">
	<ul>
		<?php
		$remove_meta_boxes = array();
		foreach ( $tabs as $tab ) {
			if ( is_array( $tab ) ) {
				$tab = wp_parse_args(
					$tab, array(
						'title'    => '',
						'id'       => '',
						'callback' => ''
					)
				);
			} elseif ( $tab instanceof RW_Meta_Box ) {
				$metabox             = $tab;
				$tab                 = array(
					'title'    => $metabox->meta_box['title'],
					'id'       => $metabox->meta_box['id'],
					'callback' => array( $tab, 'show' )
				);
				$remove_meta_boxes[] = $metabox;
			}
			if ( empty( $current_tab ) || ( $current_tab == $tab['id'] ) ) {
				$current_tab = $tab;
			}
			echo '<li>';
			?>
			<a href="<?php echo add_query_arg( 'tab', $tab['id'], learn_press_get_current_url() ); ?>"><?php echo esc_html( $tab['title'] ); ?></a>
			<?php
			echo '</li>';
		}
		?>
	</ul>
	<div class="learn-press-tab-content">
		<?php
		global $wp_meta_boxes;
		if ( $current_tab ) {
			if ( is_callable( $current_tab['callback'] ) ) {
				call_user_func( $current_tab['callback'] );
			} else {
				do_action( 'learn_press_meta_box_tab_content', $current_tab );
			}
			echo '<input type="text" name="learn-press-meta-box-tab" value="' . $current_tab['id'] . '" />';
		}
		if ( !empty( $remove_meta_boxes ) ) {
			foreach ( $remove_meta_boxes as $meta_box ) {
				$mbox = $meta_box->meta_box;
				foreach ( $mbox['post_types'] as $page ) {
					remove_meta_box( $mbox['id'], $page, $mbox['context'] );

					$wp_meta_boxes[$page][$mbox['context']]['sorted'][$mbox['id']] = false;
					//if($wp_meta_boxes[$page]['normal'][$priority][$id] = false;
					//remove_meta_box( $mbox->meta_box['id'], $mbox->meta_box['post_types'], 'sorted' );
				}
			}
		}
		//learn_press_debug( $wp_meta_boxes['lp_course'] );
		?>
	</div>
</div>