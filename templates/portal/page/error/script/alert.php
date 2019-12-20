<?php if (isset($models['status']['alert'])) { ?>
	document.addEventListener("DOMContentLoaded", function(event) {
		alert('<?php echo $models['status']['alert']; ?>');
	});
<?php } ?>
	