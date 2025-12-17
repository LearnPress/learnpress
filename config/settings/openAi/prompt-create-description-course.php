<?php
/**
 * Prompt to create a course
 */
if ( ! isset( $params ) ) {
	return;
}

// Course Intent
$title            = $params['post-title'] ?? '';
$topic            = $params['topic'] ?? '';
$goal             = $params['goals'] ?? '';
$course_objective = trim( $params['course_objective'] ?? '' );

// AI Settings
$language = $params['language'] ?? 'English';
$audience = $params['audience'] ?? 'Students';
$tone     = $params['tone'] ?? 'analytical';
$length   = $params['length'] ?? 1000;
$outputs  = $params['outputs'] ?? 1;

return <<<XML
<prompt>
    <course_context>
        <audience>
            The target audience is: $audience
        </audience>
        <content_parameters>
            <language>$language</language>
            <tone>$tone</tone>
        </content_parameters>
    </course_context>

    <task_instructions>
        You are an expert course description creator.
		Create a course description directly based on the following:
		Course Title: {$title}
		Topic: {$topic}
		Paragraph number: 1
		Goal: Course about Subject "$title", content have html like list, heading h3, paragraph to make content easy to read and eye-catching.

        <structure_requirements>
			- The description must be between 200 and {$length} words, and include one image on the content.
        	- Generate EXACTLY **{$outputs}** course description(s) in a JSON array.
            - Each item MUST contain a relevant "item".
        </structure_requirements>
    </task_instructions>

    <output_format>
        - You MUST respond valid JSON array object.
        - Do not include any introductory text, explanations, or markdown code fences like ```json.
        - The JSON structure must strictly follow this example:
        <json_example>
			{
				"results": [
					{ "item": "Compelling Course Description Here" },
					{ "item": "Another Engaging Description" }
				]
			}
        </json_example>
    </output_format>
</prompt>
XML;
