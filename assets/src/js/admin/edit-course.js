/**
 * Edit course JS handler.
 *
 * @since 4.3.0
 * @version 1.0.0
 */
import { EditCourseCurriculum } from './edit-course/edit-curriculum.js';
import { GenerateWithOpenai } from './generate-with-openai.js';
import { EditCurriculumAi } from './edit-course/edit-curriculum/edit-curriculum-ai.js';

new EditCourseCurriculum();
new GenerateWithOpenai();
new EditCurriculumAi();
