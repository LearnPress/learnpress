<?php
if (! isset($courses) || ! isset($total_pages) || ! isset($page)) {
    return;
}

foreach ($courses as $course) {
    ?>
    <li class="lp-suggest-item">
        <a href="<?php echo get_permalink($course->ID); ?>" target="_blank"
           rel="noopener"><?php echo get_the_title($course->ID); ?></a>
    </li>
    <?php
}

if ($page < $total_pages) {
    if (LP_Page_Controller::is_page_courses()) {
        $link = '#';
    } else {
        $link = learn_press_get_page_link('courses');
    }
    ?>
    <a href="<?php echo esc_url($link); ?>"><?php esc_html_e('View All', 'learnpress'); ?></a>
    <?php
}
?>


