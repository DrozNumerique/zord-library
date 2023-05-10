		<div id="collections">
<?php $this->render('/portal/widget/switch'); ?>
			<h1><?php echo $locale->root->title; ?></h1>
			<h2><?php echo $locale->root->subtitle; ?></h2>
			<ul id="menu_context">
<?php foreach ($collections as $context) { ?>
<?php   $this->render('banner', ['context' => $context])?>
<?php } ?>
			</ul>
		</div>
