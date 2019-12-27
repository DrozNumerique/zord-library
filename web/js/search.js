var searchRefine   = getContextProperty('search.refine', false);
var searchHistory  = getContextProperty('search.history', []);
var searchIndex    = getContextProperty('search.index', 0);
var searchScope    = getContextProperty('search.scope', DEFAULT_SEARCH_SCOPE);
var searchFacets   = getContextProperty('search.facets', undefined);
var searchTitles   = getContextProperty('search.titles', undefined);
var searchCriteria = getContextProperty('search.criteria', {
	filters: {
		contentType: DEFAULT_SEARCH_TYPE,
		source: {
			from: DEFAULT_SEARCH_SOURCE_FROM,
			to: DEFAULT_SEARCH_SOURCE_TO
		}
	},
	operator: DEFAULT_SEARCH_OPERATOR
});

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
		updateCorpus(true);
	}
}

function refreshHistory(only) {
	display = document.getElementById('searchHistoryDisplay');
	style = document.getElementById('historyStyles');
	if (only == undefined || only == null || !only) {
		updateStyle(style, 'corpusStyles');
	}
	if (searchIndex > 0 && searchIndex <= searchHistory.length) {
		invokeZord({
			module:'Book',
			action:'criteria',
			criteria:JSON.stringify(searchHistory[searchIndex - 1]),
			callback:function(result) {
				display.innerHTML = '';
				index = document.createElement('div');
				index.appendChild(document.createTextNode(searchIndex + '/' + searchHistory.length));
				display.appendChild(index);
				if (result.query) {
					query = document.createElement('li');
					query.appendChild(document.createTextNode(result.query));
					display.appendChild(query);
				}
				if (result.results) {
					results = document.createElement('li');
					results.appendChild(document.createTextNode(result.results));
					display.appendChild(results);
				}
				if (result.include) {
					include = document.createElement('li');
					include.appendChild(document.createTextNode(result.include));
					display.appendChild(include);
				}
				if (result.source) {
					source = document.createElement('li');
					source.appendChild(document.createTextNode(result.source));
					display.appendChild(source);
				}
				if (result.books && result.books.length > 0) {
					books = document.createElement('li');
					display.appendChild(books);
					biblio = document.createElement('ul');
					[].forEach.call(result.books, function(text, index) {
						if (index == 0) {
							books.appendChild(document.createTextNode(text));
						} else {
							if (index == 1) {
								books.appendChild(biblio);
							}
							cslObjects = getCSLObjects('history');
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
									BASEURL + 'Book/reference',
								    {isbn: text},
								    function(result) {
								    	reference = result;
										addCSLObject('history', reference);
								    }
								);
							}
							entry = document.createElement('li');
							entry.innerHTML = getBiblio('history', reference.id);
							biblio.appendChild(entry);
						}
					});
				}
				if (result.facets) {
					if (result.operator) {
						operator = document.createElement('li');
						operator.appendChild(document.createTextNode(result.operator));
						display.appendChild(operator);
					}
					for (var name in result.facets) {
						if (result.facets[name].length > 0) {
							label = document.createElement('li');
							display.appendChild(label);
							list = document.createElement('ul');
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
	year = document.getElementById(name).value;
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
	var operator = DEFAULT_SEARCH_OPERATOR;
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
		books = [];
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
	searchCriteria = {
		query:query,
		scope:searchScope,
		filters:filters,
		operator:operator,
		context:CONTEXT
	};
}

function fetch(start, rows) {
	if (start == undefined || start == null) {
		start = 0;
	}
	if (rows == undefined || rows == null) {
		rows = Number(document.getElementById('searchSize').value);
	}
	searchCriteria.start = start;
	searchCriteria.rows = rows;
	setContextProperty('search.criteria', searchCriteria);
	searchHistory.push(searchCriteria);
	searchIndex = searchHistory.length;
	saveHistory();
	search(searchCriteria, undefined, true);
}

function updateSelectedCorpus(books) {
	selected = document.getElementById('selected');
	full = document.getElementById('full');
	remove = document.getElementById('remove');
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

function updateCorpus(only) {
	style = document.getElementById('corpusStyles');
	books = document.getElementById('books');
	if (style !== null && books !== null) {
		if (only == undefined || only == null || !only) {
			updateStyle(style, 'historyStyles');
		}
		list = books.querySelectorAll('li[data-isbn]');
		updateSelectedCorpus(list);
		refreshBiblio('corpus',	list);
	}
}

document.addEventListener("DOMContentLoaded", function(event) {

	if (searchFacets == undefined) {
		searchFacets = {};
		invokeZord({
			module: 'Book',
			action: 'facets',
			async:  false,
			before: function() {
				$dialog.wait();
			},
			after: function() {
				$dialog.hide();
			},
			callback: function(facets) {
				[].forEach.call(facets, function(facet) {
					invokeZord({
						module:'Book',
						action:'facets',
						key:   facet,
						async: false,
						callback: function(entries) {
							searchFacets[facet] = entries;
							setContextProperty('search.facets', searchFacets);
						}
					});
				});
			}
		});
	}

	if (searchTitles == undefined) {
		invokeZord({
			module:'Book',
			action:'titles',
			async: false,
			before: function() {
				$dialog.wait();
			},
			after: function() {
				$dialog.hide();
			},
			callback: function(titles) {
				searchTitles = titles;
				setContextProperty('search.titles', searchTitles);
			}
		});
	}
	
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
	
	updateCorpus();
	refreshHistory();

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
			radio = document.getElementById('search_operator_' + operator);
			if (searchCriteria.operator == radio.value) {
				radio.checked = true;
			}
		});
	}

	var titles = document.getElementById('titles');
	if (titles) {
		titles.setAttribute('data-loading', 'true');
		for (var key in searchTitles) {
			var option = document.createElement('option');
			option.value = key;
			var text = document.createTextNode(searchTitles[key]);
			option.appendChild(text);
			titles.appendChild(option);
		}
		titles.setAttribute('data-loading', 'false');
		window.dispatchEvent(new Event("selectLoaded"));
		$('#titles').on('change', function(event) {
			addCorpus(titles.value);
		});
	}
	
	var books = document.getElementById('books');
	if (books) {
		[].forEach.call(books.querySelectorAll('li[data-isbn]'), function(entry) {
			var isbn = entry.getAttribute('data-isbn');
			var search = document.querySelector('td.search[data-isbn="' + isbn + '"]');
			if (search) {
				search.classList.add('selected');
			}
		});
		var styles = document.getElementById('styles');
		if (styles) {
			cslStyle = getCSLParam('style');
			for (var index = 0 ; index < styles.options.length ; index++) {
				if (styles.item(index).value == cslStyle) {
					styles.item(index).selected = true;
				}
			}
			styles.addEventListener("change", function(event) {
				updateCorpus();
			});
		}
	}
	
	var refine   = document.getElementById('searchRefine');
	var controls = document.getElementById('searchControls');

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
	
	[].forEach.call(document.querySelectorAll('#queryButton, button.search'), function(search) {
		search.addEventListener("click", function(event) {
			fetch(getCriteria());
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

	[].forEach.call(['first','previous','next','last'], function(id) {
		var control = document.getElementById(id);
		if (control) {
			if ((START == 0 && (id == 'first' || id == 'previous')) ||
				(START + ROWS >= FOUND && (id == 'next' || id == 'last'))) {
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
							newStart = (Math.trunc(START / ROWS) - 1) * ROWS;
							break;
						}
						case 'next': {
							newStart = (Math.trunc(START / ROWS) + 1) * ROWS;
							break;
						}
						case 'last': {
							newStart = (Math.trunc((FOUND - 1) / ROWS)) * ROWS;
							break;
						}
					}
					if (newStart >= 0) {
						fetch(newStart, ROWS);
					}
				});
			}
		}
	});
	
	function removeCorpus(isbn) {
		var search = document.querySelector('td.search[data-isbn="' + isbn + '"]');
		if (search) {
			search.classList.remove('selected');
		}
		var entry = books.querySelector('li[data-isbn="' + isbn + '"]');
		if (entry) {
			removeCSLObject('corpus', entry.getAttribute('data-ref'));
			books.removeChild(entry);
			updateSelectedCorpus(books.querySelectorAll('li[data-isbn]'));
		}
	}
	
	function addCorpus(isbn, reference) {
		var entry = books.querySelector('li[data-isbn="' + isbn + '"]');
		if (entry) {
			return;
		}
		var search = document.querySelector('td.search[data-isbn="' + isbn + '"]');
		if (search) {
			search.classList.add('selected');
		}
		entry = document.createElement('li');
		entry.setAttribute('data-isbn', isbn);
		entry.addEventListener('click', function(event) {
			removeCorpus(entry.getAttribute('data-isbn'));
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
				callback:function(reference) {
					addCSLObject('corpus', reference);
					setBiblio('corpus', entry, reference);
				}
			});
		}
	}
	
	var references = getCSLObjects('corpus');
	if (references !== undefined && references !== null) {
		for (var id in references) {
			addCorpus(references[id].ean, references[id]);
		}
	}

	[].forEach.call(document.querySelectorAll('#books > li[data-isbn]'), function(entry) {
		entry.addEventListener('click', function(event) {
			removeCorpus(entry.getAttribute('data-isbn'));
		});
	});
	
	[].forEach.call(document.querySelectorAll('td.search'), function(td) {
		td.addEventListener('click', function(event) {
			var isbn = td.getAttribute('data-isbn');
			if (td.classList.contains('selected')) {
				removeCorpus(isbn);
			} else {
				addCorpus(isbn);
			}
		});
	});
	
	document.getElementById('queryInput').addEventListener("keypress", function(event) {
	    var key = event.which || event.keyCode;
	    if (key === 13) {
	    	fetch(getCriteria());
	    }
	});
	
	var instances = document.getElementById('shelves').querySelectorAll('.keyword');
	if (instances) {
		[].forEach.call(instances, function(instance) {
			instance.addEventListener("click", function(event) {
				var snip = this.parentNode;
				var isbn = snip.getAttribute('data-book');
				var part = snip.getAttribute('data-part');
				var match = snip.getAttribute('data-match');
				var index = snip.getAttribute('data-index');
				invokeZord({
					module:"Book",
					action:"show",
					isbn:isbn,
					part:part,
					search:SEARCH,
					match:match,
					index:index
				});
			});
		});
	}
	
	[].forEach.call(document.querySelectorAll('select.facet'), function(select) {
		var background = select.style.background;
		select.style.background = "url('/library/img/wait.gif') no-repeat center";
		select.setAttribute('data-loading', 'true');
		for (var key in searchFacets[select.id]) {
			var option = document.createElement('option');
			option.value = key;
			if (searchCriteria.filters !== undefined && searchCriteria.filters !== null) {
				var filter = searchCriteria.filters[select.id];
				if (filter !== undefined && filter !== null && filter.includes(key)) {
					option.selected = true;
				}
			}
			var text = document.createTextNode(searchFacets[select.id][key]);
			option.appendChild(text);
			select.appendChild(option);
		}
		select.setAttribute('data-loading', 'false');
		window.dispatchEvent(new Event("selectLoaded"));
		select.style.background = background;
	});
	
});

window.addEventListener("load", function(event) {
	
	if (typeof ALERT !== 'undefined' && ALERT !== null && ALERT.length > 0) {
		alert(ALERT);
	}
	
});

