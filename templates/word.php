<html>
    <head>
    	<meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    	<style type="text/css">
<?php foreach ($models['styles'] as $style) { ?>
<?php   echo $style."\n"; ?>
<?php } ?>
    	</style>
    </head>
    <body>
<?php foreach ($models['parts'] as $part) { ?>
<?php   foreach(explode("\n", $part) as $line) { ?>
				<?php echo $line."\n"; ?>
<?php   } ?>
<?php } ?>
    </body>
</html>
