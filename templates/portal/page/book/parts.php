			<div id="parts">
<?php 
foreach ($models['book']['parts'] as $part) {
    foreach(explode("\n", $part) as $line) {
?>
				<?php echo $line."\n"; ?>
<?php
    }
}
?>
			</div>
