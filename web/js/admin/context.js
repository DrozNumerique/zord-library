checkContext['duplicate'] = function(params) {
	var context = document.getElementById('context');
	if (context) {
		var name = context.querySelector('li.visible div.name input');
		var title = context.querySelector('li.visible div.title input');
		if (name && title) {
			if (name.value == undefined || name.value == null || name.value.length == 0) {
				return false;
			}
			if (title.value == undefined || title.value == null || title.value.length == 0) {
				return false;
			}
			params.source = params.name;
			params.name = name.value;
			params.title = title.value;
			return params;
		}
	}
	return false;
}