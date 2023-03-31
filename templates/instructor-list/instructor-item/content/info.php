<?php
if (! isset($data)) {
    return;
}
?>
<div class="instructor-info">
    <?php
    do_action('learnpress/layout/instructor-item/info', $data);
    ?>
</div>
