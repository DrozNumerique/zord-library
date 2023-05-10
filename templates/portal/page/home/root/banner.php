				<li class="banner">
					<div class="media">
						<a href="<?php echo Zord::getContextURL($context); ?>">
							<img src="<?php echo Zord::getSkin($context)->banner->image; ?>"/>
						</a>
					</div>
					<div class="title">
						<a href="<?php echo Zord::getContextURL($context); ?>">
							<h1><?php echo Zord::getLocaleValue('title', Zord::value('context', $context)); ?></h1>
						</a>
					</div>
				</li>
