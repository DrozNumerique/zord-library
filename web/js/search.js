var searchRefine   = getContextProperty('search.refine', false);
var searchHistory  = getContextProperty('search.history', []);
var searchIndex    = getContextProperty('search.index', 0);
var searchScope    = getContextProperty('search.scope', undefined);
var searchCriteria = getContextProperty('search.criteria', undefined);

function saveHistory() {
	setContextProperty('search.history', searchHistory);
	setContextProperty('search.index',   searchIndex);
	refreshHistory();
}

function updateStyle(select, id) {
	style = select.value;
	setCSLParams({
		'lang':  LANG,
		'style': style
	});
	setContextProperty('search.style', style);
	dependent = document.getElementById(id);
	if (dependent !== undefined && dependent !== null) {
		[].forEach.call(dependent.options, function(option) {
			if (option.value == style) {
				option.selected = true;
			}
		});
		$('#' + id).trigger('chosen:updated');
		updateCorpus(null, null, true);
	}
}

function refreshHistory(only) {
	var display = document.getElementById('searchHistoryDisplay');
	var style = document.getElementById('historyStyles');
	if (only == undefined || only == null || !only) {
		updateStyle(style, 'corpusStyles');
	}
	if (searchIndex > 0 && searchIndex <= searchHistory.length) {
		invokeZord({
			module:'Book',
			action:'criteria',
			criteria:JSON.stringify(searchHistory[searchIndex - 1]),
			success:function(result) {
				display.innerHTML = '';
				var index = document.createElement('div');
				index.appendChild(document.createTextNode(searchIndex + '/' + searchHistory.length));
				display.appendChild(index);
				if (result.query) {
					query = document.createElement('li');
					query.appendChild(document.createTextNode(result.query));
					display.appendChild(query);
				}
				if (result.results) {
					var results = document.createElement('li');
					results.appendChild(document.createTextNode(result.results));
					display.appendChild(results);
				}
				if (result.include) {
					var include = document.createElement('li');
					include.appendChild(document.createTextNode(result.include));
					display.appendChild(include);
				}
				if (result.source) {
					var source = document.createElement('li');
					source.appendChild(document.createTextNode(result.source));
					display.appendChild(source);
				}
				if (result.books && result.books.length > 0) {
					var books = document.createElement('li');
					display.appendChild(books);
					var biblio = document.createElement('ul');
					[].forEach.call(result.books, function(text, index) {
						if (index == 0) {
							books.appendChild(document.createTextNode(text));
						} else {
							if (index == 1) {
								books.appendChild(biblio);
							}
							var cslObjects = getCSLObjects('history');
							var reference = null;
							if (cslObjects) {
								for (var id in cslObjects) {
									if (cslObjects[id].ean == text) {
										reference = cslObjects[id];
									}
								}
							}
							if (reference == null) {
								$.get(
									BASEURL['zord'] + '/Book/reference',
								    {isbn: text},
								    function(result) {
								    	reference = result;
										addCSLObject('history', reference);
								    }
								);
							}
							var entry = document.createElement('li');
							entry.innerHTML = getBiblio('history', reference.id);
							biblio.appendChild(entry);
						}
					});
				}
				if (result.facets) {
					if (result.operator) {
						var operator = document.createElement('li');
						operator.appendChild(document.createTextNode(result.operator));
						display.appendChild(operator);
					}
					for (var name in result.facets) {
						if (result.facets[name].length > 0) {
							var label = document.createElement('li');
							display.appendChild(label);
							var list = document.createElement('ul');
							[].forEach.call(result.facets[name], function(text, index) {
								if (index == 0) {
									label.appendChild(document.createTextNode(text));
									label.appendChild(list);
								} else {
									entry = document.createElement('li');
									entry.appendChild(document.createTextNode(text));
									list.appendChild(entry);
								}
							});
						}
					}
				}
			}
		});
	} else {
		display.innerHTML = '';
	}
}

function getYear(name) {
	var year = document.getElementById(name).value;
	if (!/^([1-2]\d{3,})$/.test(year)) {
		year = '';
	}
    return year;
}

