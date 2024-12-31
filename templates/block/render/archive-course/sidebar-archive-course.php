<?php

use LearnPress\TemplateHooks\Course\ListCoursesTemplate;

echo ListCoursesTemplate::instance()->sidebar();

if ( ! empty( $inner_content ) ) {
	echo $inner_content;
}
