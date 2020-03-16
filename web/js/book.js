var TEI_SELECTOR;
var PAGE_SELECTOR_INDEX    = 0;
var LINE_SELECTOR_INDEX    = 1;
var SECTION_SELECTOR_INDEX = 2;
var CURRENT_SELECTOR_INDEX = PAGE_SELECTOR_INDEX;
var ALIGN_SELECTORS;
var PB_TEMOIN_SELECTOR;

var viewers = {};

window.$quote = {	
	add : function(data) {
		var type = data.zord_type;
		$dialog.box(document.getElementById('template_dialog_' + type).innerHTML, function(dialog) {
			var comment = dialog.querySelector('textarea[data-id="dialog_' + type + '_comment"]');
			dialog.querySelector('button[data-id="dialog_' + type + '_ok"]').addEventListener("click", function(event) {
				data.zord_note = comment.value;
				switch (type) {
					case 'citation': {
						invokeZord(
							{
								module : 'Book',
								action : 'reference',
								isbn : data.book,
								page : data.page,
								callback : function(reference) {
									if (data.zord_note != undefined && data.zord_note != null && data.zord_note != '') {
										reference.zord_note = data.zord_note;
									}
									if (data.zord_citation != undefined && data.zord_citation != null && data.zord_citation != '') {
										reference.zord_citation = formatCitation(data.zord_citation);
									}
									if (data.zord_url != undefined && data.zord_url != null && data.zord_url != '') {
										reference.zord_URL = data.zord_url;
									}
									addCSLObject('quotes', reference);
								}
							}
						);
						break;
					}
					case 'bug': {
						invokeZord(
							{
								module : 'Book',
								action : 'notify',
								bug : JSON.stringify(data)
							}
						);
						break;
					}
				}
				$dialog.waitMsg(document.getElementById('template_dialog_' + type + '_valid').innerHTML);
			});
			dialog.querySelector('button[data-id="dialog_' + type + '_cancel"]').addEventListener("click", function(event) {
				$dialog.hide();
			});
		});
	},
};

function formatCitation(citation) {
	var div = document.createElement('div');
	div.innerHTML = citation;
	var frame = function(element, start, end) {
		element.insertAdjacentHTML('afterbegin', '¡§¡'  + start + '¡¿¡');
		element.insertAdjacentHTML('beforeend',  '¡§¡/' + end   + '¡¿¡');
	};
	[].forEach.call(div.querySelectorAll('div[class]'), function (element) {
		switch (element.getAttribute('class')) {
			case ELS['note']['elm']: {
				var n = element.getAttribute('data-n');
				if (n != undefined) {
					element.insertAdjacentHTML('beforebegin', '¡§¡sup¡¿¡' + n + '¡§¡/sup¡¿¡');
				}
				div.removeChild(element);
				break;
			}
			case ELS['ref']['elm']: {
				frame(element,'sup','sup');
				break;
			}
			case ELS['emph']['elm']: {
				frame(element,'em','em');
				break;
			}
			case ELS['p']['elm']: {
				element.insertAdjacentHTML('afterend', '¡§¡br/¡¿¡');
				break;
			}
			case ELS['head']['elm']: {
				frame(element,'p style="font-size:1.3em;"','p');
				break;
			}
			case ELS['hi']['elm']: {
				var rend = element.getAttribute('data-' + ELS['hi']['rend']);
				if (rend != undefined) {
					var bounds = {
						sup       :['sub','sub'],
						b         :['b','b'],
						sc        :['span style="font-variant:small-caps;"','span'],
						n         :['span','span'],
						small     :['span style="font-size:0.8em;"','span'],
						i         :['i','i'],
						underline :['span style="text-decoration:underline;"','span'],
						big       :['span style="font-size:1.2em;"','span']
					};
					frame(element, bounds[rend][0], bounds[rend][1]);							
				}
				break;
			}
		}
	});
	return div.innerHTML.replace(/<\/?[^>]+>/g, '').replace(/¡§¡/g, '<').replace(/¡¿¡/g, '>');
}

