         			<div class="caption"><?php echo $locale->biblio->styles; ?></div>
        			<select id="<?php echo $models['id']; ?>Styles" class="chosen-select-no-single styles" data-change="<?php echo $models['change']; ?>">
<?php foreach (Zord::getConfig('csl') as $key => $name) { ?>
        				<option value="<?php echo $key; ?>"><?php echo $name; ?></option>
<?php } ?>
        			</select>
