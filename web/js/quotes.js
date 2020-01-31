document.addEventListener("DOMContentLoaded", function(event) {

	var er_key = /\{\{\$([\w\.]*)\}\}/g;
	var render = function (template, data) {
		return template.replace(er_key, function (str, key) {
			var keys = key.split(".");
			var value = data[keys.shift()];
			try {
				keys.forEach( function (val) {value = value[val]; });
				return (value === null || value === undefined) ? "" : value;
			} catch(err) {
				return "";
			}
		});
	};
	var quotes = getCSLObjects('quotes');

	[].forEach.call(document.getElementById('marker_styles_select').options, function(option) {
		if (option.value == getCSLParam('style')) {
			option.selected = true;
		}
	});

	var saveCitations = function(){
		setCSLObjects('quotes', quotes);
	};

	var saveChangeNote = function(el){
		el.addEventListener("keyup", function(event) {
			var parent = el.parentNode;
			var id = parent.getAttribute('data-id');
			quotes[id].zord_note = this.value;
			saveCitations();
		});
	};

	// This runs at document ready, and renders the bibliography
	var renderBib = function () {
		style = document.getElementById('marker_styles_select').value;
		setCSLParams({
			'lang':  LANG,
			'style': style
		});
		var citeproc = getCSLEngine('quotes', style, LANG);
		var html = [];
		for (var key in quotes) {
				var itemIDs = [];
				itemIDs.push(key);
				citeproc.updateItems(itemIDs);

				var bibResult = citeproc.makeBibliography(key);
				html.push('<div class="marker" data-id="'+key+'">');
				html.push('<span class="marker-del" data-tooltip="'+LABEL_DELCITATION+'">-</span>');
				if(quotes[key].zord_note == undefined || quotes[key].zord_note == '')
					html.push('<span class="marker-addnote" data-tooltip="'+LABEL_ADDNOTE+'">≡</span>');

				html.push('<div class="marker-bib">'+bibResult[1].join('')+'</div>');

				if(quotes[key].zord_URL != undefined)
					html.push('<div class="marker-url"><a href="'+quotes[key].zord_URL+'"  target="_blank">'+quotes[key].zord_URL+'</a></div>');

				if(quotes[key].zord_citation != undefined)
					html.push('<div class="marker-citation">'+quotes[key].zord_citation+'</div>');

				if(quotes[key].zord_note != undefined && quotes[key].zord_note != '')
					html.push('<textarea class="marker-note">'+quotes[key].zord_note+'</textarea>');

				html.push('<hr/></div>');

		}
		document.getElementById('markers').innerHTML = html.join('');
		
		[].forEach.call(document.querySelectorAll('.marker-del'), function (el) {
			el.addEventListener("click", function(event) {
				var parent = el.parentNode;
				var id = parent.getAttribute('data-id');
				delete quotes[id];
				saveCitations();
				renderBib();
			});
		});

		[].forEach.call(document.querySelectorAll('.marker-addnote'), function (el) {
			el.addEventListener("click", function(event) {
				var parent = el.parentNode;
				parent.insertAdjacentHTML('beforeend', '<textarea class="marker-note"></textarea>');
				parent.removeChild(this);
				saveChangeNote(parent.querySelector('.marker-note'));
			});
		});

		[].forEach.call(document.querySelectorAll('.marker-note'), function (el) {
			saveChangeNote(el);
		});

	};

	document.getElementById('marker_styles_select').addEventListener("change", function(event) {
		renderBib();
	});

	document.getElementById('markers_export').addEventListener("click", function(event) {
		markers = document.getElementById('markers').cloneNode(true);
		[].forEach.call(markers.childNodes, function(marker) {
			[].forEach.call(marker.querySelectorAll('span.marker-del, span.marker-addnote'), function(span) {
				marker.removeChild(span);
			});
		});		
		invokeZord({
			module:'Book',
			action:'quotes',
			markers:markers.innerHTML
		});
	});

	document.getElementById('markers_clear').addEventListener("click", function(event) {
		quotes = {};
		saveCitations();
		renderBib();
	});
	
	[].forEach.call(document.querySelectorAll('.help_bubble_red'), function (el) {
		el.addEventListener("click", function(event) {
			$dialog.help(el);
		});
	});

	renderBib();
});