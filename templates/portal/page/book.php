<?php $this->render('/portal/widget/unapi', ['title' => $models['book']['ISBN']]); ?>
<?php $this->render('/portal/widget/pullout', ['id' => 'toc', 'top' => '/portal/widget/query', 'content' => '/portal/page/book/toc', 'align' => 'left', 'handle' => '≡']); ?>
		<div class="tools fixed">
			<a id="searchBack" class="fa fa-search fa-fw<?php echo !isset($models['book']['search']) ? ' __disabled' : ''; ?>" href="<?php echo isset($models['book']['search']) ? $baseURL.'/search'.'?id='.$models['book']['search'] : ''; ?>" title="<?php echo $locale->search_back; ?>">
				<i class="fa fa-arrow-left fa-stack-1x searchBack"></i>
			</a>
			<i id="tool_citation" class="fa fa-bookmark fa-fw" title="<?php echo $locale->cite; ?>"></i>
			<i id="switchTemoin" class="fa fa-tag fa-fw" title="<?php echo $locale->references; ?>"></i>
<?php if (isset($models['book']['metadata']['ref_url'])) { ?>
			<a id="get_book" class="fa fa-book fa-fw" target="_blank" title="<?php echo $locale->get_book; ?>" href="<?php echo $models['book']['metadata']['ref_url']; ?>"></a>
<?php } ?>
		</div>
<?php if (isset($models['portal']['message'])) { ?>
<?php   $this->render('message'); ?>
<?php } ?>
		<article id="tei">
			<div id="markerAnchorLeft">❯</div>
			<div id="markerAnchorRight">❮</div>
			<div class="tools float">
    			<i id="quote" class="fa fa-quote-left fa-fw __hidden" title="<?php echo $locale->quote; ?>"></i>
    			<i id="tool_bug" class="fa fa-bug fa-fw __hidden" title="<?php echo $locale->misprint; ?>"></i>
			</div>
<?php $this->render('parts'); ?>
		</article>
		<div id="dialogs">
<?php $this->render('dialogs'); ?>
<?php $this->render('/portal/widget/help'); ?>
		</div>
		