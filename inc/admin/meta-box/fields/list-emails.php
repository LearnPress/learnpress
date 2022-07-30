<?php $emails = LP_Emails::instance()->emails; ?>

<table class="learn-press-emails">
	<thead>
	<tr>
		<th></th>
		<th><?php esc_html_e( 'Email', 'learnpress' ); ?></th>
		<th><?php esc_html_e( 'Description', 'learnpress' ); ?></th>
		<th></th>
	</tr>
	</thead>

	<tbody>
		<?php
		foreach ( $emails as $email ) {
			$group = '';

			if ( $email->group ) {
				$url = esc_url_raw(
					add_query_arg(
						array(
							'section'     => $email->group->group_id,
							'sub-section' => $email->id,
						),
						admin_url( 'admin.php?page=learn-press-settings&tab=emails' )
					)
				);

				$group = $email->group;
			} else {
				$url = esc_url_raw( add_query_arg( array( 'section' => $email->id ), admin_url( 'admin.php?page=learn-press-settings&tab=emails' ) ) );
			}
			?>

			<tr id="email-<?php echo esc_attr( $email->id ); ?>">
				<td class="status <?php echo esc_attr( $email->enable ? 'enabled' : '' ); ?>">
					<span class="change-email-status dashicons dashicons-yes" data-status="<?php echo esc_attr( $email->enable ? 'on' : 'off' ); ?>" data-id="<?php echo esc_attr( $email->id ); ?>"></span>
				</td>
				<td class="name">
					<a href="<?php echo esc_url_raw( $url ); ?>">
						<?php
						if ( $group ) {
							echo join( ' &rarr; ', array( $group, $email->title ) );
						} else {
							echo wp_kses_post( $email->title );
						}
						?>
					</a>
				</td>
				<td class="description"><?php echo wp_kses_post( $email->description ); ?></td>
				<td class="manage"><a class="button" href="<?php echo esc_url_raw( $url ); ?>"><?php esc_html_e( 'Manage', 'learnpress' ); ?></a></td>
			</tr>
		<?php } ?>
	</tbody>
</table>

<p class="email-actions">
	<button class="button" id="learn-press-enable-emails" data-status="yes"><?php esc_html_e( 'Enable all', 'learnpress' ); ?></button>
	<button class="button" id="learn-press-disable-emails" data-status="no"><?php esc_html_e( 'Disable all', 'learnpress' ); ?></button>
</p>