function getSelected(select) {
	var selected = [];
	if (select) {
		if (select.options) {
			[].forEach.call(select.options, function(option) {
				if (option.selected) {
					selected.push(option.value || option.text);
				}
			});
		}
	}
	return selected;
}

function getCriteria() {
	var query = document.getElementById('queryInput').value;
	var operator = CONFIG.default.search.operator;
	[].forEach.call(['AND','OR'], function(value) {
		radio = document.getElementById('search_operator_' + value);
		if (radio.checked) {
			operator = value;
		}
	});
	var filters = {};
	if (document.getElementById('searchInIndex').checked) {
		filters['contentType'] = [0,1];
	} else {
		filters['contentType'] = 0;
	}
	if (searchScope == 'corpus') {
		var books = [];
		[].forEach.call(document.getElementById('books').querySelectorAll('li'), function(entry) {
			books.push(entry.getAttribute('data-isbn'));
		});
		filters['ean'] = books;
	}
	if (searchScope == 'facets') {
		[].forEach.call(document.querySelectorAll('select.facet'), function(select) {
			filters[select.id] = getSelected(select);
		});
	}
	filters['source'] = {
		from:getYear('search_source_from'),
		to:getYear('search_source_to')
	}
	var rows = Number(document.getElementById('searchSize').value);
	searchCriteria = {
		query:query,
		scope:searchScope,
		filters:filters,
		operator:operator,
		context:CONTEXT,
		start:0,
		rows:rows
	};
}

function updateSelectedCorpus(books) {
	var selected = document.getElementById('selected');
	var full = document.getElementById('full');
	var remove = document.getElementById('remove');
	if (full !== null && remove !== null) {
		[].forEach.call(document.getElementById('titles').options, function(option) {
			option.selected = false;
		});
		$('#titles').trigger('chosen:updated');
		if (books.length == 0) {
			selected.classList.add('empty');
			full.style.display = full.parentNode.firstElementChild.style.display;
			remove.style.display = 'none';
		} else {
			selected.classList.remove('empty');
			full.style.display = 'none';
			remove.style.display = remove.parentNode.firstElementChild.style.display;
		}
	}
}

function updateCorpus(event, params, only) {
	var style = document.getElementById('corpusStyles');
	var books = document.getElementById('books');
	if (style !== null && books !== null) {
		if (only == undefined || only == null || !only) {
			updateStyle(style, 'historyStyles');
		}
		var list = books.querySelectorAll('li[data-isbn]');
		updateSelectedCorpus(list);
		refreshBiblio('corpus',	list);
	}
}
	
function removeCorpus(books, results, isbn) {
	if (results) {
		var search = results.querySelector('td.search[data-isbn="' + isbn + '"]');
		if (search) {
			search.classList.remove('selected');
		}
	}
	var entry = books.querySelector('li[data-isbn="' + isbn + '"]');
	if (entry) {
		removeCSLObject('corpus', entry.getAttribute('data-ref'));
		books.removeChild(entry);
		updateSelectedCorpus(books.querySelectorAll('li[data-isbn]'));
	}
}
	
function addCorpus(books, results, isbn, reference) {
	var entry = books.querySelector('li[data-isbn="' + isbn + '"]');
	if (entry) {
		return;
	}
	if (results) {
		var search = results.querySelector('td.search[data-isbn="' + isbn + '"]');
		if (search) {
			search.classList.add('selected');
		}
	}
	var entry = document.createElement('li');
	entry.setAttribute('data-isbn', isbn);
	entry.addEventListener('click', function(event) {
		removeCorpus(books, results, entry.getAttribute('data-isbn'));
	});
	books.appendChild(entry);
	updateSelectedCorpus(books.querySelectorAll('li[data-isbn]'));
	if (reference !== undefined && reference !== null) {
		setBiblio('corpus', entry, reference);
	} else {
		invokeZord({
			module:'Book',
			action:'reference',
			isbn:isbn,
			success:function(reference) {
				addCSLObject('corpus', reference);
				setBiblio('corpus', entry, reference);
			}
		});
	}
}

