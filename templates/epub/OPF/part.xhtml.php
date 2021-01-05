<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title><?php echo htmlspecialchars($models['title']); ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<?php foreach ($models['styles'] as $css) { ?>
        <link rel="stylesheet" type="text/css" media="screen" href="css/<?php echo $css; ?>.css"/>
<?php } ?>
    </head>
    <body class="epub">
		<div id="text">
<?php echo $models['text']; ?>
		</div>
    </body>
</html>
