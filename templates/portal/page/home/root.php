		<div id="collections">
<?php $this->render('/portal/widget/switch'); ?>
			<ul id="menu_context">
<?php foreach ($collections as $context) { ?>
<?php   $this->render('banner', ['context' => $context])?>
<?php } ?>
			</ul>
		</div>
