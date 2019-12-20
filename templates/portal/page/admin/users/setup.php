				<div align="center">
					<?php if (isset($models['mail']['error'])) { ?>
           			<div class="admin-panel-warning"><?php echo $locale->tab->users->mail->error; ?></div>
           			<div class="admin-panel-warning"><?php echo '('.$models['mail']['error'].')'; ?></div>
           			<div class="admin-panel-warning"><?php echo $locale->tab->users->mail->to.' : '.$models['mail']['account']; ?></div>
           			<div class="admin-panel-warning"><a href="<?php echo $models['mail']['activation']; ?>"><?php echo $locale->tab->users->mail->activation; ?></a></div>
           			<br/>
           			<br/>
					<?php } ?>
   					<ul class="admin-list" id="account" data-columns="210px,210px,210px">
   						<li class="header">
           					<div class="column"><?php echo $locale->tab->users->login; ?></div>
           					<div class="column"><?php echo $locale->tab->users->name; ?></div>
           					<div class="column"><?php echo $locale->tab->users->email; ?></div>
           					<div class="blank"></div>
           					<div class="blank"></div>
           					<div class="blank"></div>
           					<div class="blank"></div>
       					</li>
       					<li>
          					<div class="column"><input name="login" data-empty="no" type="text"/></div>
          					<div class="column"><input name="name" data-empty="no" type="text"/></div>
          					<div class="column"><input name="email" data-empty="no" type="email"/></div>
          					<div class="create"><i class="fa fa-plus fa-fw" title="<?php echo $locale->list->create; ?>"></i></div>
           					<div class="blank"></div>
           					<div class="blank"></div>
           					<div class="blank"></div>
       					</li>
                        <?php foreach((new UserEntity())->retrieve() as $account) { ?>
      					<li>
           					<div class="column"><input name="login" data-empty="no" type="text" value="<?php echo $account->login; ?>" disabled/></div>
          					<div class="column"><input name="name" data-empty="no" type="text" value="<?php echo $account->name; ?>"/></div>
           					<div class="column"><input name="email" data-empty="no" type="email" value="<?php echo $account->email; ?>"/></div>
           					<div class="delete"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->delete; ?>"></i></div>
           					<div class="update"><i class="fa fa-check fa-fw" title="<?php echo $locale->list->update; ?>"></i></div>
           					<div class="profile"><i class="fa fa-user fa-fw" title="<?php echo $locale->tab->users->profile; ?>"></i></div>
           					<div class="counter"><i class="fa fa-bar-chart fa-fw" title="<?php echo $locale->tab->users->counter; ?>"></i></div>
       					</li>
       					<?php } ?>
   					</ul>
				</div>
