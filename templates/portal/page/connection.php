<form id="connection" method="post" action="<?php echo $baseURL; ?>" class="connection">
	<input type="hidden" name="module" value="Account"/>
	<input type="hidden" name="action" value="connect"/>
	<?php if (isset($models['lasthref']) && $models['lasthref']) { ?>
	<input type="hidden" name="lasthref" value="<?php echo $models['lasthref'] ?>"/>
	<?php } ?>
	<?php if (isset($models['message']) && $models['message']) { ?>
	<div><?php echo $models['message'] ?></div>
	<br/>
	<?php } ?>
	<label><?php echo $locale->login ?></label><input type="text" name="login" value="<?php echo isset($models['login']) ? $models['login'] : ''; ?>"/><br/>
	<label><?php echo $locale->password ?></label><input type="password" name="password" /><br/>
	<?php if (isset($models['activate']) && $models['activate']) { ?>
	<input type="hidden" name="activate" value="<?php echo $models['activate'] ?>"/>
	<label><?php echo $locale->confirm ?></label><input type="password" name="confirm" /><br/>
	<?php } else { ?>
	<label><?php echo $locale->forgot ?></label><input type="email" name="email" /><br/>
	<?php } ?>
	<div>
		<input type="submit" name="submit" value="<?php echo $locale->connect ?>"/>
	</div>
</form>
