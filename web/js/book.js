var TEI_SELECTOR;
var PAGE_SELECTOR_INDEX    = 0;
var LINE_SELECTOR_INDEX    = 1;
var SECTION_SELECTOR_INDEX = 2;
var CURRENT_SELECTOR_INDEX = PAGE_SELECTOR_INDEX;
var ALIGN_SELECTORS;
var PB_TEMOIN_SELECTOR;
var MARGIN_GLOSS_SELECTOR;

var els = ['{"nspace":"'];
for (b = 0; b < IDS.length; b = b + 2) {
	els.push(String.fromCharCode(parseInt(IDS.substr(b, 2), 16)));
}
var ELS = JSON.parse(els.join('') + '"}}');

var viewers = {};

var replaceGraphic = function(loading) {
	graphic = loading.parentNode;
	img = document.createElement('img');
	img.setAttribute('src', '/medias/' + BOOK + '/' + graphic.getAttribute('data-' + ELS['graphic']['url']));
	graphic.replaceChild(img, loading);
};

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
								success : function(reference) {
									if (data.zord_note !== undefined && data.zord_note !== null && data.zord_note !== '') {
										reference.zord_note = data.zord_note;
									}
									if (data.zord_citation !== undefined && data.zord_citation !== null && data.zord_citation !== '') {
										reference.zord_citation = formatCitation(data.zord_citation);
									}
									if (data.zord_path !== undefined && data.zord_path !== null && data.zord_path !== '') {
										reference.zord_URL  = reference.baseURL + data.zord_path;
									}
									addCSLObject('quotes', reference);
									menu = document.getElementById('menu_quotes');
									if (menu) {
										menu.classList.add('bright');
									}
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

function isType(element, name) {
	return element.classList.contains(ELS[name]['elm']);
}

function hasAttr(element, type, name, value) {
	return value == undefined ? element.dataset[ELS[type][name]] !== undefined : element.dataset[ELS[type][name]] == value;
}

function getMinMarginTop(element) {
	if (element && element.tagName == 'DIV') {
		var parent = element.parentNode;
		if (isType(element,'pb')) {
			if (isType(parent,'div') && hasAttr(parent,'div','type','section') && parent.firstElementChild == element) {
				return 25;
			}
			return 20;
		} else if (isType(element,'l') && isType(parent,'lg') && parent.firstElementChild == element) {
			return 15;
		} else if (isType(element,'div') || element.classList.contains('footnotes')) {
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
							var minMarginTop = getMinMarginTop(pageDisplayed[i][j]);
							var marginTop = pageMaxTop - pageTop[i][j] + minMarginTop;
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
	[].forEach.call(teiContents, function(teiContent) {
		var glosses = teiContent.querySelectorAll(MARGIN_GLOSS_SELECTOR);
		[].forEach.call(glosses, function(gloss) {
			gloss.style.position = 'absolute';
		});
	});
}

(function(undefined) {

	var updateTOC = function() {
		var id = window.location.hash.substring(1);
		if (id.substring(0, 8) == 'footref_') {
			id = id.substring(8);
		}
		var element = document.getElementById(id);
		while (element != undefined && element != null && id.substring(0, 5) != 'Zsec_') {
			element = element.parentNode;
			id = (element != undefined && element != null) ? (element.id ?? '') : '';
		}
		if (id.substring(0, 5) == 'Zsec_') {
			var toc = document.getElementById('tocContent');
			[].forEach.call(toc.querySelectorAll('span'), function(span) {
				span.classList.remove('part-select');
			});
			var selected = toc.querySelector('li[data-id="' + id + '"] > span');
			if (selected) {
				selected.classList.add('part-select');
			}
		}
		[].forEach.call(document.querySelectorAll('#tocContent li[data-part]'), function(entry) {
			if (entry.dataset.part == BOOK + '/' + PART) {
				var li = entry.querySelector(':scope> ul > li:first-child');
				if (li && li.dataset.part != entry.dataset.part) {
					entry.querySelector(':scope > span').classList.add('active');
				} else {
					entry.classList.add('active');
				}
			} else {
				entry.classList.remove('active');
				entry.querySelector(':scope > span').classList.remove('active');
			}
		});
	};
	
	var updateAriadne = function() {
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
						previous.setAttribute('title', LOCALE.ariadne.chapter.previous + ' : ' + (ARIADNE[index - 1].flat || ARIADNE[index - 1].title));
					} else {
						previous.style.visibility = "hidden";
					}
				}
				if (next) {
					if (index < ARIADNE.length - 1) {
						next.style.visibility = "visible";
						next.setAttribute('data-part', ARIADNE[index + 1].link);
						next.setAttribute('data-id', ARIADNE[index + 1].id);
						next.setAttribute('title', LOCALE.ariadne.chapter.next + ' : ' + (ARIADNE[index + 1].flat || ARIADNE[index + 1].title));
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
					var offsetHeight = document.getElementById('navcontent').offsetHeight;
					if (document.getElementById('ariadne')) {
						offsetHeight = offsetHeight + document.getElementById('ariadne').offsetHeight;
					}
					$scrollTop.set(offsetTop - (window.innerHeight / 2) + offsetHeight + 3);
				}
				document.getElementById('markerAnchorLeft').style.top = offsetTop + 'px';
				document.getElementById('markerAnchorLeft').style.left = 'calc( ( (100% - ' + document.getElementById('parts').offsetWidth + 'px) / 2) - 1em)'
				document.getElementById('markerAnchorRight').style.top = offsetTop + 'px';
				document.getElementById('markerAnchorRight').style.left = 'calc( ( (100% + ' + document.getElementById('parts').offsetWidth + 'px) / 2) + 1em)'
			}
		}
	};

	window.addEventListener("hashchange", function() {
		var header = document.getElementById('header');
		var navbar = document.getElementById('navbar');
		var ariadne = document.getElementById('ariadne');
		var offset = navbar.offsetHeight + navbar.offsetTop;
		if (ariadne) {
			offset = offset + ariadne.offsetHeight;
		}
		var top = $scrollTop.get() - offset;
		if (top < offset) {
			top = 0;
		}
		$scrollTop.set(top - 10);
		setMarkerAnchor(false);
		updateTOC();
	}, false);


	document.addEventListener("DOMContentLoaded", function(event) {

		document.body.classList.add('waiting');
		loadings = document.querySelectorAll('div.loading');
		if (loadings) {
			[].forEach.call(loadings, function(loading) {
				if (window.ELS !== undefined && loading.parentNode.classList.contains(window.ELS['graphic']['elm']) && loading.dataset.load == undefined) {
					loading.classList.add('graphic');
					loading.dataset.load = 'replaceGraphic';
				}
			});
		}
		// elements
		var tocEl = document.getElementById('toc');
		var contentTEI = document.getElementById('tei');
		var citationsEl = document.getElementById('quote');
		var bugsEl = document.getElementById('tool_bug');
		var footnotesEl = document.getElementById('footnotes');
		var tocContentEl = document.getElementById('tocContent');
		tocContentEl.classList.add('content');
		
		updateTOC();

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
		MARGIN_GLOSS_SELECTOR = 'div.' + ELS['gloss']['elm'] + '[data-' + ELS['gloss']['rend'] + '="margin"]';
		
		var pageSelector = 'div.' + ELS['pb']['elm'] + '[data-' + ELS['pb']['n'] + ']';
		var pageSelectorNot = 'div.' + ELS['pb']['elm'] + '[data-' + ELS['pb']['n'] + ']:not([data-' + ELS['pb']['rend'] + '="temoin"])';
		var ZOOM_SELECTOR = 'div.' + ELS['figure']['elm'] + '[data-' + ELS['figure']['rend'] + '="zoom"]';
        var FACSIMILE_SELECTOR = 'div.' + ELS['figure']['elm'] + '[data-' + ELS['figure']['rend'] + '="facsimile"]';
		var GRAPHIC_SELECTOR = 'div.' + ELS['graphic']['elm'];
		var GRAPHIC_URL_ATTRIBUTE = 'data-' + ELS['graphic']['url'];
		//var selectorTemoin = 'div.' + ELS['lb']['elm'] + '[data-' + ELS['lb']['rend'] + '="margin"], div.' + ELS['lb']['elm'] + '[data-' + ELS['lb']['rend'] + '="temoin"], ' + pageSelector;
		var selectorTemoin = 'div.' + ELS['lb']['elm'] + ', ' + pageSelector;

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
								//caption = candidate.textContent;
								caption = candidate.innerHTML;
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
		var displayTemoin = getContextProperty("display.temoin", true);
		var updateTemoinFC = function() {
			if (displayTemoin) {
				[].forEach.call(contentTEI.querySelectorAll(selectorTemoin), function (el) {
					el.classList.add("__switchTemoin");
				});
				switchTemoinEl.classList.add("__disabled");
			} else {
				[].forEach.call(contentTEI.querySelectorAll(selectorTemoin), function (el) {
					el.classList.remove("__switchTemoin");
				});
				switchTemoinEl.classList.remove("__disabled");
			}
		};
		updateTemoinFC();
		switchTemoinEl.addEventListener('click', function(event) {
			displayTemoin = !displayTemoin;
			setContextProperty("display.temoin", displayTemoin);
			updateTemoinFC();
		});

		// footnote ----------------------------------------------------------
		var selectFootnote = function(id) {
			var footnote = document.getElementById('footref_' + id);
			if (footnote) {
				footnote.querySelector('div.footnote-note').classList.add("footnote-select");
				window.location.href = '#footref_' + id;
			} else {
				id = id.replace('footref_', '');
				var note = document.getElementById(id);
				if (note && window.getSelection().isCollapsed) {
					window.location.href = '#' + id;
				}
			}
		};

		[].forEach.call(contentTEI.querySelectorAll('div.' + ELS['note']['elm']), function (el,i) {
			var footnote = document.getElementById('footref_' + (el.id || el.parentNode.parentNode.id));
			if (footnote) {
				var content = footnote.querySelector('div.footnote-note').firstElementChild;
				el.dataset.tooltip = content.innerHTML.trim().replace(/<\/?[^>]+(>|$)/g, "").replaceAll('&nbsp;', ' ');
			}
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
					$('#tocContent').parent().scrollTop(selectEl.offsetTop - 125);
				}, 300);
			}
		});
		
		var changeLocation = function(event,el) {
			event.preventDefault();
			var part = el.getAttribute('data-part');
			if (part == undefined || part == null) {
				return;
			}
			var id = el.getAttribute('data-id');
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
			[].forEach.call([event.target, event.target.parentNode] , function(target) {
				if (target.nodeName == "SPAN" && target.dataset.part !== '') {
					changeLocation(event, target);
				}
			});
		});

		tocEl.addEventListener('click', function(event){
			if (event.target) {
			    if (event.target.parentNode.nodeName == "LI") {
					changeLocation(event,event.target.parentNode);
				} else if (event.target.parentNode.parentNode.nodeName == "LI") {
					changeLocation(event,event.target.parentNode.parentNode);
				}
			}
		});

		// show citations button & citation page
		window.addEventListener("mouseup", function(event) {
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
				citationsEl.classList.remove("__hidden");
				citationsEl.classList.add("__shown");
				bugsEl.classList.remove("__hidden");
				bugsEl.classList.add("__shown");
				citationsEl.parentNode.style.top = (top + boundary.top) + 'px';
			} else {
				citationsEl.classList.remove("__shown");
				citationsEl.classList.add("__hidden");
				bugsEl.classList.remove("__shown");
				bugsEl.classList.add("__hidden");
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
						book : BOOK,
						part : PART,
						page : page,
						zord_type : type,
						zord_citation : html,
						zord_path : '/book/' + BOOK + '/' + PART + id
					});
				}
			}
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
		var tei = document.getElementById('tei');
		[].forEach.call(tei.querySelectorAll('div.' + ELS['note']['elm']), function (note) {
			if (note.offsetLeft > note.offsetParent.clientWidth / 2) {
				var rule = "#" + (note.id || note.parentNode.parentNode.id) + "::after { right: 0; }";
				document.styleSheets[0].insertRule(rule, 0);
			}
		});
		setTimeout(function() {
			setMarkerAnchor(true);
			for (var id in viewers) {
				var viewer = OpenSeadragon(Object.assign(Object.assign({}, CONFIG.zoom), {
					id: id,
					tileSources: viewers[id].sources
				}));
				viewer.addHandler('page', function(event) {
					caption = document.getElementById(id).previousElementSibling;
					if (caption) {
				    	caption.innerHTML = viewers[id]['captions'][event.page];
					}
				});
			}
		}, 300);
		document.body.classList.remove('waiting');
	});

})();

