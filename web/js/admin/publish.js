document.addEventListener("DOMContentLoaded", function(event) {
	
	var books = document.getElementById('books');
	var lookup = document.getElementById('lookup_books');
	var cursor = document.getElementById('cursor_books');
	
	var dressList = function() {
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
				invokeZord({
					module:'Book',
					action:entry.dataset.action,
					open:entry.dataset.open,
					isbn:entry.dataset.isbn,
					ctx:entry.dataset.context,
					deferred:true
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
	
	activateStates(document, function(entry, next) {
		return changeStatus(entry, next);
	});
	
	attachListUpdate(books, function() {
		return {
			module    : 'Admin',
			action    : 'books',
			ctx       : lookup.querySelector('select').value,
			order     : lookup.querySelector('input[name="order"]').value,
			direction : lookup.querySelector('input[name="direction"]').value,
			success   : function() {
				var books = document.getElementById('books');
				var lookup = document.getElementById('lookup_books');
				activateListSort(books, lookup);
				dressList();
			}
		};
	});

	activateListSort(books, lookup);
	dressList();
	dressCursor(cursor);
	
	document.getElementById('context').addEventListener('change', function(event) {
		books.update();
	});

});