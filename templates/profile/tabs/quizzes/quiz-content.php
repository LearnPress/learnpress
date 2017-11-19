<?php
/**
 * Template for displaying quiz content in quizzes tab of user profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/quizzes/quiz-content.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php
global $post;
$result = $user->get_quiz_results( $post->ID );
?>

<li class="learn-press-quiz-result <?php echo sanitize_title( $result->status ); ?>">

    <a href="<?php echo get_permalink( $post->ID ) ?>" class="quiz-title"><?php echo get_the_title( $post->ID ); ?></a>

	<?php if ( $result ) { ?>

        <p class="quiz-result-meta">
            <span class="status"><?php echo ucfirst( $result->status ); ?></span>
            <span class="percentage"><?php echo sprintf( '%s/%s', $result->results['correct_percent'], 100 ); ?></span>
        </p>

	<?php } ?>

</li>
