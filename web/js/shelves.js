function showPanel(panel, present) {
	if (present) {
		setContextProperty('shelves.panel', panel);
	}
	[].forEach.call(document.querySelectorAll('.panel'), function(entry) {
		entry.style.display = entry.getAttribute('data-panel') == panel ? 'block' : 'none';
	});
	[].forEach.call(document.querySelectorAll('.tab'), function(entry) {
		if (entry.getAttribute('data-tab') == panel) {
			entry.classList.add('tabselect');
		} else {
			entry.classList.remove('tabselect');
		}
	});
}

document.addEventListener("DOMContentLoaded", function(event) {
	var tabs = document.querySelectorAll('.tab');
	[].forEach.call(tabs, function(entry) {
		entry.addEventListener("click", function(event) {
			showPanel(entry.getAttribute('data-tab'), true);
		});
	});
	var panels = document.querySelectorAll('.panel');
	if (panels !== null && panels.length > 0) {
		first = panels[0].getAttribute('data-panel');
		panel = getContextProperty('shelves.panel', first);
		present = false;
		for (index = 0; index < panels.length ; index++) {
			if (panel == panels[index].getAttribute('data-panel')) {
				present = true;
				break;
			}
		}
		showPanel(present ? panel : first, present);
	}
	var foldings = document.querySelectorAll('div.part[data-folded]');
	[].forEach.call(foldings, function(part) {
		part.querySelector('span.instances').addEventListener("click", function(event) {
			if (part.getAttribute('data-folded') == "true") {
				part.setAttribute('data-folded', "false");
				part.querySelector('div').style = "display:block;";
			} else {
				part.setAttribute('data-folded', "true");
				part.querySelector('div').style = "display:none;";
			}
		});
	});
});