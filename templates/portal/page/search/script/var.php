<?php if (isset($models['search']['id'])) { ?>
		var SEARCH = '<?php echo $models['search']['id']; ?>';
<?php } else { ?>
		var SEARCH = 'none';
<?php } ?>
		var POPUP = <?php echo POPUP_SEARCH_RESULTS ? 'true' : 'false'?>;
<?php if (isset($models['search']['books'])) { ?>
<?php   if (empty($models['search']['books'])) { ?>
		var ALERT  = '<?php echo $locale->empty; ?>';
<?php   } else { ?>
		var ALERT  = null;
<?php   } ?>
<?php } ?>
