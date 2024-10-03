/**
 * List API on backend
 *
 * @since 4.2.6
 * @version 1.0.0
 */

const lplistAPI = {};

if ( 'undefined' !== typeof lpDataAdmin ) {
	lplistAPI.admin = {
		apiAdminNotice: lpDataAdmin.lp_rest_url + 'lp/v1/admin/tools/admin-notices',
		apiAdminOrderStatic: lpDataAdmin.lp_rest_url + 'lp/v1/orders/statistic',
		apiAddons: lpDataAdmin.lp_rest_url + 'lp/v1/addon/all',
		apiAddonAction: lpDataAdmin.lp_rest_url + 'lp/v1/addon/action',
		apiAddonsPurchase: lpDataAdmin.lp_rest_url + 'lp/v1/addon/info-addons-purchase',
		apiSearchCourses: lpDataAdmin.lp_rest_url + 'lp/v1/admin/tools/search-course',
		apiSearchUsers: lpDataAdmin.lp_rest_url + 'lp/v1/admin/tools/search-user',
		apiAssignUserCourse: lpDataAdmin.lp_rest_url + 'lp/v1/admin/tools/assign-user-course',
		apiUnAssignUserCourse: lpDataAdmin.lp_rest_url + 'lp/v1/admin/tools/unassign-user-course',
		apiAJAX: lpDataAdmin.lp_rest_url + 'lp/v1/load_content_via_ajax/',
		apiGetHtmlQuestion: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/question/html-question',
		apiChangeQuestionType: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/question/change-question-type',
		apiAddNewAnswer: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/question/add-new-answer',
		apiDeleteAnswer: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/question/delete-answer',
		apiUpdateAnswerTitle: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/question/update-answer-title',
		apiChangeCorrectAnswer: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/question/change-correct',
		apiSortAnswer: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/question/sort-answer',
		apiSortQuestion: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/quiz/sort-question',
		apiChangeQuestionTitle: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/quiz/change-question-title',
		apiRemoveQuestion: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/quiz/remove-question',
		apiDeleteQuestion: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/quiz/delete-question',
		apiDuplicateQuestion: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/quiz/duplicate-question',
		apiAddNewQuestion: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/quiz/add-new-question',
		apiSearchQuestionItems: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/quiz/search-question-items',
		apiAddQuestionsToQuiz: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/quiz/add-questions-to-quiz',
		apiGetQuestionOption: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/question/get-question-option',
		apiChangeOption: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/question/change-option',
	};
}

if ( 'undefined' !== typeof lpData ) {
	lplistAPI.frontend = {
		apiWidgets: lpData.lp_rest_url + 'lp/v1/widgets/api',
		apiCourses: lpData.lp_rest_url + 'lp/v1/courses/archive-course',
		apiAJAX: lpData.lp_rest_url + 'lp/v1/load_content_via_ajax/',
	};
}

export default lplistAPI;
