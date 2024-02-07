		<div id="banner" align="center">
			<a href="<?php echo Zord::getContextURL($context); ?>"><img src="<?php if (file_exists(STORE_FOLDER.'public/library/'.$context.'/banner.png')) {echo '/public/library/'.$context.'/banner.png';} else {echo Zord::getSkin($context)->banner->image;} ?>"/></a>
		</div>
