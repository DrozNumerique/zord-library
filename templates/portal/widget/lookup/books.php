						<fieldset class="keyword">
							<legend><?php echo $this->locale('admin')->tab->publish->context; ?></legend>
        					<select id="context">
        <?php foreach (Zord::getConfig('context') as $name => $data) { ?>
        						<option value="<?php echo $name; ?>" <?php echo $name == $context ? 'selected' : ''; ?>><?php echo Zord::getLocaleValue('title', Zord::value('context', $name), $lang); ?></option>
        <?php } ?>
        					</select>
						</fieldset>
						<input name="order" type="hidden" value="<?php echo $models['order'] ?? 'login'; ?>"/>
						<input name="direction" type="hidden" value="<?php echo $models['direction'] ?? 'asc'; ?>"/> 
        					