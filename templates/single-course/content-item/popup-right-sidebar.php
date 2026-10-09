<?php
/**
 * Template for displaying course curriculum in popup
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.4.9
 */


use LearnPress\TemplateHooks\Course\CourseAIAssistantTemplate;
use LearnPress\TemplateHooks\Learning\LearningTemplate;

defined( 'ABSPATH' ) || exit;

$ai_assistant_html  = CourseAIAssistantTemplate::instance()->layout_ai_assistant_on_learning_content_bar();
$learning_bar_items = [];

if ( $ai_assistant_html ) {
	$learning_bar_items['ai-assistant'] = [
		'label' => esc_html__( 'AI Assistant', 'learnpress' ),
		'icon'  => 'lp-icon-ai-assistant',
		'html'  => $ai_assistant_html,
	];
}

$learning_bar_items = apply_filters(
	'learn-press/learning-bar/items',
	$learning_bar_items
);
?>

<?php if ( $learning_bar_items ) : ?>
<div id="popup-right-sidebar" class="popup-right-sidebar is-collapsed">
	<span class="popup-right-sidebar__toggle lp-icon-extend" title="<?php esc_attr_e( 'Toggle Sidebar', 'learnpress' ); ?>"></span>
	<ul class="popup-right-sidebar__items">
		<?php foreach ( $learning_bar_items as $item_key => $item ) : ?>
			<li class="popup-right-sidebar__item" data-learning-bar-item="<?php echo esc_attr( $item_key ); ?>" role="button" tabindex="0" aria-controls="lp-addon-content-bar" aria-expanded="false">
				<span class="popup-right-sidebar__icon <?php echo esc_attr( $item['icon'] ?? '' ); ?>"></span>
				<span class="popup-right-sidebar__text"><?php echo esc_html( $item['label'] ?? '' ); ?></span>
				<?php echo $item['html']; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
	<?php echo LearningTemplate::instance()->html_addon_content_bar(); ?>
<?php endif; ?>
