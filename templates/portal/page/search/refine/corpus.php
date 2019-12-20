        			<div class="caption"><?php echo $locale->corpus->list; ?></div>
        			<select id="titles" class="chosen-select-no-single" data-loading="true" data-placeholder="<?php echo $locale->placeholder; ?>">
        				<option></option>
        			</select>
        			<div id="selected" class="caption">
        				<div style="display:inline;"><?php echo $locale->corpus->select; ?></div>
						<div id="full"> : <?php echo $locale->corpus->full; ?></div>
        				<div id="remove"> (<?php echo $locale->corpus->remove; ?>)</div>
	    				<ul id="books"></ul>
        			</div>
<?php $this->render('#styles', ['id' => 'corpus', 'change' => 'updateCorpus']); ?>