document.addEventListener('DOMContentLoaded', function () {
	var selectField = document.getElementById('select-form-list');

	if (selectField) {
		selectField.addEventListener('change', function () {
			var selectedValue = selectField.value;
			var currentURL = window.location.href;
			var newURL = updateURLParameter(currentURL, 'form', selectedValue);
			window.location.href = newURL;
		});
	}

	function updateURLParameter(url, paramName, paramValue) {
		var pattern = new RegExp('\\b(' + paramName + '=).*?(&|$)');
		if (url.search(pattern) >= 0) {
			return url.replace(pattern, '$1' + paramValue + '$2');
		}
		return url + (url.indexOf('?') > 0 ? '&' : '?') + paramName + '=' + paramValue;
	}

	//Handle Export
	var button = document.getElementById('export');

	if (button) {
		button.addEventListener('click', function () {
			// Get the AJAX URL from the localized script data
			var ajaxUrl = EssentialBlocksProLocalize?.ajax_url;
			var params = {
				action: 'export_csv',
				form_id: selectField.value,
				admin_nonce: EssentialBlocksProLocalize?.admin_nonce
			};
			var queryString = Object.keys(params).map(key => key + '=' + params[key]).join('&');


			// Perform the AJAX call
			var xhr = new XMLHttpRequest();
			xhr.open('GET', ajaxUrl + '?' + queryString, true);
			xhr.onreadystatechange = function () {
				if (xhr.readyState === 4 && xhr.status === 200) {
					var response = JSON.parse(xhr.responseText);
					if (response && response.data) {
						var filename = response.title ? 'exported-' + response.title.replace(/\s+/g, '-').toLowerCase() : 'exported-form-response.csv'
						exportCSV(response.data, filename);
					} else {
						console.log('CSV export failed!');
					}
				}
			};
			xhr.send();
		});
	}
});

//Export CSV
function exportCSV(data, filename) {
	const csvContent = data;
	const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });

	if (navigator.msSaveBlob) {
		// For IE
		navigator.msSaveBlob(blob, filename);
	} else {
		const link = document.createElement('a');
		if (link.download !== undefined) {
			// Create a download link
			const url = URL.createObjectURL(blob);
			link.setAttribute('href', url);
			link.setAttribute('download', filename);
			link.style.visibility = 'hidden';
			document.body.appendChild(link);
			link.click();
			document.body.removeChild(link);
		}
	}
}
