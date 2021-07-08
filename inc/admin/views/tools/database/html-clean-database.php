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
$lp_db               = LP_Database::getInstance();
global $wpdb;
$clean_tables = array(
	'learnpress_sessions',
)
?>

<div class="card" id="lp-tool-clean-database">
	<h2><?php echo sprintf( '%s', __( 'Repair Database', 'learnpress' ) ); ?></h2>
	<p><?php _e( 'Remove unwanted data and re-calculate relationship', 'learnpress' ); ?></p>
	<div class="tools-prepare__message"></div>
	<div id="tools-select__id" class="tools-select__data">
			<ul class="clean-table">
				<?php foreach ( $clean_tables as $clean_table ) :
					?>
					<li style="background-color: <?php echo esc_attr( $lp_db->learn_press_get_color_code_status( $clean_table ) ); ?>">
						<input type="checkbox" id="clean-table__<?php echo esc_attr( $clean_table ); ?>" name="clean-table__<?php echo esc_attr( $clean_table ); ?>" value="<?php echo esc_attr($clean_table); ?>" >
						<label for="clean-table__<?php echo esc_attr( $clean_table ); ?>"><?php echo esc_html__( '' . $clean_table . ' (' . $lp_db->learn_press_count_row_db( $clean_table ) . ' rows)', 'learnpress' ); ?></label><br>
					</li>
				<?php endforeach; ?>
			</ul>
	</div>
	<p class="tools-button">
		<button type="button" class="button lp-btn lp-btn-clean-db"><?php esc_html_e( 'Delete', 'learnpress' ); ?></button>
	</p>

	<div class="wrapper-lp-loading" style="display: none">
		<?php
		$i = 0;
		foreach ( $clean_tables as $clean_table ) :
			$i++;
			$rows = $lp_db->learn_press_count_row_db($clean_table);
		?>
			<div class="progressbar__item step-<?php echo esc_attr($i); ?>" data-total="<?php echo esc_attr($rows); ?>">
				<div class="progressbar__container">
					<div class="progressbar__content">
						<h4><?php echo esc_html('Table name: '.$clean_table.'') ?></h4>
						<div class="progressbar__indexs">
								<span class="progressbar__rows">
									<?php echo esc_html('0 / ' . $rows) ?>
								</span>
								<span class="progressbar__percent">
									( 0% )
								</span>
						</div>
					</div>
					<div class="progressbar__value"></div>
				</div>
			</div>
			<div class="lp-tool__message"></div>
		<?php endforeach; ?>
	</div>

</div>

