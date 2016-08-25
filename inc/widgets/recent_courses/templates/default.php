<div class="<?php echo 'archive-course-widget-outer ' . $a['template'] . '  ' . $a["css_class"] ?>">
    <?php if (!empty($a['title'])) {
        echo $args['before_title'] . apply_filters('widget_title', $a['title']) . $args['after_title'];
    }
    ?>

    <div class="widget-body">
        <?php foreach ($courses as $course): ?>
            <div class="course-entry">
                <?php if ($a['show_thumbnail']): ?>
                    <div class="course-cover">
                        <a href="<?php echo get_the_permalink($course->id) ?>">
                            <?php echo get_the_post_thumbnail($course->id) ?>
                        </a>
                    </div>
                <?php endif; ?>
                <div class="course-detail">
                    <a href="<?php echo get_the_permalink($course->id) ?>">
                        <div class="course-title">
                            <?php echo $course->get_course_data()->post_title; ?>
                        </div>
                    </a>
                    <?php if ($a['show_desc']): ?>
                        <div class="course-description"><?php
                            $content_length = intval($a['desc_length']);
                            $the_content = $course->get_course_data()->post_content;
                            $the_content = wp_trim_words($the_content, $content_length, __('...', 'learnpress'));
                            echo $the_content;
                            ?></div>
                    <?php endif; ?>
                    <div class="course-meta-data">
                        <div class="section section-1">
                            <?php if($a['show_price']): ?>
                                <div
                                    class="course-price
                                    <?php if(lp_is_paid_course($course->id)) {
                                        echo 'paid';
                                        } else{
                                        echo 'free';
                                    };
                                    ?>">
                                    <?php
                                        $paid = lp_is_paid_course($course->id);
                                        if(! $paid){
                                            esc_html_e('Free', 'learnpress');
                                        } else{
                                            $price = get_post_meta( $course->id, '_lp_price', true);
                                            echo learn_press_format_price($price, true);
                                        }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($a['show_enrolled_students']): ?>
                        <div class="section section-2 inline">
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
        <?php endforeach; ?>
    </div>
    <div class="widget-footer">
        <?php if ($a['bottom_link'] == 'all_course'):
            $page_id = get_option('learn_press_courses_page_id');
            $link = get_page_link($page_id);
            $title = get_the_title($page_id);
            ?>
            <a class="pull-right" href="<?php echo $link ?>">
                <?php
                echo(!empty($a['bottom_link_text']) ? $a['bottom_link_text'] : $title);
                ?>
            </a>
        <?php endif; ?>
    </div>
</div>
<div class="clearfix"></div>