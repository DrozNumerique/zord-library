           			<div class="admin-panel-title"><?php echo $locale->tab->publish->context->urls; ?></div> 
    				<ul class="admin-list" id="urls" data-columns="30px,300px,300px">
    					<li class="header">
                      		<div class="column"><i class="fa fa-lock fa-fw" title="<?php echo $locale->tab->publish->context->secure; ?>"></i></div>
           					<div class="column"><?php echo $locale->tab->publish->context->host; ?></div>
           					<div class="column"><?php echo $locale->tab->publish->context->path; ?></div>
             				<div class="add"><i class="fa fa-plus fa-fw" title="<?php echo $locale->list->add; ?>"></i></div>
         				</li>
         				<li class="hidden">
         					<div class="column secure">
          						<input name="secure" data-empty="no" type="hidden" value="no"/>
          						<i class="fa fa-chain-broken fa-fw"></i>
         					</div>
          					<div class="column"><input name="host" data-empty="no" type="text"/></div>
          					<div class="column"><input name="path" data-empty="no" type="path"/></div>
             				<div class="remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->remove; ?>"></i></div>
         				</li>
                        <?php foreach ($models['urls'] as $url) { ?>
         				<li class="data">
         					<div class="column secure">
          						<input name="secure" data-empty="no" type="hidden" value="<?php echo (isset($url['secure']) && $url['secure']) ? 'yes' : 'no'; ?>"/>
          						<i class="fa fa-chain<?php echo (isset($url['secure']) && $url['secure']) ? '' : '-broken'; ?> fa-fw"></i>
         					</div>
           					<div class="column"><input name="host" data-empty="no" type="text" value="<?php echo $url['host']; ?>"/></div>
           					<div class="column"><input name="path" data-empty="no" type="text" value="<?php echo $url['path']; ?>"/></div>
             				<div class="remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->remove; ?>"></i></div>
         				</li>
         				<?php } ?>
     				</ul>
