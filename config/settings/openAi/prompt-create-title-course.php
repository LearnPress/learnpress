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
$output   = $params['output'] ?? 1;

return <<<PROMPT
You are an expert course title creator.
Create a concise, compelling course title with the following details:
- Topic: {$topic}
- Goal: {$goal}
- Audience: {$audience}
- Tone: {$tone}
- Language: {$language}

Constraints:
- The title must be {$length} characters.
- Do not include quotation marks
- Do not add explanation or extra text

Generate {$output} different. Respond in JSON format exactly as an array of objects with the key 'title'. Example format: [{\"title\": \"Title 1\"}, {\"title\": \"Title 2\"}]. Do not include any text or explanation outside the JSON.
PROMPT;
