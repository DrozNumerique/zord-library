<?php if (isset($models['search']['id'])) { ?>
		var SEARCH     = '<?php echo $models['search']['id']; ?>';
<?php } else { ?>
		var SEARCH     = 'none';
<?php } ?>
<?php if (isset($models['search']['books'])) { ?>
		var FOUND      = <?php echo $models['search']['found']; ?>;
<?php   if (empty($models['search']['books'])) { ?>
		var ALERT      = '<?php echo $locale->empty; ?>';
<?php   } else { ?>
		var ALERT      = null;
<?php   } ?>
<?php } ?>
<?php if (isset($models['search']['criteria']['start']) && is_int($models['search']['criteria']['start'])) { ?>
		var START      = <?php echo $models['search']['criteria']['start']; ?>;
<?php } else { ?>
		var START      = 0;
<?php }?>
<?php if (isset($models['search']['criteria']['rows']) && is_int($models['search']['criteria']['rows'])) { ?>
		var ROWS       = <?php echo $models['search']['criteria']['rows']; ?>;
<?php } else { ?>
		var ROWS       = <?php echo SEARCH_PAGE_DEFAULT_SIZE; ?>;
<?php }?>
