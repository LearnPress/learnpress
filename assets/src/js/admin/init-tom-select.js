import TomSelect from "tom-select";
import { lpFetchAPI } from "../utils.js";
import Api from "../api.js";

/**
 * Init Tom-Select.
 * Function init tom-select with custom
 * @param api
 * @param elTomSelect
 * @param keySearch
 * @param keySearch
 * @param customParam
 * @param customOptions
 * @param customOutput
 * @param tomSelectAction
 * @param callback
 *
 * @since 4.2.6.8
 * @version 1.0.0
 */

const initTomSelect = (
	api,
	elTomSelect,
	keySearch = "",
	customParams = {},
	customOptions = {},
	customOutput,
	tomSelectAction,
	callback,
) => {
	if (!elTomSelect) return;
	let dataOptions;

	const params = {
		headers: {
			"Content-Type": "application/json",
			"X-WP-Nonce": lpDataAdmin.nonce,
		},
		method: "POST",
		body: { search: keySearch },
		...customParams,
		body: JSON.stringify({
			...customParams.body,
			search: keySearch,
		}),
	};

	const tomOptions = {
		maxItems: 1,
		maxOptions: 10,
		render: {
			item(data, escape) {
				return (
					`<li data-id="${data.value}">` +
					`<div class="item" data-ts-item="">${data.text}</div>` +
					`<input type="hidden" name="author" value="${data.value}">` +
					"</li>"
				);
			},
		},
		...customOptions,
	};

	const defaultOutput = (response) => {
		return response.data.map((item) => {
			return {
				value: item.ID,
				text: `${item.display_name} (#${item.ID}) - ${item.user_email}`,
			};
		});
	};

	if (api) {
		lpFetchAPI(api, params, {
			success: (response) => {
				if ("function" === typeof customOutput) {
					dataOptions = customOutput(response);
				} else {
					dataOptions = defaultOutput(response);
				}
				tomOptions.options = dataOptions;
				// Fetch API when input change
				// tomOptions.load = (keySearch, callback) => {
				// 	const bodyParams = JSON.parse(params.body);
				// 	bodyParams.search = keySearch;
				// 	params.body = JSON.stringify(bodyParams);
				// 	let dataFetch;

				// 	lpFetchAPI(api, params, {
				// 		success: (response) => {
				// 			if ("function" === typeof customOutput) {
				// 				dataFetch = customOutput(response);
				// 			} else {
				// 				dataFetch = response.data.map((item) => {
				// 					return {
				// 						value: item.ID,
				// 						text: `${item.display_name} (#${item.ID})`,
				// 					};
				// 				});
				// 			}

				// 			tomOptions.options = dataFetch;
				// 			callback(dataFetch);
				// 		},
				// 	});
				// };
				// Set data users default first to Tom Select.
				// if (keySearch === "") {
				// 	const initTomSelectEl = new TomSelect(
				// 		elTomSelect,
				// 		tomOptions,
				// 	);

				// 	//Add action tom select
				// 	if ("function" === typeof tomSelectAction) {
				// 		tomSelectAction(initTomSelectEl);
				// 	}
				// }

				// Fetch API once
				tomOptions.load = (keySearch, callback) => {
					if (self.loading > 1) {
						callback();
						return;
					}

					let dataFetch;

					lpFetchAPI(api, params, {
						success: (response) => {
							if ("function" === typeof customOutput) {
								dataFetch = customOutput(response);
							} else {
								dataFetch = response.data.map((item) => {
									return {
										value: item.ID,
										text: `${item.display_name} (#${item.ID})`,
									};
								});
							}

							tomOptions.options = dataFetch;
							callback(dataFetch);
						},
					});
				};

				const initTomSelectEl = new TomSelect(elTomSelect, tomOptions);
				if ("function" === typeof tomSelectAction) {
					tomSelectAction(initTomSelectEl);
				}
			},
		});
	} else {
		const initTomSelectEl = new TomSelect(elTomSelect, tomOptions);

		//Add action tom select
		if ("function" === typeof tomSelectAction) {
			tomSelectAction(initTomSelectEl);
		}
	}

	if ("function" === typeof callback) {
		callback();
	}
};

// Init Tom-select user in order
const searchUserOrder = () => {
	const apiSearchUser = Api.admin.apiSearchUsers;
	const searchUserOrderEl = document.querySelector("#list-users");
	let defaultId = "";
	if (!searchUserOrderEl) return;

	if (searchUserOrderEl.dataset.userId) {
		defaultId = JSON.parse(searchUserOrderEl.dataset.userId);
	}

	const customOptions = {
		maxItems: null,
		items: defaultId,
		plugins: {
			remove_button: {
				title: "Remove this item",
			},
		},
		render: {
			item(data, escape) {
				return (
					`<li data-id="${data.value}">` +
					`<div class="item" data-ts-item="">${data.text}</div>` +
					`<input type="hidden" name="order-customer[]" value="${data.value}">` +
					"</li>"
				);
			},
		},
	};

	initTomSelect(apiSearchUser, searchUserOrderEl, "", {}, customOptions);
};