function updateResults(start, rows) {
	searchCriteria.start = start;
	searchCriteria.rows = rows;
	setContextProperty('search.criteria', searchCriteria);
	searchHistory.push(searchCriteria);
	searchIndex = searchHistory.length;
	saveHistory();
	search(searchCriteria, function(html) {
		var nodes = $.parseHTML(html);
		var results = null;
		$.each(nodes, function(index, node) {
			if (node.nodeName == 'DIV' && node.classList.contains('results')) {
				results = node;
			}
		});
		if (results) {
	 		var popup = results.classList.contains('popup');
			if (popup && document.querySelector('div.fancybox-container') !== null) {
				$.fancybox.close();
			}
			var parent = document.getElementById('searchResults');
			if (parent) {
				parent.replaceChild(results, parent.firstElementChild);
				if (popup) {
					popupResults();
				} else {
					dressResults(results);
					dressSortingToggles(results);
				}
			}
			return results;
		}
	});
}

var dressResults = function(results) {
	showPanel('search', true);
	var books = document.getElementById('books');
	if (books) {
		[].forEach.call(books.querySelectorAll('li[data-isbn]'), function(entry) {
			var isbn = entry.getAttribute('data-isbn');
			var search = document.querySelector('td.search[data-isbn="' + isbn + '"]');
			if (search) {
				search.classList.add('selected');
			}
		});
		[].forEach.call(results.querySelectorAll('td.search'), function(td) {
			td.addEventListener('click', function(event) {
				var isbn = td.getAttribute('data-isbn');
				if (td.classList.contains('selected')) {
					removeCorpus(books, results, isbn);
				} else {
					addCorpus(books, results, isbn);
				}
			});
		});
	}
	[].forEach.call(results.querySelectorAll('.keyword'), function(instance) {
		instance.addEventListener("click", function(event) {
			var snip = this.parentNode;
			var isbn = snip.dataset.book;
			var part = snip.dataset.part;
			var match = snip.dataset.match;
			var index = snip.dataset.index;
			var search = results.dataset.search;
			invokeZord({
				module:"Book",
				action:"show",
				isbn:isbn,
				part:part,
				search:search,
				match:match,
				index:index
			});
		});
	});
	var rows  = Number(results.dataset.rows);
	var start = Number(results.dataset.start);
	var found = Number(results.dataset.found);
	var select = results.querySelector('div.fetch span select');
	if (select) {
		select.addEventListener('change', function(event) {
			updateResults(select.value, rows);
		});
	}
	[].forEach.call(['first','previous','next','last'], function(id) {
		var control = results.querySelector('div.fetch span.' + id);
		if (control) {
			if ((start == 0 && (id == 'first' || id == 'previous')) ||
				(start + rows >= found && (id == 'next' || id == 'last'))) {
				control.classList.add('disabled');
			} else {
				control.addEventListener("click", function(event) {
					var newStart;
					switch(id) {
						case 'first': {
							newStart = 0;
							break;
						}
						case 'previous': {
							newStart = (Math.trunc(start / rows) - 1) * rows;
							break;
						}
						case 'next': {
							newStart = (Math.trunc(start / rows) + 1) * rows;
							break;
						}
						case 'last': {
							newStart = (Math.trunc((found - 1) / rows)) * rows;
							break;
						}
					}
					if (newStart >= 0) {
						updateResults(newStart, rows);
					}
				});
			}
		}
	});
	loadPending(results);
	var post = results.dataset.post;
	if (post !== undefined && post !== null && window[post] instanceof Function) {
		window[post]();
	}
}

var popupResults = function() {
	$.fancybox.open(document.getElementById("searchResults").innerHTML);
	var results = document.querySelector('div.fancybox-slide--html.fancybox-slide--current div.results');
	results.style.display = 'inline-block';
	results.dataset.dressed = 'false';
	dressResults(results);
	dressSortingToggles(results);
}

