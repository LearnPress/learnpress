<?php
learn_press_prevent_access_directly();
?>
<?php do_action( 'learn_press_content_quiz_before_title_element');?>
<h1 itemprop="name" class="product_title entry-title"><?php the_title(); ?></h1>
<?php do_action( 'learn_press_content_quiz_after_title_element');?>
