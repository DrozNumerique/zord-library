						<fieldset class="title">
							<legend><?php echo $this->locale('admin')->tab->publish->keyword; ?></legend>
    						<input name="title" class="search" type="text" value="<?php echo $models['title'] ?? ''; ?>"/>
<?php $this->render('#search'); ?>
						</fieldset>
						<fieldset class="context">
							<legend><?php echo $this->locale('admin')->tab->publish->context; ?></legend>
        					<select id="context">
        <?php foreach (Zord::contextList($lang, false) as $name => $title) { ?>
        						<option value="<?php echo $name; ?>" <?php echo $name == $context ? 'selected' : ''; ?>><?php echo $title; ?></option>
        <?php } ?>
        					</select>
        					<label for="only"><?php echo $this->locale('admin')->tab->publish->only; ?></label>
        					<input type="checkbox" id="only"/>
						</fieldset>
						<fieldset class="status">
							<legend><?php echo $this->locale('admin')->tab->publish->status; ?></legend>
        					<label for="new"><?php echo $this->locale('admin')->tab->publish->new; ?></label>
        					<input type="checkbox" id="new"/>
						</fieldset>
						<input name="order" type="hidden" value="<?php echo $models['order'] ?? 'login'; ?>"/>
						<input name="direction" type="hidden" value="<?php echo $models['direction'] ?? 'asc'; ?>"/> 
        					