var marginTopMin = 0;
var marginTopMax = 71;
var windowHeight = 0;

var toScroll = function() {
	var navbar = document.getElementById('navbar');
	if (navbar) {
		var header = document.getElementById('header');
		if (header) {
			marginTopMax = getNumber(header,'height');
		} else {
			marginTopMax = 0;
		}
		var marginTop = marginTopMax - $scrollTop.get();
		marginTop = (marginTop < marginTopMin) ? marginTopMin : (marginTop > marginTopMax) ? marginTopMax : marginTop;
		navbar.style.marginTop = marginTop + 'px';
		marginTop = marginTop + getNumber(document.getElementById('navbar'), 'height');
		[].forEach.call(document.querySelectorAll('.fixed'), function(element) {
			element.style.marginTop = marginTop + 'px';
			if (element.classList.contains('pullout')) {
				new Map([
					['.content',element.querySelector('.content').offsetTop + getNumber(element.querySelector('.top'), 'margin-bottom')],
					['.handle' ,element.querySelector('.handle span').offsetHeight]
				]).forEach(function(offset,selector) {
					element.querySelector(selector).style.height = (windowHeight - (marginTop + offset)) + 'px';
				});
			}
		});
	}
};

var search = function(criteria, callback) {
	var fetch = (callback === undefined || typeof callback !== 'function') ? 'false' : 'true';
	var results = null;
	parameters = {
		module:"Book",
		action:"search",
		fetch: fetch,
		criteria:JSON.stringify(criteria),
		before:function() {
			$dialog.wait();
		},
		after:function() {
			$dialog.hide(function() {
				if (fetch == 'true' && results !== undefined && results !== null && results.dataset.search.length > 0 && results.dataset.alert.length > 0) {
					alert(results.dataset.alert);
				}
			});
		}
	};
	if (fetch == 'true') {
		parameters['success'] = function(html) {
			results = callback(html);
		}
	}
	invokeZord(parameters);
}

var setWindowHeight = function() {
	windowHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
};

window.addEventListener("load", function(event) {
	
	var content = document.getElementById('content');
	var navbar = document.getElementById('navbar');
	var message = document.getElementById('message');
	if (content) {
		var marginTop = getNumber(content,'margin-top');
		if (navbar) {
			marginTop += getNumber(navbar,'height');
		}
		if (message) {
			marginTop += getNumber(message,'height');
		}
		content.style.marginTop = marginTop + 'px';
	}
	
	setWindowHeight();
	toScroll();
	
});

document.addEventListener("DOMContentLoaded", function(event) {

	window.addEventListener('resize', function(event) {
		setWindowHeight();
		toScroll();
	});

	document.addEventListener('scroll', function() {
		toScroll();
	});
	
	context = document.getElementById('menu_context');
	if (context) {
		[].forEach.call(document.getElementById('menu_context').querySelectorAll('li'), function(li) {	
			li.addEventListener("click", function() {
				form = document.getElementById('switchContextForm');
				form.action = BASEURL[li.id.substr('menu_context_'.length)];
				form.submit();
			});
		});
	}
	
	lang = document.getElementById('menu_lang');
	if (lang) {
		[].forEach.call(document.getElementById('menu_lang').querySelectorAll('li'), function(li) {
			li.addEventListener("click", function() {
				invokeZord({
					module:'Portal',
					action:'last',
					type:'VIEW',
					lang:li.id.substr('menu_lang_'.length)
				});
			});
		});
	}
	
	counter = document.getElementById('menu_counter');
	if (counter) {
		counter.addEventListener('click', function() {
			$dialog.wait();
		});
	}
	
	bindSearch({
		input:    '#menu_quick input',
		source:   BASEURL['zord'] + '/match',
		location: function(ui) {
			return BASEURL['zord'] + '/book/' + ui.item.value;
		},
		position: {
			my:'right top+8',
			at:'right bottom'
		}
	});
	
});
