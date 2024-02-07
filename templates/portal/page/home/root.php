		<div id="corpus">
<?php $this->render('/portal/widget/switch'); ?>
			<h1><?php echo $locale->root->title; ?></h1>
			<h2><?php echo $locale->root->subtitle; ?></h2>
			<div id="menu_context">
<?php foreach ($corpus as $category => $list) { ?>
				<div class="corpus"><?php echo $locale->root->corpus->$category; ?></div>
				<ul class="category <?php echo $category; ?>">
<?php   foreach ($list as $_context) { ?>
<?php     $this->render('banner', ['context' => $_context])?>
<?php   } ?>
				</ul>
<?php } ?>
			</div>
		</div>
