<?php


echo "something";

while (have_posts()) {
	the_post();
	the_content();
}