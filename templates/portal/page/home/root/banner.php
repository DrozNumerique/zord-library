				<li class="banner" id="menu_context_<?php echo $context; ?>">
					<div class="media">
						<img src="<?php if (file_exists(STORE_FOLDER.'public/library/'.$context.'/banner.png')) {echo '/public/library/'.$context.'/banner.png';} else {echo Zord::getSkin($context)->banner->image;} ?>"/>
					</div>
					<div class="title">
						<h3><?php echo Zord::getLocaleValue('title', Zord::value('context', $context)); ?></h3>
					</div>
				</li>
