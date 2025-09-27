			<a href="<?php echo $basePath; ?>" class="contextlink" title="<?php echo $user->isConnected() ? $user->name : $_SERVER['REMOTE_ADDR']; ?>">
<?php $this->render('logo'); ?>
<?php $this->render('provider'); ?>
			</a>
