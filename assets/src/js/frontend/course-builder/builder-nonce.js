/**
 * Nonce of CourseBuilderAjax actions (AbstractAjax::$action_nonce).
 * Printed only on wrapper of Course Builder page, send via field 'lp_action_nonce'.
 * Only add it to request of Course Builder actions, never to data read from markup (data-send).
 *
 * @since 4.5.1
 */
export const getCBActionNonce = () => {
	const elWrapper = document.querySelector( '.learn-press-course-builder' );

	return elWrapper?.dataset.lpActionNonce || '';
};
