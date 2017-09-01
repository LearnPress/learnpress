<?php

// Add columns for storing the time in GMT
$sql = "LTER TABLE `ob_learnpress_dev`.`wp_learnpress_user_items` 
ADD COLUMN `start_time_gmt` DATETIME NULL DEFAULT '0000-00-00 00:00:00' AFTER `start_time`,
ADD COLUMN `end_time_gmt` DATETIME NULL DEFAULT '0000-00-00 00:00:00' AFTER `end_time`;";