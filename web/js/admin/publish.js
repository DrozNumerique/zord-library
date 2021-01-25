var folded = true;

function listBooks(button) {
	button.classList.add(folded ? 'fa-compress' : 'fa-expand');
	button.classList.remove(folded ? 'fa-expand' : 'fa-compress');
	[].forEach.call(document.querySelectorAll('tr.data[data-included]'), function(entry) {
		entry.style.display = (entry.getAttribute('data-included') == 'yes' || !folded) ? "table-row" : "none";
	});
}

function getPublishData() {
	var books = [];
	var context = document.getElementById('context').value;
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
	var data = {
		context:context,
		books:JSON.stringify(books)
	};
	return data;
}

document.addEventListener("DOMContentLoaded", function(event) {
	
	var submitPublish = document.getElementById('submit-publish');
	if (submitPublish != undefined) {
		submitPublish.addEventListener("click", function(event) {
			var data = getPublishData();
			invokeZord({
				module:'Admin',
				action:'publish',
				name:data.context,
				books:data.books,
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
	
	document.getElementById('context').addEventListener('change', function(event) {
		invokeZord({
			module:'Admin',
			action:'index',
			tab:'publish',
			ctx:event.target.value
		});
	});

});