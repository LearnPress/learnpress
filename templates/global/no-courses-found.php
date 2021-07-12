<?php
/**
 * Displayed when no course are found matching the current query
 *
 * This template can be overridden by copying it to yourtheme/learnpress/global/no-courses-found.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  4.1.1
 */

defined( 'ABSPATH' ) || exit;
?>

<p class="learn-press-message error"><?php esc_html_e( 'No courses were found matching your selection.', 'learnpress' ); ?></p>
