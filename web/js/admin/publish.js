document.addEventListener("DOMContentLoaded", function(event) {
	
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
	
	document.getElementById('context').addEventListener('change', function(event) {
		invokeZord({
			module:'Admin',
			action:'index',
			tab:'publish',
			ctx:event.target.value
		});
	});

});