document.addEventListener("DOMContentLoaded", function(event) {

	if (searchScope == undefined) {
		searchScope = CONFIG.default.search.scope;
		setContextProperty('search.scope', searchScope);
	}

	if (searchCriteria == undefined) {
		searchCriteria = {
			filters: {
				contentType: CONFIG.default.search.type,
				source: {
					from: CONFIG.default.search.source.from,
					to: CONFIG.default.search.source.to
				}
			},
			operator: CONFIG.default.search.operator
		};
		setContextProperty('search.criteria', searchCriteria);
	}

	var refine     = document.getElementById('searchRefine');
	var controls   = document.getElementById('searchControls');
	var rows       = document.getElementById('searchSize');
	var titles     = document.getElementById('titles');
	var books      = document.getElementById('books');
	var shelves    = document.getElementById('shelves');
	
	[].forEach.call(document.querySelectorAll('div[data-scope="' + searchScope + '"]'), function(element) {
		element.classList.add('current');
	});
	
	[].forEach.call(document.querySelectorAll('select.styles'), function(select) {
		[].forEach.call(select.options, function(option) {
			if (option.value == getCSLParam('style')) {
				option.selected = true;
			}
		});
	});
	
	updateCorpus(null, null);
	refreshHistory();

	loadData({
		module   : 'Portal',
		action   : 'options',
		scope    : 'context',
		_context : CONTEXT
	});

	var options = getSessionProperty('context.' + CONTEXT + '.options._keys', {});
	for (var index in options) {
		loadData({
			module   : 'Portal',
			action   : 'options',
			scope    : 'context',
			key      : options[index],
			_context : CONTEXT,
			wait     : true
		});
	}

	if (searchCriteria.filters !== undefined) {
		if (searchCriteria.filters.contentType !== undefined && Array.isArray(searchCriteria.filters.contentType)) {
			document.getElementById('searchInIndex').checked = true;
		}
		if (searchCriteria.filters.source !== undefined) {
			if (searchCriteria.filters.source.from !== undefined) {
				document.getElementById('search_source_from').value = searchCriteria.filters.source.from;
			}
			if (searchCriteria.filters.source.to !== undefined) {
				document.getElementById('search_source_to').value = searchCriteria.filters.source.to;
			}
		}
	}
	
	if (searchCriteria.rows !== undefined) {
		document.getElementById('searchSize').value = searchCriteria.rows;
	} else {
		document.getElementById('searchSize').value = document.getElementById('searchSize').getAttribute('min');
	}
	
	if (searchCriteria.query !== undefined) {
		document.getElementById('queryInput').value = searchCriteria.query;
	}
	
	if (searchCriteria.operator !== undefined) {
		[].forEach.call(['AND','OR'], function(operator) {
			var radio = document.getElementById('search_operator_' + operator);
			if (searchCriteria.operator == radio.value) {
				radio.checked = true;
			}
		});
	}

	if (titles) {
		titles.setAttribute('data-loading', 'true');
		var values = getSessionProperty('context.' + CONTEXT + '.options.titles');
		for (var key in values) {
			var option = document.createElement('option');
			option.value = getOptionValue(key);
			var text = document.createTextNode(values[key]);
			option.appendChild(text);
			titles.appendChild(option);
		}
		titles.setAttribute('data-loading', 'false');
		window.dispatchEvent(new Event("selectLoaded"));
		$('#titles').on('change', function(event) {
			addCorpus(books, shelves, titles.value);
		});
	}
	
	if (books) {
		var styles = document.getElementById('styles');
		if (styles) {
			var cslStyle = getCSLParam('style');
			for (var index = 0 ; index < styles.options.length ; index++) {
				if (styles.item(index).value == cslStyle) {
					styles.item(index).selected = true;
				}
			}
			styles.addEventListener("change", function(event) {
				updateCorpus(null, null);
			});
		}
	}
	
	function switchRefine(status) {
		if (status) {
			refine.classList.add('opened');
			refine.classList.remove('closed');
			controls.style.display = 'block';
		} else {
			refine.classList.remove('opened');
			refine.classList.add('closed');
			controls.style.display = 'none';
		}
	}
	
	if (refine && refine.classList.contains('switch')) {
		switchRefine(searchRefine);
		refine.addEventListener('click', function(event) {
			searchRefine = !searchRefine;
			setContextProperty('search.refine', searchRefine);
			switchRefine(searchRefine);
		});
	}
	
	document.getElementById('searchHistoryPrevious').addEventListener("click", function(event) {
		if (searchIndex > 1) {
			searchIndex = searchIndex - 1;
			setContextProperty('search.index', searchIndex);
			refreshHistory();
		}
	});
	
	document.getElementById('searchHistoryNext').addEventListener("click", function(event) {
		if (searchIndex < searchHistory.length) {
			searchIndex = searchIndex + 1;
			setContextProperty('search.index', searchIndex);
			refreshHistory();
		}
	});
	
	document.getElementById('searchHistoryReplay').addEventListener("click", function(event) {
		if (searchIndex >=1  && searchIndex <= searchHistory.length) {
			searchCriteria = searchHistory[searchIndex - 1];
			setContextProperty('search.criteria', searchCriteria);
			search(searchCriteria, undefined, true);
		}
	});
	
	document.getElementById('searchHistoryDelete').addEventListener("click", function(event) {
		if (searchIndex > 0) {
			searchHistory.splice(searchIndex - 1, 1);
			if (searchIndex > searchHistory.length) {
				searchIndex = searchHistory.length;
			}
			saveHistory();
		}
	});
	
	document.getElementById('searchHistoryClear').addEventListener("click", function(event) {
		searchHistory = [];
		searchIndex = 0;
		saveHistory();
	});
	
	[].forEach.call(document.querySelectorAll('#queryButton, button.search'), function(button) {
		button.addEventListener("click", function(event) {
			if (button.id == 'queryButton') {
				var query = document.querySelector('#queryInput');
				if (query) {
					if (query.value.replace(' ', '').length == 0) {
						return;
					}
				}
			}
			getCriteria();
			updateResults(0, rows.value);
		});
	});
	
	[].forEach.call(document.querySelectorAll('.scope'), function(scope) {
		scope.addEventListener("click", function(event) {
			[].forEach.call(document.querySelectorAll('.scope,.block'), function(element) {
				element.classList.remove('current');
			});
			searchScope = scope.getAttribute('data-scope');
			setContextProperty('search.scope', searchScope);
			[].forEach.call(document.querySelectorAll('div[data-scope="' + searchScope + '"]'), function(element) {
				element.classList.add('current');
			});
		});
	});
		
	var references = getCSLObjects('corpus');
	if (references !== undefined && references !== null) {
		for (var id in references) {
			addCorpus(books, shelves, references[id].ean, references[id]);
		}
	}

	[].forEach.call(document.querySelectorAll('#books > li[data-isbn]'), function(entry) {
		entry.addEventListener('click', function(event) {
			removeCorpus(books, shelves, entry.getAttribute('data-isbn'));
		});
	});
	
	document.getElementById('queryInput').addEventListener("keypress", function(event) {
	    var key = event.which || event.keyCode;
	    if (key === 13) {
			getCriteria();
			updateResults(0, rows.value)
	    }
	});
		
	[].forEach.call(document.querySelectorAll('select.facet'), function(select) {
		var background = select.style.background;
		select.style.background = "url('/img/wait.gif') no-repeat center";
		select.setAttribute('data-loading', 'true');
		var values = getSessionProperty('context.' + CONTEXT + '.options.' + select.id);
		for (var key in values) {
			var option = document.createElement('option');
			var value = getOptionValue(key);
			option.value = value;
			if (searchCriteria.filters !== undefined && searchCriteria.filters !== null) {
				var filter = searchCriteria.filters[select.id];
				if (filter !== undefined && filter !== null && filter.includes(value)) {
					option.selected = true;
				}
			}
			var text = document.createTextNode(values[key]);
			option.appendChild(text);
			select.appendChild(option);
		}
		select.setAttribute('data-loading', 'false');
		window.dispatchEvent(new Event("selectLoaded"));
		select.style.background = background;
	});
	
	if (shelves.classList.contains('results')) {
		dressResults(shelves);
	}
	
});

window.addEventListener("load", function(event) {
	
	if (POPUP) {
		popupResults();
	}
	
});

