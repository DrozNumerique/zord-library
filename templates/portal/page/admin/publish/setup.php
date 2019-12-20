				<div align="center">
   					<ul class="admin-list" id="context" data-columns="200px,300px">
   						<li class="header">
           					<div class="column"><?php echo $locale->tab->publish->context->name; ?></div>
           					<div class="column"><?php echo $locale->tab->publish->context->title; ?></div>
           					<?php if ($user->isManager()) { ?>
           					<div class="blank"></div>
           					<div class="blank"></div>
           					<?php } ?>
           					<div class="blank"></div>
       					</li>
                        <?php if ($user->isManager()) { ?>
       					<li>
          					<div class="column"><input name="name"  data-empty="no" type="text"/></div>
          					<div class="column"><input name="title" data-empty="no" type="text"/></div>
          					<div class="create"><i class="fa fa-plus fa-fw" title="<?php echo $locale->list->create; ?>"></i></div>
           					<div class="blank"></div>
           					<div class="blank"></div>
       					</li>
       					<?php } ?>
                        <?php foreach(Zord::getConfig('context') as $name => $config) { ?>
                        <?php   if ($user->hasRole('admin', $name)) { ?>
      					<li>
           					<div class="column"><input name="name"  data-empty="no" type="text" value="<?php echo $name; ?>" disabled/></div>
           					<div class="column"><input name="title" data-empty="no" type="text" value="<?php echo isset($config['title'][$lang]) ? $config['title'][$lang] : ''; ?>" <?php echo !$user->isManager() ? 'disabled' : ''; ?>/></div>
           					<?php if ($user->isManager()) { ?>
           					<div class="delete"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->delete; ?>"></i></div>
           					<div class="update"><i class="fa fa-check fa-fw" title="<?php echo $locale->list->update; ?>"></i></div>
           					<?php } ?>
           					<div class="publish"><i class="fa fa-arrow-circle-right fa-fw" title="<?php echo $locale->tab->publish->list; ?>"></i></div>
       					</li>
       					<?php   } ?>
       					<?php } ?>
   					</ul>
				</div>
