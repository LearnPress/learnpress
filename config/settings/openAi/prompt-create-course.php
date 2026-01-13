<?php
/**
 * Prompt to create a course
 */
if ( ! isset( $params ) ) {
	return;
}

// Course Intent
$role_persona     = $params['role_persona'] ?? '';
$target_audience  = $params['target_audience'] ?? 'Beginners';
$course_objective = $params['course_objective'] ?? '';

// AI Settings
$language        = $params['language'] ?? 'English';
$tone            = $params['tone'] ?? '';
$lesson_length   = $params['lessons_title_length'] ?? 300;
$reading_level   = $params['reading_level'] ?? 'High school';
$seo_emphasis    = $params['seo_emphasis'] ?? '';
$target_keywords = $params['target_keywords'] ?? '';

// Course Structure
$sections             = $params['section_number'] ?? 1;
$section_title_length = $params['section_title_length'] ?? 60;
$section_desc_length  = $params['section_desc_length'] ?? 160;
$lessons_per_section  = $params['lessons_per_section'] ?? 1;
$lesson_title_length  = $params['lesson_title_length'] ?? 60;
$quizzes_per_section  = $params['quizzes_per_section'] ?? 0;
$quiz_title_length    = $params['quiz_title_length'] ?? 60;
$questions_per_quiz   = $params['questions_per_quiz'] ?? 0;

$quiz_structure_requirements = '';
$quiz_json_example           = '';
if ( $quizzes_per_section > 0 ) {
	$quiz_structure_requirements = <<<XML
        <quiz_requirements>
            - Each section MUST contain exactly **{$quizzes_per_section}** quiz object(s) within a "quizzes" array.
            - Each quiz MUST have a relevant "quiz_title" and "quiz_description".
            - Each quiz MUST contain exactly **{$questions_per_quiz}** question object(s) in a "questions" array.
            - Each question must be multiple-choice, testing concepts from the lessons in THAT SAME section.
            - Each question object MUST contain: "question_title", "question_description", "options" (an array of 4 strings), and "correct_answer" (a string matching one of the options).
        </quiz_requirements>
XML;

	$quiz_json_example = ',' . <<<JSON
        "quizzes": [
          {
            "quiz_title": "Quiz Title Here",
            "quiz_description": "Brief description of the quiz...",
            "questions": [
              {
                "question_title": "Question title here...",
                "question_description": "Question description task here...",
                "options": ["Option A", "Option B", "Correct Option", "Option D"],
                "correct_answer": "Correct Option"
              }
            ]
          }
        ]
JSON;
}

return <<<XML
<prompt>
    <role_definition>
        You are an AI assistant specialized in instructional design and content creation. Your persona for this task is: **$role_persona**.
    </role_definition>

    <course_context>
        <objective>
            The primary goal of this course is: $course_objective
        </objective>
        <audience>
            The target audience is: $target_audience
        </audience>
        <content_parameters>
            <language>$language</language>
            <tone>$tone</tone>
            <reading_level>$reading_level</reading_level>
        </content_parameters>
        <seo_parameters>
            <emphasis>$seo_emphasis</emphasis>
            <keywords>$target_keywords</keywords>
        </seo_parameters>
    </course_context>

    <task_instructions>
        Your main task is to generate a complete, well-structured, and engaging online course based on all the provided context.

        <structure_requirements>
            - The course MUST have a compelling "course_title" and a concise "course_description".
            - The "course_description" must be between 200 and 1000 words, content have html like list, heading h3, paragraph to make content easy to read and eye-catching, and include one image with tag <image> on the content.
            - The course MUST be divided into exactly **$sections** section(s).
            - "section_title" values MUST be concise, relevant, and no longer than **$section_title_length** characters.
            - "section_description" values MUST be concise, relevant, and no longer than **$section_desc_length** characters.
            - Each section MUST contain a relevant "section_title" and exactly **$lessons_per_section** lesson(s).
            - Each lesson MUST have a "lesson_title" and detailed "lesson_description".
            - "lesson_title" values MUST be concise, relevant, and no longer than **$lesson_title_length** characters.
        </structure_requirements>
        $quiz_structure_requirements
    </task_instructions>

    <output_format>
        - You MUST respond with ONLY a single, valid JSON object.
        - Do not include any introductory text, explanations, or markdown code fences like ```json.
        - The JSON structure must strictly follow this example:
        <json_example>
			{
			  "course_title": "Compelling Course Title Here",
			  "course_description": "A brief summary of the course.",
			  "sections": [
			    {
			      "section_title": "Section 1 Title Here",
			      "section_description": "Section 1 description Here",
			      "lessons": [
			        {
			          "lesson_title": "Lesson 1.1 Title Here",
			          "lesson_description": "Detailed content for lesson 1.1..."
			        }
			      ]$quiz_json_example
			    }
			  ]
			}
        </json_example>
    </output_format>
</prompt>
XML;
