		<div id="collections">
			<ul>
<?php foreach ($collections as $context) { ?>
<?php   $this->render('banner', ['context' => $context])?>
<?php } ?>
			</ul>
		</div>