function getMinMarginTop(element) {
	if (element && element.tagName == 'DIV') {
		if (element.getAttribute('class') == ELS['pb']['elm']) {
			var parent = element.parentNode;
			if (parent.getAttribute('class') == ELS['div']['elm'] && parent.getAttribute('data-' + ELS['div']['type']) == 'section' && parent.firstElementChild == element) {
				return 25;
			}
			return 20;
		} else if (element.getAttribute('class') == ELS['l']['elm']) {
			var parent = element.parentNode;
			if (parent.getAttribute('class') == ELS['lg']['elm'] && parent.firstElementChild == element) {
				return 15;
			}
			return 0;
		} else if (element.getAttribute('class') == ELS['div']['elm']) {
			return 25;
		} else if (element.getAttribute('class') == 'footnotes') {
			return 25;
		}
	}
	return 0;
}

function switchVariant(button) {
	var teiContent = document.getElementById(button.name).parentNode.parentNode;
	var newStatus = teiContent.style.display === 'none' ? 'on' : 'off';
	teiContent.style.display = newStatus === 'off' ? 'none' : 'block';
	button.className = newStatus;
	displayTEI(CURRENT_SELECTOR_INDEX);
}

function switchSelector(select) {
	CURRENT_SELECTOR_INDEX = select.value;
	displayTEI(CURRENT_SELECTOR_INDEX);
}

function searchInBook() {
	var query = document.getElementById('queryInput');
	if (query && query.checkValidity()) {
		var searchCriteria = {
			query:query.value,
			scope:'corpus',
			filters:{
				contentType:[0,1],
				ean:[BOOK],				
			},
			context:CONTEXT,
			start:0,
			rows:1000
		};
		searchHistory = getContextProperty('search.history', []);
		searchHistory.push(searchCriteria);
		searchIndex = searchHistory.length;
		setContextProperty('search.index',    searchIndex);
		setContextProperty('search.history',  searchHistory);
		setContextProperty('search.criteria', searchCriteria);
		search(searchCriteria, undefined, false);
	}
}

