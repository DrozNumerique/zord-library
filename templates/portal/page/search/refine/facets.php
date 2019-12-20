					<div id="search_operator">
        				<input id="search_operator_AND" name="operator" type="radio" value="AND"/>
        				<label for="search_operator_AND" ><?php echo $locale->operators->AND; ?></label>
        				<input id="search_operator_OR" name="operator" type="radio" value="OR"/>
        				<label for="search_operator_OR" ><?php echo $locale->operators->OR; ?></label>
					</div>
    				<ul id="search_facets">
<?php foreach ($models['facets'] as $facet) { ?>
    					<li id="search_facet_<?php echo $facet; ?>">
    						<div class="caption"><?php echo $locale->facets->$facet; ?></div>
    						<select id="<?php echo $facet; ?>" data-placeholder="<?php echo $locale->placeholder; ?>" multiple class="facet chosen-select-standard" data-loading="true"></select>
    					</li>
<?php } ?>
    				</ul>
