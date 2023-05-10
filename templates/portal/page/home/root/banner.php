				<li class="banner" id="menu_context_<?php echo $context; ?>">
					<div class="media">
						<img src="<?php echo Zord::getSkin($context)->banner->image; ?>"/>
					</div>
					<div class="title">
						<h1><?php echo Zord::getLocaleValue('title', Zord::value('context', $context)); ?></h1>
					</div>
				</li>
