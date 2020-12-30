	<nav id="navbar">
<?php $this->render('/portal/widget/menu', Menu::build($controler, $models)); ?>
<?php if (isset($models['portal']['ariadne'])) { ?>
<?php   $this->render('ariadne'); ?>
<?php } ?>
<?php if (isset($models['portal']['message'])) { ?>
<?php   $this->render('message'); ?>
<?php } ?>
	</nav>