function displayTEI(selectorIndex) {
	var contentTEI = document.getElementById('parts');
	var teiContents = contentTEI.querySelectorAll(TEI_SELECTOR);
	var nbDisplayed = 0;
	var teiDisplayed = [];
	var pageDisplayed = [];
	var pageTop = [];
	var pageMaxNumber = 0;
	[].forEach.call(teiContents, function(teiContent,i) {
		var pages = teiContent.querySelectorAll(ALIGN_SELECTORS[selectorIndex]);
		if (teiContent.style.display != 'none') {
			teiDisplayed[nbDisplayed] = teiContent;
			pageDisplayed[nbDisplayed] = pages;
			pageTop[nbDisplayed] = [];
			nbDisplayed++;
		}
	});
	[].forEach.call(teiContents, function(teiContent,i) {
		[].forEach.call(ALIGN_SELECTORS, function(selector,i) {
			var elements = teiContent.querySelectorAll(selector);
			[].forEach.call(elements, function(element,j) {
				element.setAttribute('data-visavis', nbDisplayed > 1 ? 'noopVariant' : 'noopAlone');
				element.removeAttribute('style');
			});
		});
	});
	
	//var n = 1;
	var n = 2 * (nbDisplayed - 1) / nbDisplayed;
 	var m = Math.pow(nbDisplayed, n);	
/*
 	var unit = 'px';
	var minTeiWidth = 300;
	var maxTeiWidth = 750;
	var spaceBetween = 35;
*/
 	var unit = 'em';
	var minTeiWidth = 20;
	var maxTeiWidth = 50;
	var spaceBetween = 2;
	var teiWidth = maxTeiWidth - ((maxTeiWidth - minTeiWidth) * ((m - 1) / m));
	var parentWidth = (teiWidth * nbDisplayed) + (spaceBetween * (nbDisplayed - 1));
	contentTEI.parentNode.style.width = parentWidth + unit;

	[].forEach.call(teiDisplayed, function(teiContent,i) {
		teiContent.style.width = teiWidth + unit;
		var temoins = teiContent.querySelectorAll(PB_TEMOIN_SELECTOR);
		[].forEach.call(temoins, function(temoin,j) {
			temoin.setAttribute('base', (teiContent.offsetLeft - 30) + 'px');
		});
	});
	[].forEach.call(pageDisplayed, function(pages,i) {
		[].forEach.call(pages, function (page,j) {
			page.setAttribute('data-visavis', nbDisplayed > 1 ? 'variant' : 'alone');
		});
		pageMaxNumber = pages.length > pageMaxNumber ? pages.length : pageMaxNumber;
	});
	if (nbDisplayed > 1) {
		//setTimeout(function() {
			[].forEach.call(pageDisplayed, function(pages,i) {
				[].forEach.call(pages, function (page,j) {
					pageTop[i][j] = page.getBoundingClientRect().top;
				});
			});
			for (j = 0 ; j < pageMaxNumber ; j++) {
				var pageMaxTop = -Number.MAX_VALUE;
				for (i = 0 ; i < nbDisplayed ; i++) {
					if (pageDisplayed[i][j]) {
						pageMaxTop = pageTop[i][j] > pageMaxTop ? pageTop[i][j] : pageMaxTop;
					}
				}
				for (i = 0 ; i < nbDisplayed ; i++) {
					if (pageDisplayed[i][j]) {
						if (pageTop[i][j] < pageMaxTop) {
							var marginTop = (pageMaxTop - pageTop[i][j] + getMinMarginTop(pageDisplayed[i][j]));
							pageDisplayed[i][j].style.marginTop = marginTop + 'px';
							for (k = j + 1 ; k < pageMaxNumber ; k++) {
								if (pageDisplayed[i][k]) {
									pageTop[i][k] = pageTop[i][k] + pageMaxTop - pageTop[i][j];
								}
							}
						}
					}				
				}
			}
		//}, 500);
	}		
}

