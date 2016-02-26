<?php
/**
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */

defined( 'ABSPATH' ) || exit();
?>
<div class="message<?php echo $type != 'message' ? " {$type}" : '';?>"><?php echo $content;?></div>