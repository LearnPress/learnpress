<?php
/**
 * Created by PhpStorm.
 * User: tu
 * Date: 12/14/17
 * Time: 2:56 PM
 */

$args = array( 'item' => $item, 'section' => $section );

/**
 * @since 3.0.0
 */
do_action( 'learn-press/before-section-loop-item', $item );

learn_press_get_template( "single-course/section/" . $item->get_template(), $args );

/**
 * @since 3.0.0
 *
 * @see   learn_press_section_item_meta()
 */
do_action( 'learn-press/after-section-loop-item', $item, $section );
?>