<?php
/**
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */

defined( 'ABSPATH' ) || exit();
global $post;
$result = $user->get_quiz_results( $post->ID );
?>
<li class="learn-press-quiz-result <?php echo sanitize_title( $result->status ); ?>">
	<a href="<?php echo get_permalink( $post->ID ) ?>" class="quiz-title"><?php echo get_the_title( $post->ID ); ?></a>
	<p class="quiz-result-meta">
		<span class="status">
			<?php echo ucfirst( $result->status ); ?>
		</span>
		<span class="percentage"><?php echo sprintf( '%s/%s', $result->results['correct_percent'], 100 ); ?></span>
	</p>
</li>
