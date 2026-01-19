import * as lpUtils from './../../utils.js';
import * as lpToastify from "lpAssetsJsPath/lpToastify";

const exportOrders = () => {
	const handleExport = (args) => {
		const {e, target} = args;
		let callBack;
		e.preventDefault();
		lpUtils.lpSetLoadingEl(target, true);
		let dataSend = JSON.parse(target.dataset.send);

		callBack = {
			success: (response) => {
				const {message, status, data} = response;
				lpToastify.show(message, status);

				if (status === 'success') {
				}
			},
			error: (error) => {
				lpToastify.show(error, 'error');
			},
			completed: () => {
				lpUtils.lpSetLoadingEl(target, false);
			},
		};

		window.lpAJAXG.fetchAJAX(dataSend, callBack);
	}

	lpUtils.eventHandlers('click', [
		{
			selector: 'button.export',
			class: this,
			callBack: handleExport,
		}
	]);
}

export default exportOrders;
