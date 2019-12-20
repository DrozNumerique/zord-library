                    <div id="searchHistoryPanel" class="table">
    					<div>
        					<ul id="searchHistoryDisplay"></ul>
        				</div>
        			</div>
        			<div id="searchHistoryControls" class="table">
        				<div>
                			<div id="searchHistoryPrevious">
                				<i class="fa fa-step-backward fa-fw"></i>
                				<div class="tip"><?php echo $locale->history->previous; ?></div>
                			</div>
                			<div id="searchHistoryNext">
                				<i class="fa fa-step-forward fa-fw"></i>
                				<div class="tip"><?php echo $locale->history->next; ?></div>
                			</div>
                			<div id="searchHistoryReplay">
                				<i class="fa fa-repeat fa-fw"></i>
                				<div class="tip"><?php echo $locale->history->replay; ?></div>
                			</div>
                			<div id="searchHistoryDelete">
                				<i class="fa fa-close fa-fw"></i>
                				<div class="tip"><?php echo $locale->history->delete; ?></div>
                			</div>
                			<div id="searchHistoryClear">
                				<i class="fa fa-eject fa-fw"></i>
                				<div class="tip"><?php echo $locale->history->clear; ?></div>
                			</div>
                			<div id="searchHistoryCopy">
                				<i class="fa fa-clipboard fa-fw"></i>
                				<div class="tip"><?php echo $locale->history->copy; ?></div>
                			</div>
            			</div>
        			</div>
<?php $this->render('#styles', ['id' => 'history', 'change' => 'refreshHistory']); ?>