<?php
/**
 * Prompt to create a course
 */
if ( ! isset( $params ) ) {
	return;
}

// Course Intent
$topic            = $params['topic'] ?? '';
$goal             = $params['goals'] ?? '';
$course_objective = trim( $params['course_objective'] ?? '' );

// AI Settings
$language = $params['language'] ?? 'English';
$audience = $params['audience'] ?? 'Students';
$tone     = $params['tone'] ?? 'analytical';
$length   = $params['length'] ?? 60;
$outputs   = $params['outputs'] ?? 1;

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
        You are an expert course title creator.
		Create a concise, compelling course title with the following details:
		- Topic: {$topic}
		- Goal: {$goal}

        <structure_requirements>
			- The title must not exceed {$length} characters.
        	- Generate EXACTLY **{$outputs}** course title(s) in a JSON array.
        </structure_requirements>
    </task_instructions>

    <output_format>
        - You MUST respond valid JSON array object.
        - Do not include any introductory text, explanations, or markdown code fences like ```json.
        - The JSON structure must strictly follow this example:
        <json_example>
			{
				"results": [
					{ "item": "Compelling Course Title Here" },
					{ "item": "Another Engaging Title" }
				]
			}
        </json_example>
    </output_format>
</prompt>
XML;
