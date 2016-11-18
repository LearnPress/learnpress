<?php
//template script
$template_script = get_site_url() . '/wp-content/plugins/learnpress/assets/js/frontend/shortcodes.js';
?>

<div class="<?php echo 'archive-course-collection-outer recent ' . $a['template'] . '  ' . $a["css_class"] ?>">
    <?php if (!empty($a['title'])): ?>
        <h3 class="title"><?php echo $a['title'] ?></h3>
    <?php endif; ?>
    <div class="collection-body">
        <div class="owl-carousel owl-theme"
             data-items="<?php echo (empty($a['items']))?'4':$a['items']; ?>"
             data-itemsdesktop="<?php echo (empty($a['items_desktop']))?'4':$a['items_desktop']; ?>"
             data-itemsdesktopsmall="<?php echo (empty($a['items_desktop_small']))?'3':$a['items_desktop_small']; ?>"
             data-itemsTablet="<?php echo (empty($a['items_tablet']))?'3':$a['items_tablet']; ?>"
             data-itemsTabletSmall="<?php echo (empty($a['items_tablet_small']))?'2':$a['items_tablet_small']; ?>"
             data-itemsmobile="<?php echo (empty($a['items_mobile']))?'1':$a['items_mobile']; ?>"
             data-singleitem="<?php echo (empty($a['single_item']))?'false':$a['single_item']; ?>"
             data-itemscaleup="<?php echo (empty($a['item_scale_up']))?'true':$a['item_scale_up']; ?>"
             data-slidespeed="<?php echo (empty($a['slide_speed']))?'200':$a['slide_speed']; ?>"
             data-paginationspeed="<?php echo (empty($a['pagination_speed']))?'800':$a['pagination_speed']; ?>"
             data-rewindspeed="<?php echo (empty($a['rewind_speed']))?'1000':$a['rewind_speed']; ?>"
             data-autoplay="<?php echo (empty($a['auto_play']))?'false':$a['auto_play']; ?>"
             data-stoponhover="<?php echo (empty($a['stop_on_hover']))?'true':$a['stop_on_hover']; ?>"
             data-navigation="<?php echo (empty($a['navigation']))?'true':$a['navigation']; ?>"
             data-navigationtextnext="<?php echo $a['navigation_text_next']; ?>"
             data-navigationtextprev="<?php echo $a['navigation_text_prev']; ?>"
             data-scrollperpage="<?php echo $a['scroll_per_page']; ?>"
             data-pagination="<?php echo $a['pagination']; ?>"
             data-autoheight="<?php echo $a['auto_height']; ?>"
        >
            <?php foreach ($courses as $course): ?>
                <div class="item">
                    <div class="course-entry">
                        <?php if ($a['show_thumbnail']): ?>
                            <div class="course-cover">
                                <a class="img-link" href="<?php echo get_the_permalink($course->id) ?>">
                                    <?php echo get_the_post_thumbnail($course->id) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <div class="course-detail">
                            <a href="<?php echo get_the_permalink($course->id) ?>">
                                <div class="course-title">
                                    <?php echo $course->post->post_title; ?>
                                </div>
                            </a>
                            <?php if ($a['show_desc']): ?>
                                <div class="course-description"><?php
                                    $the_content = $course->post->post_content;
                                    $the_content = get_extended($the_content);
                                    echo $the_content['main'];
                                    ?></div>
                            <?php endif; ?>
                            <div class="course-meta-data">
                                <div class="section section-1">
                                    <?php if ($a['show_price']): ?>
                                        <div
                                            class="course-price
                                    <?php if (lp_is_paid_course($course->id)) {
                                                echo 'paid';
                                            } else {
                                                echo 'free';
                                            };
                                            ?>">
                                            <?php
                                            $paid = lp_is_paid_course($course->id);
                                            if (!$paid) {
                                                esc_html_e('Free', 'learnpress');
                                            } else {
                                                $price = get_post_meta($course->id, '_lp_price', true);
                                                echo learn_press_format_price($price, true);
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="section section-2">
                                    <?php if ($a['show_enrolled_students']): ?>
                                    <div class="course-student-number meta-field">
                                        <?php
                                        $students = $course->get_users_enrolled();
                                        echo $students;
                                        if (intval($students) > 1) {
                                            _e(' students', 'learnpress');
                                        } else {
                                            _e(' student', 'learnpress');
                                        }

                                        ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($a['show_lesson']): ?>

                                    <div class="course-lesson-number meta-field">
                                        <?php
                                        $lessons = sizeof($course->get_lessons());
                                        echo $lessons;
                                        if (intval($lessons) > 1) {
                                            _e(' lessons', 'learnpress');
                                        } else {
                                            _e(' lesson', 'learnpress');
                                        }

                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                                <?php if ($a['show_teacher']): ?>
                                    <div class="section section-3">
                                        <div class="course-teacher">
                                            <small>
                                                <?php _e('instructor: ', 'learnpress'); ?>
                                                <?php echo $course->get_instructor_html(); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="collection-footer">
        <?php if ($page_count > 1): ?>
        <?php endif; ?>
    </div>
</div>
<div class="clearfix"></div>