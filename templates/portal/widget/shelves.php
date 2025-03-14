		<div id="shelves" data-alert="<?php echo empty($models['search']['books']) ? Zord::getLocale('search',$lang)->empty : ''; ?>" data-search="<?php echo $models['search']['id'] ?? ''; ?>" data-start="<?php echo $models['search']['criteria']['start'] ?? 0 ?>" data-rows="<?php echo $models['search']['criteria']['rows'] ?? SEARCH_PAGE_DEFAULT_SIZE; ?>" data-found="<?php echo $models['search']['found'] ?? 0; ?>" class="<?php echo count($models['shelves']) == 1 && isset($models['shelves']['search']) ? 'results'.(POPUP_SEARCH_RESULTS ? ' popup' : '') : '' ?>">
<?php if (isset($models['search']['matches'])) { ?>
    		<div class="fetch">
    			<span class="first fa fa-backward fa-fw"></span>
    			<span class="previous fa fa-step-backward fa-fw"></span>
    			<span class="resultSet select"><select>
<?php   for ($index = 0; $index < $models['search']['found']; $index += ($models['search']['criteria']['rows'] ?? SEARCH_PAGE_DEFAULT_SIZE)) { ?>
						<option value="<?php echo $index; ?>"<?php echo $index == $models['search']['criteria']['start'] ? ' selected' : ''?>><?php echo ($index + 1).$locale->to.min([$index + $models['search']['criteria']['rows'], $models['search']['found']]); ?></option>	
<?php   } ?>
    			</select></span>
    			<span class="resultSet found"><?php echo $locale->outof.$models['search']['found']; ?></span>
    			<span class="next fa fa-step-forward fa-fw"></span>
    			<span class="last fa fa-forward fa-fw"></span>
    		</div>
<?php } ?>
<?php foreach ($models['shelves'] as $name => $shelf) { ?>
<?php   if ($shelf['apart']) { ?>
			<div class="<?php echo $shelf['name']; ?> apart">
				<div class="apart_title apart_label"><?php echo Zord::getLocaleValue($shelf['name'], $models['labels'], $lang, ['new', 'other', 'demo'], $locale); ?></div>
				<div class="apart_title apart_count"><?php echo count($shelf['books']); ?> <?php echo $locale->books; ?></div>
<?php
$this->render('shelf', ['shelf' => $shelf, 'search' => isset($models['search']['id']) ? $models['search']['id'] : null]);
          unset($models['shelves'][$name]);
?>
			</div>
<?php   } ?>
<?php } ?>
<?php if (count($models['shelves']) > 0) { ?>
			<div class="group">
				<div class="tabs">
<?php   foreach ($models['shelves'] as $name => $shelf) { ?>
					<div data-tab="<?php echo $name; ?>" class="tab">
						<div class="frame_title"><?php echo Zord::getLocaleValue($shelf['name'], $models['labels'], $lang, ['new', 'other'], $locale); ?></div>
<?php     if (isset($models['search']['matches'])) { ?>
						<div class="frame_subtitle">
							<span class="frame_instances count"><?php echo ($models['search']['pages'] ?? 1) > 1 ? '~ ' : ''; ?><?php echo $shelf['instances']['total'] * ($models['search']['pages'] ?? 1); ?></span>&nbsp;<span class="frame_instances"><?php echo $locale->instances; ?></span>
						</div>
<?php     } ?>			
						<div class="frame_subtitle">
							<span class="frame_books count"><?php echo ($models['search']['pages'] ?? 1) > 1 ? '~ ' : ''; ?><?php echo count($shelf['books']) * ($models['search']['pages'] ?? 1); ?></span>&nbsp;<span class="frame_books"><?php echo $locale->books; ?></span>
						</div>
					</div>
<?php   } ?>
				</div>
				<div class="panels">
<?php   foreach ($models['shelves'] as $shelf) { ?>
<?php     $this->render('shelf', ['shelf' => $shelf, 'search' => isset($models['search']['id']) ? $models['search']['id'] : null]); ?>
<?php   } ?>
				</div>
			</div>
<?php } ?>
		</div>
