<?php

if (! isset($data)) {
    return;
}

if (empty($data['field'])) {
    return;
}

$lp_course = \LP_Course_DB::getInstance();

$data = wp_parse_args(
    $data,
    array(
        'free_course_number'  => $lp_course->get_courses($lp_course->free_courser_number()),
        'paid_course_number'  => $lp_course->get_courses($lp_course->paid_course_number()),
        'all_level_number'    => $lp_course->get_courses($lp_course->level_course_number('')),
        'beginner_number'     => $lp_course->get_courses($lp_course->level_course_number('beginner')),
        'intermediate_number' => $lp_course->get_courses($lp_course->paid_course_number('intermediate')),
        'expert_number'       => $lp_course->get_courses($lp_course->paid_course_number('expert')),
        'course_number'       => $lp_course->get_courses($lp_course->course_number()),
    )
);

$field   = $data['field'];
$field[] = 'course-filter-btn';
do_action('learn-press/shortcode/course-filter/before', $data);
?>
    <div
        class="<?php echo esc_attr(apply_filters('learn-press/shortcode/course-filter/class', 'lp-course-filter')); ?>">
        <?php
        foreach ($field as $key) {
            ?>
            <div
                class="<?php echo esc_attr(apply_filters('learn-press/shortcode/course-filter/item-class', 'lp-course-filter__item')); ?>">
                <?php
                learn_press_get_template(
                    'shortcode/course-filter/' . $key . '.php',
                    compact('data')
                );
                ?>
            </div>
            <?php
        }
        ?>
    </div>
<?php
do_action('learn-press/shortcode/course-filter/after', $data);
