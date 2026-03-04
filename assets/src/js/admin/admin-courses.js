/**
 * Admin Courses JS
 *
 * @since 4.3.0
 * @version 1.0.1
 */

import { CreateCourseViaAI } from './courses/generate-with-ai.js';
import { ViewStudentsModal } from './courses/view-students-modal.js';

new CreateCourseViaAI();
new ViewStudentsModal();
