						<fieldset class="keyword">
							<legend><?php echo $this->locale('admin')->tab->publish->context; ?></legend>
        					<select id="context">
        <?php foreach (Zord::getConfig('context') as $name => $data) { ?>
        						<option value="<?php echo $name; ?>" <?php echo $name == $context ? 'selected' : ''; ?>><?php echo Zord::getLocaleValue('title', Zord::value('context', $name), $lang); ?></option>
        <?php } ?>
        					</select>
        					<label for="only"><?php echo $this->locale('admin')->tab->publish->only; ?></label>
        					<input type="checkbox" id="only"/>
        					<label for="new"><?php echo $this->locale('admin')->tab->publish->new; ?></label>
        					<input type="checkbox" id="new"/>
						</fieldset>
						<input name="order" type="hidden" value="<?php echo $models['order'] ?? 'login'; ?>"/>
						<input name="direction" type="hidden" value="<?php echo $models['direction'] ?? 'asc'; ?>"/> 
        					