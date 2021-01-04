		<div id="shelves" class="<?php echo count($models['shelves']) == 1 && isset($models['shelves']['search']) ? 'results' : '' ?>">
<?php if (isset($models['search']['matches'])) { ?>
    		<div id="fetch">
    			<span id="first" class="fa fa-backward fa-fw"></span>
    			<span id="previous" class="fa fa-step-backward fa-fw"></span>
    			<span id="resultSet"><?php echo ($models['search']['criteria']['start'] + 1).$locale->to.($models['search']['end']).$locale->outof.$models['search']['found']; ?></span>
    			<span id="next" class="fa fa-step-forward fa-fw"></span>
    			<span id="last" class="fa fa-forward fa-fw"></span>
    		</div>
<?php } ?>
<?php foreach ($models['shelves'] as $name => $shelf) { ?>
<?php   if ($shelf['apart']) { ?>
			<div class="<?php echo $shelf['name']; ?> apart">
				<div class="apart_title apart_label"><?php echo Zord::getLocaleValue($shelf['name'], $models['labels'], $lang, ['new', 'other'], $locale); ?></div>
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
							<span class="frame_instances count"><?php echo $shelf['instances']['total']; ?></span>&nbsp;<span class="frame_instances"><?php echo $locale->instances; ?></span>
						</div>
<?php     } ?>			
						<div class="frame_subtitle">
							<span class="frame_books count"><?php echo count($shelf['books']); ?></span>&nbsp;<span class="frame_books"><?php echo $locale->books; ?></span>
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
