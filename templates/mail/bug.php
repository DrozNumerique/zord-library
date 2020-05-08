<!DOCTYPE html>
<html>
    <head>
    	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    	<title><?php echo $locale->notify_bug; ?></title>
    </head>
    <body>
        <a href="<?php echo $baseURL.$models['path']; ?>"><?php echo $locale->click_here; ?></a>
        <br>
        <p><?php echo $models['quote']; ?></p>
        <p><?php echo $models['note']; ?></p>
    </body>
</html>
        