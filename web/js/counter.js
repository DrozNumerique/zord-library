var before = '<html><head><meta http-equiv=Content-Type content="text/html; charset=utf-8"><style type="text/css">table {width:1500px;} td {width:700px;}</style></head><body>';
var after = '</body></html>';
var type = {type: 'application/vnd.ms-excel'};

document.addEventListener("DOMContentLoaded", function(event) {
	[].forEach.call(document.querySelectorAll('a[download]'), function (download) {
		report = download.nextElementSibling.innerHTML;
        var blob = new Blob([before + report + after], type);
        download.setAttribute("href", window.URL.createObjectURL(blob));
	});
});
