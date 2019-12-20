var folded = true;

function checkContext(operation, data) {
	if (operation == 'delete') {
		return confirm(LOCALE['admin']['context']['delete']['confirm']);
	}
	if (data == undefined || data == null) {
		return false;
	}
	if (data.name == undefined || data.name == null || data.name.length == 0) {
		return false;
	}
	if (data.title == undefined || data.title == null || data.title.length == 0) {
		return false;
	}
	return true;
}

function listBooks(button) {
	button.classList.add(folded ? 'fa-compress' : 'fa-expand');
	button.classList.remove(folded ? 'fa-expand' : 'fa-compress');
	[].forEach.call(document.querySelectorAll('tr.data[data-included]'), function(entry) {
		entry.style.display = (entry.getAttribute('data-included') == 'yes' || !folded) ? "table-row" : "none";
	});
}

function getPublish() {
	var urls = [];
	var books = [];
	var context = document.getElementById('context').value;
	var urlsElement = document.getElementById('urls');
	if (urlsElement) {
		[].forEach.call(urlsElement.querySelectorAll('.data'), function(entry) {
			urls.push({
				secure:entry.children[0].firstElementChild.value == 'yes' ? true : false,
				host:entry.children[1].firstElementChild.value,
				path:entry.children[2].firstElementChild.value
			}); 
		});
	}
	var booksElement = document.getElementById('books');
	if (booksElement) {
		[].forEach.call(booksElement.querySelectorAll('.data'), function(entry) {
			if (entry.children[2].firstElementChild.value != 'no') {
				books.push({
					isbn:entry.children[0].firstElementChild.value,
					status:entry.children[2].firstElementChild.value
				}); 
			}
		});
	}
	var publish = {
		context:context,
		urls:JSON.stringify(urls),
		books:JSON.stringify(books)
	};
	return publish;
}

document.addEventListener("DOMContentLoaded", function(event) {
	
	var submitPublish = document.getElementById('submit-publish');
	if (submitPublish != undefined) {
		submitPublish.addEventListener("click", function(event) {
			var publish = getPublish();
			invokeZord({
				module:'Admin',
				action:'publish',
				name:publish.context,
				urls:publish.urls,
				books:publish.books,
				before:function() {
					$dialog.wait();
				},
				after:function() {
					$dialog.hide();
				}
			});
		});
	}
	
	[].forEach.call(document.querySelectorAll('tr.data td input[data-isbn]'), function(entry) {
		span = entry.nextElementSibling;
		span.style.cursor = 'pointer';
		span.addEventListener("click", function(event) {
			invokeZord({
				module:'Book',
				action:entry.name == 'book' ? 'show' : 'epub',
				isbn:entry.getAttribute('data-isbn'),
				deferred:true,
				ctx:document.getElementById('context').value
			});
		});
	});
	
	
	var expandList = document.getElementById('expand-list');
	if (expandList != undefined) {
		expandList.addEventListener("click", function(event) {
			folded = !folded;
			listBooks(expandList);
		});
		listBooks(expandList);
	}

	attach(['publish'], function(entry, operation) {
		var data = {
			name:entry.parentNode.children[0].firstElementChild.value,
			title:entry.parentNode.children[1].firstElementChild.value
		};
		if (checkContext(operation, data)) {
			invokeZord({
				module:'Admin',
				action:'context',
				operation:operation,
				name:data.name,
				title:data.title,
				before:function() {
					if (operation == 'publish') {
						$dialog.wait();
					}
				},
				after:function() {
					if (operation == 'publish') {
						$dialog.hide();
					}
				}
			});
		}
	});

});