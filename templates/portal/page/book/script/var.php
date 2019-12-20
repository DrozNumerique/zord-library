		var TITLE    = "<?php echo str_replace('"', '\"', $models['book']['TITLE']); ?>";
		var BOOK     = "<?php echo $models['book']['ISBN']; ?>";
		var PART     = "<?php echo $models['book']['PART']; ?>";
		var PARTS    = [<?php echo implode(',', array_map(function($element) {return '"'.$element.'"';}, $models['book']['PARTS'])); ?>];
		var IDS      = "<?php echo $models['book']['IDS']; ?>";
