<?php
/**
 * Prompt to create a course
 */
if ( ! isset( $params ) ) {
	return;
}

// Course Intent
$title            = $params['post-title'] ?? '';
$description      = $params['post-content'] ?? '';
$target_audience  = $params['target_audience'] ?? 'Beginners';
$course_objective = trim( $params['course_objective'] ?? '' );

// AI Settings
$language        = trim( $params['language'] ?? 'English' );
$tone            = trim( $params['tone'] ?? 'Informative and encouraging' );
$reading_level   = trim( $params['reading_level'] ?? 'High school' );
$target_keywords = trim( $params['target_keywords'] ?? '' );

$goal                       = $params['goal'] ?? '';
$sections                   = $params['section_number'] ?? 3;
$section_title_length       = $params['section_title_length'] ?? 60;
$section_description_length = $params['section_description_length'] ?? 160;
$lessons_per_section        = $params['lessons_per_section'] ?? 5;
$lesson_title_length        = $params['lesson_title_length'] ?? 60;
$lesson_desc_length         = $params['lesson_desc_length'] ?? 1000;
$quizzes_per_section        = $params['quizzes_per_section'] ?? 1;
$quiz_title_length          = $params['quiz_title_length'] ?? 60;
$questions_per_quiz         = $params['questions_per_quiz'] ?? 2;

$quiz_structure_requirements = '';
$quiz_json_example           = '';
if ( $quizzes_per_section > 0 ) {
	$quiz_structure_requirements = <<<XML
        <quiz_requirements>
            - Each section MUST contain exactly **{$quizzes_per_section}** quiz object(s) within a "quizzes" array.
            - Each quiz MUST have a relevant "quiz_title" and "quiz_description".
            - Each "quiz_title" approximately {$quiz_title_length} characters.
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
        You are an AI assistant specialized in instructional design and content creation.
    </role_definition>

    <course_context>
        <objective>
            The primary goal of this structure course is:
            inspired by the course title: "$title",
            also reflect the course's content: "$description"
        </objective>
        <audience>
            The target audience is: $target_audience
        </audience>
        <content_parameters>
            <language>$language</language>
            <tone>$tone</tone>
            <reading_level>$reading_level</reading_level>
        </content_parameters>
    </course_context>

    <task_instructions>
        Your main task is to generate a complete, well-structured, and engaging online course based on all the provided context.

        <structure_requirements>
            - The goal of course is {$goal}
            - The course MUST be divided into exactly **$sections** section(s).
            - Each "section_title" approximately {$section_title_length} characters.
            - Each "section_description" approximately {$section_description_length} characters.
            - Each section MUST contain a relevant "section_title" and exactly **$lessons_per_section** lesson(s).
            - Each lesson MUST have a "lesson_title" and detailed "lesson_description".
            - "lesson_title" values MUST be concise, relevant, and no longer than **$lesson_title_length** characters.
            - "lesson_description" values MUST be concise, relevant, and no longer than **$lesson_desc_length** characters.
        </structure_requirements>
        $quiz_structure_requirements
    </task_instructions>

    <output_format>
        - You MUST respond with ONLY a single, valid JSON object.
        - Do not include any introductory text, explanations, or markdown code fences like ```json.
        - The JSON structure must strictly follow this example:
        <json_example>
			{
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
