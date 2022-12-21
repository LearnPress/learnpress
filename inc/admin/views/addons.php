<?php
/**
 * Template show list addons of LearnPress
 *
 * @version 1.0.0
 * @since 4.2.1
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $addons ) ) {
	return;
}
?>
<div id="lp-addons">
	<?php
	foreach ( $addons as $addon ) {
		?>
		<div class="lp-addon-item">
			<div class="lp-addon-item__content">
				<img src="<?php echo $addon->image ?>" alt="<?php echo $addon->name ?>" />
				<h3><a href="<?php echo $addon->link ?>"><?php echo $addon->name ?></a></h3>
				<p title="<?php echo $addon->description ?>"><?php echo $addon->description ?></p>
			</div>
			<div class="lp-addon-item__actions">
				<div class="lp-addon-item__actions__left">
					<button>Install</button>
				</div>
				<div class="lp-addon-item__actions__right"></div>
			</div>
		</div>
		<?php
	}
	?>
</div>
