		var SWITCH = {
<?php foreach ($models['portal']['switches'] as $index => $switch) { ?>
			<?php echo "'".$switch['name']."':'".$switch['url']."'".($index < count($models['portal']['switches']) - 1 ? ',' : '')."\n"; ?>
<?php } ?>
		};

