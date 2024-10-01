import courseTitle from './open-ai/course/title';
import courseDes from './open-ai/course/description';
import createFeatureImage from './open-ai/course/create-feature-image';
import editFeatureImage from './open-ai/course/edit-feature-image';
import curriculum from './open-ai/course/curriculum';
import curriculumQuiz from './open-ai/course/curriculum-quiz';

import lessonTitle from './open-ai/lesson/title';
import lessonDes from './open-ai/lesson/description';

import quizTitle from './open-ai/quiz/title';
import quizDes from './open-ai/quiz/description';
import quizQuestion from './open-ai/quiz/quiz-question';

import questionTitle from './open-ai/question/title';
import questionDes from './open-ai/question/description';

document.addEventListener('DOMContentLoaded', function (event) {
	courseTitle();
	courseDes();
	createFeatureImage();
	editFeatureImage();
	curriculum();
	curriculumQuiz();

	lessonTitle();
	lessonDes();

	quizTitle();
	quizDes();
	quizQuestion();

	questionTitle();
	questionDes();
});
