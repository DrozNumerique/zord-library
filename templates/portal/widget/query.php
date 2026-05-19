				<div id="queryTitle">
    				<label><?php echo $locale->query; ?></label>
    				<a id="queryHelp" href="<?php echo Zord::value('search', 'help'); ?>>"><?php echo $locale->help; ?></a>
				</div>
				<div id="queryBlock">
					<input id="queryInput" type="search" placeholder="<?php echo $locale->search; ?>" <?php echo isset($models['book']['ISBN']) ? 'required' : ''; ?>/>
					<img id="queryButton" title="<?php echo $locale->search; ?>" src="/library/img/search.png" />
				</div>
