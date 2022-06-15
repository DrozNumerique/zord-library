			<a href="<?php echo $baseURL; ?>" class="contextlink" title="<?php echo $user->isConnected() ? $user->name : $_SERVER['REMOTE_ADDR']; ?>">
<?php $this->render('logo'); ?>
<?php $this->render('provider'); ?>
			</a>
