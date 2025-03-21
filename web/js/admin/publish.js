document.addEventListener("DOMContentLoaded", function(event) {
	
	$("input[type='checkbox']").checkboxradio();
	
	var dressList = function() {
		activateStates(document, function(entry, next) {
			return changeStatus(entry, next);
		});
		activateListSort(document.getElementById('books'), document.getElementById('lookup_books'), document.getElementById('cursor_books'));
		[].forEach.call(document.querySelectorAll('tr.data td.delete'), function(entry) {
			entry.addEventListener("click", function(event) {
				if (confirm(LOCALE.admin.book.delete.confirm) && changeStatus(entry, 'del')) {
					var row = entry.parentNode;
					row.parentNode.removeChild(row);
				}
			});
		});
		[].forEach.call(document.querySelectorAll('tr.data td[data-isbn]'), function(entry) {
			entry.addEventListener("click", function(event) {
				document.body.classList.add('waiting');
				invokeZord({
					module:'Book',
					action:entry.dataset.action,
					open:entry.dataset.open,
					isbn:entry.dataset.isbn,
					ctx:entry.dataset.context,
					deferred:true,
					after: function() {
						document.body.classList.remove('waiting');
					}
				});
			});
		});
	}
	
	var changeStatus = function(entry, next) {
		var change = false;
		invokeZord({
			module:'Admin',
			action:'publish',
			async:false,
			name:entry.dataset.context,
			book:entry.dataset.book,
			status:next,
			success: function(result) {
				change = result.change;
			}
		});
		return change;
	};

	var books  = document.getElementById('books');
	var cursor = document.getElementById('cursor_books');
	var lookup = document.getElementById('lookup_books');
	
	attachListUpdate(books, function() {
		return {
			module    : 'Admin',
			action    : 'books',
			offset    : document.getElementById('cursor_books').dataset.offset,
			title     : lookup.querySelector('input[name="title"]').value,
			ctx       : lookup.querySelector('#context').value,
			only      : lookup.querySelector('#only').checked ? 'true' : 'false',
			new       : lookup.querySelector('#new').checked ? 'true' : 'false',
			order     : lookup.querySelector('input[name="order"]').value,
			direction : lookup.querySelector('input[name="direction"]').value,
			success   : function() {
				dressList();
			}
		};
	});
	
	document.getElementById('context').addEventListener('change', function(event) {
		books.update();
	});

	dressList();
	dressCursor(cursor);

});