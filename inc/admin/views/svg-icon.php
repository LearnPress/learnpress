<?php
/**
 * Template for displaying icon with six dots in 2 columns.
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */
defined( 'ABSPATH' ) or die();

$viewBox = isset( $viewBox ) ? $viewBox : '0 0 32 32';
$class   = isset( $class ) ? $class : 'svg-icon';
?>
<svg class="<?php echo $class; ?>" viewBox="<?php echo $viewBox; ?>">
    <path d="M 14 5.5 a 3 3 0 1 1 -3 -3 A 3 3 0 0 1 14 5.5 Z m 7 3 a 3 3 0 1 0 -3 -3 A 3 3 0 0 0 21 8.5 Z m -10 4 a 3 3 0 1 0 3 3 A 3 3 0 0 0 11 12.5 Z m 10 0 a 3 3 0 1 0 3 3 A 3 3 0 0 0 21 12.5 Z m -10 10 a 3 3 0 1 0 3 3 A 3 3 0 0 0 11 22.5 Z m 10 0 a 3 3 0 1 0 3 3 A 3 3 0 0 0 21 22.5 Z"></path>
</svg>