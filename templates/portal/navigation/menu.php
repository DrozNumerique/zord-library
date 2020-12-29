		<div id="navcontent">
			<ul>
<?php foreach ($models['portal']['menu']['link'] as $entry) { ?>
				<li id="<?php echo 'menu_'.$entry['name']; ?>" class="<?php echo (isset($entry['class']) && !empty($entry['class'])) ? implode(' ', $entry['class']) : ''; ?>">
<?php   if ($entry['type'] !== 'menu') { ?>
					<a href="<?php echo $entry['url'].'?menu='.$entry['name']; ?>"><?php echo $entry['label']; ?></a>
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
			</ul>
		</div>
