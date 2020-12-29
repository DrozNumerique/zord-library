	<header id="header">
		<div class="blocklink">
<?php
$this->render('left');
$this->render('middle');
$this->render('right');
?>
		</div>
		<form method="post" id="switchContextForm">
			<input type="hidden" name="module" value="<?php echo $models['portal']['module']; ?>">
			<input type="hidden" name="action" value="<?php echo $models['portal']['action']; ?>">
			<input type="hidden" name="params" value='<?php echo $models['portal']['params']; ?>'>
			<input type="hidden" name="lang"   value="<?php echo $lang; ?>">
<?php   if ($user->isConnected()) { ?>
			<input type="hidden" name="<?php echo User::$ZORD_SESSION; ?>" value="<?php echo $user->session; ?>">
<?php   } ?>
		</form>
	</header>
