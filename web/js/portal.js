var marginTopMin = 0;
var marginTopMax = 71;
var windowHeight = 0;

window.$scrollTop = {
	set : function(value) {
		window.scrollTo(0, value);
	},
	get : function() {
		return document.body.scrollTop || document.documentElement.scrollTop;
	}
};

window.$dialog = (function(undefined) {

	var dialogID = '__dialog_';
	var dialogModalID = '__dialogModal_';
	
	var _topZIndex = function() {
			var num = [1];
			[].forEach.call(document.querySelectorAll('*'),function(el, i){
				var x = parseInt(window.getComputedStyle(el, null).getPropertyValue("z-index")) || null;
				if(x!=null)
					num.push(x);
			});
			return Math.max.apply(null, num)+1;
	};
	
	var _position = function(elem) {
		// selon la talle de l'élément détermine le top et left
		var top = ((window.innerHeight / 2) - (elem.offsetHeight / 2)) - 50;
		var left = ((window.innerWidth / 2) - (elem.offsetWidth / 2));

		// reste dans la fenêtre
		if( top < 0 ) top = 0;
		if( left < 0 ) left = 0;

		// css sur l'élément
		elem.style.top = top + 'px';
		elem.style.left = left + 'px';
	};

	var show  = function(msg,type,isModal,callback) {
		if (isModal) {
			modal();
		}
		var dialogEl = document.getElementById(dialogID);
		if (dialogEl==undefined) {
			document.body.insertAdjacentHTML('beforeend','<div class="dialog" id="'+dialogID+'"></div>');
			dialogEl = document.getElementById(dialogID);
		}
		dialogEl.style.zIndex = _topZIndex()+1;
		switch (type) {
			case 'box':
				dialogEl.innerHTML = msg;
			break;
			case 'waitMsg':
				dialogEl.innerHTML = msg;
				setTimeout(function(){
					dialog.hide();
				},1500);
			break;
		}
		if (callback != undefined) {
			callback(dialogEl);
		}

		_position(dialogEl);

		dialogEl.style.visibility = 'visible';
	};
	
	var modal = function() {
		var dialogModalEl = document.getElementById(dialogModalID);
		if (dialogModalEl == undefined) {
			document.body.insertAdjacentHTML('beforeend', '<div id="' + dialogModalID + '"></div>');
			dialogModalEl = document.getElementById(dialogModalID);
		}
		dialogModalEl.style.zIndex = _topZIndex();
		var body = document.body, html = document.documentElement;
		var height = Math.max( body.scrollHeight, body.offsetHeight, html.clientHeight, html.scrollHeight, html.offsetHeight );
		dialogModalEl.style.height = height + 'px';
	};

	var dialog = {
		hide : function() {
			setTimeout(function() {
				var dialogEl = document.getElementById(dialogID);
				if(dialogEl)
					dialogEl.parentNode.removeChild( dialogEl );
				var dialogModalEl = document.getElementById(dialogModalID);
				if(dialogModalEl)
					dialogModalEl.parentNode.removeChild( dialogModalEl );
			},20);
		},
		hideDelay: function() {
			setTimeout(function() {
				dialog.hide();
			},350);
		},
		box : function(msg,callback) {
			show(msg,'box',true,callback);
		},
		waitMsg : function(msg,callback) {
			show(msg,'waitMsg',false,callback);
		},
		wait : function(callback) {
			show('<div class="dialog-wait"></div>','box',true,callback);
		},
		help : function(element) {
			show(document.getElementById('template_dialog_help').innerHTML, 'box', true, function(dialogEl) {
				dialogEl.querySelector('div[data-id="content"]').innerHTML = element.firstElementChild.innerHTML;
				dialogEl.querySelector('button[data-id="dialog_help_close"]').addEventListener("click", function(event) {
					$dialog.hide();
				});
			});
		}
	};
	return dialog;
}());

var getNumber = function(element,property) {
	var string = window.getComputedStyle(element).getPropertyValue(property);
	return Number(string.substring(0, string.length - 2));
};

var setWindowHeight = function() {
	windowHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
};

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
			if (element.classList.contains('slide')) {
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

var search = function(criteria, callback, wait) {
	invokeZord({
		module:"Book",
		action:"search",
		criteria:JSON.stringify(criteria),
		before:function() {
			$dialog.wait();
		},
		after:function() {
			$dialog.hide();
		}
	});
}

var chosenStyles = getSessionProperty('chosen.styles', undefined);

var activateChosen = function() {
	for (var type in chosenStyles) {
		$('.chosen-select-' + type).chosen(chosenStyles[type]).change(function(event, params) {
			if (event.target.hasAttribute('data-change')) {
				method = event.target.getAttribute('data-change');
				if (window[method] instanceof Function) {
					window[method]();
				}
			}
	    });
	}
}

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

	if (chosenStyles == undefined) {
		invokeZord({
			module: 'Data',
			action: 'chosen',
			async:  false,
			callback: function(config) {
				chosenStyles = config;
				setSessionProperty('chosen.styles', chosenStyles);
			}
		});
	}
	
	window.addEventListener('resize', function(event) {
		setWindowHeight();
		toScroll();
	});

	document.addEventListener('scroll', function() {
		toScroll();
	});
		
	document.getElementById('switchContext').addEventListener("change", function() {
		form = this.parentNode;
		form.action = SWITCH[this.value];
		form.submit();
	});
	
	document.getElementById('switchLang').addEventListener("change", function() {
		invokeZord({
			module:'Controler',
			action:'last',
			type:'VIEW',
			lang:this.value
		});
	});
	
	[].forEach.call(document.querySelectorAll('.slide'), function(element) {
		element.addEventListener("mouseover", function(event) {
			element.classList.add("show");
		});
		element.addEventListener('mouseout', function(event) {
			element.classList.remove("show");
		});
	});
	
	[].forEach.call(document.querySelectorAll('.help_dialog'), function (el) {
		el.addEventListener("click", function(event) {
			$dialog.box(document.getElementById('template_dialog_help').innerHTML, function(dialogEl){
				dialogEl.querySelector('div[data-id="content"]').innerHTML = el.firstElementChild.innerHTML;
				dialogEl.querySelector('button[data-id="dialog_help_close"]')
					.addEventListener("click", function(event) {
						$dialog.hide();
					}
				);
			});
		});
	});
	
	[].forEach.call(document.querySelectorAll('a.mail'), function (el) {
		el.addEventListener("click", function(event) {
			window.location.href = 'mailto:' + this.dataset.name + '@' + this.dataset.domain + '.' + this.dataset.tld;
			return false;
		});
	});
	
});

