	<nav id="navbar">
<?php $this->render('/portal/widget/menu', Menu::build($controler, $models)); ?>
	</nav>
<?php if (isset($models['portal']['ariadne'])) { ?>
<?php   $this->render('/portal/widget/ariadne'); ?>
<?php } ?>
	