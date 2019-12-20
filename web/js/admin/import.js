document.addEventListener("DOMContentLoaded", function(event) {
	
	var notify = document.getElementById('import-notify');
	var step = document.getElementById('import-step');
	var wait = document.getElementById('import-wait');
	var progress = document.getElementById('import-progress');
	var report = document.getElementById('import-report');
	var form = document.getElementById('import-form');
	var submit = document.getElementById('submit-file-import');
	var file = document.getElementById('file-import');
	var label = document.getElementById('label-import'); 
	var stop = document.getElementById('label-stop'); 
	var offset = 0;
	var pid = null;
	
	function reportLine(style, indent, message, newline) {
		span = document.createElement("span");
		span.classList.add(style);
		span.style.paddingLeft = (indent * 2) + "em";
		span.innerHTML = message;
		report.appendChild(span);
		if (newline) {
			var br = document.createElement("br");
			report.appendChild(br);
		}
		report.scrollTop = report.scrollHeight - report.clientHeight;
	}
	
	function resetNotify(displayReport) {
		step.innerHTML = '&nbsp;';
		progress.style.width = '0%';
		progress.innerHTML = '';
		notify.style.display = 'block';
		if (displayReport) {
			elements = report.querySelectorAll('span,br');
			if (elements) {
				[].forEach.call(elements, function(element) {
					element.parentNode.removeChild(element);
				});
			}
    		report.style.display = 'block';
    		wait.style.display = 'block';
    		offset = 0;
		} else {
			wait.style.display = 'none';
    		report.style.display = 'none';
		}
	}
	
	function toggleImport(activate) {
		if (activate) {
			file.classList.add('admin-input-file-valued');
			submit.parentNode.classList.add('admin-input-file-button-enabled');
			submit.disabled = false;
		} else {
			file.classList.remove('admin-input-file-valued');
			submit.parentNode.classList.remove('admin-input-file-button-enabled');
			submit.disabled = true;
		}
	}
	
	function checkUpload() {
		setTimeout(
			function() {
				invokeZord(
					{
						module:'Portal',
						action:'upload',
						name:'import',
						callback: function(result) {
							progress.style = 'width:' + result.percent + '%;';
							step.innerHTML = result.message;
							if (result.percent > 3) {
								progress.innerHTML = result.percent + '%';
							}
							if (result.percent < 100) {
								checkUpload();
							} else {
								setTimeout(function() {
									step.innerHTML = LOCALE['process']['wait'];
									wait.style.display = 'block';
								}, 500);
							}
						}
					}
				);
			},
			500
		);
	}

	function checkAction() {
		setTimeout(
			function() {
				if (pid == undefined || pid == null) {
					if (wait.style.display == 'block') {
						wait.style.display = 'none';
						reportLine('info',  0, '',     true);
						reportLine('error', 0, LOCALE['process']['stopped'], true);
						reportLine('info',  0, '',     true);
					}
					return;
				}
				checkProcess(pid, offset, function(result) {
					if (result.error !== undefined) {
						alert(result.error);
					} else {
						progress.style = 'width:' + result.progress + '%;';
						if (result.progress > 3) {
							progress.innerHTML = result.progress + '%';
						}
						if (result.step == 'closed') {
							step.innerHTML = LOCALE['process']['closed'];
						} else if (result.step == 'init') {
							step.innerHTML = LOCALE['process']['init'];
						} else {
							step.innerHTML = result.step;
						}
						[].forEach.call(result.report, function(line) {
							reportLine(line.style, line.indent, line.message, line.newline);
							offset++;
						});
						if (result.step !== 'closed') {
							checkAction();
						} else {
							label.style.display = 'inline';
					    	stop.style.display = 'none';
							wait.style.display = 'none';
							reportLine('info', 0, '', true);
						}
					}
				});
			},
			500
		);
	}
	
	file.addEventListener("change", function(event) {
		this.firstElementChild.innerText = document.getElementById(this.getAttribute('for')).files[0].name;
		toggleImport(true);
	});
	
	form.addEventListener("submit", function(event) {
	    event.preventDefault();
	    if (pid == undefined || pid == null) {
		    uploadZord(
		    	this, 
		    	function() {
		    		resetNotify(false);
		    		toggleImport(false);
		    		checkUpload();
		    	},
		    	function(result) {
		    		resetNotify(true);
		    		toggleImport(true);
			    	label.style.display = 'none';
			    	stop.style.display = 'inline';
		    		pid = result.pid;
		    		checkAction();
		    	}
		    );
	    } else {
	    	label.style.display = 'inline';
	    	stop.style.display = 'none';
	    	killProcess(pid);
	    	pid = null;
	    }
	    return false;
	}, false); 
	
});