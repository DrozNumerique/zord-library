   			<div id="searchRefine" class="<?php echo SEARCH_PULLOUT ? '' : 'switch'; ?>">
    			<label><?php echo $locale->refine; ?></label>
    		</div>
			<div id="searchControls">	
    			<div id="search_scope">
<?php foreach(Zord::value('search', 'scopes') as $scope) { ?>
    				<div class="scope" data-scope="<?php echo $scope; ?>">
    					<label><?php echo $locale->scopes->$scope; ?></label>
    				</div>
<?php } ?>
    			</div>
    			<div id="search_block">
<?php foreach(Zord::value('search', 'scopes') as $scope) { ?>
    				<div class="block" data-scope="<?php echo $scope; ?>">
						<label><?php echo $locale->tips->$scope; ?></label>
						<div>
<?php   $this->render($scope); ?>
						</div>    			
					</div>
<?php } ?>
				</div>
    			<div id="search_source">
    				<label><?php echo $locale->source_date; ?></label>
    				<label><?php echo $locale->from; ?></label>
    				<input id="search_source_from" type="text" maxlength="4" size="4" placeholder="<?php echo $locale->year; ?>"/>
    				<label><?php echo $locale->to; ?></label>
    				<input id="search_source_to" type="text" maxlength="4" size="4" placeholder="<?php echo $locale->year; ?>"/>
    			</div>
    			<div id="search_type">
    				<input id="searchInIndex" type="checkbox"/>
    				<label for="searchInIndex" ><?php echo $locale->include_index; ?></label>
    			</div>
    			<div id="search_size">
    				<label for="searchSize" ><?php echo $locale->page_size; ?></label>
    				<input id="searchSize" type="number" min="<?php echo SEARCH_PAGE_DEFAULT_SIZE; ?>" max="<?php echo SEARCH_PAGE_MAX_SIZE; ?>" />
    			</div>
    			<div id="search_button">
					<button class="search"><?php echo $locale->search; ?></button>
				</div>
			</div>