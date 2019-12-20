var content = function(row, column) {
	var td = row.querySelectorAll('td').item(column);
	var content = td.querySelector('.content');
	if (content !== undefined && content !== null) {
		content = content.textContent;
	} else {
		content = td.textContent;
	} 
	return content.toLowerCase();
}

var unsorted = function(first, second, column, order) {
	var firstContent  = content(first, column);
	var secondContent = content(second, column);
	return (order == 'ASC'  && firstContent > secondContent) || 
	       (order == 'DESC' && firstContent < secondContent);
}

var sort = function(table, column, order) {
	var rows = [];
	var subs = [];
	[].forEach.call(table.rows, function(row) {
		if (row.classList.contains('sort')) {
			rows.push(row);
			var current = row.nextElementSibling;
			var sub = [];
			while (current !== undefined && current !== null && !current.classList.contains('sort')) {
				sub.push(current);
				current = current.nextElementSibling;
			}
			subs.push(sub);
		}
	});
    var sorted = quick(rows, column, order);
    var tbody = table.firstElementChild.nextElementSibling;
    tbody.innerHTML = '';
    [].forEach.call(sorted, function(row) {
    	tbody.appendChild(row);
    	var index = 0;
    	[].forEach.call(rows, function(unsorted, i) {
    		if (row == unsorted) {
    			index = i;
    		}
    	});
    	[].forEach.call(subs[index], function(sub) {
    		tbody.appendChild(sub);
    	});
    });
}

var bubble = function(rows, column, order) {
	var swap = true;
	while (swap) {
	    swap = false;
	    for (var index = 0; index < rows.length - 1; index++) {
	    	if (unsorted(rows[index], rows[index + 1], column, order)) {
		    	var temp = rows[index];
		    	rows[index + 1] = rows[index];
		    	rows[index] = temp;
		    	swap = true;
	    	}
	    }
	}
	return rows;
}

var quick = function(rows, column, order) {
	  if (rows.length <= 1) {
	    return rows;
	  }
	  var pivot = rows[0];
	  var left  = []; 
	  var right = [];
	  for (var index = 1; index < rows.length; index++) {
		  if (unsorted(rows[index], pivot, column, order)) {
			  left.push(rows[index]);
		  } else {
			  right.push(rows[index]);
		  }
	  }
	  return quick(left, column, order).concat(pivot, quick(right, column, order));
};
	
document.addEventListener("DOMContentLoaded", function(event) {
	[].forEach.call(document.querySelectorAll('thead .sort'), function (toggle) {
		toggle.addEventListener("click", function(event) {
			toggle.style.cursor = 'wait';
			document.body.style.cursor = 'wait';
			var table = toggle.parentNode;
			while (table !== undefined && table.tagName !== 'TABLE') {
				table = table.parentNode;
			}
			if (table !== undefined) {
				var column = toggle.getAttribute('data-column');
				var order = toggle.hasAttribute('data-order') ? toggle.getAttribute('data-order') : 'ASC';
				sort(table, column, order);
				[].forEach.call(table.querySelectorAll('thead .sort .fa'), function (icon) {
					icon.classList.remove('fa-caret-up');
					icon.classList.remove('fa-caret-down');
					icon.classList.add('fa-sort');
				});
				if (order == 'ASC') {
					[].forEach.call(toggle.querySelectorAll('.fa'), function (icon) {
						icon.classList.remove('fa-sort');
						icon.classList.add('fa-caret-up');
					});
					order = 'DESC';
				} else {
					[].forEach.call(toggle.querySelectorAll('.fa'), function (icon) {
						icon.classList.remove('fa-sort');
						icon.classList.add('fa-caret-down');
					});
					order = 'ASC';
				}
				toggle.setAttribute('data-order', order);
			}
			toggle.style.cursor = 'pointer';
			document.body.style.cursor = 'auto';
		});
	});
});

