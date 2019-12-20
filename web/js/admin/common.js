var switches = {
	secure: {
		yes: {
			icon:'fa-chain',
			color:'black',
			next:'no'
		},
		no: {
			icon:'fa-chain-broken',
			color:'black',
			next:'yes'
		}
	},
	select: {
		no: {
			icon:'fa-check',
			color:'white',
			next:'new'
		},
		'new': {
			icon:'fa-star',
			color:'black',
			next:'yes'
		},
		yes: {
			icon:'fa-check',
			color:'black',
			next:'no'
		}
	},
	manage: {
		no: {
			icon:'fa-check',
			color:'white',
			next:'new'
		},
		'new': {
			icon:'fa-star',
			color:'black',
			next:'yes'
		},
		yes: {
			icon:'fa-check',
			color:'black',
			next:'del'
		},
		del: {
			icon:'fa-remove',
			color:'black',
			next:'no'
		}
	}
};

function add(line) {
	var list = line.parentNode;
	var newLine = list.querySelector('.hidden').cloneNode(true);
	newLine.classList.remove('hidden');
	newLine.classList.add('data');
	list.appendChild(newLine);
	newLine.children[newLine.children.length - 1].addEventListener("click", function(event) {
		remove(newLine);
	});
}

function remove(line) {
	var list = line.parentNode;
	list.removeChild(line);
}

function attach(operations, callback) {
	var opList = ['create','update','delete'];
	[].forEach.call(operations, function(op) {
		opList.push(op);
	});
	[].forEach.call(opList, function(operation) {
		[].forEach.call(document.querySelectorAll('.' + operation), function(entry) {				
			entry.addEventListener("click", function(event) {
				callback(entry, operation);
			});				
		});			
	});
}

document.addEventListener("DOMContentLoaded", function(event) {
	
	[].forEach.call(document.querySelectorAll('.admin-menu-entry'), function(entry) {
		
		entry.addEventListener("click", function(event) {
			
			invokeZord({
				module:'Admin',
				action:'index',
				tab:entry.getAttribute('data-tab')
			});
			
		});
		
	});

	[].forEach.call(document.querySelectorAll('.add'), function(entry) {
		entry.addEventListener("click", function(event) {
			add(entry.parentNode);
		});
	});

	[].forEach.call(document.querySelectorAll('.remove'), function(entry) {
		entry.addEventListener("click", function(event) {
			remove(entry.parentNode);
		});
	});

	[].forEach.call(document.querySelectorAll('.admin-list'), function(list) {
		var widths = list.getAttribute('data-columns').split(',');
		[].forEach.call(list.querySelectorAll('li'), function(line) {
			[].forEach.call(line.querySelectorAll('.column'), function(column, index) {
				column.style = "width:" + widths[index];
			});
		});
	});

	[].forEach.call(Object.keys(switches), function(type) {		
		[].forEach.call(document.querySelectorAll('.' + type), function(entry) {
			entry.addEventListener("click", function(event) {
				var current = entry.children[0].value;
				var next = switches[type][current]['next'];
				entry.children[0].value = next;
				entry.children[1].classList.remove(switches[type][current]['icon']);
				entry.children[1].classList.add(switches[type][next]['icon']);
				entry.children[1].style = 'color:' + switches[type][next]['color'] + ';';
			});
		});
	});
	
});

document.addEventListener("load", function(event) {

	lists = document.querySelectorAll('.admin-list');
	[].forEach.call(lists, function(list) {
		list.style.width = window.getComputedStyle(list.firstElementChild).width;
	});

});
