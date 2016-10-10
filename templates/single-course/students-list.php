<?php
/**
 * Template for displaying the students of a course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$course = LP()->global['course'];

$students_list_heading = apply_filters('learn_press_students_list_heading', __('Students Enrolled', 'leanpress'));
$student_limit = apply_filters('learn_press_students_list_limit', -1);
$show_avatar = apply_filters('learn_press_students_list_avatar', true);
$students_list_avatar_size = apply_filters('learn_press_students_list_avatar_size', 32);
?>
<?php do_action('learn_press_before_student-list') ?>
    <div class="course-students-list">
        <?php if ($students_list_heading): ?>
            <h3 class="students-list-title"><?php echo $students_list_heading ?></h3>
        <?php endif; ?>

        <?php if ($students = $course->get_students_list(true, $student_limit)): ?>
            <ul class="students">
                <?php foreach ($students as $student):
                    ?>
                    <li>
                        <?php if ($show_avatar): ?>
                            <?php echo get_avatar(
                                $student->ID,
                                $students_list_avatar_size,
                                '',
                                $student->display_name,
                                array(
                                    'class' => 'students_list_avatar'
                                )
                            ); ?>
                        <?php endif; ?>
                        <a
                            class="name"
                            href="<?php echo learn_press_user_profile_link($student->ID) ?>"
                            title="<?php echo $student->display_name . ' profile' ?>"
                        >
                            <?php echo $student->display_name ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <?php
                $other_student = $course->students;
                if ($other_student) {
                    echo '<p>and ' . sprintf(_n('one student enrolled.', '%s students enrolled.', $other_student, 'learnpress'), $other_student) . '</p>';
                }
                ?>
            </ul>
        <?php else: ?>
            <div class="students empty">
                <?php if ($course->students) {
                    echo apply_filters('learn_press_course_count_student', sprintf(_n('One student enrolled.', '%s students enrolled.', $course->students, 'learnpress'), $course->students));
                } else {
                    echo apply_filters('learn_press_course_no_student', __('No student enrolled.', 'learnpress'));
                } ?>
            </div>
        <?php endif; ?>
    </div>
<?php do_action('learn_press_after_student-list') ?>