// Init Tom-select user in admin
const searchUserAdmin = () => {
	const insertAfter = (referenceNode, newNode) => {
		if (!referenceNode || !newNode) return;
		referenceNode.parentNode.insertBefore(
			newNode,
			referenceNode.nextSibling,
		);
	};

	const createSelectUserHtml = () => {
		const inputEl = document.querySelector("#post-search-input");
		const createSelectUser = document.createElement("select");
		if (!createSelectUser || !inputEl) return;
		createSelectUser.setAttribute("id", "author");
		insertAfter(inputEl, createSelectUser);
	};

	const tomSearchUser = () => {
		const apiSearchUser = Api.admin.apiSearchUsers;
		const selectAuthor = document.querySelector(`#author`);
		if (!selectAuthor) return;
		const authorInputEl = document.querySelector('input[name="author"]');
		const defaultId = authorInputEl?.value ? authorInputEl.value : "";
		const tomAction = (tomSelectEl) => {
			if (!tomSelectEl) return;
			if (!authorInputEl) return;

			tomSelectEl.on("item_add", (data, item) => {
				authorInputEl.setAttribute("value", data);
			});

			tomSelectEl.on("item_remove", (data, item) => {
				authorInputEl.setAttribute("value", "");
			});
		};

		const customOptions = {
			items: defaultId,
			plugins: {
			remove_button: {
				title: "Remove this item",
			},
		},
		};

		initTomSelect(
			apiSearchUser,
			selectAuthor,
			"",
			{},
			customOptions,
			{},
			tomAction,
		);
	};

	const classSelectBox = "posts-filter";
	const selectBoxEl = document.querySelector(`#${classSelectBox}`);

	if (selectBoxEl) {
		createSelectUserHtml();
		tomSearchUser();
	}
};

// Init Tom-select author in course
const selectAuthorCourse = () => {
	const selectAuthorCourseEl = document.querySelector(
		"select#_lp_course_author",
	);
	if (!selectAuthorCourseEl) return;

	const roleSearch = "administrator,lp_teacher";
	const apiSearchUser = Api.admin.apiSearchUsers;
	const authorInputEl = document.querySelector('input[name="post_author"]');
	const defaultId = authorInputEl?.value ? authorInputEl.value : "";

	const customParams = {
		headers: {
			"Content-Type": "application/json",
			"X-WP-Nonce": lpDataAdmin.nonce,
		},
		method: "POST",
		body: { role_in: roleSearch },
	};

	const customOptions = {
		items: defaultId,
		render: {
			item(data, escape) {
				return `<li data-id="${data.value}"><div class="item" data-ts-item="">${data.text}</div></li>`;
			},
		},
	};

	const tomAction = (tomSelectEl) => {
		if (!tomSelectEl) return;
		if (!authorInputEl) return;

		tomSelectEl.on("item_add", (data, item) => {
			authorInputEl.setAttribute("value", data);
		});

		tomSelectEl.on("item_remove", (data, item) => {
			authorInputEl.setAttribute("value", defaultId);
		});
	};

	initTomSelect(
		apiSearchUser,
		selectAuthorCourseEl,
		"",
		customParams,
		customOptions,
		"",
		tomAction,
	);
};

//  Init Tom-select author co-instructor course
const selectCoInstructor = () => {
	const selectCoInstructorEl = document.querySelector("#_lp_co_teacher");
	const postAuthorEl = document.querySelector("#post_author");
	if (!selectCoInstructorEl) return;

	const userId = postAuthorEl?.value ? postAuthorEl?.value : "";
	const defaultId = selectCoInstructorEl.dataset?.coInstructors
		? JSON.parse(selectCoInstructorEl.dataset?.coInstructors)
		: "";
	const apiSearchUser = Api.admin.apiSearchUsers;
	const roleSearch = "administrator,lp_teacher";
	const customParams = {
		headers: {
			"Content-Type": "application/json",
			"X-WP-Nonce": lpDataAdmin.nonce,
		},
		method: "POST",
		body: { role_in: roleSearch, id_not_in: userId },
	};

	const customOptions = {
		maxItems: null,
		items: defaultId[0],
		plugins: {
			remove_button: {
				title: "Remove this item",
			},
		},
		render: {
			item(data, escape) {
				return (
					`<li data-id="${data.value}">` +
					`<div class="item" data-ts-item="">${data.text}</div>` +
					`<input type="hidden" name="_lp_co_teacher[]" value="${data.value}">` +
					"</li>"
				);
			},
		},
	};

	const tomAction = (tomSelectEl) => {
		if (!tomSelectEl) return;
		if (!selectCoInstructorEl) return;

		tomSelectEl.on("change", (data, item) => {
			if (data.length < 1) {
				selectCoInstructorEl.value = "";
			}
		});
	};

	initTomSelect(
		apiSearchUser,
		selectCoInstructorEl,
		"",
		customParams,
		customOptions,
		{},
		tomAction,
	);
};

export {
	initTomSelect,
	selectAuthorCourse,
	searchUserAdmin,
	searchUserOrder,
	selectCoInstructor,
};
