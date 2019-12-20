function checkAll(value) {
	[].forEach.call(document.querySelectorAll('.t_check'), function(entry) {
		if (value) {
			entry.firstElementChild.setAttribute('checked', 'true');
		} else {
			entry.firstElementChild.removeAttribute('checked');
		}
	});
}

document.addEventListener("DOMContentLoaded", function(event) {		
	document.querySelector('#selectall').addEventListener("click", function(event) {
		checkAll(true);
	});
	document.querySelector('#unselectall').addEventListener("click", function(event) {
		checkAll(false);
	});
	[].forEach.call(document.querySelectorAll('.format'), function(entry) {
		entry.addEventListener("click", function(event) {
			var books = [];
			[].forEach.call(document.querySelectorAll('.t_check'), function(entry) {
				if (entry.firstElementChild.checked) {
					books.push(entry.firstElementChild.value);
				}
			});
			if (books.length > 0) {
				invokeZord({
					module:'Book',
					action:'records',
					books:JSON.stringify(books),
					format:entry.getAttribute('data-format')
				});
			}
		});
	});
});