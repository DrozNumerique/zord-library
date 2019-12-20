<?xml version="1.0" encoding="UTF-8"?>
<formats>
<?php foreach ($models['formats'] as $name => $format) { ?>
	<format name="<?php echo $name; ?>" type="<?php echo $format['type']; ?>" docs="<?php echo $format['docs']; ?>" />
<?php } ?>
</formats>