<?php
/**
 * Template tool create indexs fo tables database.
 *
 * @template html-create-indexs-tables
 * @author  tungnx
 * @package learnpress/admin/views/tools/database
 * @version 1.0.0
 * @since 4.0.3
 */

defined( 'ABSPATH' ) or die();
$lp_db_sessions = LP_Sessions_DB::getInstance();
global $wpdb;
$clean_tables = array( 'learnpress_sessions' );
?>

<div class="card" id="lp-tool-clean-database">
	<h2><?php echo sprintf( '%s', __( 'Clean Data System', 'learnpress' ) ); ?></h2>
	<p><?php _e( 'Remove old data, not use or expire', 'learnpress' ); ?></p>
	<div class="tools-prepare__message"></div>
	<div id="tools-select__id" class="tools-select__data">
		<ul class="clean-table">
			<?php
			foreach ( $clean_tables as $clean_table ) :
				$color_code = '#ffffff';
				$rows       = $lp_db_sessions->count_row_db_sessions();
				if ( $rows > 500 ) {
					$color_code = '#ff0000';
				}
				?>
				<li style="background-color: <?php echo esc_attr( $color_code ); ?>">
					<input type="checkbox" id="clean-table__<?php echo esc_attr( $clean_table ); ?>"
						name="clean-table__<?php echo esc_attr( $clean_table ); ?>"
						value="<?php echo esc_attr( $clean_table ); ?>">
					<label for="clean-table__<?php echo esc_attr( $clean_table ); ?>">
						<?php echo sprintf( '%s (%d) %s', $clean_table, $lp_db_sessions->count_row_db_sessions(), __( 'rows expire', 'learnpress' ) ); ?>
					</label>
					<br>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<p class="tools-button">
		<button type="button" class="button lp-btn lp-btn-clean-db">
			<?php esc_html_e( 'Delete', 'learnpress' ); ?>
		</button>
	</p>

	<div class="wrapper-lp-loading" style="display: none">
		<?php
		$i = 0;
		foreach ( $clean_tables as $clean_table ) :
			$i++;
			$rows = $lp_db_sessions->count_row_db_sessions();
			?>
			<div class="progressbar__item step-<?php echo esc_attr( $i ); ?>" data-total="<?php echo esc_attr( $rows ); ?>">
				<div class="progressbar__container">
					<div class="progressbar__content">
						<h4><?php echo esc_html( 'Table name: ' . $clean_table . '' ); ?></h4>
						<div class="progressbar__indexs">
							<span class="progressbar__rows">
								<?php echo esc_html( '0 / ' . $rows . ' expire' ); ?>
							</span>
							<span class="progressbar__percent">( 0% )</span>
						</div>
					</div>
					<div class="progressbar__value"></div>
				</div>
			</div>
			<div class="lp-tool__message"></div>
		<?php endforeach; ?>
	</div>
</div>

