let elLPAdminNotices;
let data_html = null;

const urlApiAdminNotices = lpGlobalSettings.rest + 'lp/v1/admin/tools/admin-notices';
fetch( urlApiAdminNotices, {
	method: 'GET'
} ).then((res)=>
	res.json()
).then((res) => {
	// console.log(data);
	const {status, message, data } = res;
	data_html = data.content;
}).catch((err) => {
	console.log(err);
});

document.addEventListener('DOMContentLoaded', () => {
	elLPAdminNotices = document.querySelector('.lp-admin-notices');

	const interval = setInterval(() => {
		if ( data_html !== null ) {
			elLPAdminNotices.innerHTML = data_html;
			clearInterval(interval);
		}
	}, 1)

})
