<?php
if (! isset($data)) {
    return;
}

?>
<div class="instructor-content">
    <?php
    do_action('learnpress/layout/instructor-item/content', $data);
    ?>
</div>
