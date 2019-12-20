   				<input type="hidden" id="user" value="<?php echo $models['user']->login ?>"/>
				<div align="center">
           			<div class="admin-panel-title"><?php echo $models['user']->name; ?></div>
           			<?php 
           			    if (isset($models['others'])) {
           			        foreach ($models['others'] as $other) {
           			?>
           			<div class="admin-panel-warning"><?php echo $other[0].' '.$locale->tab->users->match.' '.$other[1]; ?></div>
           			<?php 
           			        }
           			    }
           			?>
           			<div class="admin-panel-title"><?php echo $locale->tab->users->roles; ?></div>
    				<ul class="admin-list" id="roles" data-columns="170px,170px,170px,170px">
    					<li class="header">
             				<div class="column"><?php echo $locale->tab->users->role; ?></div>
            				<div class="column"><?php echo $locale->tab->users->context; ?></div>
             				<div class="column"><?php echo $locale->tab->users->start; ?></div>
             				<div class="column"><?php echo $locale->tab->users->end; ?></div>
             				<div class="add"><i class="fa fa-plus fa-fw" title="<?php echo $locale->list->add; ?>"></i></div>
         				</li>
         				<li class="hidden">
             				<div class="column">
             					<select>
             						<?php foreach($models['roles'] as $name) { ?>
             						<option value="<?php echo $name; ?>"><?php echo $name; ?></option>
             						<?php } ?>
             					</select>
             				</div>
             				<div class="column">
             					<select>
             						<?php foreach($models['context'] as $name) { ?>
             						<option value="<?php echo $name; ?>"><?php echo $name; ?></option>
             						<?php } ?>
             					</select>
							</div>
             				<div class="column"><input data-empty="no" type="date" value=""/></div>
             				<div class="column"><input data-empty="no" type="date" value=""/></div>
             				<div class="remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->remove; ?>"></i></div>
         				</li>
                        <?php foreach ((new UserHasRoleEntity())->retrieve(['where' => ['user' => $models['user']->login], 'many' => true]) as $entry) { ?>
                        <?php   if ((null !== Zord::value('context', $entry->context)) || ($entry->context == '*')) { ?>
         				<li class="data">
             				<div class="column">
             					<select>
             						<?php foreach($models['roles'] as $name) { ?>
             						<option value="<?php echo $name; ?>" <?php if ($name == $entry->role) echo 'selected'; ?>><?php echo $name; ?></option>
             						<?php } ?>
             					</select>
							</div>
             				<div class="column">
             					<select>
             						<?php foreach($models['context'] as $name) { ?>
             						<option value="<?php echo $name; ?>" <?php if ($name == $entry->context) echo 'selected'; ?>><?php echo $name; ?></option>
             						<?php } ?>
             					</select>
             				</div>
             				<div class="column"><input data-empty="no" type="date" value="<?php echo $entry->start; ?>"/></div>
             				<div class="column"><input data-empty="no" type="date" value="<?php echo $entry->end; ?>"/></div>
             				<div class="remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->remove; ?>"></i></div>
         				</li>
         				<?php   } ?>
         				<?php } ?>
     				</ul>
            		<div class="admin-panel-title"><?php echo $locale->tab->users->addresses; ?></div>
     				<ul class="admin-list" id="ips" data-columns="550px,60px,80px">
     					<li class="header">
             				<div class="column"><?php echo $locale->tab->users->ip; ?></div>
             				<div class="column"><?php echo $locale->tab->users->mask; ?></div>
             				<div class="column">+/-</div>
             				<div class="add"><i class="fa fa-plus fa-fw" title="<?php echo $locale->list->add; ?>"></i></div>
         				</li>
         				<li class="hidden">
             				<div class="column">
             					<input data-empty="no" type="text" value="" class="ip"/> .
             					<input data-empty="no" type="text" value="" class="ip"/> .
             					<input data-empty="no" type="text" value="" class="ip"/> .
             					<input data-empty="no" type="text" value="" class="ip"/>
             				</div>
             				<div class="column"><input data-empty="no" type="number" value="32" min="0" max="32"/></div>
             				<div class="column">
             					<select>
             						<option value="1"><?php echo $locale->tab->users->include; ?></option>
             						<option value="0"><?php echo $locale->tab->users->exclude; ?></option>
             					</select>
             				</div>
             				<div class="remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->remove; ?>"></i></div>
         				</li>
                        <?php foreach($models['user']->explodeIP() as $entry) { ?>
         				<li class="data">
             				<div class="column">
             					<?php
             					    $index = 0;
             					    foreach(explode('.', $entry['ip'], 4) as $number) {
             					?>
             					<input data-empty="no" type="text" value="<?php echo $number; ?>" class="ip"/> <?php echo $index < 3 ? '.' : ''; ?>
             					<?php
             					        $index++;    
                                    } 
                                ?>
             				</div>
             				<div class="column"><input data-empty="no" type="number" value="<?php echo $entry['mask']; ?>" min="0" max="32"/></div>
             				<div class="column">
             					<select>
             						<option value="1" <?php if ($entry['include']) echo 'selected'; ?>><?php echo $locale->tab->users->include; ?></option>
             						<option value="0" <?php if (!$entry['include']) echo 'selected'; ?>><?php echo $locale->tab->users->exclude; ?></option>
             					</select>
             				</div>
             				<div class="remove"><i class="fa fa-times fa-fw" title="<?php echo $locale->list->remove; ?>"></i></div>
         				</li>
         				<?php } ?>
     				</ul>
     				<br/>
     				<br/>
    		        <input id="submit-profile" type="button" class="admin-button" value="<?php echo $locale->tab->users->submit; ?>"/>
     				<br/>
     				<br/>
				</div>
