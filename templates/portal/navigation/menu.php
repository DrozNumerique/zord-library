		<div id="navcontent">
			<ul>
<?php foreach ($models['portal']['menu']['link'] as $entry) { ?>
				<li id="<?php echo 'menu_'.$entry['name']; ?>" class="<?php echo (isset($entry['class']) && !empty($entry['class'])) ? implode(' ', $entry['class']) : ''; ?>">
<?php   if ($entry['type'] !== 'menu') { ?>
					<a href="<?php echo $entry['url']; ?>"><?php echo $entry['label']; ?></a>
<?php   } else { ?>
					<div class="sub"><?php echo $entry['label']; ?>
						<ul>
<?php     foreach ($entry['menu'] as $menu) { ?>
							<li id="<?php echo 'menu_'.$entry['name'].'_'.$menu['name']; ?>" class="<?php echo (isset($menu['class']) && !empty($menu['class'])) ? implode(' ', $menu['class']) : ''; ?>">
								<a href="<?php echo $menu['url']; ?>"><?php echo $menu['label']; ?></a>
							</li>
<?php     } ?>
						</ul>
					</div>
<?php   } ?>
				</li>
<?php } ?>
				<li class="switchContextMenu">
					<form method="post">
						<select id="switchContext">
<?php foreach ($models['portal']['menu']['context'] as $name => $value) { ?>
							<option value="<?php echo $name; ?>" <?php echo $name == $context ? 'selected' : ''; ?>><?php echo $value; ?></option>
<?php } ?>
						</select>
						<input type="hidden" name="module" value="<?php echo $models['portal']['module']; ?>">
						<input type="hidden" name="action" value="<?php echo $models['portal']['action']; ?>">
						<input type="hidden" name="params" value='<?php echo $models['portal']['params']; ?>'>
						<input type="hidden" name="lang"   value="<?php echo $lang; ?>">
<?php if ($user->isConnected()) { ?>
						<input type="hidden" name="<?php echo User::$ZORD_SESSION; ?>" value="<?php echo $user->session; ?>">
<?php } ?>
					</form>
				</li>
				<li class="switchLangMenu">
					<select id="switchLang">
<?php foreach ($models['portal']['menu']['lang'] as $name => $value) { ?>
						<option value="<?php echo $name; ?>" <?php echo $name == $lang ? 'selected' : ''; ?>><?php echo $value; ?></option>
<?php } ?>
					</select>
				</li>
<?php if ($user->isConnected() || !$user->hasRole('admin', $context)) { ?>
				<li class="connectionMenu">
					<form method="post" action="<?php echo $baseURL; ?>">
						<input type="hidden" name="module" value="Account"/>
						<input type="hidden" name="action" value="<?php echo $models['portal']['account']['action']; ?>"/>
						<input type="submit" value="<?php echo $models['portal']['account']['label']; ?>" class="account"/>
					</form>
				</li>
<?php } ?>
			</ul>
		</div>
