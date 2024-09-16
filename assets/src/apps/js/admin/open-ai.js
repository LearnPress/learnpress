import courseTitle from './open-ai/course/title';
import courseDes from './open-ai/course/description';
import createFeatureImage from './open-ai/course/create-feature-image';
import editFeatureImage from './open-ai/course/edit-feature-image';

import lessonTitle from './open-ai/lesson/title';
import lessonDes from './open-ai/lesson/description';

import quizTitle from './open-ai/quiz/title';
import quizDes from './open-ai/quiz/description';

import questionTitle from './open-ai/question/title';
import questionDes from './open-ai/question/description';

document.addEventListener('DOMContentLoaded', function (event) {
	courseTitle();
	courseDes();
	createFeatureImage();
	editFeatureImage();

	lessonTitle();
	lessonDes();

	quizTitle();
	quizDes();

	questionTitle();
	questionDes();
});