(function(undefined) {

	var tocContentEl = null;
	
	var updateAriadne = function() {
		var id = window.location.hash.substring(1);
		var toc = document.getElementById('tocContent');
		[].forEach.call(toc.querySelectorAll('span'), function(span) {
			span.classList.remove('part-select');
		});
		var selected = toc.querySelector('li[data-id="' + id + '"] > span');
		if (selected) {
			selected.classList.add('part-select');
		}
		var ariadne = document.getElementById('ariadne');
		var current = ariadne.querySelector('span.ariadne-current')
		var previous = ariadne.querySelector('span.ariadne-previous')
		var next = ariadne.querySelector('span.ariadne-next');
		[].forEach.call(ARIADNE, function(step, index) {
			if (step.id == id) {
				if (current) {
					if (index > 0) {
						current.style.display = "inline";
						current.setAttribute('data-part', step.link);
						current.setAttribute('data-id', step.id);
						current.innerHTML = step.title;
					} else {
						current.style.display = "none";
					}
				}
				if (previous) {
					if (index > 0) {
						previous.style.visibility = "visible";
						previous.setAttribute('data-part', ARIADNE[index - 1].link);
						previous.setAttribute('data-id', ARIADNE[index - 1].id);
						previous.setAttribute('title', PORTAL.locales[LANG].ariadne.chapter.previous + ' : ' + ARIADNE[index - 1].title);
					} else {
						previous.style.visibility = "hidden";
					}
				}
				if (next) {
					if (index < ARIADNE.length - 1) {
						next.style.visibility = "visible";
						next.setAttribute('data-part', ARIADNE[index + 1].link);
						next.setAttribute('data-id', ARIADNE[index + 1].id);
						next.setAttribute('title', PORTAL.locales[LANG].ariadne.chapter.next + ' : ' + ARIADNE[index + 1].title);
					} else {
						next.style.visibility = "hidden";
					}
				}
			}
		});
	};

	var setMarkerAnchor = function(toScroll) {
		var hash = window.location.hash.substring(1);
		if (hash) {
			var el = document.getElementById(hash);
			if (el) {
				var offsetTop = el.offsetTop;
				while (el.offsetParent !== undefined && el.offsetParent !== null) {
					el = el.offsetParent;
					offsetTop = offsetTop + el.offsetTop;
				}
				if (toScroll) {
					$scrollTop.set(offsetTop - (window.innerHeight / 2) + document.getElementById('navcontent').offsetHeight);
				}
				document.getElementById('markerAnchorLeft').style.top = offsetTop + 'px';
				document.getElementById('markerAnchorLeft').style.left = 'calc( ( (100% - ' + document.getElementById('parts').offsetWidth + 'px) / 2) - 20px)'
				document.getElementById('markerAnchorRight').style.top = offsetTop + 'px';
				document.getElementById('markerAnchorRight').style.left = 'calc( ( (100% + ' + document.getElementById('parts').offsetWidth + 'px) / 2) + 20px)'
			}
		}
	};

	window.addEventListener("hashchange", function() {
		var header = document.getElementById('header');
		var top = $scrollTop.get() - header.offsetHeight;
		if (top < header.offsetHeight) {
			top = header.offsetHeight;
		}
		$scrollTop.set(top);
		setMarkerAnchor(false);
		updateAriadne();
	}, false);


	document.addEventListener("DOMContentLoaded", function(event) {

		var els = ['{"nspace":"'];
		for (b = 0; b < IDS.length; b = b + 2) {
			els.push(String.fromCharCode(parseInt(IDS.substr(b, 2), 16)));
		}
		window.ELS = JSON.parse(els.join('') + '"}}');

		// elements
		var tocEl = document.getElementById('toc');
		var tocContentEl = document.getElementById('tocContent');
		tocContentEl.classList.add('content');
		var contentTEI = document.getElementById('tei');
		var citationsEl = document.getElementById('quote');
		var bugsEl = document.getElementById('tool_bug');
		var footnotesEl = document.getElementById('footnotes');
		
		updateAriadne();

		// vars
		var page = 1;
		var pageOld = 0;

		TEI_SELECTOR       = 'div.' + ELS['tei']['elm'];
		FOOTNOTES_SELECTOR = 'div.footnotes';
		PAGE_SELECTOR      = 'div.' + ELS['pb']['elm']  + '[data-' + ELS['pb']['n']     + ']:not([data-' + ELS['pb']['rend'] + '="temoin"])';
		LINE_SELECTOR      = 'div.' + ELS['l']['elm']   + '[data-' + ELS['l']['rend']   + '="margin"]';
		SECTION_SELECTOR   = 'div.' + ELS['div']['elm'] + '[data-' + ELS['div']['type'] + '="section"]';
		ALIGN_SELECTORS    = [
			PAGE_SELECTOR    + ',' + FOOTNOTES_SELECTOR,
			LINE_SELECTOR    + ',' + FOOTNOTES_SELECTOR,
			SECTION_SELECTOR + ',' + FOOTNOTES_SELECTOR
		];
		
		PB_TEMOIN_SELECTOR = 'div.' + ELS['pb']['elm'] + '[data-' + ELS['pb']['n'] + '][data-' + ELS['pb']['rend'] + '="temoin"]';
		
		var pageSelector = 'div.' + ELS['pb']['elm'] + '[data-' + ELS['pb']['n'] + ']';
		var pageSelectorNot = 'div.' + ELS['pb']['elm'] + '[data-' + ELS['pb']['n'] + ']:not([data-' + ELS['pb']['rend'] + '="temoin"])';
		var ZOOM_SELECTOR = 'div.' + ELS['figure']['elm'] + '[data-' + ELS['figure']['rend'] + '="zoom"]';
        var FACSIMILE_SELECTOR = 'div.' + ELS['figure']['elm'] + '[data-' + ELS['figure']['rend'] + '="facsimile"]';
		var GRAPHIC_SELECTOR = 'div.' + ELS['graphic']['elm'];
		var GRAPHIC_URL_ATTRIBUTE = 'data-' + ELS['graphic']['url'];
		var selectorTemoin = 'div.' + ELS['lb']['elm'] + '[data-' + ELS['lb']['rend'] + '="margin"], div.' + ELS['lb']['elm'] + '[data-' + ELS['lb']['rend'] + '="temoin"], ' + pageSelector;

		// ------------------------------------------------------------------
		// DISPLAY ASSIGN

		var teiContents = contentTEI.querySelectorAll(TEI_SELECTOR);
		[].forEach.call(teiContents, function(teiContent,i) {
			teiContent.style.display = 'none';
		});
		teiContents[0].style.display = 'block';
		if (teiContents.length > 1) {
			teiContents[1].style.display = 'block';
		}
		
		var first = teiContents[0].firstElementChild.firstElementChild;
		if (first.dataset.rend !== undefined) {
			switch (first.dataset.rend) {
				case 'page': {
					CURRENT_SELECTOR_INDEX = PAGE_SELECTOR_INDEX;
					break;
				} 
				case 'line': {
					CURRENT_SELECTOR_INDEX = LINE_SELECTOR_INDEX;
					break;
				} 
				case 'section': {
					CURRENT_SELECTOR_INDEX = SECTION_SELECTOR_INDEX;
					break;
				} 
			}
		}
		
		var zooms = contentTEI.querySelectorAll(ZOOM_SELECTOR + ',' + FACSIMILE_SELECTOR);
		if (zooms.length > 0) {
			[].forEach.call(zooms, function(zoom, index) {
				var sources = [];
				var captions = [];
				[].forEach.call(zoom.querySelectorAll(GRAPHIC_SELECTOR), function (element) {
					if (element.hasAttribute('data-zoom')) {
						sources.push(element.getAttribute('data-zoom'));
						caption = '';
						[].forEach.call([element.firstElementChild, element.nextElementSibling, element.previousElementSibling], function(candidate) {
							if (candidate !== null && (candidate.classList.contains('desc') || candidate.classList.contains('head'))) {
								caption = candidate.textContent;
							}
						});
						captions.push(caption);
					}
				});
				[].forEach.call(zoom.querySelectorAll('*'), function (element) {
					element.remove();
				});
				var id = '__zoom' + index;
				zoom.setAttribute('id', id);
				center = document.createElement('div');
				center.classList.add('caption');
				caption = document.createElement('span');
				caption.innerHTML = captions[0];
				center.appendChild(caption);
				zoom.parentNode.insertBefore(center, zoom);
				viewers[id] = {
					sources: sources,
					captions: captions
				}
			});
		}

		// switchTemoin
		var switchTemoinEl = document.getElementById('switchTemoin');
		var switchTemoin = getSessionProperty("switch.temoin", true);
		var switchTemoinFC = function() {
			if (switchTemoin) {
				[].forEach.call(contentTEI.querySelectorAll(selectorTemoin), function (el,i) {
					el.classList.add("__switchTemoin");
				});
				switchTemoinEl.classList.add("__disabled");
			} else {
				[].forEach.call(contentTEI.querySelectorAll(selectorTemoin), function (el,i) {
					el.classList.remove("__switchTemoin");
				});
				switchTemoinEl.classList.remove("__disabled");
			}
			switchTemoin = !switchTemoin;
			setSessionProperty("switch.temoin", switchTemoin);
		};
		switchTemoinFC(switchTemoin);
		switchTemoinEl.addEventListener('click', function(event) {
			switchTemoinFC(false);
		});

		// footnote ----------------------------------------------------------
		var selectFootnote = function(id) {
			var footnote = document.getElementById('footref_' + id);
			if (footnote) {
				footnote.querySelector('div.footnote-note').classList.add("footnote-select");
				window.location.href = '#footref_' + id;
			} else {
				var note = document.getElementById(id.replace('footref_', ''));
				if (note) {
					window.location.href = '#' + id.replace('footref_', '');
				}
			}
		};

		[].forEach.call(contentTEI.querySelectorAll('div.' + ELS['note']['elm']), function (el,i) {
			el.addEventListener('click', function(event) {
				[].forEach.call(contentTEI.querySelectorAll('div.footnote-note'), function (footnote,i) {
					footnote.classList.remove("footnote-select");
				});
				if (el.id) {
					selectFootnote(el.id);
				} else {
					selectFootnote(el.parentNode.parentNode.id);
				}
			});
		});

		// footnote counter
		[].forEach.call(contentTEI.querySelectorAll('div.footnote-counter'), function (counter, i) {
			counter.addEventListener('click', function(event) {
				window.location.href = '#' + counter.getAttribute('data-id');
				[].forEach.call(contentTEI.querySelectorAll('div.footnote-note'), function (note, i) {
					note.classList.remove("footnote-select");
				});
			});
		});

		// -------------------------------------------------------------------
		// EVENTS

		// toc
		tocEl.addEventListener('mouseenter', function(event) {
			var selectEl = tocEl.querySelector('.part-select');
			if (selectEl) {
				setTimeout(function() {
					tocContentEl.scrollTop = selectEl.offsetTop - 125;
				}, 300);
			}
		});

		var changeLocation = function(event,el) {
			event.preventDefault();
			var id = el.getAttribute('data-id');
			var part = el.getAttribute('data-part');
			var hash = window.location.hash.substring(1);
			if (id != undefined) {
				part += '#' + id;
			}
			window.location.href = BASEURL['zord'] + '/book/' + part;
			if (hash == id) {
				var event = document.createEvent("HTMLEvents");
				event.initEvent("hashchange", true, false);
				document.body.dispatchEvent(event);
			}
		};

		[].forEach.call(document.getElementById('ariadne').querySelectorAll('span.visavis-variants > button'), function(button) {
			button.addEventListener('click', function(event) {
				switchVariant(event.target);
			});
		});
		
		document.getElementById('ariadne').addEventListener('click', function(event) {
			if (event.target && event.target.nodeName == "SPAN") {
				changeLocation(event, event.target);
			}
		});

		tocEl.addEventListener('click', function(event){
			if (event.target && event.target.parentNode.nodeName == "LI") {
				changeLocation(event,event.target.parentNode);
			}
		});

		// show citations button & citation page
		contentTEI.addEventListener("mouseup", function(event) {
			var selection = window.getSelection();
			if (!selection.isCollapsed) {
				var margeB = 60;
				var node = selection.anchorNode,
				startNode = (node && node.nodeType === 3 ? node.parentNode : node);
				var boundary = selection.getRangeAt(0).getClientRects();
				boundary = boundary[0];
				var top  = window.pageYOffset || document.documentElement.scrollTop;
				var teiRects = document.getElementById('tei').getClientRects();
				var teiRectsW = teiRects[0].width;
				if (teiRectsW <= 580) {
					margeB = 20;
				}
				var left = (document.getElementById('tei').offsetLeft + teiRectsW) - margeB;
				citationsEl.classList.remove("__disabled");
				citationsEl.classList.add("__activated");
				bugsEl.classList.remove("__disabled");
				bugsEl.classList.add("__activated");
			} else {
				citationsEl.classList.remove("__activated");
				citationsEl.classList.add("__disabled");
				bugsEl.classList.remove("__activated");
				bugsEl.classList.add("__disabled");
				var nodeName = event.target.nodeName.toLowerCase();
				if (nodeName == 'div' && event.target.getAttribute('class') == ELS['pb']['elm']) {
					var attrN = event.target.getAttribute('data-' + ELS['pb']['n']);
					if (attrN != undefined) {
						var temoin = event.target.getAttribute('data-' + ELS['pb']['rend']);
						if (temoin == undefined || temoin != 'temoin') {
							$quote.add({
								zord_type : 'page',
								page : attrN,
								book : BOOK,
								zord_url : BASEURL['zord'] + '/book/' + BOOK + '/' + PART + '#' + event.target.id
							});
						}
					}
				}
			}
		});

		// citations button
		document.getElementById('tool_citation').addEventListener("click", function(event) {
			$quote.add({
				zord_type : 'citation',
				zord_citation : '',
				page : null,
				book : BOOK,
				zord_url : BASEURL['zord'] + '/book/' + BOOK
			});
		});
		
		var insertCitation = function(type) {
			var selection = window.getSelection();
			if (!selection.isCollapsed) {
				var html = '';
				if (selection.rangeCount) {
					var container = document.createElement('div');
					for (i = 0, len = selection.rangeCount; i < len; i += 1) {
						container.appendChild(selection.getRangeAt(i).cloneContents());
					}
					html = container.innerHTML;
					var parent = selection.anchorNode.parentNode;
					while (parent.getAttribute('class') != ELS['tei']['elm']) {
						parent = parent.parentNode;
					}
					// get pages number and id
					var top = selection.getRangeAt(0).getBoundingClientRect().top + $scrollTop.get();
					var pageBefore = null;
					var pageBreaks = parent.querySelectorAll(pageSelectorNot);
					[].forEach.call(pageBreaks, function (page,i) {
						if (page.offsetTop <= top) {
							pageBefore = page;
						}
					});
					var page = pageBefore ? pageBefore.getAttribute('data-n') : '';
					var id = pageBefore ? ('#' + pageBefore.id) : '';
					$quote.add({
						zord_type : type,
						zord_citation : html,
						page : page,
						book : BOOK,
						zord_url : BASEURL['zord'] + '/book/' + BOOK + '/' + PART + id
					});
				}
			}
			citationsEl.classList.add("__disabled");
			citationsEl.classList.remove("__activated");
			bugsEl.classList.add("__disabled");
			bugsEl.classList.remove("__activated");
			selection.removeAllRanges();
		};
		
		document.getElementById('quote').addEventListener("click", function() {
			insertCitation('citation');
		});
		
		document.getElementById('tool_bug').addEventListener('click',function() {
			insertCitation('bug');
		});
		
		document.getElementById('queryButton').addEventListener('click', function(event) {
			searchInBook();
		});

		// search in book
		document.getElementById('queryInput').addEventListener('keypress', function(event) {
			var key = event.which || event.keyCode;
			if (key === 13) {
				searchInBook();
			}
		});
	});

	window.addEventListener("load", function(event) {
		displayTEI(CURRENT_SELECTOR_INDEX);
		setTimeout(function() {
			setMarkerAnchor(true);
			for (var id in viewers) {
				var viewer = OpenSeadragon({
					id: id,
					prefixUrl: '/library/img/OpenSeadragon/',
					showNavigator: true,
					showRotationControl: true,
					sequenceMode: true,
					preload: true,
					tileSources: viewers[id]['sources']
				});
				viewer.addHandler('page', function(event) {
					caption = document.getElementById(id).previousElementSibling;
					if (caption) {
				    	caption.innerHTML = viewers[id]['captions'][event.page];
					}
				});
			}
			loadings = document.querySelectorAll('div.loading');
			if (loadings) {
				[].forEach.call(loadings, function(loading) {
					graphic = loading.parentNode;
					img = document.createElement('img');
					img.setAttribute('src', '/medias/' + BOOK + '/' + graphic.getAttribute('data-' + ELS['graphic']['url']));
					graphic.replaceChild(img, loading);
				});
			}
		}, 300);
	});

})();

