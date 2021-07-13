<?php if ($user->isKnown() && !$user->isConnected()) { ?>
				<div id="accessProvidedBy"><div><?php echo $locale->header->accessProvidedBy; ?><br/><?php echo $user->name; ?></div></div>
<?php } ?>