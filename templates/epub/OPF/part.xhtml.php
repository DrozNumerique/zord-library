<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title><?php echo htmlspecialchars($models['title']); ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <link rel="stylesheet" type="text/css" media="screen" href="css/common.css"/>
        <link rel="stylesheet" type="text/css" media="screen" href="css/screen.css"/>
        <link rel="stylesheet" type="text/css" media="screen" href="css/epub.css"/>
    </head>
    <body>
		<div id="text">
<?php echo $models['text']; ?>
		</div>
    </body>
</html>
